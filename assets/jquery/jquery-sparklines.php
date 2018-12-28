<?php

return [
    'versions' => [
        '2.1.2' => [
            'js' => '//cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js',
        ],
    ],
    'cdn' => [
        'url' => '//cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/{version}/',
        'version' => '2.1.2',
        'js' => 'jquery.sparkline.min.js',
    ],
    'add' => [
        'css' => '
			.jqstooltip { -webkit-box-sizing: content-box; -moz-box-sizing: content-box; box-sizing: content-box; }
		',
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
