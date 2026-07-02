<?php

use App\Http\Controllers\Admin\BackupDownloadController;
use App\Http\Controllers\Admin\ChangePasswordController;
use App\Http\Controllers\Admin\MediaImageDownloadController;
use App\Http\Controllers\Admin\PageVisualSnapshotImageController;
use App\Http\Controllers\Admin\PageVisualSnapshotPreviewController;
use App\Http\Controllers\Admin\SlideDeckImageController;
use App\Http\Controllers\Admin\SlideDeckImagesZipController;
use App\Http\Controllers\Admin\SlideDeckMetadataExportController;
use App\Http\Controllers\FileDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Models\SiteSetting;
use App\Support\PageSlugs;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/files/{fileName}', [FileDocumentController::class, 'show'])
    ->where('fileName', '[A-Za-z0-9\\-]+')
    ->name('files.show');

Route::middleware('auth')->group(function () {
    Route::get('/admin/change-password', ChangePasswordController::class)
        ->name('admin.change-password');

    Route::get('/admin/backups/{profile}/{disk}/{path}/download', BackupDownloadController::class)
        ->where('path', '[A-Za-z0-9_-]+')
        ->name('admin.backups.download');

    Route::get('/admin/files/{fileDocument}/download', [FileDocumentController::class, 'download'])
        ->name('admin.files.download');

    Route::get('/admin/files/versions/{fileDocumentVersion}/download', [FileDocumentController::class, 'downloadVersion'])
        ->name('admin.files.versions.download');

    Route::get('/admin/media-images/download', MediaImageDownloadController::class)
        ->name('admin.media-images.download');

    Route::get('/admin/slide-deck-slides/{slideDeckSlide}/image', SlideDeckImageController::class)
        ->name('admin.slide-decks.image');

    Route::get('/admin/slide-decks/{slideDeck}/images.zip', SlideDeckImagesZipController::class)
        ->name('admin.slide-decks.download-images');

    Route::get('/admin/slide-decks/{slideDeck}/metadata.{format}', SlideDeckMetadataExportController::class)
        ->where('format', 'csv|json')
        ->name('admin.slide-decks.export');
});

Route::get('/admin/page-visual-snapshots/preview/{type}/{record?}', PageVisualSnapshotPreviewController::class)
    ->middleware('signed')
    ->name('admin.page-visual-snapshots.preview');

Route::get('/admin/page-visual-snapshots/image', PageVisualSnapshotImageController::class)
    ->middleware('signed')
    ->name('admin.page-visual-snapshots.image');

Route::get('/manual', function () {
    return view('manual', [
        'settings' => SiteSetting::query()->first(),
        'updatedAt' => 'July 2, 2026',
    ]);
})->name('manual');

Route::get('/{slug}', PageController::class)
    ->where('slug', PageSlugs::routePattern())
    ->name('pages.show');
