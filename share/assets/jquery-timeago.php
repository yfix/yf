<?php

return function() {

$lang = conf('language');
return [
	'versions' => [
		'master' => [
			'js' => [
				'//cdn.rawgit.com/yfix/jquery-timeago/master/jquery.timeago.js',
				$lang && $lang != 'en' ? '//cdn.rawgit.com/yfix/jquery-timeago/master/locales/jquery.timeago.'.$lang.'.js' : '',
			],
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};