<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => 'CKEDITOR.plugins.addExternal("autosave", "https://cdn.rawgit.com/w8tcha/CKEditor-AutoSave-Plugin/master/autosave/plugin.js");',
		),
	),
	'require' => array(
		'asset' => 'ckeditor',
	),
	'config' => array(
		'no_cache' => true,
	),
);
