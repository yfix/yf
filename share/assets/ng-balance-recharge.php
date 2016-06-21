<?php

$path = YF_PATH . 'templates/user/js/ng/payment/';
$content = [
	file_get_contents( $path . 'balance-recharge.js' ),
];

return [
	'versions' => [
		'master' => [
			'js' => [
				'content' => $content,
			],
		],
	],
	'require' => [
		'asset' => [
			'ng-app',
			'ng-balance',
			'ng-uix',
		],
	],
];
