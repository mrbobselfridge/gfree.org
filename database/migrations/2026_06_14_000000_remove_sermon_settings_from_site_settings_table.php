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
        $columns = [
            'sermons_small_label',
            'sermons_title',
            'sermons_subtitle',
            'sermons_text',
            'sermons_youtube_link_label',
            'sermons_youtube_feed_url',
            'sermons_youtube_channel_url',
            'sermons_image_path',
        ];

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('site_settings', $column),
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) use ($existing): void {
            $table->dropColumn($existing);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'sermons_small_label')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('sermons_small_label')->nullable()->after('youtube_url');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_title')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('sermons_title')->nullable()->after('sermons_small_label');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_subtitle')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->text('sermons_subtitle')->nullable()->after('sermons_title');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_text')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->text('sermons_text')->nullable()->after('sermons_subtitle');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_youtube_link_label')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('sermons_youtube_link_label')->nullable()->after('sermons_text');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_youtube_feed_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->text('sermons_youtube_feed_url')->nullable()->after('sermons_youtube_link_label');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_youtube_channel_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->text('sermons_youtube_channel_url')->nullable()->after('sermons_youtube_feed_url');
            });
        }

        if (! Schema::hasColumn('site_settings', 'sermons_image_path')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('sermons_image_path')->nullable()->after('sermons_youtube_channel_url');
            });
        }
    }
};
