<?php

return function() {

return array(
	'versions' => array(
		'3.3.2' => array(
			'css' => array(
				'//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css',
				! conf('bs3_no_default_theme') ? '//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css' : '',
			),
			'js' => '//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js',
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);

};