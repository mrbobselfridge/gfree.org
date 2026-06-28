<?php

namespace App\Support;

use App\Models\SlideDeckSlide;

interface SlideAnalyzerInterface
{
    public function analyze(SlideDeckSlide $slide): SlideAnalysisResult;
}
