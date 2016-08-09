<?php

return [
	'versions' => [
		'1.0' => [
			'js' => '//cdn.jsdelivr.net/bootstrap.daterangepicker/1/daterangepicker.js',
			'css' => '//cdn.jsdelivr.net/bootstrap.daterangepicker/1/daterangepicker-bs3.css',
		],
		'master' => [
			'js' => '//rawgit.yfix.net/yfix/bootstrap-daterangepicker/master/daterangepicker.js',
			'css' => '//rawgit.yfix.net/yfix/bootstrap-daterangepicker/master/daterangepicker-bs3.css',
		],
	],
	'require' => [
		'asset' => [
			'jquery',
			'momentjs',
		],
	],
];
