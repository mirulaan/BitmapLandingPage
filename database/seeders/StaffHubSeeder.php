<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffHubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Admin User
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'admin@nordvik.local',
                'password' => Hash::make('Bitmap.123'),
                'is_admin' => true,
            ]
        );

        // Portal Settings (Singleton row #1)
        PortalSetting::updateOrCreate(
            ['id' => 1],
            [
                'office_name' => 'Nordvik & Co. — Staff Hub',
                'tagline' => 'A calm place to start the workday.',
                'office_locations' => PortalSetting::defaultLocations(),
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

        // Services
        Service::truncate();
        $services = [
            ['icon' => '☁️', 'name' => 'Nextcloud', 'description' => 'Files, calendar and shared drives.', 'url' => 'https://cloud.example.com', 'status' => 'online', 'sort_order' => 1],
            ['icon' => '📝', 'name' => 'ONLYOFFICE', 'description' => 'Docs, sheets and slides in the browser.', 'url' => 'https://office.example.com', 'status' => 'online', 'sort_order' => 2],
            ['icon' => '🗄️', 'name' => 'Office NAS', 'description' => 'Backups, archives and the media library.', 'url' => 'http://nas.local', 'status' => 'offline', 'sort_order' => 3],
            ['icon' => '📌', 'name' => 'Project Tracker', 'description' => 'Sprints, tasks and weekly checkpoints.', 'url' => 'https://tracker.example.com', 'status' => 'online', 'sort_order' => 4],
            ['icon' => '✉️', 'name' => 'Web Mail', 'description' => 'Company email on the web client.', 'url' => 'https://mail.example.com', 'status' => 'offline', 'sort_order' => 5],
        ];
        foreach ($services as $service) {
            Service::create($service);
        }

        // Notices
        Notice::truncate();
        $notices = [
            [
                'title' => 'Weekly backup window',
                'body' => 'NAS backup runs Friday 22:00–01:00. File shares may pause briefly — save your work early.',
                'category' => 'it',
                'priority' => 'high',
                'pinned' => true,
                'created_at' => now()->subHours(5),
            ],
            [
                'title' => 'Kitchen: new coffee roast',
                'body' => 'The dark roast from the local roaster is restocked. Please label your mugs before Friday.',
                'category' => 'general',
                'priority' => 'normal',
                'pinned' => false,
                'created_at' => now()->subHours(26),
            ],
            [
                'title' => 'Fire drill Thursday 10:00',
                'body' => 'Assembly point is the courtyard. Team leads, bring the roster.',
                'category' => 'reminder',
                'priority' => 'normal',
                'pinned' => false,
                'created_at' => now()->subHours(50),
            ],
        ];
        foreach ($notices as $notice) {
            Notice::create($notice);
        }

        // Bookmarks
        Bookmark::truncate();
        $bookmarks = [
            ['label' => 'Guest Wifi Pass', 'url' => '/wifi', 'sort_order' => 1],
            ['label' => 'Desk booking', 'url' => 'https://example.com/desks', 'sort_order' => 2],
            ['label' => 'Leave request', 'url' => 'https://example.com/leave', 'sort_order' => 3],
            ['label' => 'Printer queue', 'url' => 'http://print.local/queue', 'sort_order' => 4],
            ['label' => 'Expenses', 'url' => 'https://example.com/expenses', 'sort_order' => 5],
        ];
        foreach ($bookmarks as $bookmark) {
            Bookmark::create($bookmark);
        }
    }
}
