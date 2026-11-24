<?php

namespace App\Models;

use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'wallpaper_enabled' => 'boolean',
        'wallpaper_custom_urls' => 'array',
        'wallpaper_interval' => 'integer',
        'wallpaper_blur' => 'integer',
        'wallpaper_veil' => 'integer',
    ];

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
     * Generate crisp SVG QR Code for the Wi-Fi credentials.
     */
    public function getWifiQrSvgAttribute(): string
    {
        return QrCodeService::generateSvg($this->wifi_string, 260, '#000000');
    }
}
