<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/master/build/js/bootstrap-datetimepicker.min.js',
			),
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-datetimepicker/master/build/css/bootstrap-datetimepicker.min.css',
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
