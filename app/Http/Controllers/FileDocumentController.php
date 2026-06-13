<?php

namespace App\Http\Controllers;

use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDocumentController extends Controller
{
    public function show(string $fileName): StreamedResponse|RedirectResponse
    {
        $document = FileDocument::query()
            ->where('file_name', $fileName)
            ->with('currentVersion')
            ->firstOrFail();

        abort_unless($document->isLive(), Response::HTTP_NOT_FOUND);

        if (! $document->isPublic()) {
            return Auth::check()
                ? $this->streamVersion($document->currentVersion)
                : redirect()->guest(route('filament.admin.auth.login'));
        }

        return $this->streamVersion($document->currentVersion);
    }

    public function download(FileDocument $fileDocument): StreamedResponse
    {
        Gate::authorize('view', $fileDocument);

        $fileDocument->loadMissing('currentVersion');

        abort_unless($fileDocument->currentVersion, Response::HTTP_NOT_FOUND);

        return $this->streamVersion($fileDocument->currentVersion);
    }

    public function downloadVersion(FileDocumentVersion $fileDocumentVersion): StreamedResponse
    {
        $fileDocumentVersion->loadMissing('document');

        Gate::authorize('view', $fileDocumentVersion->document);

        return $this->streamVersion($fileDocumentVersion);
    }

    private function streamVersion(FileDocumentVersion $version): StreamedResponse
    {
        abort_unless($version->existsOnDisk(), Response::HTTP_NOT_FOUND);

        return Storage::disk($version->disk)->download(
            $version->path,
            $version->original_name,
            array_filter(['Content-Type' => $version->mime_type]),
        );
    }
}
