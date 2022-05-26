<?php

return [
    'versions' => [
        '4.4.7' => [
            'js' => [
                '//cdn.ckeditor.com/4.4.7/full/ckeditor.js',
                '//cdn.ckeditor.com/4.4.7/full/adapters/jquery.js',
            ],
        ],
        '4.7.3' => [
            'js' => [
                '//cdn.ckeditor.com/4.7.3/full/ckeditor.js',
                '//cdn.ckeditor.com/4.7.3/full/adapters/jquery.js',
            ],
        ],
        // '4.18.0' => [
        //     'js' => [
        //         '//cdn.ckeditor.com/4.18.0/full/ckeditor.js',
        //         '//cdn.ckeditor.com/4.18.0/full/adapters/jquery.js',
        //     ],
        // ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
