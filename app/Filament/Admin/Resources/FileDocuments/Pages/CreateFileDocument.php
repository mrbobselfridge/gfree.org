<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateFileDocument extends CreateRecord
{
    use UsesStandardCreateActions {
        afterCreate as standardAfterCreate;
    }

    protected static string $resource = FileDocumentResource::class;

    private ?string $pendingUpload = null;

    private ?string $pendingOriginalName = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingUpload = FileLibrary::normalizeUploadPath($data['pending_upload'] ?? null);
        $this->pendingOriginalName = FileLibrary::normalizeOriginalName($data['pending_original_name'] ?? null);

        unset(
            $data['pending_upload'],
            $data['pending_original_name'],
            $data['replacement_upload'],
            $data['replacement_original_name'],
        );

        $data['file_name'] = FileDocument::makeUniqueFileName($data['file_name'] ?? $data['title'] ?? null);
        $data['category'] = $data['category'] ?? FileCategory::DEFAULT_NAME;
        $data['uploaded_by_id'] = Filament::auth()->id();
        $data['updated_by_id'] = Filament::auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingUpload) {
            FileLibrary::createVersion(
                $this->getRecord(),
                $this->pendingUpload,
                $this->pendingOriginalName,
                Filament::auth()->user(),
            );
        }

        $this->standardAfterCreate();
    }
}
