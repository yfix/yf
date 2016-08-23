<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("save", "https://rawgit.yfix.net/ckeditor/ckeditor-releases/master/plugins/save/plugin.js");',
		],
	],
	'require' => [
		'asset' => [
			'ckeditor',
		],
	],
	'config' => [
		'no_cache' => true,
	],
];
