<?php

namespace App\Support;

use RuntimeException;
use Throwable;

class SlideAnalysisException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $failureType = 'analysis_failed',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
