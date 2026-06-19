<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'seo_title',
    'seo_description',
    'hero_banners_auto_rotate',
    'hero_banners_rotation_delay_seconds',
    'hero_banners_fade_duration_seconds',
    'intro_eyebrow',
    'intro_title',
    'intro_body',
    'process_eyebrow',
    'process_title',
    'process_steps',
    'feature_eyebrow',
    'feature_title',
    'feature_body',
    'feature_label',
    'feature_url',
    'content_blocks',
])]
class HomepageContent extends Model
{
    public const DEFAULT_HERO_BANNERS_ROTATION_DELAY_SECONDS = 20;

    public const DEFAULT_HERO_BANNERS_FADE_DURATION_SECONDS = 3;

    protected function casts(): array
    {
        return [
            'process_steps' => 'array',
            'content_blocks' => 'array',
            'hero_banners_auto_rotate' => 'boolean',
            'hero_banners_rotation_delay_seconds' => 'integer',
            'hero_banners_fade_duration_seconds' => 'integer',
        ];
    }

    public function heroBannersRotationDelaySeconds(): int
    {
        return $this->normalizedTimingSeconds(
            $this->hero_banners_rotation_delay_seconds,
            self::DEFAULT_HERO_BANNERS_ROTATION_DELAY_SECONDS,
        );
    }

    public function heroBannersFadeDurationSeconds(): int
    {
        return $this->normalizedTimingSeconds(
            $this->hero_banners_fade_duration_seconds,
            self::DEFAULT_HERO_BANNERS_FADE_DURATION_SECONDS,
        );
    }

    private function normalizedTimingSeconds(mixed $value, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return max(1, (int) $value);
    }
}
