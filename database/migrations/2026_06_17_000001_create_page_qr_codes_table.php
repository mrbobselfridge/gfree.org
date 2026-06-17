<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_qr_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('png_path');
            $table->string('svg_path');
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_qr_codes');
    }
};
