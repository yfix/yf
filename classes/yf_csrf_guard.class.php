<?php

/**
* CSRF protection
*/
class yf_csrf_guard {

	public $ENABLED = true;
	public $HASH_ALGO = 'sha256';
	public $FORM_ID = null;
	public $TOKEN_NAME = '_token';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args, $this->_chained_mode);
	}

	/**
	*/
	function _extend($name, $func) {
		$this->_extend[$name] = $func;
	}

	/**
	*/
	function configure($params = array()) {
		if (isset($params['form_id'])) {
			$this->FORM_ID = $params['form_id'];
		}
		if (isset($params['token_name'])) {
			$this->TOKEN_NAME = $params['token_name'];
		}
		if (isset($params['hash_algo'])) {
			$this->HASH_ALGO = $params['hash_algo'];
		}
		return $this;
	}

	/**
	*/
	function generate() {
		if (function_exists('hash_algos') && in_array($this->HASH_ALGO, hash_algos())) {
			$token = hash($this->HASH_ALGO, mt_rand(0, mt_getrandmax()). '|'. $_SERVER['REMOTE_ADDR']. '|'. microtime());
		} else {
			$token = '';
			for ($i = 0; $i < 64; ++$i) {
				$r = mt_rand(0, 35);
				if ($r < 26) {
					$c = chr(ord('a') + $r);
				} else {
					$c = chr(ord('0') + $r - 26);
				} 
				$token .= $c;
			}
		}
		$this->set($token);
		return $token;
	}

	/**
	*/
	function validate($token_value) {
		if (!$this->ENABLED) {
			return true;
		}
		$token = $this->get();
		if ($token === false) {
			return false;
		} elseif ($token === $token_value) {
			$result = true;
		} else { 
			$result = false;
		}
		$this->del();
		return $result;
	}

	/**
	*/
	function get() {
		$key = $this->FORM_ID;
		if (isset($_SESSION[$this->TOKEN_NAME][$key])) {
			return $_SESSION[$this->TOKEN_NAME][$key];
		} else {
			return false;
		}
	}

	/**
	*/
	function set($value) {
		$key = $this->FORM_ID;
		$_SESSION[$this->TOKEN_NAME][$key] = $value;
		return $this;
	}

	/**
	*/
	function del() {
		$key = $this->FORM_ID;
		$_SESSION[$this->TOKEN_NAME][$key] = '';
		unset($_SESSION[$this->TOKEN_NAME][$key]);
		return $this;
	}
}
