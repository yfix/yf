<?php

return function() {

$content = <<<EOS
var __angular_modules__ = [];
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

};