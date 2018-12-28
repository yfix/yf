<?php

return [
    'versions' => [
        '1.11.2' => [
            'css' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.css',
            'js' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js',
        ],
    ],
    'cdn' => [
        'url' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/{version}/',
        'version' => '1.11.2',
        'js' => [
            'jquery-ui.min.js',
        ],
        'css' => [
            'jquery-ui.min.css',
            'images/ui-bg_highlight-soft_100_eeeeee_1x100.png',
            'images/ui-bg_gloss-wave_35_f6a828_500x100.png',
            'images/ui-bg_glass_100_f6f6f6_1x400.png',
            'images/ui-bg_glass_100_fdf5ce_1x400.png',
            'images/ui-bg_glass_65_ffffff_1x400.png',
            'images/ui-bg_highlight-soft_75_ffe45c_1x100.png',
            'images/ui-bg_diagonals-thick_18_b81900_40x40.png',
            'images/ui-icons_222222_256x240.png',
            'images/ui-icons_ffffff_256x240.png',
            'images/ui-icons_ef8c08_256x240.png',
            'images/ui-icons_ef8c08_256x240.png',
            'images/ui-icons_ef8c08_256x240.png',
            'images/ui-icons_228ef1_256x240.png',
            'images/ui-icons_ffd27a_256x240.png',
            'images/ui-bg_diagonals-thick_20_666666_40x40.png',
            'images/ui-bg_flat_10_000000_40x100.png',
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
