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
        $this->deleteLeaderImages();
        $this->deleteLeaderWorkflowData();
        $this->removeLeaderPermissions();

        if (Schema::hasTable('media_image_metadata')) {
            DB::table('media_image_metadata')
                ->where('path', 'like', 'leadership/%')
                ->orWhere('path', 'like', 'site-settings/leadership/%')
                ->delete();
        }

        Schema::dropIfExists('staff_members');

        if (Schema::hasTable('site_settings')) {
            $columns = collect([
                'leadership_small_label',
                'leadership_title',
                'leadership_subtitle',
                'leadership_image_path',
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
        if (! Schema::hasTable('staff_members')) {
            Schema::create('staff_members', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique()->nullable();
                $table->string('role')->nullable();
                $table->longText('bio')->nullable();
                $table->json('content_blocks')->nullable();
                $table->string('photo_path')->nullable();
                $table->string('card_image_path')->nullable();
                $table->string('email')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('availability')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('site_settings')) {
            Schema::table('site_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('site_settings', 'leadership_small_label')) {
                    $table->string('leadership_small_label')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'leadership_title')) {
                    $table->string('leadership_title')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'leadership_subtitle')) {
                    $table->text('leadership_subtitle')->nullable();
                }

                if (! Schema::hasColumn('site_settings', 'leadership_image_path')) {
                    $table->string('leadership_image_path')->nullable();
                }
            });
        }
    }

    private function deleteLeaderImages(): void
    {
        $disk = Storage::disk('public');

        if (Schema::hasTable('staff_members')) {
            DB::table('staff_members')
                ->select(['photo_path', 'card_image_path'])
                ->orderBy('id')
                ->get()
                ->each(function (object $leader) use ($disk): void {
                    $this->deletePath($disk, $leader->photo_path ?? null);
                    $this->deletePath($disk, $leader->card_image_path ?? null);
                });
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'leadership_image_path')) {
            DB::table('site_settings')
                ->whereNotNull('leadership_image_path')
                ->pluck('leadership_image_path')
                ->each(fn (?string $path): bool => $this->deletePath($disk, $path));
        }

        $disk->deleteDirectory('leadership');
        $disk->deleteDirectory('site-settings/leadership');
    }

    private function deletePath(Filesystem $disk, ?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return $disk->delete($path);
    }

    private function deleteLeaderWorkflowData(): void
    {
        if (Schema::hasTable('workflow_visual_snapshots')) {
            DB::table('workflow_visual_snapshots')
                ->where('snapshotable_type', 'App\\Models\\StaffMember')
                ->delete();
        }

        if (Schema::hasTable('workflow_notification_events')) {
            DB::table('workflow_notification_events')
                ->where('content_area', 'leaders')
                ->orWhere('record_type', 'App\\Models\\StaffMember')
                ->delete();
        }

        if (Schema::hasTable('workflow_notification_rules')) {
            DB::table('workflow_notification_rules')
                ->where('content_area', 'leaders')
                ->delete();
        }
    }

    private function removeLeaderPermissions(): void
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
                    ->reject(fn (mixed $tool): bool => $tool === 'leaders')
                    ->values()
                    ->all();

                unset($permissions['records']['leaders']);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['admin_permissions' => json_encode($permissions)]);
            });
    }
};
