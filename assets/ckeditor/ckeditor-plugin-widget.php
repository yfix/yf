<?php

return [
    'versions' => [
        'master' => [
            'js' => 'CKEDITOR.plugins.addExternal("widget", "https://rawgit.yfix.net/ckeditor/ckeditor4/master/plugins/widget/plugin.js");',
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
