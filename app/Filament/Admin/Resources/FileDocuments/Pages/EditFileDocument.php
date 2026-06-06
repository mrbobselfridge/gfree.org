<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditFileDocument extends EditRecord
{
    use UsesStandardEditActions {
        afterSave as standardAfterSave;
        getHeaderViewPublicPageActions as standardHeaderViewPublicPageActions;
    }

    protected static string $resource = FileDocumentResource::class;

    private ?string $replacementUpload = null;

    private ?string $replacementOriginalName = null;

    protected function getHeaderViewPublicPageActions(): array
    {
        return [
            IconOnlyAction::make(
                Action::make('downloadCurrentFile')
                    ->label($this->getRecord()->visibility === FileDocument::VISIBILITY_PUBLIC ? 'View' : 'Download')
                    ->url(fn (): string => $this->getRecord()->downloadUrl(), true)
                    ->color('gray'),
                Heroicon::OutlinedArrowDownTray,
            ),
            ...$this->standardHeaderViewPublicPageActions(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->replacementUpload = FileLibrary::normalizeUploadPath($data['replacement_upload'] ?? null);
        $this->replacementOriginalName = FileLibrary::normalizeOriginalName($data['replacement_original_name'] ?? null);

        unset(
            $data['pending_upload'],
            $data['pending_original_name'],
            $data['replacement_upload'],
            $data['replacement_original_name'],
        );

        $data['file_name'] = FileDocument::makeUniqueFileName(
            $data['file_name'] ?? $data['title'] ?? null,
            $this->getRecord(),
        );
        $data['updated_by_id'] = Filament::auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->replacementUpload) {
            FileLibrary::createVersion(
                $this->getRecord(),
                $this->replacementUpload,
                $this->replacementOriginalName,
                Filament::auth()->user(),
            );
        }

        $this->standardAfterSave();
    }
}
