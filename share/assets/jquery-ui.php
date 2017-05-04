<?php

return [
	'versions' => [
		'1.11.2' => [
			'css' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.css',
			'js' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js',
		],
	],
	'cdn' => [
		'url' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/{version}/',
		'version' => '1.11.2',
		'js' => [
			'jquery-ui.min.js',
		],
		'css' => [
			'jquery-ui.min.css',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];
