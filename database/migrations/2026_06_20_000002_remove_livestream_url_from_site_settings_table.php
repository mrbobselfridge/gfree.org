<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'livestream_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->dropColumn('livestream_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'livestream_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('livestream_url')->nullable()->after('office_hours');
            });
        }
    }
};
