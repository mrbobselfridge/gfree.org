<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slide_deck_slides', function (Blueprint $table): void {
            if (! Schema::hasColumn('slide_deck_slides', 'public_image_path')) {
                $table->string('public_image_path')->nullable()->after('thumbnail_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('slide_deck_slides', function (Blueprint $table): void {
            if (Schema::hasColumn('slide_deck_slides', 'public_image_path')) {
                $table->dropColumn('public_image_path');
            }
        });
    }
};
