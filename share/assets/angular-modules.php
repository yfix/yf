<?php

$content = <<<EOS
var __ANGULAR_MODULES__ = [];
EOS;

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'content' => $content,
			),
		),
	),
);
