<?php

return function() {

$bfh_css_fixes = '
	[class^="bfh-flag-"], [class*="bfh-flag-"] { display: inline-block; margin-right: 5px; }
	[class^="bfh-flag-"]:empty, [class*="bfh-flag-"]:empty { width: 16px; }
	.bfh-selectbox { max-width: 300px; }
	.bfh-selectbox-options a { padding-left: 10px; }
	a.bfh-selectbox-toggle:hover { color: #333; }
';
return [
	'versions' => [
		'master' => [
			'js' => '//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/js/bootstrap-formhelpers.min.js',
			'css' => [
				'//cdn.rawgit.com/yfix/bootstrap-form-helpers/master/dist/css/bootstrap-formhelpers.min.css',
				$bfh_css_fixes,
			],
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
	'info' => [
		'url' => 'http://bootstrapformhelpers.com/',
		'name' => 'Bootstrap Form Helpers',
		'desc' => 'Extend Bootstrap\'s components with Bootstrap Form Helpers custom jQuery plugins.',
		'git' => 'https://github.com/yfix/bootstrap-form-helpers.git',
	],
];

};