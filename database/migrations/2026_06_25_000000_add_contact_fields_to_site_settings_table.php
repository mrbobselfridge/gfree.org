<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('email');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->text('contact_notes')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_name',
                'contact_email',
                'contact_phone',
                'contact_notes',
            ]);
        });
    }
};
