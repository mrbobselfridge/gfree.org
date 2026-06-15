<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->json('content_blocks')->nullable()->after('body');
        });

        Schema::table('staff_members', function (Blueprint $table): void {
            $table->json('content_blocks')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn('content_blocks');
        });

        Schema::table('staff_members', function (Blueprint $table): void {
            $table->dropColumn('content_blocks');
        });
    }
};
