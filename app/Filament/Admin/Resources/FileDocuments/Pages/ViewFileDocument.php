<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Support\IconOnlyAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewFileDocument extends ViewRecord
{
    protected static string $resource = FileDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            IconOnlyAction::make(
                Action::make('downloadCurrentFile')
                    ->label('View')
                    ->url(fn (): string => $this->getRecord()->downloadUrl(), true)
                    ->color('gray'),
                Heroicon::OutlinedArrowDownTray,
            ),
        ];
    }
}
