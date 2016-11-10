<?php

return function() {

return [
	'versions' => [
		'3.3.7' => [
			'css' => [
				'//rawgit.yfix.net/twbs/bootstrap/v3.3.7/dist/css/bootstrap.min.css',
				! conf('bs3_no_default_theme') ? '//rawgit.yfix.net/twbs/bootstrap/v3.3.7/dist/css/bootstrap-theme.min.css' : '',
			],
			'js' => '//rawgit.yfix.net/twbs/bootstrap/v3.3.7/dist/js/bootstrap.min.js',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};