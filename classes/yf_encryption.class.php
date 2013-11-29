<?php

/**
* This class perform crypt/encrypt operations
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_encryption {

	/** @var bool Use internal PHP MCrypt module or not */
	public $USE_MCRYPT		= true;
	/** @var int Define which cryptographic algorithm to use */
	public $USE_CIPHER		= 0;
	/** @var array Available algorithms (sorted in speed descending order) */
	public $_avail_ciphers	= array(
		0	=>	'CAST_128', // note: PHP Class exists
		1	=>	'BLOWFISH', // note: PHP Class exists
/*
		2	=>	'XTEA',
		3	=>	'TWOFISH',
		4	=>	'CAST_256',
		5	=>	'GOST',
		6	=>	'DES',
		7	=>	'SERPENT',
		8	=>	'RIJNDAEL_128',
		9	=>	'RC2',
		10	=>	'RIJNDAEL_192',
*/
		11	=>	'3DES',
/*
		12	=>	'LOKI97',
		13	=>	'RIJNDAEL_256',
		14	=>	'SAFERPLUS',
*/
	);
/*
MCRYPT_3DES
MCRYPT_ARCFOUR_IV (libmcrypt > 2.4.x only)
MCRYPT_ARCFOUR (libmcrypt > 2.4.x only)
MCRYPT_BLOWFISH
MCRYPT_CAST_128
MCRYPT_CAST_256
MCRYPT_CRYPT
MCRYPT_DES
MCRYPT_DES_COMPAT (libmcrypt 2.2.x only)
MCRYPT_ENIGMA (libmcrypt > 2.4.x only, alias for MCRYPT_CRYPT)
MCRYPT_GOST
MCRYPT_IDEA (non-free)
MCRYPT_LOKI97 (libmcrypt > 2.4.x only)
MCRYPT_MARS (libmcrypt > 2.4.x only, non-free)
MCRYPT_PANAMA (libmcrypt > 2.4.x only)
MCRYPT_RIJNDAEL_128 (libmcrypt > 2.4.x only)
MCRYPT_RIJNDAEL_192 (libmcrypt > 2.4.x only)
MCRYPT_RIJNDAEL_256 (libmcrypt > 2.4.x only)
MCRYPT_RC2
MCRYPT_RC4 (libmcrypt 2.2.x only)
MCRYPT_RC6 (libmcrypt > 2.4.x only)
MCRYPT_RC6_128 (libmcrypt 2.2.x only)
MCRYPT_RC6_192 (libmcrypt 2.2.x only)
MCRYPT_RC6_256 (libmcrypt 2.2.x only)
MCRYPT_SAFER64
MCRYPT_SAFER128
MCRYPT_SAFERPLUS (libmcrypt > 2.4.x only)
MCRYPT_SERPENT(libmcrypt > 2.4.x only)
MCRYPT_SERPENT_128 (libmcrypt 2.2.x only)
MCRYPT_SERPENT_192 (libmcrypt 2.2.x only)
MCRYPT_SERPENT_256 (libmcrypt 2.2.x only)
MCRYPT_SKIPJACK (libmcrypt > 2.4.x only)
MCRYPT_TEAN (libmcrypt 2.2.x only)
MCRYPT_THREEWAY
MCRYPT_TRIPLEDES (libmcrypt > 2.4.x only)
MCRYPT_TWOFISH (for older mcrypt 2.x versions, or mcrypt > 2.4.x )
MCRYPT_TWOFISH128 (TWOFISHxxx are available in newer 2.x versions, but not in the 2.4.x versions)
MCRYPT_TWOFISH192
MCRYPT_TWOFISH256
MCRYPT_WAKE (libmcrypt > 2.4.x only)
MCRYPT_XTEA (libmcrypt > 2.4.x only)
*/
/*
3DES
3Way
AES128
AES192
AES256
ARC4
Blowfish
CAST128
CAST256
DES
Enigma
RC2
Rijndael128
Rijndael192
Rijndael256
Rijndael
SimpleXOR
Skipjack
Vigenere
*/
	/** @var string Secret key */
	public $_secret_key	= 'secret 134578';
	/** @var mixed @conf_skip Mcrypt cipher constant value */
	public $_mcrypt_cipher = null;
	/** @var mixed @conf_skip Current cipher processor */
	public $_cur_cipher	= null;
	/** @var mixed @conf_skip Current cipher id */
	public $_cur_cipher_id	= null;
	/** @var mixed @conf_skip Key assigned */
	public $_key_assigned	= false;
	/** @var mixed @conf_skip Initialization vector (Need in non-ECB mode) */
	public $_iv			= null;

	/**
	*/
	function __construct () {
		$crypto_use_mcrypt = conf('crypto_use_mcrypt');
		if (isset($crypto_use_mcrypt)) {
			$this->USE_MCRYPT = $crypto_use_mcrypt;
		}
		$crypto_use_cipher = conf('crypto_use_cipher');
		if (isset($crypto_use_cipher)) {
			$this->USE_CIPHER = $crypto_use_cipher;
		}
		if (!extension_loaded('mcrypt')) {
			$this->USE_MCRYPT = false;
		}
	}

	/**
	*/
	function _load_phpcrypt () {
		require_once YF_PATH.'libs/phpcrypt/phpCrypt.php';
#		use PHP_Crypt\PHP_Crypt as PHP_Crypt;

#		$text = "This is my secret message.";
#		$key = "^mY@TEst~Key_0123456789abcefghij"; // the key will be truncated if it's too long

#		$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_256, PHP_Crypt::MODE_CTR);

#		$iv = $crypt->createIV();
#		$encrypt = $crypt->encrypt($text);

#		$crypt->IV($iv);
#		$decrypt = $crypt->decrypt($encrypt);

#		print "CIPHER: ".$crypt->cipherName()."\n";
#		print "MODE: ".$crypt->modeName()."\n";
	}

	/**
	*/
	function init () {
		if (!extension_loaded('mcrypt')) {
			$this->USE_MCRYPT = false;
		}
		if ($this->USE_MCRYPT == true) {
			if (isset($this->_avail_ciphers[$this->USE_CIPHER])) {
				eval('$this->_mcrypt_cipher = MCRYPT_'.$this->_avail_ciphers[$this->USE_CIPHER].';');
			} else {
				trigger_error('Wrong Cipher number '.$this->USE_CIPHER, E_USER_ERROR);
			}
		} elseif ($this->_cur_cipher_id !== $this->USE_CIPHER) {
/*
			$driver_name = 'encryption_'.strtolower($this->_avail_ciphers[$this->USE_CIPHER]);
			$driver_loaded_class_name = main()->load_class_file($driver_name, 'classes/encryption/');
			if ($driver_loaded_class_name) {
				$this->_cur_cipher = new $driver_loaded_class_name();
				$this->_cur_cipher_id = $this->USE_CIPHER;
			}
			if (!is_object($this->_cur_cipher)) {
				trigger_error('Wrong Cipher number '.$this->USE_CIPHER, E_USER_ERROR);
			}
*/
			$this->_load_phpcrypt();

			$cipher_id_to_name = array(
				0	=>	PHP_Crypt\PHP_Crypt::CIPHER_CAST_128,
				1	=>	PHP_Crypt\PHP_Crypt::CIPHER_BLOWFISH,
				11	=>	PHP_Crypt\PHP_Crypt::CIPHER_3DES,
			);
/*
			const CIPHER_3DES			= "3DES";
			const CIPHER_3WAY			= "3-Way";
			const CIPHER_AES_128		= "AES-128";
			const CIPHER_AES_192		= "AES-192";
			const CIPHER_AES_256		= "AES-256";
			const CIPHER_ARC4			= "ARC4"; // Alternative RC4
			const CIPHER_BLOWFISH		= "Blowfish";
			const CIPHER_CAST_128		= "CAST-128";
			const CIPHER_CAST_256		= "CAST-256";
			const CIPHER_DES			= "DES";
			const CIPHER_ENIGMA			= "Enigma";
			const CIPHER_GOST			= "GOST";
			const CIPHER_RC2			= "RC2";
			const CIPHER_RIJNDAEL_128	= "Rijndael-128";
			const CIPHER_RIJNDAEL_192	= "Rijndael-192";
			const CIPHER_RIJNDAEL_256	= "Rijndael-256";
			const CIPHER_SKIPJACK		= "Skipjack";
			const CIPHER_SIMPLEXOR		= "SimpleXOR";
			const CIPHER_VIGENERE		= "Vigenere"; // historical
*/
			$this->_cur_cipher = new PHP_Crypt\PHP_Crypt($this->_secret_key, $cipher_id_to_name[$this->USE_CIPHER]/*, PHP_Crypt\PHP_Crypt::MODE_CTR*/);
		}
	}

	/**
	*/
	function get_key() {
		return $this->_secret_key;
	}

	/**
	* Alias
	*/
	function get_secret() {
		return $this->get_key();
	}

	/**
	*/
	function set_key($value) {
		$this->_secret_key = $value;
		return $this;
	}

	/**
	* Alias
	*/
	function set_secret($value) {
		return $this->set_key($value);
	}

	/**
	*/
	function get_cipher() {
		return $this->_avail_ciphers[$this->USE_CIPHER];
	}

	/**
	* Choose encoding/decoding algorithm by its name.
	* Examples: _class('encyption')->set_cipher('cast128'), _class('encyption')->set_cipher('CAST_128')
	*/
	function set_cipher($name) {
		$name = str_replace(array('_','-',' '), '', strtolower(trim($name)));
		if (!strlen($name)) {
			return $this;
		}
		$name_to_id = array();
		foreach((array)$this->_avail_ciphers as $id => $n) {
			$n = str_replace(array('_','-',' '), '', strtolower(trim($n)));
			$name_to_id[$n] = $id;
		}
		if (isset($name_to_id[$name])) {
			$this->USE_CIPHER = $name_to_id[$name];
		}
		return $this;
	}

	/**
	*/
	function encrypt($data, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		$this->init();
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_ECB, '');
			$key = substr(md5($this->_secret_key), 0, mcrypt_enc_get_key_size($td));
			// In ECB mode IV value is ignored !
			if (!strlen($this->_iv)) {
				$this->_iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			}
			mcrypt_generic_init ($td, $key, $this->_iv);
			$encrypt = mcrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
/*
			if ($this->_key_assigned == false) {
				$this->_cur_cipher->setkey($this->_secret_key);
				$this->_key_assigned = true;
			}
			$encrypt = $this->_cur_cipher->encrypt($data);
*/
			$this->_cur_cipher->cipherKey($this->_secret_key);
#			$iv = $this->_cur_cipher->createIV();
			$encrypt = $this->_cur_cipher->encrypt($data);
		}
		return $encrypt;
	}

	/**
	*/
	function decrypt($data, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		$this->init();
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_ECB, '');
			$key = substr(md5($this->_secret_key), 0, mcrypt_enc_get_key_size($td));
			// In ECB mode IV value is ignored !
			if (!strlen($this->_iv)) {
				$this->_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			}
			mcrypt_generic_init ($td, $key, $this->_iv);
			$decrypt = mdecrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
/*
			if ($this->_key_assigned == false) {
				$this->_cur_cipher->setkey($this->_secret_key);
				$this->_key_assigned = true;
			}
			$decrypt = $this->_cur_cipher->decrypt($data);
*/
			$this->_cur_cipher->cipherKey($this->_secret_key);
#			$iv = $this->_cur_cipher->createIV();
#			$this->_cur_cipher->IV($iv);
			$decrypt = $this->_cur_cipher->decrypt($data);
		}
		return rtrim($decrypt);
	}

	/**
	* Encrypt specified file using private key
	*/
	function encrypt_file ($source, $encrypted, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		file_put_contents($encrypted, $this->encrypt(file_get_contents($source)));
		return $this;
	}

	/**
	* Decrypt specified file using private key
	*/
	function decrypt_file($source, $decrypted, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		file_put_contents($decrypted, $this->decrypt(file_get_contents($source)));
		return $this;
	}

	/**
	* Safe encrypt data into base64 string (replace '/' symbol)
	*/
	function _safe_encrypt_with_base64 ($input, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		$r = array(
			'/' => '*',
		);
		return str_replace(array_keys($r), array_values($r), base64_encode($this->encrypt($input)));
	}

	/**
	* Safe decrypt data from base64 string (replace '/' symbol)
	*/
	function _safe_decrypt_with_base64 ($input, $secret = null, $cipher = null) {
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		$r = array(
			'*' => '/',
			' ' => '+',
			'%20' => '+',
		);
		return $this->decrypt(base64_decode(str_replace(array_keys($r), array_values($r), $input)));
	}
}
