<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'slug',
    'intro',
    'hero_label',
    'body',
    'content_blocks',
    'hero_image_path',
    'seo_title',
    'seo_description',
    'sort_order',
    'is_published',
    'show_site_chrome',
    'show_page_header',
])]
class Page extends Model implements HasPublicUrl
{
    public function publicUrl(): ?string
    {
        if (blank($this->slug)) {
            return null;
        }

        return url('/'.ltrim((string) $this->slug, '/'));
    }

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'is_published' => 'boolean',
            'show_site_chrome' => 'boolean',
            'show_page_header' => 'boolean',
        ];
    }
}
