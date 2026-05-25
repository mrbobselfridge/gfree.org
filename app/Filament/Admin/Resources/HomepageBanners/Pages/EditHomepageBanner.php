<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Pages;

use App\Filament\Admin\Resources\Concerns\RedirectsEditToIndex;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomepageBanner extends EditRecord
{
    use RedirectsEditToIndex;

    protected static string $resource = HomepageBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
