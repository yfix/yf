<?php

return [
    'versions' => [
        '1.0.2' => [
            'js' => [
                '//oss.maxcdn.com/icheck/1.0.2/icheck.min.js',
                '$(function(){
					$("input").iCheck({
						checkboxClass: "icheckbox_square",
						radioClass: "iradio_square"
					});
				})',
            ],
            'css' => [
                '//oss.maxcdn.com/icheck/1.0.2/skins/square/square.min.css',
            ],
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
