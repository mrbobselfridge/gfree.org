<?php

namespace App\Filament\Admin\Resources\SlideDecks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Jobs\AnalyzeSlideDeckSlideJob;
use App\Jobs\ProcessSlideDeckJob;
use App\Models\SlideDeckSlide;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSlideDeck extends EditRecord
{
    use UsesStandardEditActions {
        getHeaderActions as getStandardHeaderActions;
        getFormActions as getStandardFormActions;
    }

    protected static string $resource = SlideDeckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->rerunDeckImportAction(),
            $this->rerunDeckAnalysisAction(),
            $this->downloadImagesZipAction(),
            $this->exportCsvAction(),
            $this->exportJsonAction(),
            ...$this->getStandardHeaderActions(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            ...$this->getStandardFormActions(),
            $this->exportJsonAction('footerExportSlideJson'),
            $this->exportCsvAction('footerExportSlideCsv'),
            $this->downloadImagesZipAction('footerDownloadSlideImages', withShortcut: false),
            $this->rerunDeckAnalysisAction('footerRerunDeckAnalysis', withShortcut: false),
            $this->rerunDeckImportAction('footerRerunDeckImport'),
        ];
    }

    private function rerunDeckImportAction(string $name = 'rerunDeckImport'): Action
    {
        return IconOnlyAction::make(
            Action::make($name)
                ->label('Re-run import')
                ->requiresConfirmation()
                ->modalHeading('Re-run slide deck import?')
                ->modalDescription('This reconverts the original PowerPoint file and replaces the generated slide images and slide analysis records.')
                ->action(function (): void {
                    ProcessSlideDeckJob::dispatch($this->getRecord())->afterResponse();

                    Notification::make()
                        ->title('Slide deck import queued')
                        ->success()
                        ->send();
                }),
            Heroicon::OutlinedArrowPath,
        );
    }

    private function rerunDeckAnalysisAction(string $name = 'rerunDeckAnalysis', bool $withShortcut = true): Action
    {
        $action = Action::make($name)
            ->label('Re-run analysis')
            ->requiresConfirmation()
            ->modalHeading('Re-run analysis for this deck?')
            ->action(function (): void {
                $this->getRecord()
                    ->slides()
                    ->get()
                    ->each(fn (SlideDeckSlide $slide): mixed => AnalyzeSlideDeckSlideJob::dispatch($slide)->afterResponse());

                Notification::make()
                    ->title('Slide analysis queued')
                    ->success()
                    ->send();
            });

        if ($withShortcut) {
            $action->keyBindings(['alt+a']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedSparkles,
        );
    }

    private function downloadImagesZipAction(string $name = 'downloadSlideImages', bool $withShortcut = true): Action
    {
        $action = Action::make($name)
            ->label('Download PNG ZIP')
            ->url(fn (): string => route('admin.slide-decks.download-images', ['slideDeck' => $this->getRecord()]), true);

        if ($withShortcut) {
            $action->keyBindings(['alt+d']);
        }

        return IconOnlyAction::make(
            $action,
            Heroicon::OutlinedArrowDownTray,
        );
    }

    private function exportCsvAction(string $name = 'exportSlideCsv'): Action
    {
        return IconOnlyAction::make(
            Action::make($name)
                ->label('Export CSV')
                ->url(fn (): string => route('admin.slide-decks.export', ['slideDeck' => $this->getRecord(), 'format' => 'csv']), true),
            Heroicon::OutlinedDocumentArrowDown,
        );
    }

    private function exportJsonAction(string $name = 'exportSlideJson'): Action
    {
        return IconOnlyAction::make(
            Action::make($name)
                ->label('Export JSON')
                ->url(fn (): string => route('admin.slide-decks.export', ['slideDeck' => $this->getRecord(), 'format' => 'json']), true),
            Heroicon::OutlinedClipboardDocumentList,
        );
    }
}
