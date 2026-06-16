<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'sort_order',
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
}
