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
        Schema::create('portal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('office_name', 60)->default('Nordvik & Co. — Staff Hub');
            $table->string('tagline', 90)->default('A calm place to start the workday.');
            $table->string('helpdesk_email')->default('helpdesk@nordvik.local');
            $table->string('helpdesk_ext', 12)->default('x4210');
            $table->string('theme')->nullable(); // 'day', 'night', or null (auto)
            $table->boolean('wallpaper_enabled')->default(true);
            $table->string('wallpaper_mode', 20)->default('picsum'); // 'picsum' or 'custom'
            $table->text('wallpaper_custom_urls')->nullable(); // JSON array
            $table->integer('wallpaper_interval')->default(300000);
            $table->integer('wallpaper_blur')->default(28);
            $table->integer('wallpaper_veil')->default(55);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_settings');
    }
};
