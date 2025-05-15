<?php

return [
    'templates' => [
        'standard' => [
            'name'   => 'Standard',
            'view'   => 'emails.newsletter.standard',
            'fields' => ['subject','header_text','body_text'],
        ],
        'with_image' => [
            'name'   => 'With Image',
            'view'   => 'emails.newsletter.with_image',
            'fields' => ['subject','header_text','image_url','body_text','cta_url','cta_text'],
        ],
        // add more templates as needed
    ],
];
