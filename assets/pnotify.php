<?php

return [
    'versions' => [
        '3.0.0' => [
            'js' => [
                '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.buttons.min.js',
                '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.confirm.min.js',
            ],
            'css' => [
                '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.css',
                '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.buttons.min.css',
            ],
        ],
    ],
    'cdn' => [
        'url' => '//cdnjs.cloudflare.com/ajax/libs/pnotify/{version}/',
        'version' => '3.0.0',
        'js' => [
            'pnotify.min.js',
            'pnotify.buttons.min.js',
            'pnotify.confirm.min.js',
        ],
        'css' => [
            'pnotify.min.css',
            'pnotify.buttons.min.css',
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
