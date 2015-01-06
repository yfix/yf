<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/js/bootstrap-formhelpers.min.js',
			),
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/css/bootstrap-formhelpers.min.css',
				'
					[class^="bfh-flag-"], [class*="bfh-flag-"] { display: inline-block; margin-right: 5px; }
					[class^="bfh-flag-"]:empty, [class*="bfh-flag-"]:empty { width: 16px; }
					.bfh-selectbox-options a { padding-left: 10px; }
				',
			),
		),
	),
	'require' => array(
		'js' => array(
			'jquery',
			'bootstrap',
		),
	),
);
