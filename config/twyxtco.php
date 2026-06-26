<?php

return [
    'homepage' => [
        'theme' => [
            'layout' => 'editorial-color',
            'accent' => 'teal',
        ],

        'navigation' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Contact', 'url' => '/contact'],
        ],

        'hero' => [
            'eyebrow' => 'Welcome',
            'title' => 'Welcome to our site.',
            'subtitle' => 'We\'re glad you made it!',
            'image_url' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
            'primary_label' => 'Plan a Visit',
            'primary_url' => '/',
            'secondary_label' => 'Shop our Store',
            'secondary_url' => '/store',
        ],

        'service_details' => [
            ['label' => 'Hours', 'value' => '9:00 AM - 5:00 PM'],
            ['label' => 'Visit', 'value' => '123 Pine St, Town, ST 12345'],
        ],

        'intro' => [
            'eyebrow' => 'Start here',
            'title' => 'Everything a customer needs without digging.',
            'body' => 'Find store details, ways to connect, and sales information.',
        ],

        'next_steps' => [
            [
                'number' => '01',
                'title' => 'Visit Us',
                'summary' => 'Store Hours, Sales, and ways to reach out to us.',
                'url' => '/store',
            ],
            [
                'number' => '02',
                'title' => 'Buy Stuff',
                'summary' => 'Shop our store and find good stuff to buy!',
                'url' => '/store',
            ],
        ],

        'process' => [
            'eyebrow' => 'Looking for Sales?',
            'title' => 'We have great sales regularly.',
            'steps' => [
                ['title' => 'Talk with a representative', 'summary' => 'Find the perfect stuff for your place.'],
            ],
        ],

        'feature' => [
            'eyebrow' => 'Featured',
            'title' => 'One business, lots of options.',
            'body' => 'Shope till you drop, supersale coming and here and gone!',
            'label' => 'Open Information',
            'url' => '#',
        ],
    ],
];
