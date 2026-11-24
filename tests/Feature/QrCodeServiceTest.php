<?php

namespace Tests\Feature;

use App\Models\PortalSetting;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_valid_svg_qr_code_for_wifi_payload(): void
    {
        $payload = 'WIFI:T:WPA;S:Nordvik-Guest;P:CozyWelcome2026;;';
        $svg = QrCodeService::generateSvg($payload, 260, '#000000');

        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('viewBox="0 0 41 41"', $svg);
        $this->assertStringContainsString('width="260" height="260"', $svg);
        $this->assertStringContainsString('shape-rendering="crispEdges"', $svg);
        $this->assertStringContainsString('<rect width="100%" height="100%" fill="#ffffff"/>', $svg);
        $this->assertStringContainsString('fill="#000000"', $svg);
    }

    public function test_qr_code_contains_finder_patterns_and_dark_module(): void
    {
        $payload = 'WIFI:T:WPA;S:Nordvik-Guest;P:CozyWelcome2026;;';
        $svg = QrCodeService::generateSvg($payload, 240, '#000000');

        preg_match('/viewBox="0 0 (\d+) \d+"/', $svg, $vb);
        $total = (int) $vb[1];
        $size = $total - 8; // Subtract 4-module quiet zone on each side

        preg_match_all('/M(\d+),(\d+)h1v1h-1z/', $svg, $matches, PREG_SET_ORDER);
        $grid = array_fill(0, $size, array_fill(0, $size, false));
        foreach ($matches as $m) {
            $x = (int) $m[1] - 4;
            $y = (int) $m[2] - 4;
            if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
                $grid[$y][$x] = true;
            }
        }

        // Top-left finder center (3, 3) must be dark
        $this->assertTrue($grid[3][3]);
        // Top-right finder center (size-4, 3) must be dark
        $this->assertTrue($grid[3][$size - 4]);
        // Bottom-left finder center (3, size-4) must be dark
        $this->assertTrue($grid[$size - 4][3]);

        // Separator modules (e.g. at (7, 0..7)) must be white
        for ($i = 0; $i <= 7; $i++) {
            $this->assertFalse($grid[7][$i]);
            $this->assertFalse($grid[$i][7]);
        }

        // Dark module at (x=8, y=size-8) must be dark
        $this->assertTrue($grid[$size - 8][8]);
    }

    public function test_wifi_portal_setting_integration_renders_pure_black_qr(): void
    {
        $setting = PortalSetting::current();
        $setting->update([
            'wifi_ssid' => 'Nordvik-Secure-Staff',
            'wifi_password' => 'PassKey9876!',
        ]);

        $svg = $setting->wifi_qr_svg;
        $this->assertStringContainsString('viewBox="0 0', $svg);
        $this->assertStringContainsString('fill="#000000"', $svg);
    }

    public function test_handles_various_wifi_payload_lengths_adaptively(): void
    {
        $testCases = [
            'WIFI:T:nopass;S:OpenNet;P:;;',
            'WIFI:T:WPA;S:Short;P:12345678;;',
            'WIFI:T:WPA;S:A-Very-Long-Enterprise-Network-Name-Guest;P:ExtremelyLongAndComplexWPA2KeyForVisitingStaffMembers2026!;;',
        ];

        foreach ($testCases as $payload) {
            $svg = QrCodeService::generateSvg($payload);
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringContainsString('<path', $svg);
        }
    }
}
