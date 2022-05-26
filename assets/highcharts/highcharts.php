<?php

return [
    'versions' => ['v10.0.0' => [
        'js' => [
            '//rawgit.yfix.net/highcharts/highcharts-dist/v10.0.0/highcharts.js',
            '//rawgit.yfix.net/highcharts/highcharts-dist/v10.0.0/themes/gray.js',
            '//rawgit.yfix.net/highcharts/highcharts-dist/v10.0.0/modules/exporting.js',
        ],
        'css' => [
//			'//rawgit.yfix.net/highcharts/highcharts-dist/v10.0.0/css/highcharts.css',
        ],
    ]],
    'github' => [
        'name' => 'highcharts/highcharts-dist',
        'version' => 'v10.0.0',
        'js' => [
            'highcharts.js',
            'themes/gray.js',
            'modules/exporting.js',
        ],
        'css' => [
//			'css/highcharts.css',
        ],
    ],
    'require' => ['asset' => 'jquery'],
    'add' => ['asset' => [
        'highcharts-export-csv',
        'highcharts-export-clientside',
    ]],
];
