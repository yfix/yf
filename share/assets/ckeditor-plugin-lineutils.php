<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => 'CKEDITOR.plugins.addExternal("lineutils", "https://cdn.rawgit.com/ckeditor/ckeditor-releases/master/plugins/lineutils/plugin.js");',
		),
	),
	'require' => array(
		'asset' => array(
			'ckeditor',
		),
	),
	'config' => array(
		'no_cache' => true,
	),
);
