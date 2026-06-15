<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('file_documents') || Schema::hasColumn('file_documents', 'sort_order')) {
            return;
        }

        Schema::table('file_documents', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->index()->after('parent_page_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('file_documents') || ! Schema::hasColumn('file_documents', 'sort_order')) {
            return;
        }

        Schema::table('file_documents', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
