<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Models\FileDocument;
use App\Support\FileLibrary;
use App\Support\OpenAiFileDocumentExtractor;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class EditFileDocument extends EditRecord
{
    use UsesStandardEditActions {
        afterSave as standardAfterSave;
        getHeaderViewPublicPageActions as standardHeaderViewPublicPageActions;
    }

    protected static string $resource = FileDocumentResource::class;

    private ?string $replacementUpload = null;

    private ?string $replacementOriginalName = null;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            ...$this->getHeaderViewPublicPageActions(),
            ...$this->getHeaderAiPageReviewActions(),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->getRecord()),
            $this->getExtractFileContentAction(),
            $this->getHeaderDeleteAction(),
            $this->getHeaderSaveAndCloseAction(),
            $this->getHeaderSaveAction(),
        ];
    }

    protected function getHeaderViewPublicPageActions(): array
    {
        return [
            IconOnlyAction::make(
                Action::make('downloadCurrentFile')
                    ->label('Download')
                    ->url(fn (): string => route('admin.files.download', ['fileDocument' => $this->getRecord()]), true)
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

        $data['file_name'] = filled($data['file_name'] ?? null)
            ? FileDocument::makeUniqueFileName($data['file_name'], $this->getRecord())
            : FileDocument::makeUniqueFileNameForCategoryTitle(
                $data['category'] ?? null,
                $data['title'] ?? null,
                $this->getRecord(),
            );
        $data['updated_by_id'] = Filament::auth()->id();

        return $data;
    }

    protected function getExtractFileContentAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('extractFileContent')
                ->label('Extract File Content')
                ->color('warning')
                ->modalHeading('Extract file content')
                ->modalDescription('Review the exact prompt before sending this file to OpenAI. Continuing will start the extraction request and may take a moment.')
                ->modalSubmitActionLabel('Continue')
                ->modalWidth(Width::Screen)
                ->extraModalWindowAttributes(['class' => 'twyxtco-file-extraction-modal'], merge: true)
                ->closeModalByClickingAway(false)
                ->disabled(fn (): bool => $this->getRecord()->currentVersion === null)
                ->fillForm(fn (): array => $this->fileExtractionPromptPreview())
                ->schema([
                    Textarea::make('extraction_prompt')
                        ->label('Prompt to be used')
                        ->helperText('This is the exact prompt that will be sent with the saved file if you continue.')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(8)
                        ->extraFieldWrapperAttributes(['class' => 'twyxtco-file-extraction-prompt-field'])
                        ->columnSpanFull(),
                ])
                ->action(fn (): mixed => $this->replaceMountedAction('reviewExtractedFileContent')),
            Heroicon::OutlinedSparkles,
        );
    }

    protected function reviewExtractedFileContentAction(): Action
    {
        return Action::make('reviewExtractedFileContent')
            ->label('Review Extracted File Content')
            ->modalHeading('Review extracted file content')
            ->modalDescription('OpenAI has processed the saved file. Review and edit the extracted content before placing it into Optional Content.')
            ->modalSubmitActionLabel('Use extracted content')
            ->modalWidth(Width::Screen)
            ->extraModalWindowAttributes(['class' => 'twyxtco-file-extraction-modal'], merge: true)
            ->closeModalByClickingAway(false)
            ->fillForm(fn (): array => $this->extractFileContentForReview())
            ->schema([
                Textarea::make('extraction_prompt')
                    ->label('Prompt used')
                    ->helperText('This is the exact prompt sent with the saved file.')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(8)
                    ->extraFieldWrapperAttributes(['class' => 'twyxtco-file-extraction-prompt-field'])
                    ->columnSpanFull(),
                RichEditorDefaults::configure(RichEditor::make('extracted_content'), withAiRewrite: false)
                    ->label('Extracted content')
                    ->helperText('Review and edit this content, then choose Use extracted content to place it into Optional Content.')
                    ->required()
                    ->extraInputAttributes([
                        'class' => 'twyxtco-file-extraction-result-editor',
                    ])
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $content = trim((string) ($data['extracted_content'] ?? ''));

                if (blank($content)) {
                    Notification::make()
                        ->title('No extracted content to use')
                        ->danger()
                        ->send();

                    return;
                }

                $this->getRecord()
                    ->forceFill([
                        'content' => $content,
                        'updated_by_id' => Filament::auth()->id(),
                    ])
                    ->save();

                $this->getRecord()->refresh();
                $this->refreshFormData(['content']);
                $this->standardAfterSave();

                Notification::make()
                    ->title('Extracted content added')
                    ->body('Review the Optional Content field before saving any other edits.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array{extraction_prompt: string}
     */
    private function fileExtractionPromptPreview(): array
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        $record = $this->getRecord()->refresh()->load('currentVersion');
        $extractor = app(OpenAiFileDocumentExtractor::class);

        return [
            'extraction_prompt' => $extractor->promptFor($record),
        ];
    }

    /**
     * @return array{extraction_prompt: ?string, extracted_content: ?string}
     */
    private function extractFileContentForReview(): array
    {
        $record = $this->getRecord()->refresh()->load('currentVersion');
        $extractor = app(OpenAiFileDocumentExtractor::class);
        $prompt = $extractor->promptFor($record);

        try {
            return [
                'extraction_prompt' => $prompt,
                'extracted_content' => $extractor->extract($record),
            ];
        } catch (Throwable $exception) {
            Notification::make()
                ->title('File extraction failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return [
                'extraction_prompt' => $prompt,
                'extracted_content' => null,
            ];
        }
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

        $this->getRecord()->refresh()->load('currentVersion');

        $this->standardAfterSave();

        $this->refreshCachedFileHeaderActions();
    }

    private function refreshCachedFileHeaderActions(): void
    {
        unset(
            $this->cachedActions['downloadCurrentFile'],
            $this->cachedActions['headerViewPublicPage'],
        );

        $this->cachedHeaderActions = [];
        $this->cacheInteractsWithHeaderActions();
    }
}
