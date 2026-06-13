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
        Schema::create('file_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $now = now();
        $starterCategories = [
            'Form',
            'Poster',
            'Policy',
            'Ministry Resource',
            'Event Handout',
            'Spreadsheet',
            'Other',
        ];

        $existingCategories = DB::table('file_documents')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter(fn (mixed $category): bool => filled($category))
            ->all();

        collect([...$starterCategories, ...$existingCategories])
            ->unique()
            ->values()
            ->each(fn (string $name, int $index) => DB::table('file_categories')->insert([
                'name' => $name,
                'sort_order' => ($index + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_categories');
    }
};
