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
            $table->string('sermons_small_label')->nullable()->after('ministry_image_path');
            $table->string('sermons_title')->nullable()->after('sermons_small_label');
            $table->text('sermons_subtitle')->nullable()->after('sermons_title');
            $table->text('sermons_text')->nullable()->after('sermons_subtitle');
            $table->string('sermons_youtube_link_label')->nullable()->after('sermons_text');
            $table->string('sermons_image_path')->nullable()->after('sermons_youtube_link_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sermons_small_label',
                'sermons_title',
                'sermons_subtitle',
                'sermons_text',
                'sermons_youtube_link_label',
                'sermons_image_path',
            ]);
        });
    }
};
