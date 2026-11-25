<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
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
        $host = self::extractHost($url);
        if (! $host) {
            return 'offline';
        }

        try {
            $response = Http::timeout($timeoutSeconds)
                ->connectTimeout(min(1, $timeoutSeconds))
                ->withoutVerifying()
                ->head($url);

            if ($response->successful() || $response->status() < 500) {
                return 'online';
            }

            return 'offline';
        } catch (\Throwable $e) {
            if (self::isUnreachableNetworkError($e)) {
                return 'offline';
            }

            return self::pingSocket($url, min(1, $timeoutSeconds));
        }
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
     * Ping all services concurrently and update changed statuses in the database.
     *
     * @return array<int, array{id: int, status: string}>
     */
    public static function pingAll(int $timeoutSeconds = 2): array
    {
        $services = self::all();
        if ($services->isEmpty()) {
            return [];
        }

        $validServices = $services->filter(fn (Service $service): bool => self::extractHost($service->url) !== null);
        $connectTimeout = min(1, $timeoutSeconds);

        $responses = $validServices->isNotEmpty()
            ? Http::pool(function (Pool $pool) use ($validServices, $timeoutSeconds, $connectTimeout): void {
                foreach ($validServices as $service) {
                    $pool->as((string) $service->id)
                        ->timeout($timeoutSeconds)
                        ->connectTimeout($connectTimeout)
                        ->withoutVerifying()
                        ->head(trim($service->url));
                }
            })
            : [];

        $results = [];

        foreach ($services as $service) {
            $key = (string) $service->id;
            $status = 'offline';

            if (isset($responses[$key])) {
                $res = $responses[$key];

                if ($res instanceof Response) {
                    $status = ($res->successful() || $res->status() < 500) ? 'online' : 'offline';
                } elseif ($res instanceof \Throwable && ! self::isUnreachableNetworkError($res)) {
                    $status = self::pingSocket($service->url, $connectTimeout);
                }
            }

            if ($service->status !== $status) {
                $service->update(['status' => $status]);
            }

            $results[] = [
                'id' => $service->id,
                'status' => $status,
            ];
        }

        return $results;
    }

    private static function extractHost(?string $url): ?string
    {
        if (empty($url) || $url === '#') {
            return null;
        }

        $parsed = parse_url(trim($url));

        return $parsed['host'] ?? null;
    }

    private static function isUnreachableNetworkError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'could not resolve host')
            || str_contains($message, 'name or service not known')
            || str_contains($message, 'nodename nor servname')
            || str_contains($message, 'timed out')
            || str_contains($message, 'failed to connect')
            || str_contains($message, 'connection refused');
    }

    private static function pingSocket(string $url, int $timeoutSeconds): string
    {
        try {
            $parsed = parse_url(trim($url));
            $host = $parsed['host'] ?? null;
            if (! $host) {
                return 'offline';
            }

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
        } catch (\Throwable) {
        }

        return 'offline';
    }
}
