<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_visual_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('snapshotable_type');
            $table->unsignedBigInteger('snapshotable_id');
            $table->string('snapshot_path');
            $table->timestamp('snapshot_captured_at')->nullable();
            $table->timestamps();

            $table->unique(['snapshotable_type', 'snapshotable_id'], 'workflow_visual_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_visual_snapshots');
    }
};
