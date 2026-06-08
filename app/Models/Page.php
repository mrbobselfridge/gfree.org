<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'parent_page_id',
    'title',
    'slug',
    'intro',
    'hero_label',
    'body',
    'content_blocks',
    'hero_image_path',
    'seo_title',
    'seo_description',
    'sort_order',
    'is_published',
    'show_site_chrome',
    'show_page_header',
])]
class Page extends Model implements HasPublicUrl
{
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
        });
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_page_id');
    }

    public function childPages(): HasMany
    {
        return $this->hasMany(self::class, 'parent_page_id');
    }

    public function publicUrl(): ?string
    {
        if (blank($this->slug)) {
            return null;
        }

        return url('/'.ltrim((string) $this->slug, '/'));
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
            'is_published' => 'boolean',
            'show_site_chrome' => 'boolean',
            'show_page_header' => 'boolean',
        ];
    }
}
