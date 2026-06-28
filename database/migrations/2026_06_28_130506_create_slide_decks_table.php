<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_decks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('original_filename');
            $table->string('stored_file_path');
            $table->foreignId('file_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('total_slides')->default(0);
            $table->unsignedInteger('processed_slides')->default(0);
            $table->longText('error_message')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_decks');
    }
};
