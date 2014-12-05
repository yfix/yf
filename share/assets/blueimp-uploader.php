<?php

return array(
	'versions' => array(
		'master' => array(
			'css' => array(
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload.css',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui.css',
			),
			'js' => array(
				'//cdn.rawgit.com/yfix/JavaScript-Load-Image/master/js/load-image.all.min.js',
				'//cdn.rawgit.com/yfix/JavaScript-Canvas-to-Blob/master/js/canvas-to-blob.min.js',
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
			'jquery',
			'jquery-ui',
		),
	),
	'add' => array(
		'js' => array(
			'blueimp-uploader-cors',
		),
	),
);
