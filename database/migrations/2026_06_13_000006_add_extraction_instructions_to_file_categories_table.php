<?php

use App\Support\FileCategoryExtractionInstructions;
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
        Schema::table('file_categories', function (Blueprint $table) {
            $table->text('extraction_instructions')->nullable()->after('sort_order');
        });

        $now = now();

        $position = 0;

        collect(FileCategoryExtractionInstructions::starterInstructions())
            ->each(function (string $instructions, string $name) use ($now, &$position): void {
                $position++;
                $category = DB::table('file_categories')->where('name', $name);

                if ($category->exists()) {
                    $category
                        ->whereNull('extraction_instructions')
                        ->update([
                            'extraction_instructions' => $instructions,
                            'updated_at' => $now,
                        ]);

                    return;
                }

                DB::table('file_categories')->insert([
                    'name' => $name,
                    'sort_order' => $position * 10,
                    'extraction_instructions' => $instructions,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_categories', function (Blueprint $table) {
            $table->dropColumn('extraction_instructions');
        });
    }
};
