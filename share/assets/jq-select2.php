<?php

return function() {

$locale = conf('language') ?: 'ru';
return array(
	'versions' => array(
		'3.4.6' => array(
			'js' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.js',
				$locale !== 'en' ? '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2_locale_'.$locale.'.min.js' : '',
			),
			'css' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.css',
				// CSS to make Select2 fit in with Bootstrap 3.x
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2-bootstrap.min.css',
			),
		),
		'3.5.2' => array(
			'js' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js',
				$locale !== 'en' ? '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2_locale_'.$locale.'.min.js' : '',
			),
			'css' => array(
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.css',
				// CSS to make Select2 fit in with Bootstrap 3.x
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2-bootstrap.min.css',
			),
		),
	),
	'require' => array(
		'js' => 'jquery',
	),
);

};
