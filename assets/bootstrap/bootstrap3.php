<?php

return function () {
    return [
    'versions' => [
        '3.4.1' => [
            'css' => [
                '//rawgit.yfix.net/twbs/bootstrap/v3.4.1/dist/css/bootstrap.min.css',
                ! conf('bs3_no_default_theme') ? '//rawgit.yfix.net/twbs/bootstrap/v3.4.1/dist/css/bootstrap-theme.min.css' : '',
            ],
            'js' => '//rawgit.yfix.net/twbs/bootstrap/v3.4.1/dist/js/bootstrap.min.js',
        ],
    ],
    'github' => [
        'name' => 'twbs/bootstrap',
        'version' => 'v3.4.1',
        'js' => [
            'dist/js/bootstrap.min.js',
        ],
        'css' => [
            'dist/css/bootstrap.min.css',
            ! conf('bs3_no_default_theme') ? 'dist/css/bootstrap-theme.min.css' : '',
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
};
