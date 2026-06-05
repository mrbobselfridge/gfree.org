<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardListActions;
use App\Filament\Admin\Resources\WorkflowNotificationRules\WorkflowNotificationRuleResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowNotificationRules extends ListRecords
{
    use UsesStandardListActions;

    protected static string $resource = WorkflowNotificationRuleResource::class;
}
