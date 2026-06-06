<?php

namespace App\Support;

use App\Models\FileDocument;
use App\Models\FileDocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileLibrary
{
    public const DISK = 'local';

    public const DIRECTORY = 'file-library/documents';

    public static function allowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/csv',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-publisher',
            'application/x-mspublisher',
        ];
    }

    public static function createVersion(FileDocument $document, string $path, ?string $originalName = null, ?User $user = null): FileDocumentVersion
    {
        $disk = Storage::disk(self::DISK);
        $extension = Str::of(pathinfo($originalName, PATHINFO_EXTENSION) ?: pathinfo($path, PATHINFO_EXTENSION))
            ->lower()
            ->toString();
        $originalName = self::downloadName($document, $path, $originalName, $extension ?: null);

        $version = $document->versions()->create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $originalName,
            'extension' => $extension ?: null,
            'mime_type' => $disk->exists($path) ? $disk->mimeType($path) : null,
            'size' => $disk->exists($path) ? $disk->size($path) : 0,
            'uploaded_by_id' => $user?->getKey(),
        ]);

        self::makeCurrent($document, $version, $user);

        return $version;
    }

    public static function makeCurrent(FileDocument $document, FileDocumentVersion $version, ?User $user = null): void
    {
        if ($version->file_document_id !== $document->getKey()) {
            return;
        }

        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'updated_by_id' => $user?->getKey() ?? $document->updated_by_id,
        ])->save();
    }

    public static function normalizeUploadPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->first();
        }

        $value = trim((string) $value);

        return filled($value) ? $value : null;
    }

    public static function normalizeOriginalName(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->first();
        }

        $value = trim((string) $value);

        return filled($value) ? $value : null;
    }

    private static function downloadName(FileDocument $document, string $path, ?string $originalName, ?string $extension): string
    {
        $originalName = filled($originalName) ? (string) $originalName : basename($path);

        if ($originalName !== basename($path)) {
            return $originalName;
        }

        return collect([$document->file_name, $extension])
            ->filter()
            ->implode('.');
    }
}
