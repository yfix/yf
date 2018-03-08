<?php

return function() {

$lang = conf('language');
return [
	'versions' => [
		'master' => [
			'js' => [
				'//rawgit.yfix.net/yfix/bootstrap-select/master/dist/js/bootstrap-select.js',
				'//rawgit.yfix.net/yfix/bootstrap-select/master/dist/js/i18n/defaults-'.$lang.'_'.strtoupper($lang === 'en' ? 'us' : $lang).'.js',
			],
			'css' => '//rawgit.yfix.net/yfix/bootstrap-select/master/dist/css/bootstrap-select.css',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};