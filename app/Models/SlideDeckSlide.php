<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'slide_deck_id',
    'slide_number',
    'image_path',
    'thumbnail_path',
    'public_image_path',
    'slide_type',
    'suggested_name',
    'extracted_text',
    'summary',
    'event_title',
    'event_date',
    'event_time',
    'event_location',
    'event_audience',
    'contact_person',
    'announcement_details',
    'confidence_score',
    'raw_analysis_json',
])]
class SlideDeckSlide extends Model
{
    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_GENERAL = 'general';

    public const TYPE_UNKNOWN = 'unknown';

    public static function types(): array
    {
        return [
            self::TYPE_ANNOUNCEMENT => 'Announcement',
            self::TYPE_GENERAL => 'General',
            self::TYPE_UNKNOWN => 'Unknown',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(SlideDeck::class, 'slide_deck_id');
    }

    public function imageUrl(): ?string
    {
        return $this->temporaryUrl($this->image_path);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->temporaryUrl($this->thumbnail_path ?: $this->image_path);
    }

    private function temporaryUrl(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk(SlideDeck::DISK)->exists($path)) {
            return null;
        }

        return route('admin.slide-decks.image', ['slideDeckSlide' => $this]);
    }

    protected function casts(): array
    {
        return [
            'raw_analysis_json' => 'array',
            'confidence_score' => 'decimal:4',
        ];
    }
}
