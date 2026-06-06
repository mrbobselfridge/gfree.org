<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

#[Fillable([
    'file_document_id',
    'disk',
    'path',
    'original_name',
    'extension',
    'mime_type',
    'size',
    'uploaded_by_id',
])]
class FileDocumentVersion extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $version): void {
            Storage::disk($version->disk)->delete($version->path);
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(FileDocument::class, 'file_document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function sizeForHumans(): string
    {
        return Number::fileSize($this->size);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
