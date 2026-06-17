<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'page_id',
    'url',
    'png_path',
    'svg_path',
    'generated_at',
])]
class PageQrCode extends Model
{
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function pngUrl(): string
    {
        return Storage::disk('public')->url($this->png_path);
    }

    public function svgUrl(): string
    {
        return Storage::disk('public')->url($this->svg_path);
    }

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }
}
