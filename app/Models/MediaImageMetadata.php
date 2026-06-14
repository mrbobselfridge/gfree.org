<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'path',
    'title',
    'slug',
    'tags',
])]
class MediaImageMetadata extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
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
}
