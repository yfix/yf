<?php

return array(
	'versions' => array(
		'4.2.0' => array(
		//	'js' => '//oss.maxcdn.com/bootbox/4.2.0/bootbox.min.js',
			'js' => '//cdn.rawgit.com/yfix/bootbox/yyv/bootbox.js',
		),
	),
	'require' => array(
		'asset' => array(
			'jquery',
			'bootstrap-theme',
		),
	),
	'info' => array(
		'url' => 'http://bootboxjs.com/',
		'name' => 'Bootbox.js - alert, confirm and flexible modal dialogs for the Bootstrap framework',
		'desc' => 'Bootbox.js is a small JavaScript library which allows you to create programmatic dialog boxes using Bootstrap modals, without having to worry about creating, 
			managing or removing any of the required DOM elements or JS event handlers.',
		'git' => 'https://github.com/yfix/bootbox.git',
	),
);
