<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Support\PublicPageActions;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use UsesStandardListActions {
        getHeaderActions as getStandardHeaderActions;
    }

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublicPageActions::button('viewHomepage', route('home')),
            ...$this->getStandardHeaderActions(),
        ];
    }
}
