<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_bookmark(): void
    {
        $user = User::factory()->create();

        // 1. Create
        $response = $this->actingAs($user)->post('/admin/bookmarks', [
            'label' => 'HR Portal',
            'url' => 'https://hr.example.com',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookmarks', ['label' => 'HR Portal']);

        $bm = Bookmark::where('label', 'HR Portal')->first();

        // 2. Update
        $updateResp = $this->actingAs($user)->put("/admin/bookmarks/{$bm->id}", [
            'label' => 'HR & Payroll Portal',
            'url' => 'https://hr.example.com/payroll',
        ]);
        $updateResp->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookmarks', ['label' => 'HR & Payroll Portal']);

        // 3. Delete
        $delResp = $this->actingAs($user)->delete("/admin/bookmarks/{$bm->id}");
        $delResp->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('bookmarks', ['id' => $bm->id]);
    }
}
