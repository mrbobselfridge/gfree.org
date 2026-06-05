<?php

namespace App\Filament\Admin\Resources\WorkflowNotificationRules\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\WorkflowNotificationRules\WorkflowNotificationRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflowNotificationRule extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = WorkflowNotificationRuleResource::class;
}
