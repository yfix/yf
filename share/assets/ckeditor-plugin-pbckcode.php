<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => 'CKEDITOR.plugins.addExternal("pbckcode", "https://cdn.rawgit.com/prbaron/pbckcode/master/plugin.js");',
		),
	),
	'require' => array(
		'asset' => 'ckeditor',
	),
	'config' => array(
		'no_cache' => true,
	),
);
