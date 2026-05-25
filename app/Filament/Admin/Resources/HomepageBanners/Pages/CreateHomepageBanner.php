<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsCreateToIndex;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomepageBanner extends CreateRecord
{
    use RedirectsCreateToIndex;

    protected static string $resource = HomepageBannerResource::class;
}
