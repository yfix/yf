<?php

return function() {

return [
	'versions' => [
		'3.3.7' => [
			'css' => [
				'//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
				! conf('bs3_no_default_theme') ? '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css' : '',
			],
			'js' => '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};