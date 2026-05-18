<?php
/**
 * manifest.json - PWA Configuration
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$manifest = [
    'lang' => 'uk-UA',
    'dir' => 'ltr',
    'name' => 'ACTION: Прозора Спілка',
    'short_name' => 'Прозора Спілка',
    'description' => 'Електронний кабінет члена профспілкової організації',
    'start_url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/index.php',
    'scope' => '/',
    'display' => 'standalone',
    'orientation' => 'portrait',
    'background_color' => '#ffffff',
    'theme_color' => '#3b5bdb',
    'icons' => [
        [
            'src' => '/assets/icon-192.png',
            'type' => 'image/png',
            'sizes' => '192x192',
            'purpose' => 'any'
        ],
        [
            'src' => '/assets/icon-512.png',
            'type' => 'image/png',
            'sizes' => '512x512',
            'purpose' => 'any maskable'
        ]
    ],
    'screenshots' => [
        [
            'src' => '/assets/screenshot-540.png',
            'type' => 'image/png',
            'sizes' => '540x720',
            'form_factor' => 'narrow'
        ],
        [
            'src' => '/assets/screenshot-1280.png',
            'type' => 'image/png',
            'sizes' => '1280x720',
            'form_factor' => 'wide'
        ]
    ],
    'categories' => ['productivity', 'business'],
    'shortcuts' => [
        [
            'name' => 'Голосування',
            'short_name' => 'Голосувати',
            'description' => 'Брати участь у голосуванні',
            'url' => '/index.php?section=polls',
            'icons' => [[
                'src' => '/assets/icon-96.png',
                'sizes' => '96x96'
            ]]
        ]
    ],
    'prefer_related_applications' => false,
    'share_target' => [
        'action' => '/share',
        'method' => 'POST',
        'enctype' => 'multipart/form-data',
        'params' => [
            'title' => 'title',
            'text' => 'text',
            'url' => 'url'
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
