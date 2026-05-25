<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'parent_id',
    'label',
    'url',
    'location',
    'sort_order',
    'opens_in_new_tab',
    'is_published',
])]
class NavigationLink extends Model
{
    protected function casts(): array
    {
        return [
            'opens_in_new_tab' => 'boolean',
            'is_published' => 'boolean',
        ];
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
