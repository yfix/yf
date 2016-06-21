<?php

return [
	'versions' => [
		'1.2.0' => [
			'js' => '//cdn.rawgit.com/yfix/jquery-pnotify/1.2.0/jquery.pnotify.js',
			'css' => [
				'//cdn.rawgit.com/yfix/jquery-pnotify/1.2.0/jquery.pnotify.default.css',
				'//cdn.rawgit.com/yfix/jquery-pnotify/1.2.0/jquery.pnotify.default.icons.css',
			],
		],
		'3.0.0' => [
			'js' => '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.js',
			'css' => '//cdnjs.cloudflare.com/ajax/libs/pnotify/3.0.0/pnotify.min.css',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];
