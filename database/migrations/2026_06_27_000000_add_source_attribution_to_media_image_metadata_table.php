<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_image_metadata') || Schema::hasColumn('media_image_metadata', 'source')) {
            return;
        }

        Schema::table('media_image_metadata', function (Blueprint $table): void {
            $table->string('source')->nullable()->after('created_by_user_id')->index();
            $table->string('source_id')->nullable()->after('source')->index();
            $table->string('source_url', 2048)->nullable()->after('source_id');
            $table->string('source_author_name')->nullable()->after('source_url');
            $table->string('source_author_url', 2048)->nullable()->after('source_author_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('media_image_metadata') || ! Schema::hasColumn('media_image_metadata', 'source')) {
            return;
        }

        Schema::table('media_image_metadata', function (Blueprint $table): void {
            $table->dropColumn([
                'source',
                'source_id',
                'source_url',
                'source_author_name',
                'source_author_url',
            ]);
        });
    }
};
