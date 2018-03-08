<?php

return [
	'versions' => [
		'fixed' => [
			'js' => 'CKEDITOR.plugins.addExternal("autosave", "https://rawgit.yfix.net/w8tcha/CKEditor-AutoSave-Plugin/beeb157cf4a8e889646762470d0e966ffddcfb9a/autosave/plugin.js");',
		],
	],
	'require' => [
		'asset' => 'ckeditor',
	],
	'config' => [
		'no_cache' => true,
	],
];
