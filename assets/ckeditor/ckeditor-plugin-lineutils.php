<?php

return [
    'versions' => [
        '4.4.x' => [
            'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://rawgit.yfix.net/ckeditor/ckeditor4-releases/4.4.x/plugins/lineutils/plugin.js");',
        ],
        '4.7.x' => [
            'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://rawgit.yfix.net/ckeditor/ckeditor4-releases/4.7.x/plugins/lineutils/plugin.js");',
        ],
    ],
    'require' => [
        'asset' => [
            'ckeditor',
        ],
    ],
    'config' => [
        'no_cache' => true,
    ],
];
