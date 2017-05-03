<?php

return [
	'versions' => [
		'1.2.0' => [
			'js' => '//rawgit.yfix.net/yfix/jquery-pnotify/1.2.0/jquery.pnotify.js',
			'css' => [
				'//rawgit.yfix.net/yfix/jquery-pnotify/1.2.0/jquery.pnotify.default.css',
				'//rawgit.yfix.net/yfix/jquery-pnotify/1.2.0/jquery.pnotify.default.icons.css',
			],
		],
		'3.0.0' => [
			'js' => [
				'//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.buttons.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.confirm.min.js',
			],
			'css' => [
				'//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.css',
				'//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.buttons.min.css',
			],
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];
