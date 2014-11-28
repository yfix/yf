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
	public function test_complex() {
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js');
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript"></script>', _class('core_js')->show());
	}
	public function test_params() {
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js', array('class' => 'yf_core'));
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript" class="yf_core"></script>', _class('core_js')->show());
	}
}