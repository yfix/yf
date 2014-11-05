<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_class_basename extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$this->assertEquals('test', class_basename('test'));
		$this->assertEquals('test', class_basename('yf_test'));
		$this->assertEquals('test', class_basename('site__test'));
		$this->assertEquals('test', class_basename('adm__test'));
		$this->assertEquals('test', class_basename('custom__test', 'custom__'));
		$this->assertEquals('test', class_basename('custom_test', 'custom_'));
		$this->assertEquals('test', class_basename('yf_custom_test', 'custom_'));
		$this->assertEquals('test', class_basename('test', 'custom_'));
		$this->assertEquals('test', class_basename('test',''));
		$this->assertEquals('test', class_basename('test','',''));
		$this->assertEquals('test', class_basename('test','','_suffix'));
		$this->assertEquals('test', class_basename('test_suffix','','_suffix'));
		$this->assertEquals('test', class_basename('prefix_test_suffix','prefix_','_suffix'));
		$this->assertEquals('test', class_basename('yf_test_suffix','','_suffix'));
		$this->assertEquals('test', class_basename('yf_prefix_test_suffix','prefix_','_suffix'));
	}
}