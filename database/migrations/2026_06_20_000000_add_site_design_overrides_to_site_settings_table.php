<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'design_accent_color')) {
                $table->string('design_accent_color', 7)->nullable()->after('design_background_colors');
            }

            if (! Schema::hasColumn('site_settings', 'design_accent_text_color')) {
                $table->string('design_accent_text_color', 7)->nullable()->after('design_accent_color');
            }

            if (! Schema::hasColumn('site_settings', 'design_accent_soft_color')) {
                $table->string('design_accent_soft_color', 7)->nullable()->after('design_accent_text_color');
            }

            if (! Schema::hasColumn('site_settings', 'custom_css')) {
                $table->text('custom_css')->nullable()->after('design_accent_soft_color');
            }
        });

        DB::table('site_settings')
            ->whereNull('design_accent_color')
            ->update(['design_accent_color' => '#17b8ad']);

        DB::table('site_settings')
            ->whereNull('design_accent_text_color')
            ->update(['design_accent_text_color' => '#05756f']);

        DB::table('site_settings')
            ->whereNull('design_accent_soft_color')
            ->update(['design_accent_soft_color' => '#ddf8f5']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            foreach (['custom_css', 'design_accent_soft_color', 'design_accent_text_color', 'design_accent_color'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
