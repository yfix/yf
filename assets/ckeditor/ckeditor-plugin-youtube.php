<?php

return [
    'versions' => [
        'v2.1.19' => [
            'js' => 'CKEDITOR.plugins.addExternal("youtube", "https://rawgit.yfix.net/fonini/ckeditor-youtube-plugin/v2.1.19/youtube/plugin.js");',
        ],
    ],
    'require' => [
        'asset' => 'ckeditor',
    ],
    'config' => [
        'no_cache' => true,
    ],
];
