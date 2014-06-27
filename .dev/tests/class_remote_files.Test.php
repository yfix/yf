<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/**
 * @requires extension curl
 */
class class_remote_files extends PHPUnit_Framework_TestCase {
	public function test_get_remote_page_simple() {
		$this->assertNotEmpty( common()->get_remote_page('http://google.com/') );
	}
}
