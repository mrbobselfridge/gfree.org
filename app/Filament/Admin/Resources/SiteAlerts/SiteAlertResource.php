<?php

namespace App\Filament\Admin\Resources\SiteAlerts;

use App\Filament\Admin\Concerns\HasCentralizedAdminNavigation;
use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\SiteAlerts\Pages\CreateSiteAlert;
use App\Filament\Admin\Resources\SiteAlerts\Pages\EditSiteAlert;
use App\Filament\Admin\Resources\SiteAlerts\Pages\ListSiteAlerts;
use App\Filament\Admin\Resources\SiteAlerts\Schemas\SiteAlertForm;
use App\Filament\Admin\Resources\SiteAlerts\Tables\SiteAlertsTable;
use App\Models\SiteAlert;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SiteAlertResource extends Resource
{
    use AppliesAdminAccess;
    use HasCentralizedAdminNavigation;

    protected static ?string $model = SiteAlert::class;

    protected static ?string $modelLabel = 'site alert';

    protected static ?string $recordTitleAttribute = 'label';

    /**
     * @return array<int, NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return collect(parent::getNavigationItems())
            ->map(fn (NavigationItem $item): NavigationItem => $item->extraAttributes([
                'class' => 'twyxtco-sidebar-indent-35',
            ], merge: true))
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return SiteAlertForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteAlertsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteAlerts::route('/'),
            'create' => CreateSiteAlert::route('/create'),
            'edit' => EditSiteAlert::route('/{record}/edit'),
        ];
    }
}
