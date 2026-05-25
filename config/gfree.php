<?php

return [
    'homepage' => [
        'theme' => [
            'layout' => 'editorial-color',
            'accent' => 'teal',
        ],

        'navigation' => [
            ['label' => 'New Here', 'url' => '/new-here'],
            ['label' => 'Sundays', 'url' => '/sundays'],
            ['label' => 'Ministries', 'url' => '/ministries'],
            ['label' => 'Messages', 'url' => '/messages'],
        ],

        'hero' => [
            'eyebrow' => 'Welcome home',
            'title' => 'Grace for real life.',
            'subtitle' => 'A church family in central Pennsylvania learning to follow Jesus together with clarity, care, and room for real questions.',
            'image_url' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
            'primary_label' => 'Plan a Visit',
            'primary_url' => '/new-here',
            'secondary_label' => 'Watch Live',
            'secondary_url' => '/live',
        ],

        'service_details' => [
            ['label' => 'Sunday', 'value' => '9:00 & 10:45 AM'],
            ['label' => 'Visit', 'value' => '305 Keystone Hill Road'],
            ['label' => 'Next Step', 'value' => 'Connect Card & Prayer'],
        ],

        'intro' => [
            'eyebrow' => 'Start here',
            'title' => 'Everything a guest needs without digging.',
            'body' => 'Find service details, kids check-in, ways to connect, and a direct path to the next step before you ever walk through the doors.',
        ],

        'next_steps' => [
            [
                'number' => '01',
                'title' => 'Visit Sunday',
                'summary' => 'Service times, what to expect, kids check-in, and where to go.',
                'url' => '/new-here',
            ],
            [
                'number' => '02',
                'title' => 'Find Community',
                'summary' => 'Groups, kids, students, and ways to belong beyond Sunday.',
                'url' => '/ministries',
            ],
            [
                'number' => '03',
                'title' => 'Start Serving',
                'summary' => 'A direct path into teams, prayer, events, giving, and One Church.',
                'url' => '/serve',
            ],
        ],

        'process' => [
            'eyebrow' => 'Ready to serve?',
            'title' => 'Every step matters.',
            'steps' => [
                ['title' => 'Fill out the form', 'summary' => 'Tell us where you are interested.'],
                ['title' => 'Talk with a leader', 'summary' => 'Find a team that fits your gifts.'],
                ['title' => 'Begin serving', 'summary' => 'Use what God has given you.'],
            ],
        ],

        'feature' => [
            'eyebrow' => 'Featured',
            'title' => 'One Church handles the moving parts.',
            'body' => 'Forms, event registrations, giving, and ministry signups can stay in One Church while the website stays focused on welcome, clarity, and direction.',
            'label' => 'Open One Church',
            'url' => '#',
        ],

        'updates' => [
            [
                'type' => 'Announcement',
                'title' => 'Family ministry night',
                'summary' => 'Registration open now',
                'url' => '#',
            ],
            [
                'type' => 'Message',
                'title' => 'Grace for the week ahead',
                'summary' => 'Watch the latest sermon',
                'url' => '#',
            ],
            [
                'type' => 'Ministry',
                'title' => 'Students summer schedule',
                'summary' => 'See upcoming gatherings',
                'url' => '#',
            ],
        ],
    ],
];
