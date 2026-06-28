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

    private function rerunDeckImportAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('rerunDeckImport')
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

    private function rerunDeckAnalysisAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('rerunDeckAnalysis')
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
                }),
            Heroicon::OutlinedSparkles,
        );
    }

    private function downloadImagesZipAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('downloadSlideImages')
                ->label('Download PNG ZIP')
                ->url(fn (): string => route('admin.slide-decks.download-images', ['slideDeck' => $this->getRecord()]), true),
            Heroicon::OutlinedArrowDownTray,
        );
    }

    private function exportCsvAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('exportSlideCsv')
                ->label('Export CSV')
                ->url(fn (): string => route('admin.slide-decks.export', ['slideDeck' => $this->getRecord(), 'format' => 'csv']), true),
            Heroicon::OutlinedDocumentArrowDown,
        );
    }

    private function exportJsonAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('exportSlideJson')
                ->label('Export JSON')
                ->url(fn (): string => route('admin.slide-decks.export', ['slideDeck' => $this->getRecord(), 'format' => 'json']), true),
            Heroicon::OutlinedClipboardDocumentList,
        );
    }
}
