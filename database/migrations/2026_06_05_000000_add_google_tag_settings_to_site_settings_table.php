<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('google_tag_manager_id')->nullable()->after('youtube_url');
            $table->string('google_analytics_measurement_id')->nullable()->after('google_tag_manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'google_tag_manager_id',
                'google_analytics_measurement_id',
            ]);
        });
    }
};
