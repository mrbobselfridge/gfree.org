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
])]
class SiteSetting extends Model
{
}
