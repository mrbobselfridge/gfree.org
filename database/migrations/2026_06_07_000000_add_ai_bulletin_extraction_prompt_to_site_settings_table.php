<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('ai_bulletin_extraction_prompt')->nullable()->after('ai_content_prompt');
        });

        DB::table('site_settings')->update([
            'ai_bulletin_extraction_prompt' => 'Extract readable bulletin content for the public website.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('ai_bulletin_extraction_prompt');
        });
    }
};
