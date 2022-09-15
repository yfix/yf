<?php

return [
    'versions' => [
        'master' => [
            'js' => '
				CKEDITOR.dtd.$removeEmpty["span"] = false;
				CKEDITOR.plugins.addExternal("fontawesome", "https://rawgit.yfix.net/yfix/ckeditor-fontawesome4/master/fontawesome/plugin.js");
//				CKEDITOR.config.contentsCss = "//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css",
			',
        ],
    ],
    'require' => [
        'asset' => [
            'font-awesome4',
            'ckeditor-plugin-widget',
            'ckeditor-plugin-lineutils',
            'ckeditor',
        ],
    ],
    'config' => [
        'no_cache' => true,
    ],
];
