<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_require_css_test extends PHPUnit_Framework_TestCase {
	public function test_detect_content_type() {
		$this->assertEquals('asset', _class('core_css')->_detect_content('jquery-ui'));
		$this->assertEquals('url', _class('core_css')->_detect_content('http://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.css'));
		$this->assertEquals('url', _class('core_css')->_detect_content('https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.css'));
		$this->assertEquals('url', _class('core_css')->_detect_content('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.css'));
		$this->assertEquals('inline', _class('core_css')->_detect_content('<style>$(function(){})</style>'));
		$this->assertEquals('inline', _class('core_css')->_detect_content('<style type="text/css">.some_class { border: 1px solid black; } #some_id { display:none; }</style>'));
		$this->assertEquals('inline', _class('core_css')->_detect_content('.some_class { border: 1px solid black; } #some_id { display:none; }'));
		$this->assertEquals('inline', _class('core_css')->_detect_content('
			.some_class { border: 1px solid black; }
			#some_id { display:none; }
		'));

		$f = '/tmp/yf_unit_tests_empty_style.css';
		file_put_contents($f, 'test');
		$this->assertEquals('file', _class('core_css')->_detect_content($f));
		unlink($f);
	}
	public function test_strip_style_tags() {
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style>#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style>#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('#some_id { display:none; }'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style type="text/css">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style type="text/css" id="some_id">#some_id { display:none; }</style>'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style><style><style type="text/css" id="some_id">#some_id { display:none; }</style></style></style>'));
		$this->assertEquals('#some_id { display:none; }', _class('core_js')->_strip_style_tags('<style><style><style type="text/css" id="some_id">#some_id { display:none; }'));
	}
	public function test_complex() {
		require_css('//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.css');
		$this->assertEquals('', _class('core_css')->show());
	}
}