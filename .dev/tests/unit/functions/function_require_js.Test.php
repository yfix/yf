<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_require_js_test extends PHPUnit_Framework_TestCase {
	public function test_detect_content_type() {
		$this->assertEquals('asset', _class('core_js')->_detect_content('jquery'));
		$this->assertEquals('url', _class('core_js')->_detect_content('http://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('url', _class('core_js')->_detect_content('https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('url', _class('core_js')->_detect_content('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js'));
		$this->assertEquals('inline', _class('core_js')->_detect_content('<script>$(function(){})</script>'));
		$this->assertEquals('inline', _class('core_js')->_detect_content('<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('inline', _class('core_js')->_detect_content('$(function(){
			$("#element").on("click", function(){})
		})'));
		$this->assertEquals('inline', _class('core_js')->_detect_content('$(function(){
			var url="http://www.google.com/";
		})'));
		$this->assertEquals('inline', _class('core_js')->_detect_content('$(function(){})'));

		$f = '/tmp/yf_unit_tests_empty_script.js';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('core_js')->_detect_content($f));
		unlink($f);
	}
	public function test_strip_script_tags() {
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script>$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script>$(function(){})'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('$(function(){})'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script type="text/javascript">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script type="text/javascript" id="test">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script type="text/javascript" some-attr="some-val">$(function(){})</script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script><script>$(function(){})</script></script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})</script></script></script>'));
		$this->assertEquals('$(function(){})', _class('core_js')->_strip_script_tags('<script><script type="text/javascript" some-attr="some-val"><script>$(function(){})'));
	}
	public function test_complex() {
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js');
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript"></script>', _class('core_js')->show());
	}
	public function test_params() {
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js', array('class' => 'yf_core'));
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript" class="yf_core"></script>', _class('core_js')->show());
	}
	public function test_jquery() {
		$jquery_url = _class('assets')->get_asset('jquery', 'js');
		$this->assertNotEmpty($jquery_url);
		jquery('var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });');
		$this->assertEquals('<script src="'.$jquery_url.'" type="text/javascript"></script>'.PHP_EOL
			.'<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.'var i = 0; $("#id").on("click", ".sub_selector", function(){ return false; });'.PHP_EOL.'})'.PHP_EOL
			.'</script>', _class('core_js')->show());
	}
}