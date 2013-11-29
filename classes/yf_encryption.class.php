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
		0	=>	'CAST_128',
		4	=>	'CAST_256',
	);
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
	function init () {
		if (!extension_loaded('mcrypt')) {
			$this->USE_MCRYPT = false;
		}
		if ($this->USE_MCRYPT == true) {
			$this->_mcrypt_cipher = constant('MCRYPT_'.$this->_avail_ciphers[$this->USE_CIPHER]);
		} else {/*if ($this->_cur_cipher_id !== $this->USE_CIPHER) {*/
			require_once YF_PATH.'libs/phpcrypt/phpCrypt.php';

			$cipher_id_to_name = array(
				0	=>	PHP_Crypt\PHP_Crypt::CIPHER_CAST_128,
				4	=>	PHP_Crypt\PHP_Crypt::CIPHER_CAST_256,
			);
			$this->_cur_cipher = new PHP_Crypt\PHP_Crypt($this->_secret_key, $cipher_id_to_name[$this->USE_CIPHER], PHP_Crypt\PHP_Crypt::MODE_ECB);
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
			$md5_key = md5($this->_secret_key);
			$key = substr($md5_key, 0, mcrypt_enc_get_key_size($td));

#			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			$iv = substr($md5_key, 0, mcrypt_enc_get_iv_size($td));

			mcrypt_generic_init ($td, $key, $iv);
			$encrypt = mcrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			$this->_cur_cipher->cipherKey($this->_secret_key);

#			$md5_key = md5($this->_secret_key);
#			$iv = substr($md5_key, 0, 8);
#			$this->_cur_cipher->IV($iv);

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
			$md5_key = md5($this->_secret_key);
			$key = substr($md5_key, 0, mcrypt_enc_get_key_size($td));

#			$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			$iv = substr($md5_key, 0, mcrypt_enc_get_iv_size($td));

			mcrypt_generic_init ($td, $key, $iv);
			$decrypt = mdecrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			$this->_cur_cipher->cipherKey($this->_secret_key);

#			$md5_key = md5($this->_secret_key);
#			$iv = substr($md5_key, 0, 8);
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
	*/
	function _safe_base64_encode ($text) {
		$r = array(
			'/' => '*',
		);
		return str_replace(array_keys($r), array_values($r), base64_encode($text));
	}

	/**
	*/
	function _safe_base64_decode ($text) {
		$r = array(
			'*' => '/',
			' ' => '+',
			'%20' => '+',
		);
		return base64_decode(str_replace(array_keys($r), array_values($r), $text));
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
		return $this->_safe_base64_encode($this->encrypt($input));
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
		return $this->decrypt($this->_safe_base64_decode($input));
	}
}
