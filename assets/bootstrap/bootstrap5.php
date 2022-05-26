<?php

return function () {
    return [
        'versions' => [
            '5.1.3' => [
                'css' => '//rawgit.yfix.net/twbs/bootstrap/v5.1.3/dist/css/bootstrap.min.css',
                'js' => '//rawgit.yfix.net/twbs/bootstrap/v5.1.3/dist/js/bootstrap.min.js',
            ],
        ],
        'github' => [
            'name' => 'twbs/bootstrap',
            'version' => 'v5.1.3',
            'js' => [
                'dist/js/bootstrap.min.js',
            ],
            'css' => [
                'dist/css/bootstrap.min.css',
            ],
        ],
        // 'require' => [
        //     'asset' => 'jquery',
        // ],
    ];
};
