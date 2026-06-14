<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'file_name',
    'category',
    'parent_page_id',
    'card_image_path',
    'is_published',
    'visibility',
    'description',
    'content',
    'tags',
    'publish_at',
    'expires_at',
    'current_version_id',
    'uploaded_by_id',
    'updated_by_id',
])]
class FileDocument extends Model implements HasPublicUrl
{
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const DEFAULT_CARD_IMAGE_PATH = 'images/file-card-default.svg';

    public static function categories(): array
    {
        return FileCategory::options();
    }

    public static function categoryOptions(): array
    {
        return collect(FileCategory::options())
            ->merge(static::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category', 'category')
                ->all())
            ->all();
    }

    public static function makeUniqueFileName(?string $source, ?self $ignore = null): string
    {
        $base = Str::slug(pathinfo((string) $source, PATHINFO_FILENAME) ?: (string) $source);

        if (blank($base)) {
            $base = 'file';
        }

        $candidate = $base;
        $counter = 2;

        while (static::query()
            ->when($ignore?->exists, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('file_name', $candidate)
            ->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    public static function makeFileNameSource(?string $category, ?string $title): string
    {
        return collect([$category, $title])
            ->map(fn (?string $part): string => trim((string) $part))
            ->filter()
            ->implode(' ');
    }

    public static function makeUniqueFileNameForCategoryTitle(?string $category, ?string $title, ?self $ignore = null): string
    {
        return self::makeUniqueFileName(self::makeFileNameSource($category, $title), $ignore);
    }

    public function publicUrl(): ?string
    {
        return $this->isLive() ? route('files.show', ['fileName' => $this->file_name]) : null;
    }

    public function downloadUrl(): string
    {
        return $this->publicUrl() ?? route('admin.files.download', ['fileDocument' => $this]);
    }

    public function cardImageUrl(): string
    {
        if (filled($this->card_image_path)) {
            return Storage::disk('public')->url($this->card_image_path);
        }

        return asset(self::DEFAULT_CARD_IMAGE_PATH);
    }

    public function isPublic(): bool
    {
        return $this->isLive()
            && $this->visibility === self::VISIBILITY_PUBLIC;
    }

    public function isLive(): bool
    {
        $now = now();

        return (bool) $this->is_published
            && filled($this->file_name)
            && $this->currentVersion !== null
            && ($this->publish_at === null || $this->publish_at->lte($now))
            && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FileDocumentVersion::class)->latest();
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FileDocumentVersion::class, 'current_version_id');
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_page_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $document): void {
            $document->versions()
                ->get()
                ->each(fn (FileDocumentVersion $version): bool => Storage::disk($version->disk)->delete($version->path));
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'tags' => 'array',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
