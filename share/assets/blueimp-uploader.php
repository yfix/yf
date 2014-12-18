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
);
