<?php

namespace App\Filament\Admin\Resources\Bulletins;

use App\Filament\Admin\Resources\Bulletins\Pages\CreateBulletin;
use App\Filament\Admin\Resources\Bulletins\Pages\EditBulletin;
use App\Filament\Admin\Resources\Bulletins\Pages\ListBulletins;
use App\Filament\Admin\Resources\Bulletins\Schemas\BulletinForm;
use App\Filament\Admin\Resources\Bulletins\Tables\BulletinsTable;
use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Models\Bulletin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BulletinResource extends Resource
{
    use AppliesAdminAccess;

    protected static ?string $model = Bulletin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return BulletinForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BulletinsTable::configure($table);
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
            'index' => ListBulletins::route('/'),
            'create' => CreateBulletin::route('/create'),
            'edit' => EditBulletin::route('/{record}/edit'),
        ];
    }
}
