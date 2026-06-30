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
    'sort_order',
    'publish_at',
    'expires_at',
    'is_published',
    'is_dismissible',
])]
class SiteAlert extends Model
{
    protected static function booted(): void
    {
        static::saving(function (SiteAlert $alert): void {
            $alert->label = self::nullableTrim($alert->label);
            $alert->message = trim((string) $alert->message);
            $alert->link_label = self::nullableTrim($alert->link_label);
            $alert->link_url = self::nullableTrim($alert->link_url);
        });
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
