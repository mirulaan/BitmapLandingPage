<?php

namespace App\Models;

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
}
