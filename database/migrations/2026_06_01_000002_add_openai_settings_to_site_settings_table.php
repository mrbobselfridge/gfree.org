<?php

use App\Support\OpenAiSiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('openai_api_key')->nullable()->after('office_hours');
            $table->string('openai_bulletin_model')->default(OpenAiSiteSettings::DEFAULT_MODEL)->after('openai_api_key');
        });

        DB::table('site_settings')->update([
            'openai_api_key' => env('OPENAI_API_KEY'),
            'openai_bulletin_model' => env('OPENAI_BULLETIN_MODEL', OpenAiSiteSettings::DEFAULT_MODEL),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'openai_api_key',
                'openai_bulletin_model',
            ]);
        });
    }
};
