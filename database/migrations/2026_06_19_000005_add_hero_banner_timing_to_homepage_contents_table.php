<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('hero_banners_rotation_delay_seconds')->default(20)->after('hero_banners_auto_rotate');
            $table->unsignedSmallInteger('hero_banners_fade_duration_seconds')->default(3)->after('hero_banners_rotation_delay_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_banners_rotation_delay_seconds',
                'hero_banners_fade_duration_seconds',
            ]);
        });
    }
};
