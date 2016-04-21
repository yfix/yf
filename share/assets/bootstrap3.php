<?php

return function() {

return array(
	'versions' => array(
		'3.3.6' => array(
			'css' => array(
				'//netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
				! conf('bs3_no_default_theme') ? '//netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css' : '',
			),
			'js' => '//netdna.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);

};