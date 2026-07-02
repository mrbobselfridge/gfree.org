<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'original_filename',
    'stored_file_path',
    'file_document_id',
    'status',
    'total_slides',
    'processed_slides',
    'error_message',
    'created_by_user_id',
    'notes',
])]
class SlideDeck extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const DISK = 'local';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(SlideDeckSlide::class)->orderBy('slide_number');
    }

    public function fileDocument(): BelongsTo
    {
        return $this->belongsTo(FileDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function directory(string $child = ''): string
    {
        return trim('slide-decks/'.$this->getKey().'/'.$child, '/');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $deck): void {
            if ($deck->getKey()) {
                Storage::disk(self::DISK)->deleteDirectory($deck->directory());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total_slides' => 'integer',
            'processed_slides' => 'integer',
        ];
    }
}
