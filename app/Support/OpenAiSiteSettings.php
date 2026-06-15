<?php

namespace App\Support;

use App\Models\SiteSetting;

class OpenAiSiteSettings
{
    public const DEFAULT_MODEL = 'gpt-5-nano';

    /**
     * @return array<string, string>
     */
    public static function modelOptions(): array
    {
        return [
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
}
