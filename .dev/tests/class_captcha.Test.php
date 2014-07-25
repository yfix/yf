<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/**
 * @requires extension gd
 */
class class_captcha_test extends PHPUnit_Framework_TestCase {
	public function test_captcha() {
		ob_start();
		_class('captcha')->show_image($no_header = true);
		$img = ob_get_clean();
		$this->assertNotEmpty( $img );
		$this->assertGreaterThan( 2000, strlen($img) );
	}
}