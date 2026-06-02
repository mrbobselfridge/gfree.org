<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_page_views', function (Blueprint $table): void {
            $table->id();
            $table->text('url');
            $table->string('path')->index();
            $table->string('route_name')->nullable()->index();
            $table->string('page_title')->nullable()->index();
            $table->text('referrer_url')->nullable();
            $table->string('referrer_domain')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable()->index();
            $table->string('platform')->nullable()->index();
            $table->string('device_type')->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('visitor_hash', 64)->nullable()->index();
            $table->string('session_hash', 64)->nullable()->index();
            $table->timestamp('viewed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_page_views');
    }
};
