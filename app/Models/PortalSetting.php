<?php

namespace App\Models;

use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['wifi_qr_svg'];

    protected $casts = [
        'wallpaper_enabled' => 'boolean',
        'wallpaper_custom_urls' => 'array',
        'wallpaper_interval' => 'integer',
        'wallpaper_blur' => 'integer',
        'wallpaper_veil' => 'integer',
    ];

    /**
     * Regenerate cached Wi-Fi QR SVG only when Wi-Fi credentials change.
     */
    protected static function booted(): void
    {
        static::saving(function (PortalSetting $setting): void {
            $wifiChanged = ! $setting->exists || $setting->isDirty(['wifi_ssid', 'wifi_password', 'wifi_encryption']);
            $missingCachedSvg = empty($setting->attributes['wifi_qr_svg'] ?? null);

            if ($wifiChanged || $missingCachedSvg) {
                $setting->attributes['wifi_qr_svg'] = QrCodeService::generateSvg($setting->wifi_string, 260, '#000000');
            }
        });
    }

    /**
     * Singleton accessor — always returns row #1
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'office_name' => 'Nordvik & Co. — Staff Hub',
                'tagline' => 'A calm place to start the workday.',
                'helpdesk_email' => 'helpdesk@nordvik.local',
                'helpdesk_ext' => 'x4210',
                'wifi_ssid' => 'Nordvik-Guest',
                'wifi_password' => 'CozyWelcome2026',
                'wifi_encryption' => 'WPA',
                'theme' => null,
                'wallpaper_enabled' => true,
                'wallpaper_mode' => 'picsum',
                'wallpaper_custom_urls' => [],
                'wallpaper_interval' => 300000,
                'wallpaper_blur' => 28,
                'wallpaper_veil' => 55,
            ]
        );
    }

    /**
     * Build the standard Wi-Fi QR Code content string.
     * Format: WIFI:T:WPA;S:MySSID;P:MyPassword;;
     */
    public function getWifiStringAttribute(): string
    {
        $ssid = addcslashes($this->wifi_ssid ?: 'Nordvik-Guest', '";,:\\');
        $pass = addcslashes($this->wifi_password ?: '', '";,:\\');
        $type = $this->wifi_encryption ?: 'WPA';

        return "WIFI:T:{$type};S:{$ssid};P:{$pass};;";
    }

    /**
     * Return cached SVG QR Code for the Wi-Fi credentials, generating and storing it only when absent.
     */
    public function getWifiQrSvgAttribute(?string $value = null): string
    {
        if (! empty($value)) {
            return $value;
        }

        $svg = QrCodeService::generateSvg($this->wifi_string, 260, '#000000');

        if ($this->exists) {
            $this->updateQuietly(['wifi_qr_svg' => $svg]);
        }

        return $svg;
    }
}
