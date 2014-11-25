<?php

return array(
	'css' => array(
		'jquery-ui'	=> array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.1/css/jquery-ui.min.css',
			'version' => '1.11.1',
		),
		'angular-ui'=> array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/angular-ui/0.4.0/angular-ui.min.css',
			'version' => '0.4.0',
		),
		'bs2' => array(
			'url' => '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css',
			'version' => '2.3.2',
		),
		'bs3' => array(
			'url' => '//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css',
			'version' => '3.2.0',
		),
	),
	'js' => array(
		'jquery'	=> array(
			'url' => '//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js',
			'version' => '1.11.1',
		),
		'jquery-ui'	=> array(
			'url' => '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js',
			'require' => 'jquery',
			'version' => '1.11.1',
		),
		'jquery-cookie' => array(
			'url' => '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js',
			'require' => 'jquery',
			'version' => '1.4.1',
		),
		'bs2'		=> array(
			'url' => '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js',
			'require' => 'jquery',
			'version' => '2.3.2',
		),
		'bs3'		=> array(
			'url' => '//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js',
			'require' => 'jquery',
			'version' => '3.2.0',
		),
		'html5shiv'	=> array(
			'before_tag' => '<!--[if lt IE 9]>',
			'url' => '//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js',
			'after_tag' => '<![endif]-->',
			'version'	=> '3.7.2',
		),
#		<!--[if lt IE 9]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js" class="yf_core"></script><![endif]-->
// TODO: modernizr
// TODO: momentjs
// TODO: angularjs
// TODO: bootstrap-datetime
// TODO: ckeditor
// TODO: tinymce  '//cdnjs.cloudflare.com/ajax/libs/tinymce/3.5.8/tiny_mce.js';
// TODO: ace-editor
	),
	'blueimp-uploader' => array(
		'css' => array(
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload.css',
			'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui.css',
		),
		'require_css' => array(
			'jquery-ui',
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
		'require_js' => array(
			'jquery',
			'jquery-ui',
		),
	),
	'font-avesome4' => array(
		'css' => array(
		),
	),
);
