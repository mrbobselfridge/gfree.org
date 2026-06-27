<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'youtube' => [
        'sermons_channel_id' => env('YOUTUBE_SERMONS_CHANNEL_ID', 'UCDDrEtN3XPxVE9-oY008IYA'),
    ],

    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
        'api_url' => env('UNSPLASH_API_URL', 'https://api.unsplash.com'),
    ],

    'openai' => [
        'api_key' => null,
        'content_model' => env('OPENAI_CONTENT_MODEL', 'gpt-5-nano'),
        'file_extraction_model' => env('OPENAI_FILE_EXTRACTION_MODEL', 'gpt-5.4-mini'),
        'file_extraction_reasoning_effort' => env('OPENAI_FILE_EXTRACTION_REASONING_EFFORT', 'low'),
        'file_extraction_text_verbosity' => env('OPENAI_FILE_EXTRACTION_TEXT_VERBOSITY', 'low'),
        'file_extraction_max_output_tokens' => env('OPENAI_FILE_EXTRACTION_MAX_OUTPUT_TOKENS', 12000),
    ],

    'page_visual_snapshot' => [
        'node_binary' => env('PAGE_VISUAL_SNAPSHOT_NODE_BINARY', 'node'),
    ],

];
