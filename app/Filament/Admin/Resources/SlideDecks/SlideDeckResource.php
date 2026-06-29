<?php

namespace App\Filament\Admin\Resources\SlideDecks;

use App\Filament\Admin\Concerns\HasCentralizedAdminNavigation;
use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\SlideDecks\Pages\CreateSlideDeck;
use App\Filament\Admin\Resources\SlideDecks\Pages\EditSlideDeck;
use App\Filament\Admin\Resources\SlideDecks\Pages\ListSlideDecks;
use App\Filament\Admin\Resources\SlideDecks\RelationManagers\SlidesRelationManager;
use App\Filament\Admin\Resources\SlideDecks\Schemas\SlideDeckForm;
use App\Filament\Admin\Resources\SlideDecks\Tables\SlideDecksTable;
use App\Models\SlideDeck;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SlideDeckResource extends Resource
{
    use AppliesAdminAccess;
    use HasCentralizedAdminNavigation;

    protected static ?string $model = SlideDeck::class;

    protected static ?string $modelLabel = 'slide deck import';

    protected static ?string $pluralModelLabel = 'Slide Deck Imports';

    protected static ?string $recordTitleAttribute = 'name';

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
        return SlideDeckForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlideDecksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SlidesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlideDecks::route('/'),
            'create' => CreateSlideDeck::route('/create'),
            'edit' => EditSlideDeck::route('/{record}/edit'),
        ];
    }
}
