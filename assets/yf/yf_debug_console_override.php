<?php

return DEBUG_MODE ? function () {
    $assets = _class('assets');
    $bs2_slate_css = '//netdna.bootstrapcdn.com/bootswatch/2.3.2/slate/bootstrap.min.css';
    return [
        'versions' => ['master' => [
            'js' => [
                'content' => '
					var debug_console_override_head = [
						\'<l\' + \'ink href="' . $bs2_slate_css . '" rel="stylesheet">\',
						\'<l\' + \'ink href="' . $assets->get_asset('font-awesome3', 'css') . '" rel="stylesheet">\',
						\'<sc\' + \'ript src="' . $assets->get_asset('jquery', 'js') . '"></sc\' + \'ript>\',
						\'<sc\' + \'ript src="' . $assets->get_asset('bootstrap2', 'js') . '"></sc\' + \'ript>\'
					];
				',
                'params' => [
                    'class' => 'yf_debug_console_asset',
                ],
            ],
        ]],
        'config' => [
            'no_cache' => true,
        ],
    ];
} : null;
