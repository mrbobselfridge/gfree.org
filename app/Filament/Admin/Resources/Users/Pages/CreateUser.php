<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
                'ministries' => array_values($data['admin_permissions']['records']['ministries'] ?? []),
                'pages' => array_values($data['admin_permissions']['records']['pages'] ?? []),
                'leaders' => array_values($data['admin_permissions']['records']['leaders'] ?? []),
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
