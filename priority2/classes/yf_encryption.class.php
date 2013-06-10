<?php

/**
* This class perform crypt/encrypt operations
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_encryption {

	/** @var bool Use internal PHP MCrypt module or not */
	public $USE_MCRYPT		= false;
	/** @var int Define which cryptographic algorithm to use */
	public $USE_CIPHER		= 0;
	/** @var array Available algorithms (sorted in speed descending order) */
	public $_avail_ciphers	= array(
		0	=>	"CAST_128", // note: PHP Class exists
		1	=>	"BLOWFISH", // note: PHP Class exists
		2	=>	"XTEA",
		3	=>	"TWOFISH",
		4	=>	"CAST_256",
		5	=>	"GOST",
		6	=>	"DES",
		7	=>	"SERPENT",
		8	=>	"RIJNDAEL_128",
		9	=>	"RC2",
		10	=>	"RIJNDAEL_192",
		11	=>	"3DES",
		12	=>	"LOKI97",
		13	=>	"RIJNDAEL_256",
		14	=>	"SAFERPLUS",
	);
	/** @var string Secret key */
	public $_secret_key	= "secret 134578";
	/** @var mixed @conf_skip Mcrypt cipher constant value */
	public $_mcrypt_cipher = null;
	/** @var mixed @conf_skip Current cipher processor */
	public $_cur_cipher	= null;
	/** @var mixed @conf_skip Key assigned */
	public $_key_assigned	= false;
	/** @var mixed @conf_skip Initialization vector (Need in non-ECB mode) */
	public $_iv			= null;

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_encryption () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
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
	}

	/**
	* Initialize params
	*/
	function init () {
		// Check if Mcrypt module exists
		if (!extension_loaded('mcrypt')) $this->USE_MCRYPT = false;
		// Process using PHP classes
		if ($this->USE_MCRYPT == true) {
			if (isset($this->_avail_ciphers[$this->USE_CIPHER])) {
				eval("\$this->_mcrypt_cipher = MCRYPT_".$this->_avail_ciphers[$this->USE_CIPHER].";");
			} else trigger_error("Wrong Cipher number \"".$this->USE_CIPHER."\"", E_USER_ERROR);
		} elseif (!is_object($this->_cur_cipher)) {
			$driver_name = strtolower($this->_avail_ciphers[$this->USE_CIPHER]);
			// Try Create new instanse of the driver class
			$driver_loaded_class_name = main()->load_class_file($driver_name, "classes/crypto/");
			if ($driver_loaded_class_name) {
				$this->_cur_cipher = &new $driver_loaded_class_name();
			}
			if (!is_object($this->_cur_cipher)) {
				trigger_error("Wrong Cipher number \"".$this->USE_CIPHER."\"", E_USER_ERROR);
			}
		}
	}

	/**
	* Set secret key
	*/
	function set_key($value) {
		$this->_secret_key = $value;
	}

	/**
	* Encrypt block of data
	*/
	function encrypt($data) {
		// Initialization
		$this->init();
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_ECB, '');
			$key = substr(md5($this->_secret_key), 0, mcrypt_enc_get_key_size($td));
			// In ECB mode IV value is ignored !
			if (!strlen($this->_iv)) $this->_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init ($td, $key, $this->_iv);
			$encrypt = mcrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			if ($this->_key_assigned == false) {
				$this->_cur_cipher->setkey($this->_secret_key);
				$this->_key_assigned = true;
			}
			$encrypt = $this->_cur_cipher->encrypt($data);
		}
		return $encrypt;
	}

	/**
	* Decrypt block of data
	*/
	function decrypt($data) {
		// Initialization
		$this->init();
		// MCrypt PHP module processing (high speed)
		if ($this->USE_MCRYPT && $this->_mcrypt_cipher) {
			$td = mcrypt_module_open ($this->_mcrypt_cipher, '', MCRYPT_MODE_ECB, '');
			$key = substr(md5($this->_secret_key), 0, mcrypt_enc_get_key_size($td));
			// In ECB mode IV value is ignored !
			if (!strlen($this->_iv)) $this->_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init ($td, $key, $this->_iv);
			$decrypt = mdecrypt_generic ($td, $data);
			mcrypt_generic_deinit ($td);
		// Use classes written in PHP (less speed, more flexibility)
		} elseif (is_object($this->_cur_cipher)) {
			if ($this->_key_assigned == false) {
				$this->_cur_cipher->setkey($this->_secret_key);
				$this->_key_assigned = true;
			}
			$decrypt = $this->_cur_cipher->decrypt($data);
		}
		return rtrim($decrypt);
	}

	/**
	* Encrypt specified file using private key
	*/
	function encrypt_file ($SourceFile, $EncryptedFile) {
		$data = file_get_contents($SourceFile);
		$encrypted = $this->encrypt($data);
		file_put_contents($EncryptedFile, $encrypted);
	}

	/**
	* Decrypt specified file using private key
	*/
	function decrypt_file($SourceFile, $DecryptedFile) {
		$data = file_get_contents($SourceFile);
		$decrypted = $this->decrypt($data);
		file_put_contents($DecryptedFile, $decrypted);
	}

	/**
	* Safe encrypt data into base64'ed string (replace "/" symbol)
	*/
	function _safe_encrypt_with_base64 ($input = "") {
		$output = base64_encode($this->encrypt($input));
		$output = str_replace("/", "*", $output);
		return $output;
	}

	/**
	* Safe decrypt data from base64'ed string (replace "/" symbol)
	*/
	function _safe_decrypt_with_base64 ($input = "") {
		$input = str_replace("*", "/", $input);
		$output = $this->decrypt(base64_decode($input));
		return $output;
	}
}
