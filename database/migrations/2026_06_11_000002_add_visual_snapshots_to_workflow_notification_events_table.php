<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_notification_events', function (Blueprint $table): void {
            $table->string('pre_snapshot_path')->nullable()->after('public_url');
            $table->timestamp('pre_snapshot_captured_at')->nullable()->after('pre_snapshot_path');
            $table->string('post_snapshot_path')->nullable()->after('pre_snapshot_captured_at');
            $table->timestamp('post_snapshot_captured_at')->nullable()->after('post_snapshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_notification_events', function (Blueprint $table): void {
            $table->dropColumn([
                'pre_snapshot_path',
                'pre_snapshot_captured_at',
                'post_snapshot_path',
                'post_snapshot_captured_at',
            ]);
        });
    }
};
