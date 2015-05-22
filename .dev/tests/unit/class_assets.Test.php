<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class class_assets_test extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		// Replace default style and script templates with empty strings
		tpl()->parse_string('', array(), 'style_css');
		tpl()->parse_string('', array(), 'script_js');
		_class('assets')->ADD_IS_DIRECT_OUT = false;
		_class('assets')->OUT_ADD_ASSET_NAME = false;
	}

	public function setUp() {
		_class('assets')->clean_all();
	}

	/***/
	public function test_detect_content_type_css() {
		$this->assertEquals('asset', _class('assets')->detect_content_type('css', 'jquery-ui'));
		$ckeditor_url = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css';
		$this->assertEquals('url', _class('assets')->detect_content_type('css', $ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'http:'.$ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'https:'.$ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', $ckeditor_url.'?time=1234567890'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'http:'.$ckeditor_url.'?time=1234567890'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'https:'.$ckeditor_url.'?time=1234567890'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', '//fonts.googleapis.com/css?family=Roboto+Condensed:300italic,400italic,700italic,400,700,300&subset=cyrillic-ext,latin-ext'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '<style>$(function(){})</style>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '<style type="text/css">.some_class { border: 1px solid black; } #some_id { display:none; }</style>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '.some_class { border: 1px solid black; } #some_id { display:none; }'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '
			.some_class { border: 1px solid black; }
			#some_id { display:none; }
		'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '@import "test.css"'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '@import url("test.css")'));

		$f = '/tmp/yf_unit_tests_empty_style.css';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('assets')->detect_content_type('css', $f));
		unlink($f);
	}

	/***/
	public function test_detect_content_type_js() {
		$this->assertEquals('asset', _class('assets')->detect_content_type('js', 'jquery'));
		$ckeditor_url = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js';
		$this->assertEquals('url', _class('assets')->detect_content_type('js', $ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'http:'.$ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'https:'.$ckeditor_url));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', $ckeditor_url.'?time=1234567890'));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'http:'.$ckeditor_url.'?time=1234567890'));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'https:'.$ckeditor_url.'?time=1234567890'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '<script>$(function(){})</script>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){
			$("#element").on("click", function(){})
		})'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){
			var url="http://www.google.com/";
		})'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){})'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', 'var a="abc";'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', 'alert("hello")'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', PHP_EOL.'var testtag="<span>";'.PHP_EOL));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', 'var testtag="<span>";'));

		$f = '/tmp/yf_unit_tests_empty_script.js';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('assets')->detect_content_type('js', $f));
		unlink($f);
	}

	/***/
	public function test_strip_js_input() {
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script>$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script>$(function(){})'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('$(function(){})'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script type="text/javascript" id="test">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script type="text/javascript" some-attr="some-val">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script><script>$(function(){})</script></script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})</script></script></script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_js_input('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})'));
		$this->assertEquals('path.to/script.js', _class('assets')->_strip_js_input('<script src="path.to/script.js"></script>'));
		$this->assertEquals('path.to/script.js', _class('assets')->_strip_js_input('<script type="text/javascript" src="path.to/script.js"></script>'));
		$this->assertEquals('path.to/script.js', _class('assets')->_strip_js_input('<script src="path.to/script.js" type="text/javascript"></script>'));
	}

	/***/
	public function test_strip_css_input() {
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style>#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style>#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style type="text/css">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style type="text/css" id="some_id">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style><style><style type="text/css" id="some_id">#some_id { display:none; }</style></style></style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_css_input('<style><style><style type="text/css" id="some_id">#some_id { display:none; }'));
		$this->assertEquals('path.to/style.css', _class('assets')->_strip_css_input('<link href="path.to/style.css">'));
		$this->assertEquals('path.to/style.css', _class('assets')->_strip_css_input('<link rel="stylesheet" href="path.to/style.css">'));
		$this->assertEquals('path.to/style.css', _class('assets')->_strip_css_input('<link href="path.to/style.css" rel="stylesheet">'));
		$this->assertEquals('path.to/style.css', _class('assets')->_strip_css_input('<link href="path.to/style.css" rel="stylesheet" />'));
		$this->assertEquals('path.to/style.css', _class('assets')->_strip_css_input('<link rel="stylesheet" href="path.to/style.css" />'));
	}

	/***/
	public function test_jquery() {
		$url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($url);
		$jquery_js = 'var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });';
		$expected_jquery_lib = '<script src="'.$url.'" type="text/javascript"></script>';
		$expected_js = '<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL. $jquery_js. PHP_EOL.'})'.PHP_EOL.'</script>';

		$jquery_result = jquery($jquery_js);
		$this->assertInstanceOf( get_class(_class('assets')), $jquery_result );
		$this->assertEquals( $expected_jquery_lib. PHP_EOL. $expected_js, _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing');

		// Second call should avoid adding jquery again
		$jquery_result = jquery($jquery_js);
		$this->assertSame( _class('assets'), $jquery_result );
		$this->assertEquals( $expected_jquery_lib. PHP_EOL. $expected_js, _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing' );

		_class('assets')->clean_all();
		_class('assets')->ADD_IS_DIRECT_OUT = true;

		$jquery_result = js('jquery');
		$this->assertSame( $expected_jquery_lib, $jquery_result );
		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing' );

		_class('assets')->clean_all();
		$jquery_result = jquery($jquery_js);
		$this->assertSame( $expected_jquery_lib. PHP_EOL. $expected_js, $jquery_result );
		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing' );

		_class('assets')->ADD_IS_DIRECT_OUT = false;

#		_class('assets')->clean_all();
#		$jquery_result = js('jquery', 'auto', array('direct_out' => true));
#		$this->assertSame( $expected_jquery_lib, $jquery_result );
#		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing' );

#		_class('assets')->clean_all();
#		$jquery_result = jquery($jquery_js, array('direct_out' => true));
#		$this->assertSame( $expected_jquery_lib. PHP_EOL. $expected_js, $jquery_result );
#		$this->assertEmpty( _class('assets')->show_js(), 'Calling output method again should return nothing' );
	}

	/***/
	public function test_angularjs() {
		$url = _class('assets')->get_asset('angularjs', 'js');
		$this->assertNotEmpty($url);
		angularjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}

	/***/
	public function test_backbonejs() {
		$url = _class('assets')->get_asset('backbonejs', 'js');
		$this->assertNotEmpty($url);
		backbonejs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}

	/***/
	public function test_reactjs() {
		$url = _class('assets')->get_asset('reactjs', 'js');
		$this->assertNotEmpty($url);
		reactjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}

	/***/
	public function test_emberjs() {
		$url = _class('assets')->get_asset('emberjs', 'js');
		$this->assertNotEmpty($url);
		emberjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}

	/***/
	public function test_basic() {
		$url = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css';
		$expected = '<link href="'.$url.'" rel="stylesheet" />';
		asset($url, 'css');
		$this->assertEquals($expected, _class('assets')->show_css());
		css($url);
		$this->assertEquals($expected, _class('assets')->show_css());
		css($url);
		asset($url, 'css');
		css($url);
		$this->assertEquals($expected, _class('assets')->show_css());
	}

	/***/
	public function test_bundle() {
		$asset_out = asset('blueimp-uploader');
		$this->assertInstanceOf( get_class(_class('assets')), $asset_out );

		$out_js = _class('assets')->show_js();
		$this->assertNotEmpty($out_js);
		$this->assertContains('<script', $out_js);
		$this->assertContains('jquery.min.js', $out_js);
		$this->assertContains('jquery.fileupload', $out_js);

		$out_css = _class('assets')->show_css();
		$this->assertNotEmpty($out_css);
		$this->assertContains('<link href="', $out_css);
		$this->assertContains('jquery-ui.min.css', $out_css);
		$this->assertContains('jquery.fileupload.css', $out_css);

		_class('assets')->clean_all();
		_class('assets')->ADD_IS_DIRECT_OUT = true;

		$out = asset('blueimp-uploader');
		// In this mode we out generated JS and CSS one after another together
		$this->assertContains('<script', $out);
		$this->assertContains('jquery.min.js', $out);
		$this->assertContains('jquery-ui', $out);
		$this->assertContains('jquery.fileupload', $out);
		$this->assertContains('<link href="', $out);
		$this->assertContains('jquery-ui.min.css', $out);
		$this->assertContains('jquery.fileupload.css', $out);

		_class('assets')->ADD_IS_DIRECT_OUT = false;
	}

	/***/
	public function test_show() {
		asset('blueimp-uploader');
		$out = _class('assets')->show_js();
		$this->assertContains('<script', $out);
		$this->assertContains('jquery.min.js', $out);
		$this->assertContains('jquery-ui', $out);
		$this->assertContains('jquery.fileupload', $out);
		$out = _class('assets')->show_css();
		$this->assertContains('<link href="', $out);
		$this->assertContains('jquery-ui.min.css', $out);
		$this->assertContains('jquery.fileupload.css', $out);
	}

	/***/
	public function test_show_cached() {
		_class('assets')->USE_CACHE = true;
		_class('assets')->CACHE_DIR_TPL = '{project_path}/_tmp/assets_cache_{out_type}/{asset_name}_{version}/';
		asset('blueimp-uploader');
		$out = _class('assets')->show_js();
		$this->assertContains('/_tmp/assets_cache_js/', $out);
		$this->assertContains('<script', $out);
		$this->assertContains('jquery.min.js', $out);
		$this->assertContains('jquery-ui', $out);
		$this->assertContains('jquery.fileupload', $out);
		$out = _class('assets')->show_css();
		$this->assertContains('/_tmp/assets_cache_css/', $out);
		$this->assertContains('<link href="', $out);
		$this->assertContains('jquery-ui.min.css', $out);
		$this->assertContains('jquery.fileupload.css', $out);

#		$this->assertFileExists($result);
#		$this->assertTrue(strlen(file_get_contents($result)) > 100000);
#		unlink($out_file);

		_class('assets')->USE_CACHE = false;
	}

	/***/
	public function test_show_combined() {
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			$this->markTestSkipped( 'PHPUnit will skip this test method for PHP version <5.4' );
		}
		_class('assets')->USE_CACHE = true;
		_class('assets')->CACHE_DIR_TPL = '{project_path}/_tmp/assets_cache_{out_type}/{asset_name}_{version}/';
		_class('assets')->COMBINE = true;
		_class('assets')->COMBINED_VERSION_TPL = '{year}{month}';

		asset('blueimp-uploader');
		$out = _class('assets')->show_js();
		$this->assertContains('/_tmp/assets_cache_js/combined_'.date('Ym').'/', $out);
		$out = _class('assets')->show_css();
		$this->assertContains('/_tmp/assets_cache_css/combined_'.date('Ym').'/', $out);

#		$this->assertFileExists($result);
#		$this->assertTrue(strlen(file_get_contents($result)) > 100000);
#		unlink($out_file);

		_class('assets')->USE_CACHE = false;
		_class('assets')->COMBINE = false;
	}

	/***/
	public function test_filter_custom() {
		$in = 'body{'.PHP_EOL.'color:white'.PHP_EOL.'}';
		$expected = 'body{color:white}';
		$func = function($in) {
			return str_replace(PHP_EOL, '', $in);
		};
		$this->assertEquals( $expected, _class('assets')->filters_process_input($in, $func) );
	}

	/***/
	public function test_add() {
		$url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($url);
		$this->assertEmpty( _class('assets')->show_js() );
		$expected = '<script src="'.$url.'" type="text/javascript"></script>';

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery');
		$this->assertEquals( $expected, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery', 'bundle');
		$this->assertEquals( $expected, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery', 'bundle', 'auto');
		$this->assertEquals( $expected, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery', 'js');
		$this->assertEquals( $expected, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery', 'js', 'auto');
		$this->assertEquals( $expected, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->add('jquery', 'js', 'asset');
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/***/
	public function test_config() {
		$fake_lib1_url = _class('assets')->get_asset('jquery', 'js');
		$fake_lib1 = array(
			'versions' => array(
				'1.0' => array(	'js' => 'alert("hello")' ),
				'1.1' => array(	'js' => $fake_lib1_url ),
			),
		);
		$this->assertEmpty( _class('assets')->get_asset('fake_lib1', 'js') );
		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->bundle_register('fake_lib1', $fake_lib1);
		$this->assertSame( $fake_lib1['versions']['1.1']['js'], _class('assets')->get_asset('fake_lib1', 'js') );
		$expected1 = '<script src="'.$fake_lib1_url.'" type="text/javascript"></script>';
		_class('assets')->add('fake_lib1');
		$this->assertEquals( $expected1, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib2', 'js') );
		$fake_lib2 = array(
			'versions' => array(
				'1.0' => array(	'js' => 'var a="abc";' ),
			),
			'require' => array(
				'js' => 'fake_lib1',
			),
		);
		_class('assets')->bundle_register('fake_lib2', $fake_lib2);
		$this->assertSame( $fake_lib2['versions']['1.0']['js'], _class('assets')->get_asset('fake_lib2', 'js') );
		_class('assets')->add('fake_lib2');
		$expected2 = $expected1 . PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib2['versions']['1.0']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected2, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib3', 'js') );
		$fake_lib3 = array(
			'require' => array(
				'js' => array(
					'fake_lib2',
					'fake_lib1',
				),
			),
		);
		_class('assets')->bundle_register('fake_lib3', $fake_lib3);
		_class('assets')->add('fake_lib3');
		$expected3 = $expected2;
		$this->assertEquals( $expected3, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib4', 'js') );
		$fake_lib4 = array(
			'require' => array( 'js' => 'fake_lib3' ),
		);
		_class('assets')->bundle_register('fake_lib4', $fake_lib4);
		_class('assets')->add('fake_lib4');
		$expected4 = $expected3;
		$this->assertEquals( $expected4, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib4', 'js') );
		$fake_lib5 = array(
			'versions' => array(
				'master' => array( 'js' => 'var b="123"' ),
			),
		);
		_class('assets')->bundle_register('fake_lib5', $fake_lib5);
		_class('assets')->add('fake_lib5');
		$this->assertSame( $fake_lib5['versions']['master']['js'], _class('assets')->get_asset('fake_lib5', 'js') );
		$expected5 = '<script type="text/javascript">'.PHP_EOL.$fake_lib5['versions']['master']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected5, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib6', 'js') );
		$fake_lib6 = array(
			'require' => array( 'js' => array('fake_lib3', 'fake_lib5') ),
		);
		_class('assets')->bundle_register('fake_lib6', $fake_lib6);
		_class('assets')->add('fake_lib6');
		$expected6 = $expected3. PHP_EOL. $expected5;
		$this->assertEquals( $expected6, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib7', 'js') );
		$fake_lib7 = array(
			'require' => array( 'js' => 'fake_lib6' ),
			'add' => array( 'js' => 'var my3="val";'),
		);
		_class('assets')->bundle_register('fake_lib7', $fake_lib7);
		_class('assets')->add('fake_lib7');
		$expected7 = $expected6. PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib7['add']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected7, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib8', 'js') );
		$fake_lib8 = array(
			'versions' => array(
				'master' => array( 'js' => 'var c="45678";' ),
			),
			'require' => array( 'js' => 'fake_lib1' ),
			'add' => array( 'js' => 'var my8="val8";'),
		);
		_class('assets')->bundle_register('fake_lib8', $fake_lib8);
		_class('assets')->add('fake_lib8');
		$expected8 = $expected1
			. PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib8['versions']['master']['js'].PHP_EOL.'</script>'
			. PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib8['add']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected8, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib9', 'js') );
		$fake_lib9 = array(
			'versions' => array(
				'master' => array( 'js' => 'var d="fake9";' ),
			),
			'config' => array(
				'before' => '<!-- before -->',
				'after' => '<!-- after -->',
			),
		);
		_class('assets')->bundle_register('fake_lib9', $fake_lib9);
		_class('assets')->add('fake_lib9');
		$expected9 = $fake_lib9['config']['before']. '<script type="text/javascript">'.PHP_EOL. $fake_lib9['versions']['master']['js']. PHP_EOL.'</script>'. $fake_lib9['config']['after'];
		$this->assertEquals( $expected9, _class('assets')->show_js() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib10', 'js') );
		$fake_lib10 = array(
			'versions' => array(
				'master' => array(
					'js' => 'var e="fake10";',
					'css' => '.fake10 {color:red;}";',
				),
			),
			'require' => array(
				'js' => 'fake_lib9',
			),
		);
		_class('assets')->bundle_register('fake_lib10', $fake_lib10);
		_class('assets')->add('fake_lib10');
		$expected10 = $expected9. PHP_EOL. '<script type="text/javascript">'.PHP_EOL. $fake_lib10['versions']['master']['js']. PHP_EOL.'</script>';
		$this->assertEquals( $expected10, _class('assets')->show_js() );
		$expected10_css = '<style type="text/css">'.PHP_EOL. $fake_lib10['versions']['master']['css']. PHP_EOL.'</style>';
		$this->assertEquals( $expected10_css, _class('assets')->show_css() );

		$this->assertEmpty( _class('assets')->show_js() );
		$this->assertEmpty( _class('assets')->show_css() );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib11', 'js') );
		$this->assertEmpty( _class('assets')->get_asset('fake_lib11', 'css') );
		$fake_lib11 = array(
			'versions' => array(
				'master' => array(
					'js' => 'var f="fake11";',
					'css' => '.fake11 {color:black;}";',
				),
			),
			'require' => array(
				'js' => 'fake_lib10',
				'css' => 'fake_lib10',
			),
		);
		_class('assets')->bundle_register('fake_lib11', $fake_lib11);
		_class('assets')->add('fake_lib11');
		$expected11_js = $expected10. PHP_EOL. '<script type="text/javascript">'.PHP_EOL. $fake_lib11['versions']['master']['js']. PHP_EOL.'</script>';
		$this->assertEquals( $expected11_js, _class('assets')->show_js() );
		$expected11_css = $expected10_css. PHP_EOL. '<style type="text/css">'.PHP_EOL. $fake_lib11['versions']['master']['css']. PHP_EOL.'</style>';
		$this->assertEquals( $expected11_css, _class('assets')->show_css() );
	}

	/***/
	public function test_recursion1() {
		_class('assets')->clean_all();
		$fake_lib1_url = _class('assets')->get_asset('jquery', 'js').'?123';
		$lib_name1 = __FUNCTION__.'_fake_lib1';
		$lib_name2 = __FUNCTION__.'_fake_lib2';
		$fake_lib1 = array(
			'versions' => array('master' => array('js' => $fake_lib1_url)),
			'require' => array('js' => $lib_name2),
		);
		$fake_lib2 = array(
			'versions' => array('master' => array('js' => 'var a="abc";')),
			'require' => array('js' => $lib_name1),
		);
		$this->assertEmpty( _class('assets')->get_asset($lib_name1, 'js') );
		$this->assertEmpty( _class('assets')->get_asset($lib_name2, 'js') );
		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->bundle_register($lib_name1, $fake_lib1);
		_class('assets')->bundle_register($lib_name2, $fake_lib2);
		$this->assertSame( $fake_lib1['versions']['master']['js'], _class('assets')->get_asset($lib_name1, 'js') );
		$this->assertSame( $fake_lib2['versions']['master']['js'], _class('assets')->get_asset($lib_name2, 'js') );
		_class('assets')->add($lib_name1);
		_class('assets')->add($lib_name2);
		$expected1 = '<script src="'.$fake_lib1_url.'" type="text/javascript"></script>';
		$expected2 = $expected1 . PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib2['versions']['master']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected2, _class('assets')->show_js() );
	}

	/***/
	public function test_recursion2() {
		_class('assets')->clean_all();
		$fake_lib1_url = _class('assets')->get_asset('jquery', 'js').'?123';
		$lib_name3 = __FUNCTION__.'_fake_lib3';
		$lib_name4 = __FUNCTION__.'_fake_lib4';
		$fake_lib3 = array(
			'versions' => array('master' => array('js' => $fake_lib1_url)),
			'require' => array('asset' => $lib_name4),
		);
		$fake_lib4 = array(
			'versions' => array('master' => array('js' => 'var a="abc";')),
			'require' => array('asset' => $lib_name3),
		);
		$this->assertEmpty( _class('assets')->get_asset($lib_name3, 'js') );
		$this->assertEmpty( _class('assets')->get_asset($lib_name4, 'js') );
		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->bundle_register($lib_name3, $fake_lib3);
		_class('assets')->bundle_register($lib_name4, $fake_lib4);
		$this->assertSame( $fake_lib3['versions']['master']['js'], _class('assets')->get_asset($lib_name3, 'js') );
		$this->assertSame( $fake_lib4['versions']['master']['js'], _class('assets')->get_asset($lib_name4, 'js') );
		_class('assets')->add($lib_name3);
		_class('assets')->add($lib_name4);
		$expected1 = '<script src="'.$fake_lib1_url.'" type="text/javascript"></script>';
		$expected2 = $expected1 . PHP_EOL. '<script type="text/javascript">'.PHP_EOL.$fake_lib4['versions']['master']['js'].PHP_EOL.'</script>';
		$this->assertEquals( $expected2, _class('assets')->show_js() );
	}

	/***/
	public function test_recusrion3() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$url = $jquery_url;
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';
		$url4 = $url.'?v=4';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';
		$name4 = __FUNCTION__.'_fake_lib4';

		$this->assertEmpty( _class('assets')->show_js() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array(
					'master' => array(
						'js' => array(
							$url1,
							$url2,
						),
						'jquery' => '$("body").click()',
						'asset' => $name3,
					)
				),
				'require' => array(
					'asset' => 'jquery',
				),
				'add' => array(
					'asset' => $name4,
				),
			),
			$name3 => array(
				'versions' => array('master' => array('js' => $url3)),
				'require' => array('asset' => $name1),
				'add' => array('asset' => $name1),
			),
			$name4 => array(
				'versions' => array('master' => array('js' => $url4)),
				'require' => array('asset' => $name1),
				'add' => array('asset' => $name1),
			),
		));
		$expected = implode(PHP_EOL, array(
			'<script src="'.$jquery_url.'" type="text/javascript"></script>', // Appears first because of required config entry
			'<script src="'.$url1.'" type="text/javascript"></script>', // main script url
			'<script src="'.$url2.'" type="text/javascript"></script>', // main script url
			'<script src="'.$url3.'" type="text/javascript"></script>', // main asset appears after js and jquery
			'<script src="'.$url4.'" type="text/javascript"></script>', // added last inside urls
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'$("body").click()'.PHP_EOL.'})'.PHP_EOL.'</script>', // Inline script should be after urls, wrapped with jquery doc ready
		));
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/**
	*/
	public function _helper_add_config($libs = array(), $types = array('js','css')) {
		_class('assets')->clean_all();
		foreach ($libs as $name => $config) {
			foreach ($types as $type) {
				$this->assertEmpty( _class('assets')->get_asset($name, $type) );
			}
		}
		foreach ($libs as $name => $config) {
			_class('assets')->bundle_register($name, $config);
		}
		foreach ($libs as $name => $config) {
			foreach ($types as $type) {
				$type_conf = $config['versions']['master'][$type];
				if (!$type_conf) {
					continue;
				}
				$this->assertSame( $type_conf, _class('assets')->get_asset($name, $type) );
			}
		}
		foreach ($libs as $name => $config) {
			_class('assets')->add($name);
		}
		foreach ($libs as $name => $config) {
			foreach ($types as $type) {
				$type_conf = $config['versions']['master'][$type];
				if (!$type_conf) {
					continue;
				}
				$this->assertNotEmpty( _class('assets')->get_asset($name, $type) );
			}
		}
	}

	/***/
	public function test_order1() {
		$url = 'http://jquery.com/jquery-wp-content/themes/jquery.com/style.css';
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';

		$this->assertEmpty( _class('assets')->show_css() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('css' => $url1)),
				'require' => array(
					'css' => $name2,
				),
				'add' => array(
					'css' => $name3,
				),
			),
			$name2 => array(
				'versions' => array('master' => array('css' => $url2)),
			),
			$name3 => array(
				'versions' => array('master' => array('css' => $url3)),
			),
		));
		$expected = implode(PHP_EOL, array(
			'<link href="'.$url2.'" rel="stylesheet" />', // required
			'<link href="'.$url1.'" rel="stylesheet" />', // main
			'<link href="'.$url3.'" rel="stylesheet" />', // added
		));
		$this->assertEquals( $expected, _class('assets')->show_css() );
	}

	/***/
	public function test_order2() {
		$url = 'http://jquery.com/jquery-wp-content/themes/jquery.com/style.css';
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';

		$this->assertEmpty( _class('assets')->show_css() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('css' => $url1)),
				'require' => array(
					'css' => $name2,
				),
				'add' => array(
					'asset' => $name3,
				),
			),
			$name2 => array(
				'versions' => array('master' => array('css' => $url2)),
			),
			$name3 => array(
				'versions' => array('master' => array('css' => $url3)),
			),
		));
		$expected = implode(PHP_EOL, array(
			'<link href="'.$url2.'" rel="stylesheet" />', // required
			'<link href="'.$url1.'" rel="stylesheet" />', // main
			'<link href="'.$url3.'" rel="stylesheet" />', // added
		));
		$this->assertEquals( $expected, _class('assets')->show_css() );
	}

	/***/
	public function test_order3() {
		$url = 'http://jquery.com/jquery-wp-content/themes/jquery.com/style.css';
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';

		$this->assertEmpty( _class('assets')->show_css() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('css' => $url1)),
				'require' => array(
					'asset' => $name2,
				),
				'add' => array(
					'asset' => $name3,
				),
			),
			$name2 => array(
				'versions' => array('master' => array('css' => $url2)),
			),
			$name3 => array(
				'versions' => array('master' => array('css' => $url3)),
			),
		));
		$expected = implode(PHP_EOL, array(
			'<link href="'.$url2.'" rel="stylesheet" />', // required
			'<link href="'.$url1.'" rel="stylesheet" />', // main
			'<link href="'.$url3.'" rel="stylesheet" />', // added
		));
		$this->assertEquals( $expected, _class('assets')->show_css() );
	}

	/***/
	public function test_order4() {
		$url = 'http://jquery.com/jquery-wp-content/themes/jquery.com/style.css';
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';

		$this->assertEmpty( _class('assets')->show_css() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('css' => $url1)),
				'require' => array(
					'asset' => $name2,
				),
				'add' => array(
					'asset' => $name3,
				),
			),
			$name2 => array(
				'versions' => array('master' => array('css' => $url2)),
				'add' => array(
					'asset' => $name3,
				),
			),
			$name3 => array(
				'versions' => array('master' => array('css' => $url3)),
			),
		));
		$expected = implode(PHP_EOL, array(
			'<link href="'.$url2.'" rel="stylesheet" />', // required
			'<link href="'.$url3.'" rel="stylesheet" />', // added after required element
			'<link href="'.$url1.'" rel="stylesheet" />', // main
		));
		$this->assertEquals( $expected, _class('assets')->show_css() );
	}

	/***/
	public function test_order5() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$url = $jquery_url;
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';
		$url4 = $url.'?v=4';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';
		$name4 = __FUNCTION__.'_fake_lib4';

		$this->assertEmpty( _class('assets')->show_js() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('js' => $url1)),
				'require' => array(
					'js' => $name3,
					'asset' => $name4,
				),
				'add' => array(
					'asset' => $name2,
				),
			),
			$name2 => array('versions' => array('master' => array('js' => $url2))),
			$name3 => array('versions' => array('master' => array('js' => $url3))),
			$name4 => array('versions' => array('master' => array('js' => $url4))),
		));
		$expected = implode(PHP_EOL, array(
			'<script src="'.$url3.'" type="text/javascript"></script>',
			'<script src="'.$url4.'" type="text/javascript"></script>',
			'<script src="'.$url1.'" type="text/javascript"></script>',
			'<script src="'.$url2.'" type="text/javascript"></script>',
		));
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/***/
	public function test_order6() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$url = $jquery_url;
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';
		$url4 = $url.'?v=4';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';
		$name4 = __FUNCTION__.'_fake_lib4';

		$this->assertEmpty( _class('assets')->show_js() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array('master' => array('js' => $url1)),
				'require' => array(
					'js' => $name3,
					'jquery' => '$("body").click()',
					'asset' => $name4,
				),
				'add' => array(
					'asset' => $name2,
				),
			),
			$name2 => array('versions' => array('master' => array('js' => $url2))),
			$name3 => array('versions' => array('master' => array('js' => $url3))),
			$name4 => array('versions' => array('master' => array('js' => $url4))),
		));
		$expected = implode(PHP_EOL, array(
			'<script src="'.$url3.'" type="text/javascript"></script>', // required js
			'<script src="'.$jquery_url.'" type="text/javascript"></script>', // Appears as requirement for inlined script, after required js
			'<script src="'.$url4.'" type="text/javascript"></script>', // required asset appears after js and jquery
			'<script src="'.$url1.'" type="text/javascript"></script>', // main script
			'<script src="'.$url2.'" type="text/javascript"></script>', // added script after main
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'$("body").click()'.PHP_EOL.'})'.PHP_EOL.'</script>', // Inline script should be after urls, wrapped with jquery doc ready
		));
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/***/
	public function test_order7() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$url = $jquery_url;
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';
		$url4 = $url.'?v=4';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';
		$name4 = __FUNCTION__.'_fake_lib4';

		$this->assertEmpty( _class('assets')->show_js() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array(
					'master' => array(
						'js' => array(
							$url1,
							$url2,
						),
						'jquery' => '$("body").click()',
						'asset' => $name3,
					)
				),
				'add' => array(
					'asset' => $name4,
				),
			),
			$name3 => array('versions' => array('master' => array('js' => $url3))),
			$name4 => array('versions' => array('master' => array('js' => $url4))),
		));
		$expected = implode(PHP_EOL, array(
			'<script src="'.$url1.'" type="text/javascript"></script>', // main script url
			'<script src="'.$url2.'" type="text/javascript"></script>', // main script url
			'<script src="'.$jquery_url.'" type="text/javascript"></script>', // Appears as requirement for inlined script, after required js
			'<script src="'.$url3.'" type="text/javascript"></script>', // main asset appears after js and jquery
			'<script src="'.$url4.'" type="text/javascript"></script>', // added last inside urls
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'$("body").click()'.PHP_EOL.'})'.PHP_EOL.'</script>', // Inline script should be after urls, wrapped with jquery doc ready
		));
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/***/
	public function test_order8() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$url = $jquery_url;
		$url1 = $url.'?v=1';
		$url2 = $url.'?v=2';
		$url3 = $url.'?v=3';
		$url4 = $url.'?v=4';

		$name1 = __FUNCTION__.'_fake_lib1';
		$name2 = __FUNCTION__.'_fake_lib2';
		$name3 = __FUNCTION__.'_fake_lib3';
		$name4 = __FUNCTION__.'_fake_lib4';

		$this->assertEmpty( _class('assets')->show_js() );
		$this->_helper_add_config(array(
			$name1 => array(
				'versions' => array(
					'master' => array(
						'js' => array(
							$url1,
							$url2,
						),
						'jquery' => '$("body").click()',
						'asset' => $name3,
					)
				),
				'require' => array(
					'asset' => 'jquery',
				),
				'add' => array(
					'asset' => $name4,
				),
			),
			$name3 => array('versions' => array('master' => array('js' => $url3))),
			$name4 => array('versions' => array('master' => array('js' => $url4))),
		));
		$expected = implode(PHP_EOL, array(
			'<script src="'.$jquery_url.'" type="text/javascript"></script>', // Appears first because of required config entry
			'<script src="'.$url1.'" type="text/javascript"></script>', // main script url
			'<script src="'.$url2.'" type="text/javascript"></script>', // main script url
			'<script src="'.$url3.'" type="text/javascript"></script>', // main asset appears after js and jquery
			'<script src="'.$url4.'" type="text/javascript"></script>', // added last inside urls
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'$("body").click()'.PHP_EOL.'})'.PHP_EOL.'</script>', // Inline script should be after urls, wrapped with jquery doc ready
		));
		$this->assertEquals( $expected, _class('assets')->show_js() );
	}

	/*
	* idea from  https://getcomposer.org/doc/01-basic-usage.md#package-versions
	* In the previous example we were requiring version 1.0.* of monolog. This means any version in the 1.0 development branch. It would match 1.0.0, 1.0.2 or 1.0.20.
	* Version constraints can be specified in a few different ways.
	* Exact version    1.0.2	You can specify the exact version of a package.
	* Range	           >=1.0 >=1.0,<2.0 >=1.0,<1.1 | >=1.2
	*		By using comparison operators you can specify ranges of valid versions. Valid operators are >, >=, <, <=, !=. 
	*		You can define multiple ranges. Ranges separated by a comma (,) will be treated as a logical AND. A pipe (|) will be treated as a logical OR. AND has higher precedence than OR.
	* Wildcard         1.0.* You can specify a pattern with a * wildcard. 1.0.* is the equivalent of >=1.0,<1.1.
	* Tilde Operator   ~1.2 Very useful for projects that follow semantic versioning. ~1.2 is equivalent to >=1.2,<2.0. For more details, read the next section below.
	*/
	public function test_versions() {
		_class('assets')->clean_all();
		$name = __FUNCTION__;
		$data = array(
			'versions' => array(
				'1.11.0' => array('js' => '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js'),
				'1.11.2' => array('js' => '//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js'),
			),
		);
		$this->assertEmpty( _class('assets')->get_asset($name, 'js') );
		$this->assertEmpty( _class('assets')->show_js() );
		_class('assets')->bundle_register($name, $data);

		$expected = $data['versions']['1.11.2']['js'];
		$this->assertSame( $expected, _class('assets')->get_asset($name, 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':1.11.2', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>1.11.1', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>1.11', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>1', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>=1.11.2', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':<2', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':<1.11.3', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':<1.11.3,>1.11.1', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':1.11.*', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>1.11.0,<1.11.3', 'js') );
#		$this->assertSame( $expected, _class('assets')->get_asset($name.':>1.11.0 | <2', 'js') );
	}

	/***/
 	public function test_filter_cssmin() {
		$in = 'body {'.PHP_EOL.'    color : white; '.PHP_EOL.'}';
		$expected = 'body{color:white}';
		$out = _class('assets')->filters_process_input($in, 'cssmin');
		$this->assertEquals( $expected, trim($out) );
		$this->assertEmpty( _class('assets')->show_css() );
		$expected2 = '<style type="text/css">'.PHP_EOL. $expected. PHP_EOL.'</style>';
		$out = _class('assets')->add_css($in)->filters_add_css('cssmin')->filters_process_css()->show_css();
		$this->assertEquals( $expected2, trim($out) );
#		$this->assertEquals( $expected2, _class('assets')->add_css($in)->show_css(array('filters' => 'cssmin')) );
	}

	/***/
	public function test_filter_jsmin() {
		$in = 'var a = "abc";'.PHP_EOL.PHP_EOL.'// fsfafwe.'.PHP_EOL.PHP_EOL.';;'.PHP_EOL.PHP_EOL.'var bbb = "u";'.PHP_EOL;
		$expected = 'var a="abc";;;var bbb="u";';
		$out = _class('assets')->filters_process_input($in, 'jsmin');
        $this->assertEquals( $expected, trim($out) );
		$this->assertEmpty( _class('assets')->show_js() );
		$expected2 = '<script type="text/javascript">'.PHP_EOL. $expected. PHP_EOL.'</script>';
		$out = _class('assets')->add_js($in)->filters_add_js('jsmin')->filters_process_js()->show_js();
		$this->assertEquals( $expected2, trim($out) );
#		$this->assertEquals( $expected2, _class('assets')->add_js($in)->show_js(array('filters' => 'jsmin')) );
	}

	/***/
	public function test_filter_jsminplus() {
		$in = 'var a = "abc";'.PHP_EOL.PHP_EOL.'// fsfafwe.'.PHP_EOL.PHP_EOL.';;'.PHP_EOL.PHP_EOL.'var bbb = "u";'.PHP_EOL;
		$expected = 'var a="abc",bbb="u"';
        $this->assertEquals( $expected, _class('assets')->filters_process_input($in, 'jsminplus') );
		$this->assertEmpty( _class('assets')->show_js() );
		$expected2 = '<script type="text/javascript">'.PHP_EOL. $expected. PHP_EOL.'</script>';
		$this->assertEquals( $expected2, _class('assets')->add_js($in)->filters_add_js('jsminplus')->filters_process_js()->show_js() );
#		$this->assertEquals( $expected2, _class('assets')->add_js($in)->show_js(array('filters' => 'jsminplus')) );
	}
}