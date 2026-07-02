<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'content_area',
    'triggers',
    'notify_admins',
    'notify_all_users',
    'selected_user_ids',
    'extra_emails',
    'subject',
    'message',
    'delay_minutes',
    'is_enabled',
    'notes',
])]
class WorkflowNotificationRule extends Model
{
    public const TRIGGER_CREATED = 'created';

    public const TRIGGER_UPDATED = 'updated';

    public const TRIGGER_DELETED = 'deleted';

    public const TRIGGER_MANUAL = 'manual';

    public function events(): HasMany
    {
        return $this->hasMany(WorkflowNotificationEvent::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function hasTrigger(string $trigger): bool
    {
        return in_array($trigger, $this->triggers ?? [], true);
    }

    public function recipientEmails(): Collection
    {
        $emails = collect();

        if ($this->notify_all_users) {
            $emails = $emails->merge(User::query()->pluck('email'));
        } elseif ($this->notify_admins) {
            $emails = $emails->merge(
                User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->pluck('email'),
            );
        }

        $selectedIds = collect($this->selected_user_ids ?? [])
            ->filter()
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        if ($selectedIds) {
            $emails = $emails->merge(
                User::query()
                    ->whereKey($selectedIds)
                    ->pluck('email'),
            );
        }

        $emails = $emails->merge(self::parseEmailList($this->extra_emails));

        return $emails
            ->filter()
            ->map(fn (mixed $email): string => strtolower((string) $email))
            ->unique()
            ->values();
    }

    public static function parseEmailList(?string $emails): Collection
    {
        return Str::of((string) $emails)
            ->replace(["\r\n", "\n", ';'], ',')
            ->explode(',')
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->map(fn (string $email): string => strtolower($email))
            ->unique()
            ->values();
    }

    protected function casts(): array
    {
        return [
            'triggers' => 'array',
            'notify_admins' => 'boolean',
            'notify_all_users' => 'boolean',
            'selected_user_ids' => 'array',
            'delay_minutes' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
