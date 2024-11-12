<?php

return [
    'versions' => [
        'master' => [
            'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://rawgit.yfix.net/ckeditor/ckeditor4/master/plugins/lineutils/plugin.js");',
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
