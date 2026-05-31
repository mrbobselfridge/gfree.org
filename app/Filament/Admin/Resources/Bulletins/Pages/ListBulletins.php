<?php

namespace App\Filament\Admin\Resources\Bulletins\Pages;

use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\Concerns\ManagesListingPageSettings;
use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListBulletins extends ListRecords
{
    use ManagesListingPageSettings;
    use UsesStandardListActions;

    protected static string $resource = BulletinResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->mountListingSettings();
    }

    protected function getListingSettingsPrefix(): string
    {
        return 'bulletins';
    }

    protected function getListingSettingsLabelPrefix(): string
    {
        return 'Bulletins';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                $this->getListingSettingsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }
}
