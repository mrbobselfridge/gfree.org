<?php

namespace App\Support;

use App\Models\SlideDeckSlide;
use Throwable;

class SlideAnalysisService
{
    public function __construct(private readonly SlideAnalyzerInterface $analyzer) {}

    public function analyze(SlideDeckSlide $slide): void
    {
        try {
            $result = $this->analyzer->analyze($slide);
            $slide->fill($result->toSlideAttributes())->save();
        } catch (Throwable $exception) {
            report($exception);

            $slide->forceFill([
                'raw_analysis_json' => [
                    'error' => $exception->getMessage(),
                    'analyzer_failed' => true,
                ],
            ])->save();
        }
    }
}
