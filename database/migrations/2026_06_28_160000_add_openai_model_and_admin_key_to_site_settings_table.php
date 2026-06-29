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
            if (! Schema::hasColumn('site_settings', 'openai_api_key_id')) {
                $table->string('openai_api_key_id')->nullable()->after('openai_api_key');
            }

            if (! Schema::hasColumn('site_settings', 'openai_content_model')) {
                $table->string('openai_content_model')->nullable()->after('openai_api_key_id');
            }

            if (! Schema::hasColumn('site_settings', 'openai_admin_api_key')) {
                $table->text('openai_admin_api_key')->nullable()->after('openai_content_model');
            }
        });

        DB::table('site_settings')
            ->whereNull('openai_content_model')
            ->update(['openai_content_model' => config('services.openai.content_model')]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('site_settings', 'openai_content_model')) {
                $columns[] = 'openai_content_model';
            }

            if (Schema::hasColumn('site_settings', 'openai_admin_api_key')) {
                $columns[] = 'openai_admin_api_key';
            }

            if (Schema::hasColumn('site_settings', 'openai_api_key_id')) {
                $columns[] = 'openai_api_key_id';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
