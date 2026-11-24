<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Service extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Determine if a given URL is online or offline via ping probe.
     */
    public static function pingUrl(?string $url, int $timeoutSeconds = 2): string
    {
        if (empty($url) || $url === '#') {
            return 'offline';
        }

        try {
            $parsed = parse_url(trim($url));
            $host = $parsed['host'] ?? null;
            if (! $host) {
                return 'offline';
            }

            // Fast HTTP probe without strict SSL verification
            $response = Http::timeout($timeoutSeconds)
                ->connectTimeout($timeoutSeconds)
                ->withoutVerifying()
                ->head($url);

            if ($response->successful() || $response->status() < 500) {
                return 'online';
            }
        } catch (\Throwable) {
            // Fallback: fast TCP socket check (e.g. if HEAD method is disallowed or on custom ports)
            try {
                $parsed = parse_url(trim($url));
                $host = $parsed['host'] ?? null;
                if ($host) {
                    $scheme = strtolower($parsed['scheme'] ?? 'http');
                    $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
                    $socketHost = ($scheme === 'https' ? 'ssl://' : '').$host;
                    $context = stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]);

                    $errno = 0;
                    $errstr = '';
                    $fp = @stream_socket_client(
                        $socketHost.':'.$port,
                        $errno,
                        $errstr,
                        $timeoutSeconds,
                        STREAM_CLIENT_CONNECT,
                        $context
                    );

                    if ($fp) {
                        fclose($fp);

                        return 'online';
                    }
                }
            } catch (\Throwable) {
            }
        }

        return 'offline';
    }

    /**
     * Ping this service and update its status.
     */
    public function ping(int $timeoutSeconds = 2): string
    {
        $status = self::pingUrl($this->url, $timeoutSeconds);
        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }

        return $status;
    }

    /**
     * Ping all services and update their status in the database.
     *
     * @return array<int, array{id: int, status: string}>
     */
    public static function pingAll(int $timeoutSeconds = 2): array
    {
        $services = self::all();
        $results = [];

        foreach ($services as $service) {
            $status = $service->ping($timeoutSeconds);
            $results[] = [
                'id' => $service->id,
                'status' => $status,
            ];
        }

        return $results;
    }
}
