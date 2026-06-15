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
        $this->deleteMinistryImages();
        $this->deleteMinistryWorkflowData();
        $this->removeMinistryPermissions();
        $this->deleteMinistryMediaMetadata();
        $this->dropMinistrySiteSettingColumns();

        Schema::dropIfExists('ministries');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ministries')) {
            Schema::create('ministries', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('short_summary')->nullable();
                $table->longText('description')->nullable();
                $table->json('content_blocks')->nullable();
                $table->string('hero_image_path')->nullable();
                $table->string('card_image_path')->nullable();
                $table->string('category')->nullable();
                $table->string('meeting_time')->nullable();
                $table->string('location')->nullable();
                $table->string('leader_name')->nullable();
                $table->string('leader_email')->nullable();
                $table->string('leader_phone')->nullable();
                $table->string('one_church_url')->nullable();
                $table->longText('embed_code')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('site_settings')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('site_settings', 'ministry_small_label')) {
                    $table->string('ministry_small_label')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'ministry_title')) {
                    $table->string('ministry_title')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'ministry_subtitle')) {
                    $table->text('ministry_subtitle')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'ministry_image_path')) {
                    $table->string('ministry_image_path')->nullable();
                }
            });
        }
    }

    private function deleteMinistryImages(): void
    {
        $disk = Storage::disk('public');

        if (Schema::hasTable('ministries')) {
            DB::table('ministries')
                ->select(['hero_image_path', 'card_image_path', 'content_blocks'])
                ->orderBy('id')
                ->get()
                ->each(function (object $ministry) use ($disk): void {
                    $this->deletePath($disk, $ministry->hero_image_path ?? null);
                    $this->deletePath($disk, $ministry->card_image_path ?? null);

                    foreach ($this->contentBlockImagePaths($ministry->content_blocks ?? null) as $path) {
                        $this->deletePath($disk, $path);
                    }
                });
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'ministry_image_path')) {
            DB::table('site_settings')
                ->whereNotNull('ministry_image_path')
                ->pluck('ministry_image_path')
                ->each(fn (?string $path): bool => $this->deletePath($disk, $path));
        }

        $disk->deleteDirectory('ministries');
        $disk->deleteDirectory('site-settings/ministry');
    }

    private function deleteMinistryWorkflowData(): void
    {
        if (! Schema::hasTable('workflow_notification_rules')) {
            return;
        }

        $ruleIds = DB::table('workflow_notification_rules')
            ->where('content_area', 'ministries')
            ->pluck('id')
            ->all();

        if ($ruleIds !== [] && Schema::hasTable('workflow_notification_events')) {
            DB::table('workflow_notification_events')
                ->whereIn('workflow_notification_rule_id', $ruleIds)
                ->delete();
        }

        DB::table('workflow_notification_rules')
            ->where('content_area', 'ministries')
            ->delete();
    }

    private function removeMinistryPermissions(): void
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
                    ->reject(fn (mixed $tool): bool => $tool === 'ministries')
                    ->values()
                    ->all();

                if (isset($permissions['records']) && is_array($permissions['records'])) {
                    unset($permissions['records']['ministries']);
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['admin_permissions' => json_encode($permissions)]);
            });
    }

    private function deleteMinistryMediaMetadata(): void
    {
        if (! Schema::hasTable('media_image_metadata')) {
            return;
        }

        DB::table('media_image_metadata')
            ->where('path', 'like', 'ministries/%')
            ->orWhere('path', 'like', 'site-settings/ministry/%')
            ->delete();
    }

    private function dropMinistrySiteSettingColumns(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $columns = collect([
            'ministry_small_label',
            'ministry_title',
            'ministry_subtitle',
            'ministry_image_path',
        ])->filter(fn (string $column): bool => Schema::hasColumn('site_settings', $column))->all();

        if ($columns === []) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    private function deletePath(Filesystem $disk, ?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return $disk->delete($path);
    }

    /**
     * @return array<int, string>
     */
    private function contentBlockImagePaths(mixed $contentBlocks): array
    {
        $blocks = json_decode((string) $contentBlocks, true);

        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->pluck('data.image_path')
            ->filter()
            ->map(fn (mixed $path): string => is_array($path) ? (string) collect($path)->first() : (string) $path)
            ->filter()
            ->values()
            ->all();
    }
};
