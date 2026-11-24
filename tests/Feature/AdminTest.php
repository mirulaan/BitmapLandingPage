<?php

namespace Tests\Feature;

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
            'helpdesk_email' => 'support@test.local',
            'helpdesk_ext' => 'x9999',
            'wallpaper_enabled' => 1,
            'wallpaper_mode' => 'picsum',
            'wallpaper_interval' => 60000,
            'wallpaper_blur' => 20,
            'wallpaper_veil' => 50,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portal_settings', [
            'office_name' => 'Updated Office Hub',
            'helpdesk_ext' => 'x9999',
        ]);
    }
}
