<?php

namespace App\Filament\Admin\Resources\FileDocuments\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Support\IconOnlyAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListFileDocuments extends ListRecords
{
    use UsesStandardListActions {
        getHeaderActions as getStandardHeaderActions;
    }

    protected static string $resource = FileDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCategoriesAction(),
            ...$this->getStandardHeaderActions(),
        ];
    }

    protected function getCategoriesAction(): Action
    {
        return IconOnlyAction::make(
            Action::make('categories')
                ->label('Categories')
                ->url(FileCategoryResource::getUrl()),
            Heroicon::OutlinedTag,
        );
    }
}
