<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'http://yfix.s3-website-eu-west-1.amazonaws.com/bfh/js/bootstrap-formhelpers-selectbox.js.gz',
			),
			'css' => array(
				'http://yfix.s3-website-eu-west-1.amazonaws.com/bfh/css/bootstrap-formhelpers.min.css.gz',
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
