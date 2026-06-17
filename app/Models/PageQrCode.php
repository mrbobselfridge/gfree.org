<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function pngDownloadName(): string
    {
        return $this->downloadName('png');
    }

    public function svgDownloadName(): string
    {
        return $this->downloadName('svg');
    }

    private function downloadName(string $extension): string
    {
        return $this->downloadBaseName().'.'.$extension;
    }

    private function downloadBaseName(): string
    {
        $siteName = SiteSetting::query()->value('church_name') ?: config('app.name', 'TwyxtCo Church');
        $path = $this->page?->slug ?: parse_url($this->url, PHP_URL_PATH) ?: 'page';

        return self::filenameSegment((string) $siteName, 'Site').'-'.self::filenameSegment((string) $path, 'page');
    }

    private static function filenameSegment(string $value, string $fallback): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[\/\s]+/', '-', $value) ?? '';
        $value = preg_replace('/[^A-Za-z0-9-]+/', '', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : $fallback;
    }

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }
}
