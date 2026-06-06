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
    'visibility',
    'description',
    'content',
    'tags',
    'expires_at',
    'current_version_id',
    'uploaded_by_id',
    'updated_by_id',
])]
class FileDocument extends Model implements HasPublicUrl
{
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public static function categories(): array
    {
        return [
            'Form' => 'Form',
            'Poster' => 'Poster',
            'Policy' => 'Policy',
            'Ministry Resource' => 'Ministry Resource',
            'Event Handout' => 'Event Handout',
            'Spreadsheet' => 'Spreadsheet',
            'Other' => 'Other',
        ];
    }

    public static function categoryOptions(): array
    {
        return collect(static::categories())
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

    public function publicUrl(): ?string
    {
        return $this->isPublic() ? route('files.show', ['fileName' => $this->file_name]) : null;
    }

    public function downloadUrl(): string
    {
        return $this->publicUrl() ?? route('admin.files.download', ['fileDocument' => $this]);
    }

    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC
            && filled($this->file_name)
            && $this->currentVersion !== null
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
            'tags' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
