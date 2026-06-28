<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SlideDeckImageController extends Controller
{
    public function __invoke(Request $request, SlideDeckSlide $slideDeckSlide): BinaryFileResponse
    {
        abort_unless(AdminAccess::canAccessTool(Auth::user(), AdminAccess::SLIDE_DECK_IMPORT), Response::HTTP_FORBIDDEN);

        $path = $request->boolean('thumbnail') && filled($slideDeckSlide->thumbnail_path)
            ? $slideDeckSlide->thumbnail_path
            : $slideDeckSlide->image_path;
        $disk = Storage::disk(SlideDeck::DISK);

        abort_unless(filled($path) && $disk->exists($path), Response::HTTP_NOT_FOUND);

        return response()->file($disk->path($path), [
            'Content-Type' => 'image/png',
        ]);
    }
}
