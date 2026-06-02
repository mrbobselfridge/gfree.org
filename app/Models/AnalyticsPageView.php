<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'url',
    'path',
    'route_name',
    'page_title',
    'referrer_url',
    'referrer_domain',
    'user_agent',
    'browser',
    'platform',
    'device_type',
    'ip_hash',
    'visitor_hash',
    'session_hash',
    'country_code',
    'country_name',
    'region_code',
    'region_name',
    'city_name',
    'postal_code',
    'timezone',
    'latitude',
    'longitude',
    'location_driver',
    'viewed_at',
])]
class AnalyticsPageView extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'viewed_at' => 'datetime',
        ];
    }
}
