<?php

namespace Tests\Feature;

use App\Models\PortalSetting;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_valid_json_payload(): void
    {
        $user = User::factory()->create();
        PortalSetting::current()->update(['office_name' => 'Nordvik Test HQ']);
        Service::create([
            'icon' => '☁️',
            'name' => 'Cloud Backup',
            'description' => 'Offsite archive',
            'url' => 'https://cloud.test',
            'status' => 'online',
        ]);

        $response = $this->actingAs($user)->get('/admin/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $json = $response->json();

        $this->assertEquals('Nordvik Test HQ', $json['office']);
        $this->assertCount(1, $json['services']);
        $this->assertEquals('Cloud Backup', $json['services'][0]['name']);
    }

    public function test_import_replaces_configuration_cleanly(): void
    {
        $user = User::factory()->create();

        $backupData = [
            'version' => 1,
            'office' => 'Imported Custom Office',
            'tagline' => 'Imported tagline',
            'helpdesk' => ['email' => 'custom@office.local', 'ext' => 'x1111'],
            'wallpaper' => ['enabled' => true, 'mode' => 'picsum', 'blur' => 24, 'veil' => 60],
            'services' => [
                ['icon' => '☕', 'name' => 'Coffee Machine IoT', 'desc' => 'Check beans level', 'url' => 'http://coffee.local', 'status' => 'online'],
            ],
            'notices' => [
                ['title' => 'Imported Announcement', 'body' => 'Everything imported cleanly', 'category' => 'general', 'priority' => 'normal', 'pinned' => true],
            ],
            'bookmarks' => [
                ['label' => 'Imported Link', 'url' => 'https://imported.local'],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($backupData));

        $response = $this->actingAs($user)->post('/admin/import', [
            'config_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portal_settings', ['office_name' => 'Imported Custom Office']);
        $this->assertDatabaseHas('services', ['name' => 'Coffee Machine IoT']);
        $this->assertDatabaseHas('notices', ['title' => 'Imported Announcement']);
        $this->assertDatabaseHas('bookmarks', ['label' => 'Imported Link']);
    }

    public function test_reset_re_seeds_default_data(): void
    {
        $user = User::factory()->create();
        Service::truncate();
        $this->assertDatabaseCount('services', 0);

        $response = $this->actingAs($user)->post('/admin/reset');

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('services', 5);
        $this->assertDatabaseHas('services', ['name' => 'Nextcloud']);
    }
}
