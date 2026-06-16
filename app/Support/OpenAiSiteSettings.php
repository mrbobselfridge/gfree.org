<?php

namespace App\Support;

use App\Models\SiteSetting;

class OpenAiSiteSettings
{
    public const DEFAULT_MODEL = 'gpt-5-nano';

    public const DEFAULT_FILE_EXTRACTION_MODEL = 'gpt-5.4-mini';

    private const DEFAULT_FILE_EXTRACTION_REASONING_EFFORT = 'low';

    private const DEFAULT_FILE_EXTRACTION_TEXT_VERBOSITY = 'low';

    private const DEFAULT_FILE_EXTRACTION_MAX_OUTPUT_TOKENS = 12000;

    private const REASONING_EFFORTS = [
        'none',
        'minimal',
        'low',
        'medium',
        'high',
        'xhigh',
    ];

    private const TEXT_VERBOSITY_LEVELS = [
        'low',
        'medium',
        'high',
    ];

    /**
     * @return array<string, string>
     */
    public static function modelOptions(): array
    {
        return [
            'gpt-5.5' => 'GPT-5.5',
            'gpt-5.4' => 'GPT-5.4',
            'gpt-5.4-mini' => 'GPT-5.4 Mini',
            'gpt-5.4-nano' => 'GPT-5.4 Nano',
            'gpt-5-nano' => 'GPT-5 Nano',
            'gpt-5-mini' => 'GPT-5 Mini',
            'gpt-4.1-nano' => 'GPT-4.1 Nano',
            'gpt-4.1-mini' => 'GPT-4.1 Mini',
            'gpt-4o-mini' => 'GPT-4o Mini',
        ];
    }

    public static function apiKey(): ?string
    {
        $apiKey = SiteSetting::query()->value('openai_api_key');

        return filled($apiKey) ? $apiKey : config('services.openai.api_key');
    }

    public static function contentModel(): string
    {
        return config('services.openai.content_model') ?: self::DEFAULT_MODEL;
    }

    public static function fileExtractionModel(): string
    {
        return config('services.openai.file_extraction_model') ?: self::DEFAULT_FILE_EXTRACTION_MODEL;
    }

    public static function fileExtractionReasoningEffort(): string
    {
        $effort = strtolower((string) (config('services.openai.file_extraction_reasoning_effort') ?: self::DEFAULT_FILE_EXTRACTION_REASONING_EFFORT));

        return in_array($effort, self::REASONING_EFFORTS, true)
            ? $effort
            : self::DEFAULT_FILE_EXTRACTION_REASONING_EFFORT;
    }

    public static function fileExtractionTextVerbosity(): string
    {
        $verbosity = strtolower((string) (config('services.openai.file_extraction_text_verbosity') ?: self::DEFAULT_FILE_EXTRACTION_TEXT_VERBOSITY));

        return in_array($verbosity, self::TEXT_VERBOSITY_LEVELS, true)
            ? $verbosity
            : self::DEFAULT_FILE_EXTRACTION_TEXT_VERBOSITY;
    }

    public static function fileExtractionMaxOutputTokens(): int
    {
        $tokens = (int) (config('services.openai.file_extraction_max_output_tokens') ?: self::DEFAULT_FILE_EXTRACTION_MAX_OUTPUT_TOKENS);

        return max(1000, $tokens);
    }

    public static function modelSupportsReasoningControls(string $model): bool
    {
        return str_starts_with($model, 'gpt-5');
    }
}
