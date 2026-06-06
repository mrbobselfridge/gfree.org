<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('file_document_versions')) {
            if (DB::table('file_document_versions')->exists()) {
                throw new RuntimeException('The file_document_versions table already exists and contains rows, so it cannot be safely recreated automatically.');
            }

            Schema::dropIfExists('file_document_versions');
        }

        Schema::create('file_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_document_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('extension')->nullable()->index();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_document_versions');
    }
};
