<?php

namespace App\Support;

use App\Models\SlideDeckSlide;
use RuntimeException;

class OpenAiSlideAnalyzer implements SlideAnalyzerInterface
{
    public function analyze(SlideDeckSlide $slide): SlideAnalysisResult
    {
        throw new RuntimeException('OpenAI slide analysis is not connected yet. Configure a SlideAnalyzerInterface binding before enabling AI analysis.');
    }
}
