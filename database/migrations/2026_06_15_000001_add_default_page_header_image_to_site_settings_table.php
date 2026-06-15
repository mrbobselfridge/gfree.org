<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings') || Schema::hasColumn('site_settings', 'default_page_header_image_path')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('default_page_header_image_path')->nullable()->after('site_logo_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'default_page_header_image_path')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('default_page_header_image_path');
        });
    }
};
