<?php

namespace App\Support;

use App\Models\HomepageContent;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class PageVisualSnapshot
{
    public const DEFAULT_WIDTH = 1440;

    public const DEFAULT_HEIGHT = 1000;

    public const DEFAULT_TIMEOUT_SECONDS = 90;

    public function capture(Model $record, int $width = self::DEFAULT_WIDTH, int $height = self::DEFAULT_HEIGHT): PageVisualSnapshotResult
    {
        if (! $this->supports($record)) {
            throw new RuntimeException('This record type is not available for visual page snapshots.');
        }

        $relativePath = $this->relativeOutputPath($record);
        $absolutePath = Storage::disk('local')->path($relativePath);

        Storage::disk('local')->makeDirectory(dirname($relativePath));

        $result = Process::timeout(self::DEFAULT_TIMEOUT_SECONDS)
            ->run([
                $this->nodeBinary(),
                base_path('scripts/capture-page-snapshot.mjs'),
                '--url='.$this->previewUrl($record),
                '--output='.$absolutePath,
                '--width='.$width,
                '--height='.$height,
                '--timeout=60000',
            ]);

        if ($result->failed()) {
            Storage::disk('local')->delete($relativePath);

            throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'Page visual snapshot capture failed.');
        }

        if (! file_exists($absolutePath)) {
            throw new RuntimeException('Page visual snapshot capture finished without creating an image.');
        }

        return new PageVisualSnapshotResult(
            path: $relativePath,
            absolutePath: $absolutePath,
            previewUrl: $this->previewUrl($record),
            width: $width,
            height: $height,
            imageUrl: $this->imageUrl($relativePath),
        );
    }

    public function imageUrl(string $path): string
    {
        return URL::temporarySignedRoute(
            'admin.page-visual-snapshots.image',
            now()->addDays(7),
            ['path' => $path],
        );
    }

    public function previewUrl(Model $record): string
    {
        $route = $this->routeParts($record);

        if ($route === null) {
            throw new RuntimeException('This record type is not available for visual page snapshots.');
        }

        return URL::temporarySignedRoute(
            'admin.page-visual-snapshots.preview',
            now()->addMinutes(10),
            $route,
        );
    }

    public function supports(Model $record): bool
    {
        return $this->routeParts($record) !== null;
    }

    /**
     * @return array{type: string, record?: int}|null
     */
    private function routeParts(Model $record): ?array
    {
        return match (true) {
            $record instanceof Page => ['type' => 'page', 'record' => $record->getKey()],
            $record instanceof HomepageContent => ['type' => 'homepage'],
            default => null,
        };
    }

    private function relativeOutputPath(Model $record): string
    {
        $type = Str::kebab(class_basename($record));
        $id = $record->getKey() ?: 'home';

        return 'page-visual-snapshots/'.$type.'-'.$id.'-'.now()->format('YmdHis').'-'.Str::random(8).'.png';
    }

    private function nodeBinary(): string
    {
        return config('services.page_visual_snapshot.node_binary', env('PAGE_VISUAL_SNAPSHOT_NODE_BINARY', 'node'));
    }
}
