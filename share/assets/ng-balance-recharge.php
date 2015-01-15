<?php

$path = YF_PATH . 'templates/user/js/ng/payment/';
$content = array(
	file_get_contents( $path . 'balance-recharge.js' ),
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
			'ng-balance',
			'ng-uix',
		),
	),
);
