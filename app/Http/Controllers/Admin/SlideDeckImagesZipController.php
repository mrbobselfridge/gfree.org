<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideDeck;
use App\Support\AdminAccess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SlideDeckImagesZipController extends Controller
{
    public function __invoke(SlideDeck $slideDeck): BinaryFileResponse
    {
        abort_unless(AdminAccess::canAccessTool(Auth::user(), AdminAccess::SLIDE_DECK_IMPORT), Response::HTTP_FORBIDDEN);

        $slides = $slideDeck->slides()->get();

        abort_if($slides->isEmpty(), Response::HTTP_NOT_FOUND);

        $zipPath = storage_path('app/tmp/slide-deck-'.$slideDeck->getKey().'-'.Str::ulid().'.zip');

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, Response::HTTP_INTERNAL_SERVER_ERROR);

        $disk = Storage::disk(SlideDeck::DISK);

        foreach ($slides as $slide) {
            if (blank($slide->image_path) || ! $disk->exists($slide->image_path)) {
                continue;
            }

            $zip->addFile(
                $disk->path($slide->image_path),
                'slide-'.str_pad((string) $slide->slide_number, 3, '0', STR_PAD_LEFT).'.png',
            );
        }

        $zip->close();

        return response()
            ->download($zipPath, Str::slug($slideDeck->name ?: 'slide-deck').'-slides.zip')
            ->deleteFileAfterSend();
    }
}
