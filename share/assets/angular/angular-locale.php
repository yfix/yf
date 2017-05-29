<?php

return function() {

$locale = conf('language') ?: 'ru';
return [
	'versions' => [
		'1.2.15' => [
			'js' => '//cdnjs.cloudflare.com/ajax/libs/angular-i18n/1.2.15/angular-locale_'.$locale.'.js',
		],
	],
	'require' => [
		'js' => 'angularjs',
	],
];

};