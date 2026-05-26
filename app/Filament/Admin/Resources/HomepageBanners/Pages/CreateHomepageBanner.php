<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomepageBanner extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = HomepageBannerResource::class;
}
