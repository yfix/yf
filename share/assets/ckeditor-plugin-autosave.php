<?php

return array(
	'versions' => array(
		'fixed' => array(
			'js' => 'CKEDITOR.plugins.addExternal("autosave", "https://cdn.rawgit.com/w8tcha/CKEditor-AutoSave-Plugin/beeb157cf4a8e889646762470d0e966ffddcfb9a/autosave/plugin.js");',
		),
	),
	'require' => array(
		'asset' => 'ckeditor',
	),
	'config' => array(
		'no_cache' => true,
	),
);
