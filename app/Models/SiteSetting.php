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
class SiteSetting extends Model {}
