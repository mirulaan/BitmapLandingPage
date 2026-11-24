<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_page_returns_successful_response(): void
    {
        $setting = PortalSetting::current();
        $service = Service::create([
            'icon' => '☁️',
            'name' => 'Nextcloud',
            'description' => 'Files and shared drives',
            'url' => 'https://cloud.example.com',
            'status' => 'online',
            'sort_order' => 1,
        ]);
        $notice = Notice::create([
            'title' => 'Important Maintenance',
            'body' => 'Scheduled for tonight',
            'category' => 'it',
            'priority' => 'high',
            'pinned' => true,
        ]);
        $bookmark = Bookmark::create([
            'label' => 'Guest Wi-Fi',
            'url' => 'https://example.com/wifi',
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Nextcloud');
        $response->assertSee('Important Maintenance');
        $response->assertSee('Guest Wi-Fi');
        $response->assertSee($setting->office_name);
    }
}
