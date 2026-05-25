<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('card_image_path')->nullable();
            $table->string('category')->nullable();
            $table->string('meeting_time')->nullable();
            $table->string('location')->nullable();
            $table->string('leader_name')->nullable();
            $table->string('leader_email')->nullable();
            $table->string('one_church_url')->nullable();
            $table->longText('embed_code')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ministries');
    }
};
