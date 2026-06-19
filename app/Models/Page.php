<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use App\Support\PageQrCodeService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'parent_page_id',
    'title',
    'slug',
    'intro',
    'message',
    'hero_label',
    'body',
    'content_blocks',
    'hero_image_path',
    'card_image_path',
    'seo_title',
    'noindex_nofollow',
    'seo_description',
    'sort_order',
    'publish_at',
    'expires_at',
    'featured_at',
    'feature_expires_at',
    'is_published',
    'is_redirect',
    'redirect_url',
    'redirect_status_code',
    'show_site_chrome',
    'show_page_header',
])]
class Page extends Model implements HasPublicUrl
{
    public const REDIRECT_TEMPORARY = 302;

    public const REDIRECT_PERMANENT = 301;

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if (self::wouldCreateParentLoop(
                parentPageId: $page->parent_page_id,
                pageId: $page->exists ? $page->getKey() : null,
            )) {
                throw ValidationException::withMessages([
                    'parent_page_id' => 'The parent page must be another page and cannot be one of this page\'s subpages.',
                ]);
            }

            $page->guardAgainstInvalidRedirect();
        });

        static::saved(fn (Page $page): ?PageQrCode => app(PageQrCodeService::class)->regenerate($page));

        static::deleting(fn (Page $page): mixed => app(PageQrCodeService::class)->delete($page));
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_page_id');
    }

    public function childPages(): HasMany
    {
        return $this->hasMany(self::class, 'parent_page_id');
    }

    public function fileDocuments(): HasMany
    {
        return $this->hasMany(FileDocument::class, 'parent_page_id');
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(PageQrCode::class);
    }

    public function publicUrl(): ?string
    {
        if (blank($this->slug)) {
            return null;
        }

        return url('/'.ltrim((string) $this->slug, '/'));
    }

    public function isRedirect(): bool
    {
        return (bool) $this->is_redirect;
    }

    public function redirectStatusLabel(): string
    {
        return match ((int) $this->redirect_status_code) {
            self::REDIRECT_PERMANENT => 'Permanent',
            default => 'Temporary',
        };
    }

    public static function redirectStatusOptions(): array
    {
        return [
            self::REDIRECT_TEMPORARY => 'Temporary',
            self::REDIRECT_PERMANENT => 'Permanent',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }

    public function isActive(): bool
    {
        $now = now();

        return (bool) $this->is_published
            && ($this->publish_at === null || $this->publish_at->lte($now))
            && ($this->expires_at === null || $this->expires_at->gte($now));
    }

    public static function wouldCreateParentLoop(mixed $parentPageId, mixed $pageId): bool
    {
        if (blank($parentPageId)) {
            return false;
        }

        $parentPageId = (int) $parentPageId;

        if (blank($pageId)) {
            return false;
        }

        $pageId = (int) $pageId;

        if ($parentPageId === $pageId) {
            return true;
        }

        $seenPageIds = [];
        $candidate = self::query()->select(['id', 'parent_page_id'])->find($parentPageId);

        while ($candidate !== null) {
            $candidateId = (int) $candidate->getKey();

            if ($candidateId === $pageId) {
                return true;
            }

            if (in_array($candidateId, $seenPageIds, true)) {
                return true;
            }

            $seenPageIds[] = $candidateId;

            if (blank($candidate->parent_page_id)) {
                return false;
            }

            $candidate = self::query()->select(['id', 'parent_page_id'])->find($candidate->parent_page_id);
        }

        return false;
    }

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'sort_order' => 'integer',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'featured_at' => 'datetime',
            'feature_expires_at' => 'datetime',
            'is_published' => 'boolean',
            'is_redirect' => 'boolean',
            'noindex_nofollow' => 'boolean',
            'redirect_status_code' => 'integer',
            'show_site_chrome' => 'boolean',
            'show_page_header' => 'boolean',
        ];
    }

    private function guardAgainstInvalidRedirect(): void
    {
        if (! $this->isRedirect()) {
            return;
        }

        if (blank($this->redirect_url)) {
            throw ValidationException::withMessages([
                'redirect_url' => 'Redirect pages need a destination URL or local path.',
            ]);
        }

        if (! $this->hasValidRedirectUrl()) {
            throw ValidationException::withMessages([
                'redirect_url' => 'The redirect destination must be an http:// or https:// URL, or a local path like /new-here.',
            ]);
        }

        if (blank($this->redirect_status_code)) {
            $this->redirect_status_code = self::REDIRECT_TEMPORARY;
        }

        if (! array_key_exists((int) $this->redirect_status_code, self::redirectStatusOptions())) {
            throw ValidationException::withMessages([
                'redirect_status_code' => 'Choose Temporary or Permanent for the redirect type.',
            ]);
        }

        $localPath = $this->localRedirectPath();

        if ($localPath === null) {
            return;
        }

        if ($localPath === trim((string) $this->slug, '/')) {
            throw ValidationException::withMessages([
                'redirect_url' => 'A page cannot redirect to itself.',
            ]);
        }

        $redirectPageExists = self::query()
            ->where('slug', $localPath)
            ->when($this->exists, fn ($query): mixed => $query->whereKeyNot($this->getKey()))
            ->where('is_redirect', true)
            ->exists();

        if ($redirectPageExists) {
            throw ValidationException::withMessages([
                'redirect_url' => 'Choose the final destination instead of redirecting to another redirect page.',
            ]);
        }
    }

    private function localRedirectPath(): ?string
    {
        $url = trim((string) $this->redirect_url);

        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
            return trim((string) parse_url($url, PHP_URL_PATH), '/');
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        $redirectHost = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($redirectHost && $appHost && strcasecmp($redirectHost, $appHost) === 0) {
            return trim((string) parse_url($url, PHP_URL_PATH), '/');
        }

        return null;
    }

    private function hasValidRedirectUrl(): bool
    {
        $url = trim((string) $this->redirect_url);

        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//') && ! preg_match('/\s/', $url)) {
            return true;
        }

        return Str::startsWith($url, ['http://', 'https://'])
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
