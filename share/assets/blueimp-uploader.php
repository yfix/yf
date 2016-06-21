<?php

return [
	'versions' => [
		'master' => [
			'css' => [
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload.css',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui.css',
			],
			'js' => [
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.iframe-transport.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-ui.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-process.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-image.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-audio.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-video.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-validate.js',
			],
		],
	],
	'require' => [
		'asset' => [
			'blueimp-load-image',
			'blueimp-canvas-to-blob',
			'jquery',
			'jquery-ui',
		],
	],
	'add' => [
		'js' => [
			'blueimp-uploader-cors',
		],
	],
	'noscript' => [
		'css' => [
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-noscript.css',
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui-noscript.css',
		],
	],
	'info' => [
		'url' => 'https://blueimp.github.io/jQuery-File-Upload/',
		'name' => 'blueimp jQuery-File-Upload',
		'desc' => 'File Upload widget with multiple file selection, drag&amp;drop support, progress bar, validation and preview images, audio and video for jQuery. 
			Supports cross-domain, chunked and resumable file uploads. Works with any server-side platform (Google App Engine, PHP, Python, Ruby on Rails, Java, etc.) 
			that supports standard HTML form file uploads.',
		'git' => 'https://github.com/yfix/jQuery-File-Upload.git',
	],
];
