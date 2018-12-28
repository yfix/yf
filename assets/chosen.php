<?php

return [
    'cdn' => [
        'url' => '//cdnjs.cloudflare.com/ajax/libs/{name}/{version}/',
        'version' => '1.7.0',
        'js' => 'chosen.jquery.min.js',
        'css' => [
            'chosen.min.css',
            'chosen-sprite.png',
        ],
    ],
    'versions' => [
        '1.7.0' => [
            'js' => '//cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.jquery.min.js',
            'css' => '//cdnjs.cloudflare.com/ajax/libs/chosen/1.7.0/chosen.min.css',
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
