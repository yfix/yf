<?php

return function() {

$content = <<<'EOS'
var __ANGULAR_MODULES__ = [];
EOS;

return [
	'versions' => [
		'master' => [
			'js' => [
				'content' => $content,
			],
		],
	],
];

};