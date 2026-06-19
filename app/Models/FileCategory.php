<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'sort_order',
    'default_parent_page_id',
    'default_card_image_path',
    'extraction_instructions',
])]
class FileCategory extends Model
{
    public const DEFAULT_NAME = 'Other';

    public static function options(?string $current = null): array
    {
        $options = static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        if (filled($current) && ! array_key_exists($current, $options)) {
            $options[$current] = $current;
        }

        if ($options === []) {
            $options[self::DEFAULT_NAME] = self::DEFAULT_NAME;
        }

        return $options;
    }

    public static function defaultParentPageIdFor(?string $category): ?int
    {
        if (blank($category)) {
            return null;
        }

        $pageId = static::query()
            ->where('name', $category)
            ->value('default_parent_page_id');

        return filled($pageId) ? (int) $pageId : null;
    }

    public function defaultParentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'default_parent_page_id');
    }
}
