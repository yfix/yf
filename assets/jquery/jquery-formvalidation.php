<?php

return function() {

$lang = conf('language');
$lang_files = [
	'en' => 'en_US',
	'ru' => 'ru_RU',
	'ua' => 'ua_UA',
];
return [
	'versions' => [
		'master' => [
			'js' => [
				'//rawgit.yfix.net/formvalidation/formvalidation/master/dist/js/formValidation.min.js',
				'//rawgit.yfix.net/formvalidation/formvalidation/master/dist/js/framework/bootstrap.min.js',
				$lang_files[$lang] ? '//rawgit.yfix.net/formvalidation/formvalidation/master/dist/js/language/'.$lang_files[$lang].'.js' : '',
			],
			'css' => '//rawgit.yfix.net/formvalidation/formvalidation/master/dist/css/formValidation.min.css',
		],
	],
	'require' => [
		'asset' => 'jquery',
	],
];

};