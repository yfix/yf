<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("widget", "https://cdn.rawgit.com/ckeditor/ckeditor-releases/master/plugins/widget/plugin.js");',
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
