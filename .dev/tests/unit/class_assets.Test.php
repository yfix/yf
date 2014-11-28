<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class class_assets_test extends PHPUnit_Framework_TestCase {
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
}