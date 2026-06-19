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
    'dashboard_notes',
    'openai_api_key',
    'ai_content_prompt',
    'livestream_url',
    'giving_url',
    'facebook_url',
    'instagram_url',
    'youtube_url',
    'google_tag_manager_id',
    'google_analytics_measurement_id',
])]
class SiteSetting extends Model
{
    public function backgroundColors(): array
    {
        return SiteDesignPalette::normalizeBackgroundColors($this->design_background_colors)
            ?: SiteDesignPalette::defaultBackgroundColors();
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
