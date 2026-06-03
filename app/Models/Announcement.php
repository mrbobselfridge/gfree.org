<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'slug',
    'summary',
    'body',
    'content_blocks',
    'image_path',
    'background',
    'cta_label',
    'cta_url',
    'publish_at',
    'expires_at',
    'featured_at',
    'feature_expires_at',
    'is_featured',
    'is_published',
])]
class Announcement extends Model
{
    public function scopePublicListingOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('feature_expires_at IS NULL')
            ->orderBy('feature_expires_at')
            ->orderByRaw('featured_at IS NULL')
            ->orderByDesc('featured_at')
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderByRaw('publish_at IS NULL')
            ->orderByDesc('publish_at')
            ->orderByDesc('is_featured')
            ->orderBy('title');
    }

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'featured_at' => 'datetime',
            'feature_expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }
}
