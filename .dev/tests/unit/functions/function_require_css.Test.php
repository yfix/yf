<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_require_css_test extends PHPUnit_Framework_TestCase {
	public function test_complex() {
		css('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css');
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css" rel="stylesheet" />', _class('core_css')->show());
	}
	public function test_params() {
		css('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css', array('class' => 'yf_core'));
		$this->assertEquals('<link href="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/contents.css" rel="stylesheet" class="yf_core" />', _class('core_css')->show());
	}
}