<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_asset_test extends PHPUnit_Framework_TestCase {
	public function test_basic() {
		_class('assets')->clean_all();
		$url = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.css';
#		asset($url);
#		$this->assertEquals('<link href="'.$url.'" rel="stylesheet" />', _class('assets')->show());
	}
}