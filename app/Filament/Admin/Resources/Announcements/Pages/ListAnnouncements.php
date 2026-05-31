<?php

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Concerns\ManagesListingPageSettings;
use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListAnnouncements extends ListRecords
{
    use ManagesListingPageSettings;
    use UsesStandardListActions;

    protected static string $resource = AnnouncementResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->mountListingSettings();
    }

    protected function getListingSettingsPrefix(): string
    {
        return 'announcements';
    }

    protected function getListingSettingsLabelPrefix(): string
    {
        return 'Announcements';
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
