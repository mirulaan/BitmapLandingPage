<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_reorder_and_delete_service(): void
    {
        $user = User::factory()->create();

        // 1. Create
        $response = $this->actingAs($user)->post('/admin/services', [
            'icon' => '🚀',
            'name' => 'Deployer',
            'description' => 'CI/CD pipeline portal',
            'url' => 'https://deploy.example.com',
            'status' => 'online',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['name' => 'Deployer']);

        $service1 = Service::where('name', 'Deployer')->first();

        // 2. Update
        $updateResp = $this->actingAs($user)->put("/admin/services/{$service1->id}", [
            'icon' => '🛠️',
            'name' => 'Deployer Pro',
            'description' => 'Upgraded CI/CD',
            'url' => 'https://deploy.example.com',
            'status' => 'maintenance',
        ]);
        $updateResp->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['name' => 'Deployer Pro', 'status' => 'maintenance']);

        // 3. Create second service and reorder
        $service2 = Service::create([
            'icon' => '📦',
            'name' => 'Storage',
            'url' => 'https://storage.local',
            'status' => 'online',
            'sort_order' => 2,
        ]);

        $reorderResp = $this->actingAs($user)->postJson('/admin/services/reorder', [
            'order' => [$service2->id, $service1->id],
        ]);
        $reorderResp->assertOk();
        $this->assertEquals(1, $service2->fresh()->sort_order);
        $this->assertEquals(2, $service1->fresh()->sort_order);

        // 4. Delete
        $delResp = $this->actingAs($user)->delete("/admin/services/{$service1->id}");
        $delResp->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('services', ['id' => $service1->id]);
    }
}
