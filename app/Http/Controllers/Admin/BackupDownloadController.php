<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use App\Support\BackupProfiles;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(string $profile, string $disk, string $path): StreamedResponse
    {
        abort_unless(AdminAccess::canAccessTool(Auth::user(), AdminAccess::BACKUPS), Response::HTTP_FORBIDDEN);

        $backupPath = BackupProfiles::downloadPath($profile, $disk, $path);

        abort_unless($backupPath, Response::HTTP_NOT_FOUND);

        return Storage::disk($disk)->download($backupPath, basename($backupPath));
    }
}
