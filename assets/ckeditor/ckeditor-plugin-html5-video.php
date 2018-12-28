<?php

return [
    'versions' => [
        'master' => [
            'js' => 'CKEDITOR.plugins.addExternal("video", "https://rawgit.yfix.net/yfix/ckeditor-html5-video/master/video/plugin.js");',
        ],
    ],
    'require' => [
        'asset' => 'ckeditor',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
