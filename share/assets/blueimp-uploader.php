<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array(
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload.css',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui.css',
			),
			'js' => array(
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.iframe-transport.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-ui.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-process.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-image.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-audio.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-video.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-validate.js',
			),
		),
	),
	'require' => array(
		'css' => array(
			'jquery-ui',
		),
		'js' => array(
			'blueimp-load-image',
			'blueimp-canvas-to-blob',
			'jquery',
			'jquery-ui',
		),
	),
	'add' => array(
		'js' => array(
			'blueimp-uploader-cors',
		),
	),
	'noscript' => array(
		'css' => array(
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-noscript.css',
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui-noscript.css',
		),
	),
	'info' => array(
		'url' => 'https://blueimp.github.io/jQuery-File-Upload/',
		'name' => 'blueimp jQuery-File-Upload',
		'desc' => 'File Upload widget with multiple file selection, drag&amp;drop support, progress bar, validation and preview images, audio and video for jQuery. 
			Supports cross-domain, chunked and resumable file uploads. Works with any server-side platform (Google App Engine, PHP, Python, Ruby on Rails, Java, etc.) 
			that supports standard HTML form file uploads.',
		'git' => 'https://github.com/yfix/jQuery-File-Upload.git',
	),
);
