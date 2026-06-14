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
        Schema::table('file_documents', function (Blueprint $table) {
            $table->string('card_image_path')->nullable()->after('parent_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_documents', function (Blueprint $table) {
            $table->dropColumn('card_image_path');
        });
    }
};
