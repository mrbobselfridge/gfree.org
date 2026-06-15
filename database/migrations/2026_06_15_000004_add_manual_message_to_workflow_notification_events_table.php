<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_notification_events', function (Blueprint $table): void {
            $table->text('manual_message')->nullable()->after('public_url');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_notification_events', function (Blueprint $table): void {
            $table->dropColumn('manual_message');
        });
    }
};
