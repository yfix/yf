<?php

return [
	'versions' => [
		'2.7.1' => [
			'css' => '//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/fullcalendar.min.css',
			'js' => [
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/fullcalendar.min.js',
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/gcal.js',
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/lang-all.js',
			],
		],
	],
	'require' => [
		'js' => [
			'jquery',
			'momentjs',
		],
	],
	'info' => [
		'url' => 'http://fullcalendar.io/',
		'name' => 'FullCalendar',
		'desc' => 'A JavaScript event calendar. Customizable and open source. 
			FullCalendar is a drag-n-drop jQuery plugin for displaying events on a full-sized calendar.',
		'git' => 'https://github.com/yfix/fullcalendar.git',
	],
];
