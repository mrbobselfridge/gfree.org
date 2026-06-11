<?php

namespace App\Support;

use App\Models\WorkflowVisualSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkflowVisualSnapshots
{
    public function __construct(private readonly PageVisualSnapshot $pageVisualSnapshot) {}

    public function baselineFor(Model $record): ?WorkflowVisualSnapshot
    {
        return WorkflowVisualSnapshot::query()
            ->whereMorphedTo('snapshotable', $record)
            ->first();
    }

    public function seedBaseline(Model $record): ?WorkflowVisualSnapshot
    {
        return $this->baselineFor($record) ?? $this->captureBaseline($record);
    }

    public function captureCurrent(Model $record): ?PageVisualSnapshotResult
    {
        if (! $this->pageVisualSnapshot->supports($record)) {
            return null;
        }

        try {
            return $this->pageVisualSnapshot->capture($record);
        } catch (Throwable $exception) {
            Log::warning('Workflow visual snapshot capture failed.', [
                'record_type' => $record::class,
                'record_id' => $record->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function captureBaseline(Model $record): ?WorkflowVisualSnapshot
    {
        $snapshot = $this->captureCurrent($record);

        if (! $snapshot) {
            return null;
        }

        return $this->advanceBaseline($record, $snapshot);
    }

    public function advanceBaseline(Model $record, PageVisualSnapshotResult $snapshot): WorkflowVisualSnapshot
    {
        return WorkflowVisualSnapshot::query()->updateOrCreate(
            [
                'snapshotable_type' => $record::class,
                'snapshotable_id' => $record->getKey(),
            ],
            [
                'snapshot_path' => $snapshot->path,
                'snapshot_captured_at' => now(),
            ],
        );
    }

    public function recordFor(?string $recordType, ?int $recordId): ?Model
    {
        if (! $recordType || ! $recordId || ! is_subclass_of($recordType, Model::class)) {
            return null;
        }

        return $recordType::query()->find($recordId);
    }
}
