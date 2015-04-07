<?php

return function() {

$bfh_css_fixes = '
	[class^="bfh-flag-"], [class*="bfh-flag-"] { display: inline-block; margin-right: 5px; }
	[class^="bfh-flag-"]:empty, [class*="bfh-flag-"]:empty { width: 16px; }
	.bfh-selectbox { max-width: 300px; }
	.bfh-selectbox-options a { padding-left: 10px; }
';
return array(
	'versions' => array(
		'master' => array(
			'js' => '//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/js/bootstrap-formhelpers.min.js',
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/css/bootstrap-formhelpers.min.css',
				$bfh_css_fixes,
			),
		),
		'2.3.0' => array(
			'js' => '//cdn.rawgit.com/yfix/bootstrap-form-helpers/2.3.0/dist/js/bootstrap-formhelpers.min.js',
			'css' => array(
				'//cdn.rawgit.com/yfix/bootstrap-form-helpers/2.3.0/dist/css/bootstrap-formhelpers.min.css',
				$bfh_css_fixes,
			),
		),
	),
	'require' => array(
		'asset' => array(
			'jquery',
#			'bootstrap-theme',
		),
	),
	'info' => array(
		'url' => 'http://bootstrapformhelpers.com/',
		'name' => 'Bootstrap Form Helpers',
		'desc' => 'Extend Bootstrap\'s components with Bootstrap Form Helpers custom jQuery plugins.',
		'git' => 'https://github.com/yfix/bootstrap-form-helpers.git',
	),
);

};