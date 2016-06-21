<?php

$path = YF_PATH . 'templates/user/js/ng/uix/';
$js = [
	file_get_contents( $path . 'uix.js' ),
	file_get_contents( $path . 'uix.a.directive.js' ),
	file_get_contents( $path . 'uix.wait.directive.js' ),
	file_get_contents( $path . 'uix.animate.directive.js' ),
];

$css = <<<'EOS'
a[ng-click] {
    cursor: pointer;
}
EOS;

return [
	'versions' => [
		'master' => [
			'js' => [
				'content' => $js,
			],
			'css' => [
				'content' => $css,
			],
		],
	],
	'require' => [
		'asset' => 'ng-app',
	],
];
