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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('sermons_youtube_feed_url')->nullable()->after('sermons_youtube_link_label');
            $table->text('sermons_youtube_channel_url')->nullable()->after('sermons_youtube_feed_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sermons_youtube_feed_url',
                'sermons_youtube_channel_url',
            ]);
        });
    }
};
