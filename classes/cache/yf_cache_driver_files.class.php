<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_files extends yf_cache_driver {

	/** @var @conf_skip */
	public $FILE_PREFIX	= 'cache_';
	/** @var @conf_skip */
	public $FILE_EXT	= '.php';
	/** @var bool Auto-create cache folder */
	public $CREATE_DIR	= true;

	/**
	*/
	function _init() {
		$this->CACHE_DIR = PROJECT_PATH. 'core_cache/';
		if (!file_exists($this->CACHE_DIR) && $this->CREATE_DIR) {
// TODO: add 1-2 levels of subdirs to store 100 000+ entries easily in files (no matters when use memcached)
			mkdir($this->CACHE_DIR, 0777, true);
		}
	}

	/**
	*/
	function is_ready() {
		return file_exists($this->CACHE_DIR) && is_writable($this->CACHE_DIR);
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->CACHE_DIR. $this->FILE_PREFIX. $name. $this->FILE_EXT;
		return $this->_get_cache_file($path, $ttl);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->CACHE_DIR. $this->FILE_PREFIX. $name. $this->FILE_EXT;
		return $this->_put_cache_file($data, $path);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->CACHE_DIR. $this->FILE_PREFIX. $name. $this->FILE_EXT;
		if (file_exists($path)) {
			unlink($path);
		}
		return !file_exists($path);
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		foreach ((array)$this->_get_all_files() as $path) {
			unlink($path);
		}
		return true;
	}

	/**
	*/
	function list_keys() {
		if (!$this->is_ready()) {
			return null;
		}
		$keys = array();
		foreach ((array)$this->_get_all_files() as $path) {
			$name = substr(trim(basename($path)), strlen($this->FILE_PREFIX), -strlen($this->FILE_EXT));
			if ($name) {
				$keys[$name] = $name;
			}
		}
		return $keys;
	}

	/**
	*/
	function _get_all_files() {
		if (!$this->is_ready()) {
			return null;
		}
		return _class('dir')->rglob($this->CACHE_DIR. '*'. $this->FILE_PREFIX. '*'. $this->FILE_EXT);
	}

	/**
	* Do get cache file contents
	*/
	function _get_cache_file ($path = '', $ttl = 0) {
		if (empty($path)) {
			return null;
		}
		if (!file_exists($path)) {
			return null;
		}
		$last_modified = filemtime($path);
		$ttl = intval($ttl ?: $this->_parent->TTL);
		if ($last_modified < (time() - $ttl)) {
			return null;
		}
		$data = array();
		if (DEBUG_MODE) {
			$_time_start = microtime(true);
		}

		include $path;

		if (DEBUG_MODE) {
			$_cf = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $path));
			debug('include_files_exec_time::'.$_cf, microtime(true) - $_time_start);
		}
		return $data;
	}

	/**
	* Do put cache file contents
	*/
	function _put_cache_file ($data = array(), $path = '') {
		if (empty($path)) {
			return false;
		}
		$str = str_replace(' => '.PHP_EOL.'array (', '=>array(', preg_replace('/^\s+/m', '', var_export($data, 1)));
		$str = '<?'.'php'.PHP_EOL.'$data = '.$str.';'.PHP_EOL;
		return file_put_contents($path, $str);
	}
}
