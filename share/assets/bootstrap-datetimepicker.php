<?php

return array(
	'versions' => array(
		'v4.0.0' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/v4.0.0/build/js/bootstrap-datetimepicker.min.js',
			),
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/v4.0.0/build/css/bootstrap-datetimepicker.min.css',
				'.bootstrap-datetimepicker-widget .picker-switch { width: 90%; }',
			),
		),
	),
	'require' => array(
		'js' => array(
			'jquery',
#			'bootstrap',
			'momentjs',
		),
		'css' => array(
#			'bootstrap',
		),
	),
);
