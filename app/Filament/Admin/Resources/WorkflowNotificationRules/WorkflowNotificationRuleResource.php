<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\WorkflowNotificationRules\Pages\CreateWorkflowNotificationRule;
use App\Filament\Admin\Resources\WorkflowNotificationRules\Pages\EditWorkflowNotificationRule;
use App\Filament\Admin\Resources\WorkflowNotificationRules\Pages\ListWorkflowNotificationRules;
use App\Filament\Admin\Resources\WorkflowNotificationRules\Schemas\WorkflowNotificationRuleForm;
use App\Filament\Admin\Resources\WorkflowNotificationRules\Tables\WorkflowNotificationRulesTable;
use App\Models\WorkflowNotificationRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkflowNotificationRuleResource extends Resource
{
    use AppliesAdminAccess;

    protected static ?string $model = WorkflowNotificationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|\UnitEnum|null $navigationGroup = 'Sitewide';

    protected static ?int $navigationSort = 250;

    protected static ?string $modelLabel = 'workflow notification';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WorkflowNotificationRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowNotificationRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowNotificationRules::route('/'),
            'create' => CreateWorkflowNotificationRule::route('/create'),
            'edit' => EditWorkflowNotificationRule::route('/{record}/edit'),
        ];
    }
}
