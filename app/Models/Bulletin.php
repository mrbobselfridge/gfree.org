<?php

namespace App\Models;

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
class Bulletin extends Model
{
    protected function casts(): array
    {
        return [
            'bulletin_date' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
