<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table): void {
            $table->longText('announcement_review')->nullable()->after('extracted_html');
        });
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table): void {
            $table->dropColumn('announcement_review');
        });
    }
};
