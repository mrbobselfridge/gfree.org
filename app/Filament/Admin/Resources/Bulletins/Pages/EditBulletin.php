<?php

namespace App\Filament\Admin\Resources\Bulletins\Pages;

use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Filament\Admin\Support\WorkflowNotificationActions;
use App\Support\OpenAiBulletinAnnouncementReviewer;
use App\Support\OpenAiBulletinExtractor;
use Filament\Actions\Action;
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
            ...$this->getHeaderViewPublicPageActions(),
            ...WorkflowNotificationActions::notifyTeamForRecordActions($this->getRecord()),
            $this->getReviewAnnouncementsAction(),
            $this->getExtractPdfAction(),
            $this->getHeaderDeleteAction(),
            $this->getHeaderSaveAndCloseAction(),
            $this->getHeaderSaveAction(),
        ];
    }

    protected function getReviewAnnouncementsAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('reviewAnnouncements')
                ->label('Review Announcements')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Review bulletin against announcements')
                ->modalDescription('This saves the current bulletin, sends the bulletin content and current announcement records to OpenAI, then stores a review checklist on this bulletin.')
                ->action(function (OpenAiBulletinAnnouncementReviewer $reviewer): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    try {
                        $review = $reviewer->review($this->record->refresh());
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Announcement review failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->forceFill([
                        'announcement_review' => $review,
                    ])->save();

                    $this->refreshFormData(['announcement_review']);

                    Notification::make()
                        ->title('Announcement review ready')
                        ->body('Review the checklist in the AI Announcement Review section.')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedClipboardDocumentCheck,
        );
    }

    protected function getExtractPdfAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('extractPdf')
                ->label('Extract PDF')
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
                }),
            Heroicon::OutlinedSparkles,
        );
    }
}
