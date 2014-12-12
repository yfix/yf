<?php

$lang = conf('language');
return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/bootstrap-select/master/dist/js/bootstrap-select.js',
				'//cdn.rawgit.com/yfix/bootstrap-select/master/dist/js/i18n/defaults-'.$lang.'_'.strtoupper($lang === 'en' ? 'us' : $lang).'.js',
			),
			'css' => '//cdn.rawgit.com/yfix/bootstrap-select/master/dist/css/bootstrap-select.css',
		),
	),
	'require' => array(
		'js' => 'jquery',
#		'css' => 'bootstrap',
	),
);
