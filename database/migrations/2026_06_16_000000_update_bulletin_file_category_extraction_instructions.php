<?php

use App\Support\FileCategoryExtractionInstructions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_BULLETIN_INSTRUCTIONS = 'Extract the important public bulletin content for the church website. Preserve worship details, announcements, dates, event details, contact information, links, and next steps. Do not include page numbers, repeated headers, footers, or decorative filler.';

    public function up(): void
    {
        DB::table('file_categories')
            ->where('name', 'Bulletin')
            ->where(function ($query): void {
                $query
                    ->whereNull('extraction_instructions')
                    ->orWhere('extraction_instructions', self::OLD_BULLETIN_INSTRUCTIONS);
            })
            ->update([
                'extraction_instructions' => FileCategoryExtractionInstructions::forCategory('Bulletin'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('file_categories')
            ->where('name', 'Bulletin')
            ->where('extraction_instructions', FileCategoryExtractionInstructions::forCategory('Bulletin'))
            ->update([
                'extraction_instructions' => self::OLD_BULLETIN_INSTRUCTIONS,
                'updated_at' => now(),
            ]);
    }
};
