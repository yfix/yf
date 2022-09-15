<?php

return function () {
    return [
        'versions' => [
            '4.6.1' => [
                'css' => '//rawgit.yfix.net/twbs/bootstrap/v4.6.1/dist/css/bootstrap.min.css',
                'js' => '//rawgit.yfix.net/twbs/bootstrap/v4.6.1/dist/js/bootstrap.min.js',
            ],
        ],
        'github' => [
            'name' => 'twbs/bootstrap',
            'version' => 'v4.6.1',
            'js' => [
                'dist/js/bootstrap.min.js',
            ],
            'css' => [
                'dist/css/bootstrap.min.css',
            ],
        ],
        'require' => [
            'asset' => 'jquery',
        ],
    ];
};
