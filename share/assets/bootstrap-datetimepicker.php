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
		'4.17.37' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/4.17.37/build/js/bootstrap-datetimepicker.min.js',
			),
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/4.17.37/build/css/bootstrap-datetimepicker.min.css',
				'.bootstrap-datetimepicker-widget .picker-switch { width: 90%; }',
			),
		),
	),
	'require' => array(
		'asset' => array(
			'jquery',
			'momentjs',
		),
	),
);
