<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            $table->string('wifi_ssid', 64)->default('Nordvik-Guest')->after('helpdesk_ext');
            $table->string('wifi_password', 64)->default('CozyWelcome2026')->after('wifi_ssid');
            $table->string('wifi_encryption', 16)->default('WPA')->after('wifi_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            $table->dropColumn(['wifi_ssid', 'wifi_password', 'wifi_encryption']);
        });
    }
};
