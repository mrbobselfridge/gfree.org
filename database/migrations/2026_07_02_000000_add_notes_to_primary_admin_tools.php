<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'homepage_contents',
        'homepage_banners',
        'site_alerts',
        'pages',
        'navigation_links',
        'media_image_metadata',
        'file_documents',
        'slide_decks',
        'site_settings',
        'workflow_notification_rules',
        'users',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'notes')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->longText('notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'notes')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('notes');
            });
        }
    }
};
