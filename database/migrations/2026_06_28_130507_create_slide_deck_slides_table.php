<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_deck_slides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('slide_deck_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('slide_number');
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('public_image_path')->nullable();
            $table->string('slide_type')->default('unknown')->index();
            $table->string('suggested_name')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->longText('summary')->nullable();
            $table->string('event_title')->nullable();
            $table->string('event_date')->nullable();
            $table->string('event_time')->nullable();
            $table->string('event_location')->nullable();
            $table->string('event_audience')->nullable();
            $table->string('contact_person')->nullable();
            $table->longText('announcement_details')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('raw_analysis_json')->nullable();
            $table->timestamps();

            $table->unique(['slide_deck_id', 'slide_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_deck_slides');
    }
};
