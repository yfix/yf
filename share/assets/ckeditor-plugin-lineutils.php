<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://cdn.rawgit.com/ckeditor/ckeditor-releases/master/plugins/lineutils/plugin.js");',
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
