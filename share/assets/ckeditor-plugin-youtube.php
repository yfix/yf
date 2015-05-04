<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => 'CKEDITOR.plugins.addExternal("youtube", "https://cdn.rawgit.com/fonini/ckeditor-youtube-plugin/master/youtube/plugin.js");',
		),
	),
	'require' => array(
		'asset' => 'ckeditor',
	),
	'config' => array(
		'no_cache' => true,
	),
);
