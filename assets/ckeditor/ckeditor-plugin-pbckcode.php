<?php

return [
    'versions' => [
        'master' => [
            'js' => 'CKEDITOR.plugins.addExternal("pbckcode", "https://rawgit.yfix.net/prbaron/pbckcode/master/src/plugin.js");',
        ],
    ],
    'require' => [
        'asset' => 'ckeditor',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
