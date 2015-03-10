<?php

return function() {

$lang = conf('language');
return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/jquery-timeago/master/jquery.timeago.js',
				$lang && $lang != 'en' ? '//cdn.rawgit.com/yfix/jquery-timeago/master/locales/jquery.timeago.'.$lang.'.js' : '',
			),
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);

};