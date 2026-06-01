<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'slug',
    'role',
    'bio',
    'content_blocks',
    'photo_path',
    'email',
    'sort_order',
    'is_published',
])]
class StaffMember extends Model
{
    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
