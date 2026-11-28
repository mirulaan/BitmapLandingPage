<?php

namespace Tests\Feature;

use App\Models\PortalSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Admin Sign In');
    }

    public function test_admin_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@nordvik.local',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@nordvik.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $logoutResp = $this->actingAs($user)->post('/logout');
        $logoutResp->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_authenticated_admin_can_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Admin Settings');
    }

    public function test_admin_can_update_portal_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/admin/settings', [
            'office_name' => 'Updated Office Hub',
            'tagline' => 'New tagline test',
            'office_locations' => [
                ['name' => 'Head Office', 'address' => '1 Harbour Drive, Singapore 068816'],
                ['name' => 'North Studio', 'address' => '22 Bukit Batok Street 22, Singapore 659572'],
                ['name' => 'East Depot', 'address' => '45 Geylang Road, Singapore 389280'],
            ],
            'wifi_ssid' => 'Nordvik-NewWifi',
            'wifi_password' => 'SuperPass2026',
            'wifi_encryption' => 'WPA',
            'wallpaper_enabled' => 1,
            'wallpaper_mode' => 'picsum',
            'wallpaper_interval' => 60000,
            'wallpaper_blur' => 20,
            'wallpaper_veil' => 50,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portal_settings', [
            'office_name' => 'Updated Office Hub',
            'wifi_ssid' => 'Nordvik-NewWifi',
            'wifi_password' => 'SuperPass2026',
        ]);

        $this->assertSame([
            ['name' => 'Head Office', 'address' => '1 Harbour Drive, Singapore 068816'],
            ['name' => 'North Studio', 'address' => '22 Bukit Batok Street 22, Singapore 659572'],
            ['name' => 'East Depot', 'address' => '45 Geylang Road, Singapore 389280'],
        ], PortalSetting::current()->locations());
    }

    public function test_office_locations_are_capped_at_three_slots(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/admin/settings', [
            'office_name' => 'Capped Office',
            'office_locations' => [
                ['name' => ' One ', 'address' => ' 1 One Street '],
                ['name' => 'Two', 'address' => '2 Two Street'],
                ['name' => 'Three', 'address' => '3 Three Street'],
                ['name' => 'Four', 'address' => '4 Four Street'],
            ],
            'wallpaper_mode' => 'picsum',
            'wallpaper_interval' => 60000,
            'wallpaper_blur' => 20,
            'wallpaper_veil' => 50,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame([
            ['name' => 'One', 'address' => '1 One Street'],
            ['name' => 'Two', 'address' => '2 Two Street'],
            ['name' => 'Three', 'address' => '3 Three Street'],
        ], PortalSetting::current()->locations());
    }

    public function test_portal_hides_blank_office_location_slots(): void
    {
        PortalSetting::current()->update([
            'office_locations' => [
                ['name' => 'Head Office', 'address' => '1 Harbour Drive, Singapore 068816'],
                ['name' => '', 'address' => ''],
                ['name' => 'East Depot', 'address' => '45 Geylang Road, Singapore 389280'],
            ],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Head Office');
        $response->assertSee('East Depot');
        $response->assertDontSee('loc-name"></span>', false);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@nordvik.local',
            'password' => Hash::make('Bitmap.123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin',
            'password' => 'Bitmap.123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_create_and_delete_portal_users(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
        ]);

        $createResponse = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Staff Manager',
            'username' => 'staffmgr',
            'email' => 'staffmgr@nordvik.local',
            'password' => 'Secret123',
        ]);

        $createResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'name' => 'Staff Manager',
            'username' => 'staffmgr',
            'email' => 'staffmgr@nordvik.local',
            'is_admin' => false,
        ]);

        $createdUser = User::where('username', 'staffmgr')->firstOrFail();

        $this->post('/logout');
        $loginResponse = $this->post('/login', [
            'email' => 'staffmgr',
            'password' => 'Secret123',
        ]);
        $loginResponse->assertRedirect('/admin');
        $this->assertAuthenticatedAs($createdUser);

        // Cannot delete self or manage users as non-admin
        $this->actingAs($createdUser)->post('/admin/users', [
            'name' => 'Other',
            'username' => 'other',
            'email' => 'other@nordvik.local',
            'password' => 'Secret123',
        ])->assertForbidden();

        $this->actingAs($createdUser)->delete("/admin/users/{$admin->id}")->assertForbidden();

        // Admin cannot delete self, but can delete created user
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($admin)->delete("/admin/users/{$createdUser->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $createdUser->id]);
    }
}
