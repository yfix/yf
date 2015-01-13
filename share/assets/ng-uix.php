<?php

$path = YF_PATH . 'templates/user/js/ng/uix/';
$content = array(
	file_get_contents( $path . 'uix.js' ),
	file_get_contents( $path . 'uix.wait.directive.js' ),
	file_get_contents( $path . 'uix.animate.directive.js' ),
);

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'content' => $content,
			)
		),
	),
	'require' => array(
		'js' => 'angularjs',
	),
);
