<?php

namespace App\Models;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'bulletin_date',
    'pdf_path',
    'extraction_prompt',
    'extracted_html',
    'is_published',
])]
class Bulletin extends Model implements HasPublicUrl
{
    public function publicUrl(): ?string
    {
        if (! $this->bulletin_date) {
            return null;
        }

        return route('bulletins.show', ['date' => $this->bulletin_date->toDateString()]);
    }

    protected function casts(): array
    {
        return [
            'bulletin_date' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
