<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'slug',
    'short_summary',
    'description',
    'content_blocks',
    'hero_image_path',
    'card_image_path',
    'category',
    'meeting_time',
    'location',
    'leader_name',
    'leader_email',
    'leader_phone',
    'one_church_url',
    'embed_code',
    'sort_order',
    'is_published',
])]
class Ministry extends Model
{
    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
