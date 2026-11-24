<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_submit_public_notice_from_portal(): void
    {
        $response = $this->postJson('/notices', [
            'title' => 'Fresh Doughnuts in Breakroom',
            'body' => 'Glazed and chocolate on the kitchen counter.',
            'category' => 'general',
            'priority' => 'normal',
            'pinned' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notices', ['title' => 'Fresh Doughnuts in Breakroom']);
    }

    public function test_admin_can_pin_and_delete_notice(): void
    {
        $user = User::factory()->create();
        $notice = Notice::create([
            'title' => 'Server Maintenance',
            'body' => 'Tonight at 10 PM',
            'category' => 'it',
            'priority' => 'high',
            'pinned' => false,
        ]);

        // Toggle Pin
        $pinResp = $this->actingAs($user)->putJson("/admin/notices/{$notice->id}", [
            'pinned' => true,
        ]);
        $pinResp->assertOk();
        $this->assertTrue($notice->fresh()->pinned);

        // Delete
        $delResp = $this->actingAs($user)->deleteJson("/admin/notices/{$notice->id}");
        $delResp->assertOk();
        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }
}
