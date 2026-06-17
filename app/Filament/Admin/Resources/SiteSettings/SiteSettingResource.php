<?php

namespace App\Filament\Admin\Resources\SiteSettings;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Filament\Admin\Resources\SiteSettings\Pages\ListSiteSettings;
use App\Filament\Admin\Resources\SiteSettings\Schemas\SiteSettingForm;
use App\Filament\Admin\Resources\SiteSettings\Tables\SiteSettingsTable;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    use AppliesAdminAccess;

    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 930;

    protected static ?string $modelLabel = 'site settings';

    protected static ?string $pluralModelLabel = 'site settings';

    protected static ?string $recordTitleAttribute = 'church_name';

    /**
     * @return array<int, NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return collect(parent::getNavigationItems())
            ->map(fn (NavigationItem $item): NavigationItem => $item->extraAttributes([
                'class' => 'twyxtco-sidebar-site-tools-divider',
            ], merge: true))
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return SiteSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteSettingsTable::configure($table);
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
            'index' => ListSiteSettings::route('/'),
            'edit' => EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
