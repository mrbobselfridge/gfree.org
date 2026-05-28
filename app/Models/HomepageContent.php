<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'intro_eyebrow',
    'intro_title',
    'intro_body',
    'process_eyebrow',
    'process_title',
    'process_steps',
    'feature_eyebrow',
    'feature_title',
    'feature_body',
    'feature_label',
    'feature_url',
    'content_blocks',
])]
class HomepageContent extends Model
{
    protected function casts(): array
    {
        return [
            'process_steps' => 'array',
            'content_blocks' => 'array',
        ];
    }
}
