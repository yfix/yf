<?php

return function() {

$path = YF_PATH . 'templates/user/js/ng/payment/';
$content = [
	file_get_contents( $path . 'balance.js' ),
	file_get_contents( $path . 'balance.service.js' ),
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
		],
	],
];

};