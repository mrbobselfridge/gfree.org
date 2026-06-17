<?php

namespace App\Filament\Admin\Resources\FileDocuments;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\FileDocuments\Pages\CreateFileDocument;
use App\Filament\Admin\Resources\FileDocuments\Pages\EditFileDocument;
use App\Filament\Admin\Resources\FileDocuments\Pages\ListFileDocuments;
use App\Filament\Admin\Resources\FileDocuments\Pages\ViewFileDocument;
use App\Filament\Admin\Resources\FileDocuments\RelationManagers\VersionsRelationManager;
use App\Filament\Admin\Resources\FileDocuments\Schemas\FileDocumentForm;
use App\Filament\Admin\Resources\FileDocuments\Schemas\FileDocumentInfolist;
use App\Filament\Admin\Resources\FileDocuments\Tables\FileDocumentsTable;
use App\Models\FileDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FileDocumentResource extends Resource
{
    use AppliesAdminAccess;

    protected static ?string $model = FileDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 910;

    protected static ?string $navigationLabel = 'File Library';

    protected static ?string $modelLabel = 'file';

    protected static ?string $pluralModelLabel = 'File Library';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FileDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FileDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FileDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFileDocuments::route('/'),
            'create' => CreateFileDocument::route('/create'),
            'view' => ViewFileDocument::route('/{record}'),
            'edit' => EditFileDocument::route('/{record}/edit'),
        ];
    }
}
