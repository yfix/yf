<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_encryption_test extends PHPUnit_Framework_TestCase {
	private static $secret = 'my_secret';
	private static $to_encode = 'testing long string containing different symols:;.,%^&*()';
	private static $cipher = 'CAST_128';
	private static $_bak_settings = array();

	public static function setUpBeforeClass() {
		self::$_bak_settings['USE_MCRYPT'] = _class('encryption')->USE_MCRYPT;
		self::$_bak_settings['USE_CIPHER'] = _class('encryption')->USE_CIPHER;
		_class('encryption')->USE_MCRYPT = false;
	}

	public static function tearDownAfterClass() {
		_class('encryption')->USE_MCRYPT = self::$_bak_settings['USE_MCRYPT'];
		_class('encryption')->USE_CIPHER = self::$_bak_settings['USE_CIPHER'];
	}

	public function test_01() {
		_class('encryption')->_secret_key = '';
		_class('encryption')->set_secret(self::$secret);
		$this->assertEquals(self::$secret, _class('encryption')->_secret_key);
		$this->assertEquals(self::$secret, _class('encryption')->get_secret());

		_class('encryption')->_secret_key = '';
		_class('encryption')->set_key(self::$secret);
		$this->assertEquals(self::$secret, _class('encryption')->_secret_key);
		$this->assertEquals(self::$secret, _class('encryption')->get_key());
	}

	public function test_02() {
		_class('encryption')->set_cipher('3des');
		$this->assertEquals(11, _class('encryption')->USE_CIPHER);
		$this->assertEquals('3DES', _class('encryption')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher(self::$cipher)->get_cipher());
		$this->assertEquals('3DES', _class('encryption')->set_cipher('3des')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('cast128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('cast-128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST-128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST_128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST 128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('  CAST 128  ')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('  CAST128  ')->get_cipher());

		$this->assertEquals('BLOWFISH', _class('encryption')->set_cipher('blowfish')->get_cipher());
		$this->assertEquals('BLOWFISH', _class('encryption')->set_cipher('BLOWFISH')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher(self::$cipher)->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('some unknown cipher')->get_cipher());
		$this->assertEquals('CAST_128', _class('encryption')->set_cipher('')->get_cipher());
	}

	public function test_03() {
		_class('encryption')->USE_MCRYPT = false;
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			if (!_class('encryption')->USE_MCRYPT && !in_array($cipher, array('CAST_128', 'BLOWFISH'))) {
				continue;
			}
			$this->assertEquals($cipher, _class('encryption')->set_cipher($cipher)->get_cipher());
			$this->assertEquals($cipher, _class('encryption')->set_cipher(strtolower($cipher))->get_cipher());
		}
	}

	public function test_04() {
		_class('encryption')->USE_MCRYPT = true;
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			if (!_class('encryption')->USE_MCRYPT && !in_array($cipher, array('CAST_128', 'BLOWFISH'))) {
				continue;
			}
			$this->assertEquals($cipher, _class('encryption')->set_cipher($cipher)->get_cipher());
			$this->assertEquals($cipher, _class('encryption')->set_cipher(strtolower($cipher))->get_cipher());
		}
		_class('encryption')->USE_MCRYPT = false;
	}

	public function test_11() {
		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->encrypt(self::$to_encode, self::$secret);
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);

		$decrypted = _class('encryption')->decrypt($encrypted, self::$secret);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_21() {
		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret);
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);
		$this->assertRegexp('/^[a-z0-9\=+\*]+$/i', $encrypted);
		$this->assertEquals('jkVmKEjPznM5brNc+dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282+WX4HhJE6YZgYnDo054w==', $encrypted);

		$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_22() {
		$this->assertEquals(
			'jkVmKEjPznM5brNc+dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282+WX4HhJE6YZgYnDo054w==',
			_class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, self::$cipher)
		);
	}

	public function test_31() {
		_class('encryption')->set_cipher('blowfish');

		$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret);
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);
		$this->assertRegexp('/^[a-z0-9\=+\*]+$/i', $encrypted);
		$this->assertEquals('VHRbYP9UBcpE3TQwjAlWERnQ4o1hSYK4evSry5yrEfgJqMRifcLWqdnHcOFnFsrTufZWJt0jxRpL7KzunBEh1A==', $encrypted);

		$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_32() {
		$this->assertEquals(
			'VHRbYP9UBcpE3TQwjAlWERnQ4o1hSYK4evSry5yrEfgJqMRifcLWqdnHcOFnFsrTufZWJt0jxRpL7KzunBEh1A==',
			_class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, 'blowfish')
		);
	}

	public function test_41() {
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64('jkVmKEjPznM5brNc+dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282+WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64('jkVmKEjPznM5brNc dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282 WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64('jkVmKEjPznM5brNc+dFQZjyvLqYng+8dFvy3E/o2uDfoddCEo2J3VK079cHoOST282+WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64('jkVmKEjPznM5brNc dFQZjyvLqYng+8dFvy3E/o2uDfoddCEo2J3VK079cHoOST282+WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64('jkVmKEjPznM5brNc%20dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282%20WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
		$this->assertNotEquals(self::$to_encode, _class('encryption')->_safe_decrypt_with_base64(' jkVmKEjPznM5brNc dFQZjyvLqYng+8dFvy3E*o2uDfoddCEo2J3VK079cHoOST282 WX4HhJE6YZgYnDo054w==', self::$secret, self::$cipher));
	}

	public function test_51() {
		$prev_encrypted = '';
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			if (!_class('encryption')->USE_MCRYPT && !in_array($cipher, array('CAST_128', 'BLOWFISH'))) {
				continue;
			}
			$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, $cipher);
			$this->assertRegexp('/^[a-z0-9\=+\*]+$/i', $encrypted);
			$this->assertNotEquals($prev_encrypted, $encrypted);
			$prev_encrypted = $encrypted;

			$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret, $cipher);
			$this->assertEquals(self::$to_encode, $decrypted);
		}
	}

	public function test_52() {
		_class('encryption')->USE_MCRYPT = true;
		$prev_encrypted = '';
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			if (!_class('encryption')->USE_MCRYPT && !in_array($cipher, array('CAST_128', 'BLOWFISH'))) {
				continue;
			}
			$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, $cipher);
			$this->assertRegexp('/^[a-z0-9\=+\*]+$/i', $encrypted);
			$this->assertNotEquals($prev_encrypted, $encrypted);
			$prev_encrypted = $encrypted;

			$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret, $cipher);
			$this->assertEquals(self::$to_encode, $decrypted);
		}
		_class('encryption')->USE_MCRYPT = false;
	}
}