<?php

namespace App\Models;

use App\Support\SiteDesignPalette;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'church_name',
    'site_logo_path',
    'default_page_header_image_path',
    'tagline',
    'sunday_service_times',
    'address',
    'phone',
    'email',
    'office_hours',
    'design_background_colors',
    'design_accent_color',
    'design_accent_text_color',
    'design_accent_soft_color',
    'custom_css',
    'dashboard_notes',
    'openai_api_key',
    'ai_content_prompt',
    'livestream_url',
    'facebook_url',
    'instagram_url',
    'youtube_url',
    'google_tag_manager_id',
    'google_analytics_measurement_id',
])]
class SiteSetting extends Model
{
    public const DEFAULT_DESIGN_ACCENT_COLOR = '#17b8ad';

    public const DEFAULT_DESIGN_ACCENT_TEXT_COLOR = '#05756f';

    public const DEFAULT_DESIGN_ACCENT_SOFT_COLOR = '#ddf8f5';

    public function backgroundColors(): array
    {
        return SiteDesignPalette::normalizeBackgroundColors($this->design_background_colors)
            ?: SiteDesignPalette::defaultBackgroundColors();
    }

    public function publicDesignCss(): ?string
    {
        $css = [];
        $variables = $this->publicDesignVariables();

        if ($variables !== []) {
            $css[] = sprintf(
                ".site-home, .site-page {\n%s\n}",
                collect($variables)
                    ->map(fn (string $value, string $name): string => "    {$name}: {$value};")
                    ->implode("\n")
            );
        }

        $customCss = $this->safeCustomCss();

        if ($customCss !== null) {
            $css[] = $customCss;
        }

        return $css === [] ? null : implode("\n\n", $css);
    }

    /**
     * @return array<string, string>
     */
    public function publicDesignVariables(): array
    {
        return collect([
            '--site-accent' => $this->design_accent_color ?: self::DEFAULT_DESIGN_ACCENT_COLOR,
            '--site-accent-text' => $this->design_accent_text_color ?: self::DEFAULT_DESIGN_ACCENT_TEXT_COLOR,
            '--site-accent-soft' => $this->design_accent_soft_color ?: self::DEFAULT_DESIGN_ACCENT_SOFT_COLOR,
        ])
            ->map(fn (mixed $color): ?string => SiteDesignPalette::normalizeHex($color))
            ->filter()
            ->all();
    }

    private function safeCustomCss(): ?string
    {
        $css = trim((string) $this->custom_css);

        if ($css === '') {
            return null;
        }

        $css = preg_replace('/<\/?style\b[^>]*>/i', '', $css) ?? $css;

        return str_ireplace('</style', '<\/style', trim($css));
    }

    public function logoUrl(): string
    {
        if (blank($this->site_logo_path)) {
            return asset('images/twyxtco-logo.png');
        }

        $path = (string) $this->site_logo_path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function normalizedGoogleTagManagerId(): ?string
    {
        return $this->normalizeTrackingId($this->google_tag_manager_id, '/^GTM-[A-Z0-9]+$/');
    }

    public function normalizedGoogleAnalyticsMeasurementId(): ?string
    {
        return $this->normalizeTrackingId($this->google_analytics_measurement_id, '/^G-[A-Z0-9]+$/');
    }

    private function normalizeTrackingId(?string $value, string $pattern): ?string
    {
        $value = strtoupper(trim((string) $value));

        if (! preg_match($pattern, $value)) {
            return null;
        }

        return $value;
    }

    protected function casts(): array
    {
        return [
            'design_background_colors' => 'array',
        ];
    }
}
