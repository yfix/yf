<?php

return [
    'versions' => [
        '4.4.x' => [
            'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://rawgit.yfix.net/ckeditor/ckeditor4-releases/4.4.x/plugins/save/plugin.js");',
        ],
        '4.7.x' => [
            'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://rawgit.yfix.net/ckeditor/ckeditor4-releases/4.7.x/plugins/save/plugin.js");',
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
