<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'slug',
    'summary',
    'body',
    'image_path',
    'background',
    'cta_label',
    'cta_url',
    'publish_at',
    'expires_at',
    'is_featured',
    'is_published',
])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }
}
