<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'header_custom_js')) {
                $table->longText('header_custom_js')->nullable()->after('custom_css');
            }

            if (! Schema::hasColumn('site_settings', 'body_top_custom_js')) {
                $table->longText('body_top_custom_js')->nullable()->after('header_custom_js');
            }

            if (! Schema::hasColumn('site_settings', 'body_bottom_custom_js')) {
                $table->longText('body_bottom_custom_js')->nullable()->after('body_top_custom_js');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            foreach (['body_bottom_custom_js', 'body_top_custom_js', 'header_custom_js'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
