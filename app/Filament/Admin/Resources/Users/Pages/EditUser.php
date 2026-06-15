<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardEditActions;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccess;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use UsesStandardEditActions;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tools = $data['admin_permissions']['tools'] ?? [];

        $data['admin_permissions']['tool_groups'] = [
            'content' => array_values(array_intersect($tools, array_keys(AdminAccess::toolOptionsForGroup('Content')))),
            'sitewide' => array_values(array_intersect($tools, array_keys(AdminAccess::toolOptionsForGroup('Site Tools')))),
            'additional' => array_values(array_intersect($tools, array_keys(AdminAccess::additionalToolOptions()))),
        ];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->normalizePermissions($data);
    }

    private function normalizePermissions(array $data): array
    {
        if (($data['role'] ?? null) === User::ROLE_ADMIN) {
            $data['admin_permissions'] = null;

            return $data;
        }

        $data['admin_permissions'] = [
            'tools' => $this->selectedTools($data),
            'records' => [
                'pages' => array_values($data['admin_permissions']['records']['pages'] ?? []),
            ],
        ];

        return $data;
    }

    private function selectedTools(array $data): array
    {
        $permissions = $data['admin_permissions'] ?? [];

        return collect($permissions['tool_groups'] ?? [])
            ->flatMap(fn (array $tools): array => $tools)
            ->merge($permissions['tools'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
