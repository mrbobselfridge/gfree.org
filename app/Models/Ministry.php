<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'slug',
    'short_summary',
    'description',
    'hero_image_path',
    'card_image_path',
    'category',
    'meeting_time',
    'location',
    'leader_name',
    'leader_email',
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
            'is_published' => 'boolean',
        ];
    }
}
