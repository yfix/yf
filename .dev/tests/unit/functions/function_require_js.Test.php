<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_require_js_test extends PHPUnit_Framework_TestCase {
	public function test_complex() {
		_class('assets')->clean_all();
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js');
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript"></script>', _class('core_js')->show());
	}
	public function test_params() {
		_class('assets')->clean_all();
		js('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js', array('class' => 'yf_core'));
		$this->assertEquals('<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js" type="text/javascript" class="yf_core"></script>', _class('core_js')->show());
	}
}