<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'tiktok_url')) {
                $table->string('tiktok_url')->nullable()->after('youtube_url');
            }

            if (! Schema::hasColumn('site_settings', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable()->after('tiktok_url');
            }

            if (! Schema::hasColumn('site_settings', 'google_business_profile_url')) {
                $table->string('google_business_profile_url')->nullable()->after('linkedin_url');
            }

            if (! Schema::hasColumn('site_settings', 'pinterest_url')) {
                $table->string('pinterest_url')->nullable()->after('google_business_profile_url');
            }

            if (! Schema::hasColumn('site_settings', 'x_url')) {
                $table->string('x_url')->nullable()->after('pinterest_url');
            }

            if (! Schema::hasColumn('site_settings', 'threads_url')) {
                $table->string('threads_url')->nullable()->after('x_url');
            }

            if (! Schema::hasColumn('site_settings', 'additional_social_links')) {
                $table->json('additional_social_links')->nullable()->after('threads_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $columns = [
            'additional_social_links',
            'threads_url',
            'x_url',
            'pinterest_url',
            'google_business_profile_url',
            'linkedin_url',
            'tiktok_url',
        ];

        Schema::table('site_settings', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
