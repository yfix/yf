<?php

$bundles = array(
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
);

class assets {
	private $data = array();
	private $bundles = array();
	public function __construct(array $bundles) {
		$this->bundles = $bundles;
	}
	public function add_js($content, $extra = array()) {
		$this->data['js'][] = array(
			'content'	=> $content,
		) + $extra;
		return $this;
	}
	public function add_css($content, $extra = array()) {
		$this->data['css'][] = array(
			'content'	=> $content,
		) + $extra;
		return $this;
	}
	public function output_js() {
		$out = array();
		foreach ((array)$this->data['js'] as $item) {
			$out[] = $item['content'];
		}
		return '<script type="text/javascript">'.implode(PHP_EOL, $out).'</script>';
#		return '<script type="text/javascript" src=".$web_path_to_combined_css."></script>';
	}
	public function output_css($name) {
		$out = array();
		foreach ((array)$this->data['css'] as $item) {
			$out[] = $item['content'];
		}
		return '<style type="text/css">'.implode(PHP_EOL, $out).'</style>';
#		return '<link rel="stylesheet" href="'.$web_path_to_combined_css.'" />';
	}
}

$assets = new assets($bundles);
$assets
	->add_js('//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.1/css/jquery-ui.min.css')
	->add_js(dirname(__DIR__).'/assets_cache/bootswatch/2.3.2/bootstrap.min.js')
	->add_js('../assets_cache/bootswatch/2.3.2/bootstrap.min.js')
	->add_js('<script>alert("hello world")</script>')
	->add_js('<script type="text/javascript">alert("hello world 2")</script>')
	->add_js('jquery')
;
echo $assets->outpub_js();

/*
$assets->add_js(array(
	'//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.1/css/jquery-ui.min.css',
	dirname(__DIR__).'/assets_cache/bootswatch/2.3.2/bootstrap.min.js',
	'../assets_cache/bootswatch/2.3.2/bootstrap.min.js',
	'<script>alert("hello world")</script>',
	'<script type="text/javascript">alert("hello world 2")</script>',
	'jquery',
));
*/
# TODO: versioning
# TODO: custom tags for IE fixes
# TODO: several collections support (main, debug, inline, header, footer, page-specific)
# TODO: minification
# TODO: gzip
# TODO: custom filters
# TODO: data-uri with base64
# TODO: images optimizing
# TODO: images sprites
# TODO: sass
# TODO: less
# TODO: fonts
# TODO: check existing urls for alive
# TODO: upload generated content (S3, Git, FTP)
