<?php

return array(
	'css' => array(
		'jquery-ui'	=> array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.1/css/jquery-ui.min.css',
			'version' => '1.11.1',
		),
		'angular-ui'=> array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/angular-ui/0.4.0/angular-ui.min.css',
			'version' => '0.4.0',
		),
		'bs2' => array(
			'url' => '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css',
			'version' => '2.3.2',
		),
		'bs3' => array(
			'url' => '//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css',
			'version' => '3.2.0',
		),
	),
	'js' => array(
		'jquery'	=> array(
			'url' => '//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js',
			'version' => '1.11.1',
		),
		'jquery-ui'	=> array(
			'url' => '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js',
			'require' => 'jquery',
			'version' => '1.11.1',
		),
		'jquery-cookie' => array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js',
			'require' => 'jquery',
			'version' => '1.4.1',
		),
		'bs2'		=> array(
			'url' => '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js',
			'require' => 'jquery',
			'version' => '2.3.2',
		),
		'bs3'		=> array(
			'url' => '//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js',
			'require' => 'jquery',
			'version' => '3.2.0',
		),
#		'html5shiv'	=> '<!--[if lt IE 9]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js" class="yf_core"></script><![endif]-->',
	),
);
