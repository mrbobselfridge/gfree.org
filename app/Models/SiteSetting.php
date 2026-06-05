<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'church_name',
    'tagline',
    'sunday_service_times',
    'address',
    'phone',
    'email',
    'office_hours',
    'openai_api_key',
    'openai_bulletin_model',
    'ai_content_prompt',
    'livestream_url',
    'giving_url',
    'one_church_url',
    'facebook_url',
    'instagram_url',
    'youtube_url',
    'google_tag_manager_id',
    'google_analytics_measurement_id',
    'announcements_small_label',
    'announcements_title',
    'announcements_subtitle',
    'announcements_image_path',
    'leadership_small_label',
    'leadership_title',
    'leadership_subtitle',
    'leadership_image_path',
    'ministry_small_label',
    'ministry_title',
    'ministry_subtitle',
    'ministry_image_path',
    'sermons_small_label',
    'sermons_title',
    'sermons_subtitle',
    'sermons_text',
    'sermons_youtube_link_label',
    'sermons_youtube_feed_url',
    'sermons_youtube_channel_url',
    'sermons_image_path',
    'bulletins_small_label',
    'bulletins_title',
    'bulletins_subtitle',
    'bulletins_image_path',
])]
class SiteSetting extends Model
{
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
}
