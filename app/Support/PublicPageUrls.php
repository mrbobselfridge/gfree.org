<?php

namespace App\Support;

use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicPageUrls
{
    public static function forRecord(?Model $record): ?string
    {
        if (! $record instanceof HasPublicUrl) {
            return null;
        }

        return $record->publicUrl();
    }

    public static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);

        if (blank($url) || $url === '#') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '/')) {
            return url($url);
        }

        return null;
    }
}
