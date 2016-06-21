<?php

return [
	'versions' => [
		'1.0' => [
			'js' => '//cdn.jsdelivr.net/bootstrap.daterangepicker/1/daterangepicker.js',
			'css' => '//cdn.jsdelivr.net/bootstrap.daterangepicker/1/daterangepicker-bs3.css',
		],
		'master' => [
			'js' => '//cdn.rawgit.com/yfix/bootstrap-daterangepicker/master/daterangepicker.js',
			'css' => '//cdn.rawgit.com/yfix/bootstrap-daterangepicker/master/daterangepicker-bs3.css',
		],
	],
	'require' => [
		'asset' => [
			'jquery',
			'momentjs',
		],
	],
];
