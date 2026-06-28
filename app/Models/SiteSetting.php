<?php

namespace App\Models;

use App\Support\SiteDesignPalette;
use App\Support\SiteVariables;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'church_name',
    'site_logo_path',
    'default_page_header_image_path',
    'tagline',
    'phone',
    'email',
    'contact_name',
    'contact_email',
    'contact_phone',
    'contact_notes',
    'design_background_colors',
    'design_accent_color',
    'design_accent_text_color',
    'design_accent_soft_color',
    'custom_css',
    'header_custom_js',
    'body_top_custom_js',
    'body_bottom_custom_js',
    'dashboard_notes',
    'site_variables',
    'openai_api_key',
    'ai_content_prompt',
    'facebook_url',
    'instagram_url',
    'youtube_url',
    'tiktok_url',
    'linkedin_url',
    'google_business_profile_url',
    'pinterest_url',
    'x_url',
    'threads_url',
    'additional_social_links',
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

    public function socialLinks()
    {
        $builtInLinks = collect([
            ['label' => 'Facebook', 'url' => $this->facebook_url, 'icon' => 'facebook'],
            ['label' => 'Instagram', 'url' => $this->instagram_url, 'icon' => 'instagram'],
            ['label' => 'YouTube', 'url' => $this->youtube_url, 'icon' => 'youtube'],
            ['label' => 'TikTok', 'url' => $this->tiktok_url, 'icon' => 'tiktok'],
            ['label' => 'LinkedIn', 'url' => $this->linkedin_url, 'icon' => 'linkedin'],
            ['label' => 'Google Business Profile', 'url' => $this->google_business_profile_url, 'icon' => 'google-business-profile'],
            ['label' => 'Pinterest', 'url' => $this->pinterest_url, 'icon' => 'pinterest'],
            ['label' => 'X', 'url' => $this->x_url, 'icon' => 'x'],
            ['label' => 'Threads', 'url' => $this->threads_url, 'icon' => 'threads'],
        ])
            ->filter(fn (array $link): bool => filled($link['url']))
            ->map(fn (array $link): array => [
                ...$link,
                'image_url' => null,
            ]);

        return $builtInLinks
            ->merge($this->additionalSocialLinks())
            ->values();
    }

    public function additionalSocialLinks()
    {
        return collect(is_array($this->additional_social_links) ? $this->additional_social_links : [])
            ->filter(fn (mixed $link): bool => is_array($link))
            ->map(function (array $link): ?array {
                $label = trim((string) ($link['label'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));
                $imagePath = $this->selectedImagePath($link['image_path'] ?? null);

                if ($label === '' || $url === '' || $imagePath === null) {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                    'icon' => 'custom',
                    'image_url' => $this->imageUrl($imagePath),
                ];
            })
            ->filter()
            ->values();
    }

    public function siteVariableValue(string $variable): ?string
    {
        return SiteVariables::variableValue($variable, $this);
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

    private function imageUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function selectedImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->first();
        }

        $path = trim((string) $path);

        return $path === '' ? null : $path;
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
            'additional_social_links' => 'array',
            'site_variables' => 'array',
        ];
    }
}
