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
        Schema::create('file_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_name')->unique();
            $table->string('category')->index();
            $table->string('visibility')->default('public')->index();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->json('tags')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->foreignId('current_version_id')->nullable()->index();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_documents');
    }
};
