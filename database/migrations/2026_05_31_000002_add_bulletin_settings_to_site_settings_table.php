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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('bulletins_small_label')->nullable()->after('sermons_image_path');
            $table->string('bulletins_title')->nullable()->after('bulletins_small_label');
            $table->text('bulletins_subtitle')->nullable()->after('bulletins_title');
            $table->string('bulletins_image_path')->nullable()->after('bulletins_subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bulletins_small_label',
                'bulletins_title',
                'bulletins_subtitle',
                'bulletins_image_path',
            ]);
        });
    }
};
