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
	public $USE_CIPHER		= 4;
	/** @var array Available algorithms (sorted in speed descending order) */
	public $_avail_ciphers	= array(
		4	=> 'CAST_256',
		0	=> 'CAST_128',
	);
	/** @var string Secret key */
	public $_secret_key	= 'secret__13457890'; // Padded for 16 bytes
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
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

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
			$this->_cur_cipher = new PHP_Crypt\PHP_Crypt($this->_secret_key, $cipher_id_to_name[$this->USE_CIPHER], PHP_Crypt\PHP_Crypt::MODE_CBC);
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
	function get_iv() {
		return $this->_iv;
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
		$key = $this->_secret_key;
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_CBC, '');
			$key = substr($key, 0, mcrypt_enc_get_key_size($td));
			$this->_iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init ($td, $key, $this->_iv);
			$encrypted = mcrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			$this->_cur_cipher->cipherKey($key);
			$this->_iv = $this->_cur_cipher->createIV();
			$this->_cur_cipher->IV($this->_iv);
			$encrypted = $this->_cur_cipher->encrypt($data);
		}
		return $encrypted;
	}

	/**
	*/
	function decrypt($data, $secret = null, $cipher = null, $iv) {
		if (!$iv) {
			return false;
		}
		if (isset($secret)) {
			$this->set_key($secret);
		}
		if (isset($cipher)) {
			$this->set_cipher($cipher);
		}
		$this->init();
		$key = $this->_secret_key;
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_CBC, '');
			$key = substr($key, 0, mcrypt_enc_get_key_size($td));
			mcrypt_generic_init ($td, $key, $iv);
			$decrypted = mdecrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			$this->_cur_cipher->cipherKey($key);
			$this->_cur_cipher->IV($iv);
			$decrypted = $this->_cur_cipher->decrypt($data);
		}
		return rtrim($decrypted);
	}

	/**
	* Encrypt specified file using private key
	*/
	function encrypt_file ($source_path, $encrypted_path, $secret = null, $cipher = null) {
		file_put_contents($encrypted_path, $this->encrypt(file_get_contents($source_path), $secret, $cipher));
		return $this;
	}

	/**
	* Decrypt specified file using private key
	*/
	function decrypt_file($source_path, $decrypted_path, $secret = null, $cipher = null, $iv) {
		file_put_contents($decrypted_path, $this->decrypt(file_get_contents($source_path)), $secret, $cipher, $iv);
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
		$encrypted = $this->encrypt($input, $secret, $cipher);
		$iv = $this->_iv;
		return $this->_safe_base64_encode($iv).'|'.$this->_safe_base64_encode($encrypted);
	}

	/**
	* Safe decrypt data from base64 string (replace '/' symbol)
	*/
	function _safe_decrypt_with_base64 ($input, $secret = null, $cipher = null) {
		if (strpos($input, '|') === false) {
			return false;
		}
		list($iv, $encrypted) = explode('|', $input);
		return $this->decrypt($this->_safe_base64_decode($encrypted), $secret, $cipher, $this->_safe_base64_decode($iv));
	}
}
