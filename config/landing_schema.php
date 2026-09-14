<?php

/**
 * Single source of truth for the media placements each landing section
 * expects.
 *
 * Kept in sync with resources/js/lib/landingSchema.js. When you add a
 * new getMedia(...) call to a Welcome/* component, mirror the key here
 * so the heal migration (and any future healer) can re-create the slot
 * with a safe placeholder if it ever goes missing — never overwriting
 * a row that already exists.
 *
 * Shape:
 *   'section_key' => [
 *       'static' => [
 *           'media_key' => ['original_path' => '/path.jpg', 'mime_type' => 'image/jpeg'],
 *           ...
 *       ],
 *       // Dynamic slots derived from a content array. For every existing
 *       // `<array_name>.<i>.*` content_key, we ensure the corresponding
 *       // media_key exists.
 *       'dynamic' => [
 *           [
 *               'array' => 'partners',
 *               'key' => 'partner_{i}_logo',
 *               'default' => '/legacy/assets/Tena-logo-square.jpg',
 *               'mime_type' => 'image/jpeg',
 *           ],
 *       ],
 *   ]
 */

$placeholderLogo = '/legacy/assets/Tena-logo-square.jpg';

return [
    'hero' => [
        'static' => [
            'main_image' => ['original_path' => '/legacy/img/hero-slider-1.jpg', 'mime_type' => 'image/jpeg'],
        ],
        'dynamic' => [
            ['array' => 'features', 'key' => 'feature_{i}_image', 'default' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg', 'mime_type' => 'image/jpeg'],
        ],
    ],

    'features' => [
        'static' => [],
        'dynamic' => [
            ['array' => 'items', 'key' => 'item_{i}_image', 'default' => '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg', 'mime_type' => 'image/jpeg'],
        ],
    ],

    'credibility' => [
        'static' => [
            'main_image' => ['original_path' => '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg', 'mime_type' => 'image/jpeg'],
            'stay_awhile_logo' => ['original_path' => $placeholderLogo, 'mime_type' => 'image/jpeg'],
        ],
        'dynamic' => [],
    ],

    'how_it_works' => [
        'static' => [],
        'dynamic' => [
            ['array' => 'steps', 'key' => 'step_{i}_image', 'default' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg', 'mime_type' => 'image/jpeg'],
        ],
    ],

    'problem' => [
        'static' => [
            'image_0' => ['original_path' => '/legacy/assets/Tena-Landing/Problem-1.jpg', 'mime_type' => 'image/jpeg'],
            'image_1' => ['original_path' => '/legacy/assets/Tena-Landing/Problem-2.jpg', 'mime_type' => 'image/jpeg'],
            'image_2' => ['original_path' => '/legacy/assets/Tena-Landing/Problem-3.jpg', 'mime_type' => 'image/jpeg'],
        ],
        'dynamic' => [],
    ],

    'detailed_features' => [
        'static' => [],
        'dynamic' => [
            ['array' => 'sections', 'key' => 'section_{i}_image', 'default' => '/legacy/assets/Tena-Landing/Tena-Hero-1.jpg', 'mime_type' => 'image/jpeg'],
        ],
    ],

    'media_showcase' => [
        'static' => [
            'showcase_media' => ['original_path' => '/legacy/assets/Tena-Landing/Step-1-Connect.jpg', 'mime_type' => 'image/jpeg'],
        ],
        'dynamic' => [],
    ],

    'partners' => [
        'static' => [],
        'dynamic' => [
            ['array' => 'partners', 'key' => 'partner_{i}_logo', 'default' => $placeholderLogo, 'mime_type' => 'image/jpeg'],
        ],
    ],

    'pricing' => [
        'static' => [],
        'dynamic' => [],
    ],

    'roi_calculator' => [
        'static' => [],
        'dynamic' => [],
    ],
];
