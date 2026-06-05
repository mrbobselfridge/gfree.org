<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_notification_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('content_area');
            $table->json('triggers');
            $table->boolean('notify_admins')->default(false);
            $table->boolean('notify_all_users')->default(false);
            $table->json('selected_user_ids')->nullable();
            $table->text('extra_emails')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->unsignedSmallInteger('delay_minutes')->default(15);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['content_area', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notification_rules');
    }
};
