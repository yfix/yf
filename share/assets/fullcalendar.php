<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => '//cdn.rawgit.com/yfix/fullcalendar/master/dist/fullcalendar.min.css',
			'js' => array(
				'//cdn.rawgit.com/yfix/fullcalendar/master/dist/fullcalendar.min.js',
#				'//cdn.rawgit.com/yfix/fullcalendar/master/dist/gcal.js',
				'//cdn.rawgit.com/yfix/fullcalendar/master/dist/lang-all.js',
			),
		),
	),
	'require' => array(
		'js' => array(
			'jquery',
			'momentjs',
		),
	),
);
