<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slide_deck_slides', function (Blueprint $table): void {
            if (Schema::hasColumn('slide_deck_slides', 'scripture_reference')) {
                $table->dropColumn('scripture_reference');
            }

            if (Schema::hasColumn('slide_deck_slides', 'scripture_text')) {
                $table->dropColumn('scripture_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('slide_deck_slides', function (Blueprint $table): void {
            if (! Schema::hasColumn('slide_deck_slides', 'scripture_reference')) {
                $table->string('scripture_reference')->nullable()->after('summary');
            }

            if (! Schema::hasColumn('slide_deck_slides', 'scripture_text')) {
                $table->longText('scripture_text')->nullable()->after('scripture_reference');
            }
        });
    }
};
