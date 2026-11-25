<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use App\Models\User;
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
        $response->assertDontSee('id="view-admin"', false);
    }

    public function test_portal_renders_both_views_for_authenticated_admin_to_enable_client_side_toggle(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="view-portal"', false);
        $response->assertSee('id="view-admin" hidden', false);
    }

    public function test_admin_renders_both_views_with_admin_visible_and_portal_hidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('id="view-portal" hidden', false);
        $response->assertSee('id="view-admin"', false);
        $response->assertDontSee('id="view-admin" hidden', false);
    }

    public function test_wifi_guest_pass_page_renders_qr_code_and_credentials(): void
    {
        $setting = PortalSetting::current();
        $setting->update([
            'wifi_ssid' => 'Nordvik-HQ-Guest',
            'wifi_password' => 'SafeGuestSecret2026',
            'wifi_encryption' => 'WPA',
        ]);

        $response = $this->get('/wifi');

        $response->assertStatus(200);
        $response->assertSee('Wi-Fi Guest Pass');
        $response->assertSee('Nordvik-HQ-Guest');
        $response->assertSee('SafeGuestSecret2026');
        $response->assertSee('<svg', false);
        $response->assertDontSee('Security:');
    }

    public function test_quick_bookmarks_guest_wifi_pass_links_to_wifi_page(): void
    {
        Bookmark::create([
            'label' => 'Guest Wifi Pass',
            'url' => '/wifi',
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Guest Wifi Pass');
        $response->assertSee(route('wifi'));
        $response->assertSee('data-nav-loading="Opening Guest Wifi Pass…"', false);
        $response->assertSee('id="nav-loading-bar"', false);
        $response->assertSee('id="nav-loading-pill"', false);
    }

    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
