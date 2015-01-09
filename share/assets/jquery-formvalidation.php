<?php

return function() {

$lang = conf('language');
$lang_files = array(
	'en' => 'en_US',
	'ru' => 'ru_RU',
	'ua' => 'ua_UA',
);
return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/formvalidation/formvalidation/master/dist/js/formValidation.min.js',
				$lang_files[$lang] ? '//cdn.rawgit.com/formvalidation/formvalidation/master/dist/js/language/'.$lang_files[$lang].'.js' : '',
			),
			'css' => '//cdn.rawgit.com/formvalidation/formvalidation/master/dist/css/formValidation.min.css',
		),
	),
	'require' => array(
		'asset' => array(
			'jquery',
		),
	),
);

};