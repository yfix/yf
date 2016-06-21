<?php

return [
	'versions' => [
		'master' => [
			'js' => 'CKEDITOR.plugins.addExternal("youtube", "https://cdn.rawgit.com/fonini/ckeditor-youtube-plugin/master/youtube/plugin.js");',
		],
	],
	'require' => [
		'asset' => 'ckeditor',
	],
	'config' => [
		'no_cache' => true,
	],
];
