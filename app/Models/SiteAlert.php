<?php

namespace App\Models;

use App\Rules\HttpOrRelativeUrl;
use App\Support\PublicPageUrls;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'label',
    'message',
    'link_label',
    'link_url',
    'tone',
    'sort_order',
    'publish_at',
    'expires_at',
    'is_published',
    'is_dismissible',
])]
class SiteAlert extends Model
{
    public const TONE_CRITICAL = 'critical';

    public const TONE_IMPORTANT = 'important';

    public const TONE_INFO = 'info';

    public const TONE_SUCCESS = 'success';

    public const TONE_NEUTRAL = 'neutral';

    protected static function booted(): void
    {
        static::saving(function (SiteAlert $alert): void {
            $alert->label = self::nullableTrim($alert->label);
            $alert->message = trim((string) $alert->message);
            $alert->link_label = self::nullableTrim($alert->link_label);
            $alert->link_url = self::nullableTrim($alert->link_url);
            $alert->tone = array_key_exists((string) $alert->tone, self::toneOptions())
                ? (string) $alert->tone
                : self::TONE_CRITICAL;
        });
    }

    public static function toneOptions(): array
    {
        return [
            self::TONE_CRITICAL => 'Critical red - urgent closures, safety, deadlines',
            self::TONE_IMPORTANT => 'Important gold - high-priority reminders or events',
            self::TONE_INFO => 'Information blue - general announcements',
            self::TONE_SUCCESS => 'Success green - good news or completed updates',
            self::TONE_NEUTRAL => 'Neutral light - simple site notices',
        ];
    }

    public static function toneLabels(): array
    {
        return [
            self::TONE_CRITICAL => 'Critical red',
            self::TONE_IMPORTANT => 'Important gold',
            self::TONE_INFO => 'Information blue',
            self::TONE_SUCCESS => 'Success green',
            self::TONE_NEUTRAL => 'Neutral light',
        ];
    }

    public function toneClass(): string
    {
        return 'site-alert--'.$this->tone;
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }

    public function scopePublicOrder(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function publicLinkUrl(): ?string
    {
        return PublicPageUrls::normalize($this->link_url);
    }

    public function dismissalKey(): string
    {
        return 'site-alert-'.$this->getKey().'-'.($this->updated_at?->timestamp ?? 'new');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'link_url' => [new HttpOrRelativeUrl],
        ];
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_published' => 'boolean',
            'is_dismissible' => 'boolean',
        ];
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
