<?php

return function() {

$lang = conf('language');
return [
	'versions' => [
		'master' => [
			'js' => [
				'//cdn.rawgit.com/yfix/bootstrap-select/master/dist/js/bootstrap-select.js',
				'//cdn.rawgit.com/yfix/bootstrap-select/master/dist/js/i18n/defaults-'.$lang.'_'.strtoupper($lang === 'en' ? 'us' : $lang).'.js',
			],
			'css' => '//cdn.rawgit.com/yfix/bootstrap-select/master/dist/css/bootstrap-select.css',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};