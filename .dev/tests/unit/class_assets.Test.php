<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class class_assets_test extends PHPUnit_Framework_TestCase {
	public function test_detect_content_type_css() {
		$this->assertEquals('asset', _class('assets')->detect_content_type('css', 'jquery-ui'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'http://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', 'https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css'));
		$this->assertEquals('url', _class('assets')->detect_content_type('css', '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '<style>$(function(){})</style>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '<style type="text/css">.some_class { border: 1px solid black; } #some_id { display:none; }</style>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '.some_class { border: 1px solid black; } #some_id { display:none; }'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('css', '
			.some_class { border: 1px solid black; }
			#some_id { display:none; }
		'));

		$f = '/tmp/yf_unit_tests_empty_style.css';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('assets')->detect_content_type('css', $f));
		unlink($f);
	}
	public function test_detect_content_type_js() {
		$this->assertEquals('asset', _class('assets')->detect_content_type('js', 'jquery'));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'http://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', 'https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('url', _class('assets')->detect_content_type('js', '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '<script>$(function(){})</script>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){
			$("#element").on("click", function(){})
		})'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){
			var url="http://www.google.com/";
		})'));
		$this->assertEquals('inline', _class('assets')->detect_content_type('js', '$(function(){})'));

		$f = '/tmp/yf_unit_tests_empty_script.js';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('assets')->detect_content_type('js', $f));
		unlink($f);
	}
	public function test_strip_script_tags() {
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script>$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script>$(function(){})'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('$(function(){})'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script type="text/javascript" id="test">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script type="text/javascript" some-attr="some-val">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script><script>$(function(){})</script></script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})</script></script></script>'));
		$this->assertEquals('$(function(){})', _class('assets')->_strip_script_tags('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})'));
	}
	public function test_strip_style_tags() {
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style>#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style>#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style type="text/css">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style type="text/css" id="some_id">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style><style><style type="text/css" id="some_id">#some_id { display:none; }</style></style></style>'));
		$this->assertEquals('#some_id { display:none; }', _class('assets')->_strip_style_tags('<style><style><style type="text/css" id="some_id">#some_id { display:none; }'));
	}
	public function test_jquery() {
		$url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($url);
		jquery('var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });');
		$this->assertEquals(
			'<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });'.PHP_EOL.'})'.PHP_EOL.'</script>'
			, _class('assets')->show_js()
		);
	}
	public function test_angularjs() {
		$url = _class('assets')->get_asset('angularjs', 'js');
		$this->assertNotEmpty($url);
		angularjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}
	public function test_backbonejs() {
		$url = _class('assets')->get_asset('backbonejs', 'js');
		$this->assertNotEmpty($url);
		backbonejs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}
	public function test_reactjs() {
		$url = _class('assets')->get_asset('reactjs', 'js');
		$this->assertNotEmpty($url);
		reactjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}
	public function test_emberjs() {
		$url = _class('assets')->get_asset('emberjs', 'js');
		$this->assertNotEmpty($url);
		emberjs('alert("Hello");');
		$this->assertEquals( '<script src="'.$url.'" type="text/javascript"></script>'.PHP_EOL.'<script type="text/javascript">'.PHP_EOL.'alert("Hello");'.PHP_EOL.'</script>', _class('assets')->show_js() );
	}
	public function test_basic() {
		_class('assets')->clean_all();
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
	public function test_bundle() {
		_class('assets')->clean_all();

		asset('blueimp-uploader');

		$out_js = _class('assets')->show_js();
		$this->assertNotEmpty($out_js);
		$this->assertContains('<script', $out_js);
		$this->assertContains('jquery.min.js', $out_js);
		$this->assertContains('jquery-ui', $out_js);
		$this->assertContains('jquery.fileupload', $out_js);

		$out_css = _class('assets')->show_css();
		$this->assertNotEmpty($out_css);
		$this->assertContains('<link href="', $out_css);
		$this->assertContains('jquery-ui.min.css', $out_css);
		$this->assertContains('jquery.fileupload.css', $out_css);
	}
	public function test_combine_js() {
		_class('assets')->clean_all();
		asset('blueimp-uploader');
		$out_file = APP_PATH.'combined/'.__FUNCTION__.'.js';
		if (file_exists($out_file)) {
			unlink($out_file);
		}
		$result = _class('assets')->combine_js(array('out_file' => $out_file));
		$this->assertSame($result, $out_file);
		$this->assertFileExists($result);
		$this->assertTrue(strlen(file_get_contents($result)) > 100000);
		unlink($out_file);
	}
	public function test_filter_cssmin() {
		$in = 'body {
		    color : white;
		}';
		$this->assertEquals('body{color:white}', _class('assets')->filter_cssmin($in));
#		$this->assertEquals('body{color:white}body{background:black}', _class('assets')->filter_cssmin($in));
	}
	public function test_filter_jsmin() {
		$in = 'var a = "abc";

			// fsfafwe

			;;
			var bbb = "u";
		';
        $this->assertEquals('var a="abc";;;var bbb="u";', _class('assets')->filter_jsmin($in));
	}
	public function test_filter_jsminplus() {
		$in = 'var a = "abc";

			// fsfafwe

			;;
			var bbb = "u";
		';
        $this->assertEquals('var a="abc",bbb="u"', _class('assets')->filter_jsminplus($in));
	}
}