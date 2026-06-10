<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->boolean('is_redirect')->default(false)->after('is_published');
            $table->string('redirect_url', 2048)->nullable()->after('is_redirect');
            $table->unsignedSmallInteger('redirect_status_code')->default(302)->after('redirect_url');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn([
                'is_redirect',
                'redirect_url',
                'redirect_status_code',
            ]);
        });
    }
};
