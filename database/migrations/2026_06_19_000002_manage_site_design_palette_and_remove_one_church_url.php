<?php

use App\Support\SiteDesignPalette;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'design_background_colors')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->json('design_background_colors')->nullable()->after('default_page_header_image_path');
            });
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'design_background_colors')) {
            DB::table('site_settings')
                ->whereNull('design_background_colors')
                ->update([
                    'design_background_colors' => json_encode(SiteDesignPalette::defaultBackgroundColors()),
                ]);
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'one_church_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->dropColumn('one_church_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'one_church_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('one_church_url')->nullable()->after('office_hours');
            });
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'design_background_colors')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->dropColumn('design_background_colors');
            });
        }
    }
};
