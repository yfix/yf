<?php

return [
	'versions' => ['v5.0.10' => [
		'js' => [
			'//rawgit.yfix.net/highcharts/highcharts-dist/v5.0.10/highcharts.js',
			'//rawgit.yfix.net/highcharts/highcharts-dist/v5.0.10/js/themes/gray.js',
			'//rawgit.yfix.net/highcharts/highcharts-dist/v5.0.10/js/modules/exporting.js',
		],
		'css' => [
#			'//rawgit.yfix.net/highcharts/highcharts-dist/v5.0.10/css/highcharts.css',
		],
	]],
	'github' => [
		'name' => 'highcharts/highcharts-dist',
		'version' => 'v5.0.10',
		'js' => [
			'highcharts.js',
			'js/themes/gray.js',
			'js/modules/exporting.js',
		],
		'css' => [
#			'css/highcharts.css',
		],
	],
	'require' => ['asset' => 'jquery'],
	'add' => ['asset' => [
		'highcharts-export-csv',
		'highcharts-export-clientside',
	]],
];
