<?php

return [
	'versions' => [
		'1.4.1' => [
			'js' => '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js',
		],
	],
	'cdn' => [
		'url' => '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/{version}/',
		'version' => '1.4.1',
		'js' => 'jquery.cookie.min.js',
	],
	'require' => [
		'asset' => 'jquery',
	],
];
