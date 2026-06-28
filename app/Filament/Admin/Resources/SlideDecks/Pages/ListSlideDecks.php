<?php

namespace App\Filament\Admin\Resources\SlideDecks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use Filament\Resources\Pages\ListRecords;

class ListSlideDecks extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = SlideDeckResource::class;
}
