<?php

namespace App\Jobs;

use App\Models\SlideDeck;
use App\Support\SlideDeckImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConvertSlideDeckToImagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 540;

    public function __construct(public SlideDeck $slideDeck) {}

    public function handle(SlideDeckImportService $importer): void
    {
        $importer->convertDeckToImages($this->slideDeck->refresh());
    }
}
