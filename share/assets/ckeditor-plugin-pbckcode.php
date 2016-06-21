<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("pbckcode", "https://cdn.rawgit.com/prbaron/pbckcode/master/plugin.js");',
		],
	],
	'require' => [
		'asset' => 'ckeditor',
	],
	'config' => [
		'no_cache' => true,
	],
];
