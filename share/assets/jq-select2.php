<?php

return function() {

$locale = conf('language') ?: 'ru';
return [
	'versions' => [
		'3.5.2' => [
			'js' => [
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js',
				$locale !== 'en' ? '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2_locale_'.$locale.'.min.js' : '',
			],
			'css' => [
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.css',
				// CSS to make Select2 fit in with Bootstrap 3.x
				'//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2-bootstrap.min.css',
			],
		],
	],
	'require' => [
		'asset' => 'jquery',
		'asset' => 'jq-select2-fix',
	],
];

};
