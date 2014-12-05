<?php

$locale = conf('language');
return array(
	'versions' => array(
		'3.4.6' => array(
			'js' => '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.js',
			'css' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.css',
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2_locale_'.$locale.'.min.js',
			),
		),
		'3.5.2' => array(
			'js' => '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js',
			'css' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.css',
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2_locale_'.$locale.'.min.js',
			),
		),
	),
	'require' => array(
		'js' => 'jquery',
	),
);
