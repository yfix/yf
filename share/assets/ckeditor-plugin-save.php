<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("save", "https://cdn.rawgit.com/ckeditor/ckeditor-releases/master/plugins/save/plugin.js");',
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
