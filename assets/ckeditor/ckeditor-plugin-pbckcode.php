<?php

return [
    'versions' => [
        'v1.2.5' => [
            'js' => 'CKEDITOR.plugins.addExternal("pbckcode", "https://rawgit.yfix.net/prbaron/pbckcode/v1.2.5/src/plugin.js");',
        ],
    ],
    'require' => [
        'asset' => 'ckeditor',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
