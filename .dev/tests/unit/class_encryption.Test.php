<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

/**
 * @requires extension mcrypt
 */
class class_encryption_test extends PHPUnit_Framework_TestCase {
	private static $secret = 'my_secret_padded_to_24_b';
	private static $to_encode = 'testing long string containing different symols:;.,%^&*()';
	private static $cipher = 'CAST_128';
	private static $_bak_settings = array();

	public static function setUpBeforeClass() {
		self::$_bak_settings['USE_MCRYPT'] = _class('encryption')->USE_MCRYPT;
		self::$_bak_settings['USE_CIPHER'] = _class('encryption')->USE_CIPHER;
		_class('encryption')->USE_MCRYPT = true;
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
		_class('encryption')->set_cipher('cast256');
		$this->assertEquals(4, _class('encryption')->USE_CIPHER);
		$this->assertEquals('CAST_256', _class('encryption')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher(self::$cipher)->get_cipher());
		$this->assertEquals('CAST_256', _class('encryption')->set_cipher('cast256')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('cast128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('cast-128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST-128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST_128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('CAST 128')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('  CAST 128  ')->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('  CAST128  ')->get_cipher());

		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher(self::$cipher)->get_cipher());
		$this->assertEquals(self::$cipher, _class('encryption')->set_cipher('some unknown cipher')->get_cipher());
		$this->assertEquals('CAST_128', _class('encryption')->set_cipher('')->get_cipher());
	}

	public function test_03() {
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = false;
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			$this->assertEquals($cipher, _class('encryption')->set_cipher($cipher)->get_cipher());
			$this->assertEquals($cipher, _class('encryption')->set_cipher(strtolower($cipher))->get_cipher());
		}
		_class('encryption')->USE_MCRYPT = $prev;
	}

	public function test_04() {
		// Do this test only if mcrypt is available
		if (!function_exists('mcrypt_module_open')) {
			return false;
		}
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = true;
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			$this->assertEquals($cipher, _class('encryption')->set_cipher($cipher)->get_cipher());
			$this->assertEquals($cipher, _class('encryption')->set_cipher(strtolower($cipher))->get_cipher());
		}
		_class('encryption')->USE_MCRYPT = $prev;
	}

	public function test_11() {
		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->encrypt(self::$to_encode, self::$secret);
		$iv = _class('encryption')->get_iv();
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);

		$decrypted = _class('encryption')->decrypt($encrypted, self::$secret, null, $iv);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_21() {
		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret);
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);
		$this->assertRegexp('/^[a-z0-9\=+\*\|]+$/i', $encrypted);

		$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_31() {
		_class('encryption')->set_cipher('cast256');

		$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret);
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);
		$this->assertRegexp('/^[a-z0-9\=+\*\|]+$/i', $encrypted);

		$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);
	}

	public function test_41() {
		$b64_decoded = _class('encryption')->_safe_base64_decode('jkVmKEjPznMjISVwD3L54Pxjle+Qb/3JR8L6CCQ5K//WSeDejJykl7QeVHVAw7X50D7qaXS2jlrDfaD1sN52iA==');
		$this->assertEquals($b64_decoded, _class('encryption')->_safe_base64_decode('jkVmKEjPznMjISVwD3L54Pxjle+Qb*3JR8L6CCQ5K**WSeDejJykl7QeVHVAw7X50D7qaXS2jlrDfaD1sN52iA=='));
		$this->assertEquals($b64_decoded, _class('encryption')->_safe_base64_decode('jkVmKEjPznMjISVwD3L54Pxjle Qb*3JR8L6CCQ5K**WSeDejJykl7QeVHVAw7X50D7qaXS2jlrDfaD1sN52iA=='));
		$this->assertEquals($b64_decoded, _class('encryption')->_safe_base64_decode('jkVmKEjPznMjISVwD3L54Pxjle Qb/3JR8L6CCQ5K//WSeDejJykl7QeVHVAw7X50D7qaXS2jlrDfaD1sN52iA=='));
	}

	public function test_42() {
		$b64_encoded = _class('encryption')->_safe_base64_encode(self::$to_encode);
		$this->assertNotEquals($b64_encoded, self::$to_encode);
		$this->assertEquals(self::$to_encode, _class('encryption')->_safe_base64_decode($b64_encoded));
	}

	public function test_51() {
		// Do this test only if mcrypt is available
		if (!function_exists('mcrypt_module_open')) {
			return false;
		}
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = true;
		$prev_encrypted = '';
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, $cipher);
			$this->assertRegexp('/^[a-z0-9\=+\*\|]+$/i', $encrypted);
			$this->assertNotEquals($prev_encrypted, $encrypted);
			$prev_encrypted = $encrypted;

			$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret, $cipher);
			$this->assertEquals(self::$to_encode, $decrypted);
		}
		_class('encryption')->USE_MCRYPT = $prev;
	}

	public function test_52() {
		// Do pure php encode/decode testing
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = false;
		$prev_encrypted = '';
		foreach (_class('encryption')->_avail_ciphers as $cipher) {
			$encrypted = _class('encryption')->_safe_encrypt_with_base64(self::$to_encode, self::$secret, $cipher);
			$this->assertRegexp('/^[a-z0-9\=+\*\|]+$/i', $encrypted);
			$this->assertNotEquals($prev_encrypted, $encrypted);
			$prev_encrypted = $encrypted;

			$decrypted = _class('encryption')->_safe_decrypt_with_base64($encrypted, self::$secret, $cipher);
			$this->assertEquals(self::$to_encode, $decrypted);
		}
		_class('encryption')->USE_MCRYPT = $prev;
	}

	// Test if mcrypt and not mcrypt versions can be encoded/decoded similarly
	public function test_61() {
		// Do this test only if mcrypt is available
		if (!function_exists('mcrypt_module_open')) {
			return false;
		}
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = true;

		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->encrypt(self::$to_encode, self::$secret);
		$iv = _class('encryption')->get_iv();
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);

		_class('encryption')->USE_MCRYPT = false;

		$decrypted = _class('encryption')->decrypt($encrypted, self::$secret, null, $iv);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);

		_class('encryption')->USE_MCRYPT = $prev;
	}

	// Test if mcrypt and not mcrypt versions can be encoded/decoded similarly
	public function test_62() {
		// Do this test only if mcrypt is available
		if (!function_exists('mcrypt_module_open')) {
			return false;
		}
		$prev = _class('encryption')->USE_MCRYPT;
		_class('encryption')->USE_MCRYPT = false;

		_class('encryption')->set_cipher('cast128');

		$encrypted = _class('encryption')->encrypt(self::$to_encode, self::$secret);
		$iv = _class('encryption')->get_iv();
		$this->assertNotEmpty($encrypted);
		$this->assertNotEquals($encrypted, self::$to_encode);

		_class('encryption')->USE_MCRYPT = true;

		$decrypted = _class('encryption')->decrypt($encrypted, self::$secret, null, $iv);
		$this->assertNotEmpty($decrypted);
		$this->assertEquals(self::$to_encode, $decrypted);

		_class('encryption')->USE_MCRYPT = $prev;
	}
}