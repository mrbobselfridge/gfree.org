<?php

namespace App\Filament\Admin\Resources\HomepageBanners;

use App\Filament\Admin\Resources\HomepageBanners\Pages\CreateHomepageBanner;
use App\Filament\Admin\Resources\HomepageBanners\Pages\EditHomepageBanner;
use App\Filament\Admin\Resources\HomepageBanners\Pages\ListHomepageBanners;
use App\Filament\Admin\Resources\HomepageBanners\Schemas\HomepageBannerForm;
use App\Filament\Admin\Resources\HomepageBanners\Tables\HomepageBannersTable;
use App\Models\HomepageBanner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageBannerResource extends Resource
{
    protected static ?string $model = HomepageBanner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'homepage banner';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return HomepageBannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageBannersTable::configure($table);
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
            'index' => ListHomepageBanners::route('/'),
            'create' => CreateHomepageBanner::route('/create'),
            'edit' => EditHomepageBanner::route('/{record}/edit'),
        ];
    }
}
