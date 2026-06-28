<?php

namespace App\Support;

use App\Models\SlideDeckSlide;

class NullSlideAnalyzer implements SlideAnalyzerInterface
{
    public function analyze(SlideDeckSlide $slide): SlideAnalysisResult
    {
        return SlideAnalysisResult::fromArray([
            'slide_type' => SlideDeckSlide::TYPE_UNKNOWN,
            'suggested_name' => $slide->suggested_name ?: 'Slide '.$slide->slide_number,
            'extracted_text' => $slide->extracted_text,
            'summary' => 'No AI slide analyzer is configured yet.',
            'confidence_score' => 0,
            'analyzer' => 'null',
        ]);
    }
}
