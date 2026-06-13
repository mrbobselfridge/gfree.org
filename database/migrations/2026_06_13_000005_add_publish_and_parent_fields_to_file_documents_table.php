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
            $table->foreignId('parent_page_id')
                ->nullable()
                ->after('category')
                ->constrained('pages')
                ->nullOnDelete();
            $table->dateTime('publish_at')->nullable()->after('is_published')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_page_id');
            $table->dropColumn('publish_at');
        });
    }
};
