<?php

namespace App\Support;

class PageVisualSnapshotResult
{
    public function __construct(
        public readonly string $path,
        public readonly string $absolutePath,
        public readonly string $previewUrl,
        public readonly int $width,
        public readonly int $height,
    ) {}
}
