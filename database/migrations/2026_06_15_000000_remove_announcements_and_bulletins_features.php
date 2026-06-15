<?php

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteAnnouncementAndBulletinFiles();
        $this->deleteWorkflowData();
        $this->removeToolPermissions();
        $this->removeAnnouncementBlocks();
        $this->deleteMediaMetadata();

        Schema::dropIfExists('announcements');
        Schema::dropIfExists('bulletins');

        if (Schema::hasTable('site_settings')) {
            $columns = collect([
                'announcements_small_label',
                'announcements_title',
                'announcements_subtitle',
                'announcements_image_path',
                'bulletins_small_label',
                'bulletins_title',
                'bulletins_subtitle',
                'bulletins_image_path',
                'openai_bulletin_model',
                'ai_bulletin_extraction_prompt',
            ])->filter(fn (string $column): bool => Schema::hasColumn('site_settings', $column))->all();

            if ($columns !== []) {
                Schema::table('site_settings', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('body')->nullable();
                $table->json('content_blocks')->nullable();
                $table->string('image_path')->nullable();
                $table->string('background')->default('white');
                $table->string('cta_label')->nullable();
                $table->string('cta_url')->nullable();
                $table->timestamp('publish_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('featured_at')->nullable();
                $table->timestamp('feature_expires_at')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_published')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bulletins')) {
            Schema::create('bulletins', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->date('bulletin_date')->nullable();
                $table->string('pdf_path')->nullable();
                $table->text('extraction_prompt')->nullable();
                $table->longText('extracted_html')->nullable();
                $table->longText('announcement_review')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('site_settings')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                foreach ([
                    'announcements_small_label',
                    'announcements_title',
                    'announcements_image_path',
                    'bulletins_small_label',
                    'bulletins_title',
                    'bulletins_image_path',
                    'openai_bulletin_model',
                ] as $column) {
                    if (! Schema::hasColumn('site_settings', $column)) {
                        $table->string($column)->nullable();
                    }
                }

                foreach ([
                    'announcements_subtitle',
                    'bulletins_subtitle',
                    'ai_bulletin_extraction_prompt',
                ] as $column) {
                    if (! Schema::hasColumn('site_settings', $column)) {
                        $table->text($column)->nullable();
                    }
                }
            });
        }
    }

    private function deleteAnnouncementAndBulletinFiles(): void
    {
        $disk = Storage::disk('public');

        if (Schema::hasTable('announcements')) {
            DB::table('announcements')
                ->select(['image_path'])
                ->whereNotNull('image_path')
                ->orderBy('id')
                ->get()
                ->each(fn (object $announcement): bool => $this->deletePath($disk, $announcement->image_path ?? null));
        }

        if (Schema::hasTable('bulletins')) {
            DB::table('bulletins')
                ->select(['pdf_path'])
                ->whereNotNull('pdf_path')
                ->orderBy('id')
                ->get()
                ->each(fn (object $bulletin): bool => $this->deletePath($disk, $bulletin->pdf_path ?? null));
        }

        if (Schema::hasTable('site_settings')) {
            foreach (['announcements_image_path', 'bulletins_image_path'] as $column) {
                if (! Schema::hasColumn('site_settings', $column)) {
                    continue;
                }

                DB::table('site_settings')
                    ->whereNotNull($column)
                    ->pluck($column)
                    ->each(fn (?string $path): bool => $this->deletePath($disk, $path));
            }
        }

        foreach ([
            'announcements',
            'bulletins',
            'site-settings/announcements',
            'site-settings/bulletins',
        ] as $directory) {
            $disk->deleteDirectory($directory);
        }
    }

    private function deletePath(Filesystem $disk, ?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return $disk->delete($path);
    }

    private function deleteWorkflowData(): void
    {
        if (Schema::hasTable('workflow_visual_snapshots')) {
            DB::table('workflow_visual_snapshots')
                ->whereIn('snapshotable_type', [
                    'App\\Models\\Announcement',
                    'App\\Models\\Bulletin',
                ])
                ->delete();
        }

        if (Schema::hasTable('workflow_notification_events')) {
            DB::table('workflow_notification_events')
                ->whereIn('content_area', ['announcements', 'bulletins'])
                ->orWhereIn('record_type', [
                    'App\\Models\\Announcement',
                    'App\\Models\\Bulletin',
                ])
                ->delete();
        }

        if (Schema::hasTable('workflow_notification_rules')) {
            DB::table('workflow_notification_rules')
                ->whereIn('content_area', ['announcements', 'bulletins'])
                ->delete();
        }
    }

    private function removeToolPermissions(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'admin_permissions')) {
            return;
        }

        DB::table('users')
            ->select(['id', 'admin_permissions'])
            ->whereNotNull('admin_permissions')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $permissions = json_decode((string) $user->admin_permissions, true);

                if (! is_array($permissions)) {
                    return;
                }

                $permissions['tools'] = collect($permissions['tools'] ?? [])
                    ->reject(fn (mixed $tool): bool => in_array($tool, ['announcements', 'bulletins'], true))
                    ->values()
                    ->all();

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['admin_permissions' => json_encode($permissions)]);
            });
    }

    private function removeAnnouncementBlocks(): void
    {
        foreach (['homepage_contents', 'pages', 'ministries'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'content_blocks')) {
                continue;
            }

            DB::table($table)
                ->select(['id', 'content_blocks'])
                ->whereNotNull('content_blocks')
                ->orderBy('id')
                ->get()
                ->each(function (object $record) use ($table): void {
                    $blocks = json_decode((string) $record->content_blocks, true);

                    if (! is_array($blocks)) {
                        return;
                    }

                    $filtered = collect($blocks)
                        ->reject(fn (mixed $block): bool => is_array($block) && ($block['type'] ?? null) === 'announcements_bar')
                        ->values()
                        ->all();

                    if (count($filtered) === count($blocks)) {
                        return;
                    }

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['content_blocks' => json_encode($filtered)]);
                });
        }
    }

    private function deleteMediaMetadata(): void
    {
        if (! Schema::hasTable('media_image_metadata')) {
            return;
        }

        DB::table('media_image_metadata')
            ->where('path', 'like', 'announcements/%')
            ->orWhere('path', 'like', 'bulletins/%')
            ->orWhere('path', 'like', 'site-settings/announcements/%')
            ->orWhere('path', 'like', 'site-settings/bulletins/%')
            ->delete();
    }
};
