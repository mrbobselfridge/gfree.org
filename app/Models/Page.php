<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'slug',
    'intro',
    'body',
    'hero_image_path',
    'seo_title',
    'seo_description',
    'sort_order',
    'is_published',
])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
