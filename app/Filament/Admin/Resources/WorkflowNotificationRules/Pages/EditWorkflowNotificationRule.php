<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\WorkflowNotificationRules\WorkflowNotificationRuleResource;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowNotificationRule extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = WorkflowNotificationRuleResource::class;
}
