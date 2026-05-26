<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use Filament\Resources\Pages\EditRecord;

class EditHomepageBanner extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = HomepageBannerResource::class;
}
