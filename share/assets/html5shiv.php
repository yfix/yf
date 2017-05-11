<?php

return [
	'versions' => [
		'3.7.2' => [
			'js' => '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js',
		],
	],
	'cdn' => [
		'url' => '//cdnjs.cloudflare.com/ajax/libs/html5shiv/{version}/',
		'version' => '3.7.2',
		'js' => 'html5shiv.min.js',
	],
	'config' => [
		'before' => '<!--[if lt IE 9]>',
		'after' => '<![endif]-->',
	],
];
