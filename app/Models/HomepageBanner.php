<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'eyebrow',
    'subtitle',
    'image_path',
    'button_label',
    'button_url',
    'secondary_button_label',
    'secondary_button_url',
    'starts_at',
    'ends_at',
    'is_published',
    'notes',
])]
class HomepageBanner extends Model implements HasPublicUrl
{
    public function publicUrl(): ?string
    {
        return route('home');
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
