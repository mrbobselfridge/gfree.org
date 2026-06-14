<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'path',
    'title',
    'slug',
    'tags',
    'created_by_user_id',
])]
class MediaImageMetadata extends Model
{
    /**
     * @var array<string, array<int, string>>
     */
    private const AUTO_TAG_RULES = [
        'banner' => [
            'banner',
            'webbanner',
            'web banner',
            'website banner',
            'homebanner',
            'banner image',
        ],
        'graphic' => [
            'graphic',
            'graphics',
            'webgraphic',
            'graphic design',
            'background',
        ],
        'picture' => [
            'photo',
            'pic',
            'image',
            'images',
            'pxl',
            'unsplash',
            'pexels',
            'facebook',
        ],
        'logo' => [
            'logo',
            'icon',
            'square',
        ],
        'person' => [
            'person',
            'people',
            'man',
            'men',
            'mom',
            'mother',
            'father',
            'family',
            'pastor',
            'volunteer',
            'student',
            'youth',
            'child',
            'children',
            'kids',
            'hands',
        ],
        'kids & children' => [
            'kid',
            'kids',
            'child',
            'children',
            'childrens',
            'sunday school',
            'vbs',
        ],
        'youth' => [
            'youth',
            'student',
            'unchained',
        ],
        'worship' => [
            'worship',
            'music',
            'spirit and truth',
            'night of worship',
        ],
        'holiday and seasonal' => [
            'easter',
            'christmas',
            'advent',
            'thanksgiving',
            'new year',
            'new years',
            'lent',
            'good friday',
            'fathers day',
            'mothers day',
            'veterans day',
            'labor day',
            'graduation',
            'fall',
            'summer',
            'spring',
        ],
        'giving and offering' => [
            'giving',
            'give',
            'offering',
            'tithe',
            'money',
        ],
        'prayer' => [
            'prayer',
            'praying',
            'fasting',
        ],
        'event or service' => [
            'event',
            'service',
            'class',
            'retreat',
            'picnic',
            'camp',
            'vbs',
            'baptism',
            'dedication',
            'graduation',
            'membership',
        ],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function setTagsAttribute(mixed $value): void
    {
        $this->attributes['tags'] = json_encode(self::normalizeTags($value));
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    public static function normalizeTags(mixed $value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->flatten()
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->map(fn (string $tag): string => Str::of($tag)
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->toString())
            ->unique(fn (string $tag): string => Str::of($tag)->lower()->toString())
            ->values()
            ->all();
    }

    public static function normalizeSlug(?string $value): ?string
    {
        $segments = collect(explode('/', (string) $value))
            ->map(fn (string $segment): string => Str::slug($segment))
            ->filter()
            ->values()
            ->all();

        return $segments === [] ? null : implode('/', $segments);
    }

    /**
     * @return array<int, string>
     */
    public static function autoTagsForTitle(?string $title): array
    {
        $normalizedTitle = self::normalizeAutoTagText($title);

        if ($normalizedTitle === '') {
            return [];
        }

        return collect(self::AUTO_TAG_RULES)
            ->filter(fn (array $keywords): bool => collect($keywords)
                ->contains(fn (string $keyword): bool => self::containsAutoTagKeyword($normalizedTitle, $keyword)))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $tags
     * @return array<int, string>
     */
    public static function mergeAutoTags(mixed $tags, ?string $title): array
    {
        return self::normalizeTags([
            ...self::normalizeTags($tags),
            ...self::autoTagsForTitle($title),
        ]);
    }

    private static function containsAutoTagKeyword(string $normalizedTitle, string $keyword): bool
    {
        $keyword = self::normalizeAutoTagText($keyword);

        if ($keyword === '') {
            return false;
        }

        return str_contains(" {$normalizedTitle} ", " {$keyword} ");
    }

    private static function normalizeAutoTagText(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}
