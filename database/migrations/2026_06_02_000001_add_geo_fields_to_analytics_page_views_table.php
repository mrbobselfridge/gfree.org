<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_page_views', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->after('session_hash')->index();
            $table->string('country_name')->nullable()->after('country_code')->index();
            $table->string('region_code')->nullable()->after('country_name')->index();
            $table->string('region_name')->nullable()->after('region_code')->index();
            $table->string('city_name')->nullable()->after('region_name')->index();
            $table->string('postal_code')->nullable()->after('city_name');
            $table->string('timezone')->nullable()->after('postal_code')->index();
            $table->decimal('latitude', 10, 7)->nullable()->after('timezone');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_driver')->nullable()->after('longitude')->index();
        });
    }

    public function down(): void
    {
        Schema::table('analytics_page_views', function (Blueprint $table): void {
            $table->dropColumn([
                'country_code',
                'country_name',
                'region_code',
                'region_name',
                'city_name',
                'postal_code',
                'timezone',
                'latitude',
                'longitude',
                'location_driver',
            ]);
        });
    }
};
