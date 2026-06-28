<?php

namespace App\Jobs;

use App\Models\SlideDeck;
use App\Support\SlideDeckImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSlideDeckJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public SlideDeck $slideDeck) {}

    public function handle(SlideDeckImportService $importer): void
    {
        try {
            ConvertSlideDeckToImagesJob::dispatchSync($this->slideDeck);
            $importer->analyzeDeck($this->slideDeck->refresh());
        } catch (Throwable $exception) {
            report($exception);
            $importer->failDeck($this->slideDeck->refresh(), $exception);
        }
    }
}
