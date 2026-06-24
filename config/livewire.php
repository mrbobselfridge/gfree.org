<?php

return [
    'temporary_file_upload' => [
        'rules' => ['required', 'file', 'max:102400'],
        'max_upload_time' => 10,
    ],

    'payload' => [
        'max_size' => 1024 * 1024,
        'max_nesting_depth' => 30,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
