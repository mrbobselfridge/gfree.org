<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaImageDownloadController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless(
            AdminAccess::canAccessToolOrAssignedRecords(Auth::user(), AdminAccess::MEDIA_LIBRARY),
            Response::HTTP_FORBIDDEN,
        );

        $path = (string) $request->query('path');

        abort_unless($this->isAllowedPath($path), Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), Response::HTTP_NOT_FOUND);

        return $disk->download($path, basename($path));
    }

    private function isAllowedPath(string $path): bool
    {
        return filled($path)
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && Str::of($path)->lower()->endsWith(['.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif', '.svg']);
    }
}
