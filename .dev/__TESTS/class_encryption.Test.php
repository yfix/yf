<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_encryption_test extends PHPUnit_Framework_TestCase {
	public function test_01() {
		_class('encryption')->set_secret('my_secret');
#		$this->assertEquals(true, _class('encryption')->_safe_encrypt_with_base64('test') );
# TODO:
# set_cipher
# encrypt
# decrypt
# encrypt_file
# decrypt_file
# _safe_encrypt_with_base64
# _safe_decrypt_with_base64
	}
}