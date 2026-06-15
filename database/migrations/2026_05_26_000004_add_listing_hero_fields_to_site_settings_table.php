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
            $table->string('announcements_small_label')->nullable()->after('youtube_url');
            $table->string('announcements_title')->nullable()->after('announcements_small_label');
            $table->text('announcements_subtitle')->nullable()->after('announcements_title');
            $table->string('announcements_image_path')->nullable()->after('announcements_subtitle');

            $table->string('leadership_small_label')->nullable()->after('announcements_image_path');
            $table->string('leadership_title')->nullable()->after('leadership_small_label');
            $table->text('leadership_subtitle')->nullable()->after('leadership_title');
            $table->string('leadership_image_path')->nullable()->after('leadership_subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'announcements_small_label',
                'announcements_title',
                'announcements_subtitle',
                'announcements_image_path',
                'leadership_small_label',
                'leadership_title',
                'leadership_subtitle',
                'leadership_image_path',
            ]);
        });
    }
};
