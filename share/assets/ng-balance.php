<?php

$path = YF_PATH . 'templates/user/js/ng/payment/';
$content = array(
	file_get_contents( $path . 'balance.js' ),
	file_get_contents( $path . 'balance.service.js' ),
);
return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'content' => $content,
			),
		),
	),
	'require' => array(
		'asset' => array(
			'angular-app',
		),
	),
);
