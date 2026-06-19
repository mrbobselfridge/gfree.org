<?php

namespace App\Support;

use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RichTextFileLibrary
{
    public static function publicDocumentQuery(): Builder
    {
        return FileDocument::query()
            ->with('currentVersion')
            ->where('is_published', true)
            ->where('visibility', FileDocument::VISIBILITY_PUBLIC)
            ->whereNotNull('current_version_id')
            ->where(fn (Builder $query) => $query
                ->whereNull('publish_at')
                ->orWhere('publish_at', '<=', now()))
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()));
    }

    public static function publicDocumentOptions(?string $search = null, int $limit = 50): array
    {
        return self::publicDocumentQuery()
            ->when(filled($search), fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn (FileDocument $document): array => [
                $document->getKey() => self::optionLabel($document),
            ])
            ->all();
    }

    public static function publicDocumentOptionLabel(?FileDocument $document): ?string
    {
        if (! $document?->exists) {
            return null;
        }

        return self::optionLabel($document);
    }

    public static function resolveDocument(array $data, ?User $user = null): ?FileDocument
    {
        $uploadPath = FileLibrary::normalizeUploadPath($data['upload'] ?? null);

        if ($uploadPath) {
            return self::createPublicDocument($data, $uploadPath, $user);
        }

        $documentId = $data['document_id'] ?? null;

        if (blank($documentId)) {
            return null;
        }

        return self::publicDocumentQuery()
            ->whereKey($documentId)
            ->first();
    }

    public static function linkLabel(FileDocument $document, ?string $label = null): string
    {
        return trim((string) $label) ?: $document->title;
    }

    private static function createPublicDocument(array $data, string $uploadPath, ?User $user): FileDocument
    {
        $originalName = FileLibrary::normalizeOriginalName($data['upload_original_name'] ?? null);
        $sourceName = $originalName ?: basename($uploadPath);
        $title = trim((string) ($data['new_title'] ?? '')) ?: Str::headline(pathinfo($sourceName, PATHINFO_FILENAME));

        if (blank($title)) {
            $title = 'Untitled File';
        }

        $category = trim((string) ($data['new_category'] ?? '')) ?: FileCategory::DEFAULT_NAME;
        $parentPageId = array_key_exists('new_parent_page_id', $data)
            ? (filled($data['new_parent_page_id']) ? (int) $data['new_parent_page_id'] : null)
            : FileCategory::defaultParentPageIdFor($category);

        $document = FileDocument::query()->create([
            'title' => $title,
            'file_name' => FileDocument::makeUniqueFileNameForCategoryTitle($category, $title),
            'category' => $category,
            'parent_page_id' => $parentPageId,
            'is_published' => true,
            'visibility' => FileDocument::VISIBILITY_PUBLIC,
            'uploaded_by_id' => $user?->getKey(),
            'updated_by_id' => $user?->getKey(),
        ]);

        FileLibrary::createVersion($document, $uploadPath, $originalName, $user);

        return $document->refresh();
    }

    private static function optionLabel(FileDocument $document): string
    {
        return collect([
            $document->title,
            $document->category,
            $document->currentVersion?->original_name,
        ])
            ->filter()
            ->implode(' - ');
    }
}
