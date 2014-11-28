<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_asset_test extends PHPUnit_Framework_TestCase {
/*
// TODO
	function jquery($content, $params = array()) { return _class('assets')->jquery($content, $params); }
	function angularjs($content, $params = array()) { return _class('assets')->angularjs($content, $params); }
	function backbonejs($content, $params = array()) { return _class('assets')->backbonejs($content, $params); }
	function reactjs($content, $params = array()) { return _class('assets')->reactjs($content, $params); }
	function emberjs($content, $params = array()) { return _class('assets')->emberjs($content, $params); }
*/
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
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($jquery_url);
		_class('assets')->already_required['jquery'] = false;
		jquery('var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });');
		$this->assertEquals(
			'<script src="'.$jquery_url.'" type="text/javascript"></script>'.PHP_EOL.
			'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });'.PHP_EOL.'})'.PHP_EOL.'</script>'
			, _class('assets')->show_js()
		);
	}
	public function test_basic() {
#		_class('assets')->clean_all();
#		$url = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css';
#		asset('css', $url);
#		$this->assertEquals('<link href="'.$url.'" rel="stylesheet" />', _class('assets')->show_css());
	}
	public function test_named_asset() {
#		_class('assets')->clean_all();
#		asset('blueimp-uploader');
#		$this->assertEquals('', _class('assets')->show_js());
#		$this->assertEquals('', _class('assets')->show_css());
	}
}