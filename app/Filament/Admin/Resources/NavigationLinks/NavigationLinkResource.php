<?php

namespace App\Filament\Admin\Resources\NavigationLinks;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\NavigationLinks\Pages\CreateNavigationLink;
use App\Filament\Admin\Resources\NavigationLinks\Pages\EditNavigationLink;
use App\Filament\Admin\Resources\NavigationLinks\Pages\ListNavigationLinks;
use App\Filament\Admin\Resources\NavigationLinks\Schemas\NavigationLinkForm;
use App\Filament\Admin\Resources\NavigationLinks\Tables\NavigationLinksTable;
use App\Models\NavigationLink;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NavigationLinkResource extends Resource
{
    use AppliesAdminAccess;

    protected static ?string $model = NavigationLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 36;

    protected static ?string $navigationLabel = 'Navigation';

    protected static ?string $modelLabel = 'navigation link';

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
        return NavigationLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NavigationLinksTable::configure($table);
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
            'index' => ListNavigationLinks::route('/'),
            'create' => CreateNavigationLink::route('/create'),
            'edit' => EditNavigationLink::route('/{record}/edit'),
        ];
    }
}
