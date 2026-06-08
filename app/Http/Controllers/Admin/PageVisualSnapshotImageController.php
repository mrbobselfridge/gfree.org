<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PageVisualSnapshotImageController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $path = (string) $request->query('path');

        abort_unless($this->isAllowedPath($path), 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Content-Type' => 'image/png',
        ]);
    }

    private function isAllowedPath(string $path): bool
    {
        return Str::startsWith($path, 'page-visual-snapshots/')
            && Str::endsWith($path, '.png')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }
}
