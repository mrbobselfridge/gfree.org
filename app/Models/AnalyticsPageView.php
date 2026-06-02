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
    'viewed_at',
])]
class AnalyticsPageView extends Model
{
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }
}
