<?php

$path = YF_PATH . 'templates/user/js/ng/uix/';
$js = array(
	file_get_contents( $path . 'uix.js' ),
	file_get_contents( $path . 'uix.a.directive.js' ),
	file_get_contents( $path . 'uix.wait.directive.js' ),
	file_get_contents( $path . 'uix.animate.directive.js' ),
);

$css = <<<'EOS'
a[ng-click] {
    cursor: pointer;
}
EOS;

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'content' => $js,
			),
			'css' => array(
				'content' => $css,
			),
		),
	),
	'require' => array(
		'asset' => 'ng-app',
	),
);
