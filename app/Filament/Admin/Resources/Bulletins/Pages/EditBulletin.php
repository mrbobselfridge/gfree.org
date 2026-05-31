<?php

namespace App\Filament\Admin\Resources\Bulletins\Pages;

use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Support\OpenAiBulletinExtractor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class EditBulletin extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = BulletinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHeaderCancelAction(),
            $this->getExtractPdfAction(),
            DeleteAction::make(),
            $this->getHeaderSaveAndCloseAction(),
            $this->getHeaderSaveAction(),
        ];
    }

    protected function getExtractPdfAction(): Action
    {
        return Action::make('extractPdf')
            ->label('Extract PDF')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Extract bulletin PDF')
            ->modalDescription('This saves the current bulletin, sends the uploaded PDF and extraction instructions to OpenAI, then replaces the rich text HTML with the extracted result.')
            ->disabled(fn (): bool => blank($this->record->pdf_path))
            ->action(function (OpenAiBulletinExtractor $extractor): void {
                $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                try {
                    $html = $extractor->extract($this->record->refresh());
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('PDF extraction failed')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $this->record->forceFill([
                    'extracted_html' => $html,
                ])->save();

                $this->refreshFormData(['extracted_html']);

                Notification::make()
                    ->title('PDF extracted')
                    ->body('The formatted HTML is ready to review and edit.')
                    ->success()
                    ->send();
            });
    }
}
