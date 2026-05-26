<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'parent_id',
    'label',
    'url',
    'location',
    'sort_order',
    'publish_at',
    'expires_at',
    'opens_in_new_tab',
    'is_published',
])]
class NavigationLink extends Model
{
    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'opens_in_new_tab' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('publish_at')->orWhere('publish_at', '<=', $now))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
