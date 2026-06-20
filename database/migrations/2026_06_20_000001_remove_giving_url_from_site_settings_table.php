<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'giving_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->dropColumn('giving_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'giving_url')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                $table->string('giving_url')->nullable()->after('livestream_url');
            });
        }
    }
};
