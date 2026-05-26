<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use Filament\Resources\Pages\ListRecords;

class ListHomepageBanners extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = HomepageBannerResource::class;
}
