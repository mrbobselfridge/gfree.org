<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_notification_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_notification_rule_id');
            $table->foreign('workflow_notification_rule_id', 'workflow_events_rule_fk')
                ->references('id')
                ->on('workflow_notification_rules')
                ->cascadeOnDelete();
            $table->string('content_area');
            $table->string('trigger');
            $table->string('status')->default('pending');
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_key');
            $table->string('record_label')->nullable();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('public_url')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('recipient_emails')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['workflow_notification_rule_id', 'record_key', 'status'], 'workflow_rule_record_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notification_events');
    }
};
