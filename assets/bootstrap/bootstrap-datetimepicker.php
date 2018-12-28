<?php

return [
    'bower' => [
        'name' => 'yfix/bootstrap-datetimepicker',
        'version' => '4.17.37',
        'js' => 'build/js/bootstrap-datetimepicker.min.js',
        'css' => 'build/css/bootstrap-datetimepicker.min.css',
    ],
    'github' => [
        'name' => 'yfix/bootstrap-datetimepicker',
        'version' => '4.17.37',
        'js' => 'build/js/bootstrap-datetimepicker.min.js',
        'css' => 'build/css/bootstrap-datetimepicker.min.css',
    ],
    'versions' => [
        '4.17.37' => [
            'js' => '//rawgit.yfix.net/yfix/bootstrap-datetimepicker/4.17.37/build/js/bootstrap-datetimepicker.min.js',
            'css' => '//rawgit.yfix.net/yfix/bootstrap-datetimepicker/4.17.37/build/css/bootstrap-datetimepicker.min.css',
        ],
    ],
    'require' => [
        'asset' => [
            'jquery',
            'momentjs',
        ],
    ],
    'add' => [
        'css' => '.bootstrap-datetimepicker-widget .picker-switch { width: 90%; }',
    ],
];
