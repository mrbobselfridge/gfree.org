<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'snapshotable_type',
    'snapshotable_id',
    'snapshot_path',
    'snapshot_captured_at',
])]
class WorkflowVisualSnapshot extends Model
{
    public function snapshotable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'snapshot_captured_at' => 'datetime',
        ];
    }
}
