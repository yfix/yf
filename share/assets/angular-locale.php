<?php

$locale = conf('language');
return array(
	'versions' => array(
		'1.2.20' => array(
			'js' => '//cdnjs.cloudflare.com/ajax/libs/angular-i18n/1.2.15/angular-locale_'.$locale.'.js',
		),
	),
	'require' => array(
		'js' => 'angularjs',
	),
);
