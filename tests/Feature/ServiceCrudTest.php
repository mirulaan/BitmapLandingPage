<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'status' => 'offline',
        ]);
        $updateResp->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['name' => 'Deployer Pro', 'status' => 'offline']);

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

    public function test_service_status_is_automatically_determined_via_ping_when_not_provided(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://alive.example.com*' => Http::response('OK', 200),
            'https://dead.example.com*' => Http::response('Error', 500),
        ]);

        // Creating service without status should auto ping and be online
        $resOnline = $this->actingAs($user)->post('/admin/services', [
            'icon' => '⚡',
            'name' => 'Alive Service',
            'description' => 'Testing auto ping online',
            'url' => 'https://alive.example.com',
        ]);
        $resOnline->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['name' => 'Alive Service', 'status' => 'online']);

        // Creating service with dead URL should auto ping and be offline
        $resOffline = $this->actingAs($user)->post('/admin/services', [
            'icon' => '💀',
            'name' => 'Dead Service',
            'description' => 'Testing auto ping offline',
            'url' => 'https://dead.example.com',
        ]);
        $resOffline->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['name' => 'Dead Service', 'status' => 'offline']);
    }

    public function test_services_ping_endpoint_pings_all_services_and_returns_status(): void
    {
        Http::fake([
            'https://up.example.com*' => Http::response('OK', 200),
            'https://down.example.com*' => Http::response('Error', 500),
        ]);

        $svc1 = Service::create([
            'name' => 'Up Service',
            'url' => 'https://up.example.com',
            'status' => 'offline',
            'sort_order' => 1,
        ]);

        $svc2 = Service::create([
            'name' => 'Down Service',
            'url' => 'https://down.example.com',
            'status' => 'online',
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/services/ping');
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'services' => [
                ['id' => $svc1->id, 'status' => 'online'],
                ['id' => $svc2->id, 'status' => 'offline'],
            ],
        ]);

        $this->assertEquals('online', $svc1->fresh()->status);
        $this->assertEquals('offline', $svc2->fresh()->status);
    }
}
