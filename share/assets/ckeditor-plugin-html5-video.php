<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => 'CKEDITOR.plugins.addExternal("video", "https://cdn.rawgit.com/yfix/ckeditor-html5-video/master/video/plugin.js");',
		),
	),
	'require' => array(
		'asset' => 'ckeditor',
	),
	'config' => array(
		'no_cache' => true,
	),
);
