<?php

return [
    'about' => [
        'title' => 'About Azveria Online',
        'subtitle' => 'A browser-based strategy sandbox for managing nations, resources, diplomacy, and live communications.',
        'website_version' => '1.1.0 Beta',
        'game_version' => 'Azveria Ruleset v1',
        'admin' => 'Isaac',
        'developer' => 'Dakota',
        'sections' => [
            [
                'heading' => 'What It Is',
                'body' => 'Azveria Online combines persistent nation management, terrain tracking, admin tooling, time progression, shop systems, and real-time announcements and chat.',
            ],
            [
                'heading' => 'Realtime Scope',
                'body' => 'WebSocket delivery is used for announcements and chat only. All state changes still flow through the Laravel API first.',
            ],
            [
                'heading' => 'Operational Notes',
                'body' => 'This deployment is designed to run cleanly through Docker on both local and remote hosts, including SSH-backed Docker contexts.',
            ],
        ],
    ],
];