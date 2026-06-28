<?php

namespace App\Jobs;

use App\Models\SlideDeckSlide;
use App\Support\SlideAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeSlideDeckSlideJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public SlideDeckSlide $slide) {}

    public function handle(SlideAnalysisService $analysis): void
    {
        $analysis->analyze($this->slide->refresh());
    }
}
