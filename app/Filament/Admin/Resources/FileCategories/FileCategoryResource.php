<?php

namespace App\Filament\Admin\Resources\FileCategories;

use App\Filament\Admin\Concerns\HasCentralizedAdminNavigation;
use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\FileCategories\Pages\CreateFileCategory;
use App\Filament\Admin\Resources\FileCategories\Pages\EditFileCategory;
use App\Filament\Admin\Resources\FileCategories\Pages\ListFileCategories;
use App\Filament\Admin\Resources\FileCategories\Schemas\FileCategoryForm;
use App\Filament\Admin\Resources\FileCategories\Tables\FileCategoriesTable;
use App\Models\FileCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FileCategoryResource extends Resource
{
    use AppliesAdminAccess;
    use HasCentralizedAdminNavigation;

    protected static ?string $model = FileCategory::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'file category';

    protected static ?string $pluralModelLabel = 'File Categories';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FileCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FileCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFileCategories::route('/'),
            'create' => CreateFileCategory::route('/create'),
            'edit' => EditFileCategory::route('/{record}/edit'),
        ];
    }
}
