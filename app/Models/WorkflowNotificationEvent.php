<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workflow_notification_rule_id',
    'content_area',
    'trigger',
    'status',
    'record_type',
    'record_id',
    'record_key',
    'record_label',
    'actor_id',
    'actor_name',
    'admin_url',
    'public_url',
    'scheduled_at',
    'sent_at',
    'cancelled_at',
    'recipient_emails',
])]
class WorkflowNotificationEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_CANCELLED = 'cancelled';

    public function rule(): BelongsTo
    {
        return $this->belongsTo(WorkflowNotificationRule::class, 'workflow_notification_rule_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'recipient_emails' => 'array',
        ];
    }
}
