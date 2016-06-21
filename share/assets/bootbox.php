<?php

return [
	'versions' => [
		'4.2.0' => [
		//	'js' => '//oss.maxcdn.com/bootbox/4.2.0/bootbox.min.js',
			'js' => '//cdn.rawgit.com/yfix/bootbox/yyv/bootbox.js',
		],
	],
	'require' => [
		'asset' => [
			'jquery',
			'bootstrap-theme',
		],
	],
	'info' => [
		'url' => 'http://bootboxjs.com/',
		'name' => 'Bootbox.js - alert, confirm and flexible modal dialogs for the Bootstrap framework',
		'desc' => 'Bootbox.js is a small JavaScript library which allows you to create programmatic dialog boxes using Bootstrap modals, without having to worry about creating, 
			managing or removing any of the required DOM elements or JS event handlers.',
		'git' => 'https://github.com/yfix/bootbox.git',
	],
];
