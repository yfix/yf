<?php

return [
	'versions' => [
		'master' => [
			'css' => '//cdn.rawgit.com/yfix/Bootstrap-Image-Gallery/master/css/bootstrap-image-gallery.min.css',
			'js' => '//cdn.rawgit.com/yfix/Bootstrap-Image-Gallery/master/js/bootstrap-image-gallery.min.js',
		],
	],
	'require' => [
		'asset' => [
			'bootstrap',
			'blueimp-gallery',
		],
	],
	'info' => [
		'url' => 'https://blueimp.github.io/Bootstrap-Image-Gallery/',
		'name' => 'Bootstrap Image Gallery',
		'desc' => 'Bootstrap Image Gallery is an extension to <a href="https://blueimp.github.io/Gallery/">blueimp Gallery</a>, a touch-enabled, responsive and customizable 
			image &amp; video gallery.<br>It displays images and videos in the modal dialog of the <a href="http://getbootstrap.com/">Bootstrap</a> framework, features swipe, 
			mouse and keyboard navigation, transition effects, fullscreen support and on-demand content loading and can be extended to display additional content types.',
		'git' => 'https://github.com/yfix/Bootstrap-Image-Gallery.git',
	],
];
