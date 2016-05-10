<?php

return array(
	'versions' => array(
		'2.7.1' => array(
			'css' => '//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/fullcalendar.min.css',
			'js' => array(
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/fullcalendar.min.js',
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/gcal.js',
				'//cdn.rawgit.com/yfix/fullcalendar/v2.7.1/dist/lang-all.js',
			),
		),
	),
	'require' => array(
		'js' => array(
			'jquery',
			'momentjs',
		),
	),
	'info' => array(
		'url' => 'http://fullcalendar.io/',
		'name' => 'FullCalendar',
		'desc' => 'A JavaScript event calendar. Customizable and open source. 
			FullCalendar is a drag-n-drop jQuery plugin for displaying events on a full-sized calendar.',
		'git' => 'https://github.com/yfix/fullcalendar.git',
	),
);
