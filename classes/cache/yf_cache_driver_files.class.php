<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_files extends yf_cache_driver {

	/** @var @conf_skip */
	public $FILE_PREFIX	= 'cache_';
	/** @var @conf_skip */
	public $FILE_EXT	= '.php';
	/** @var bool Auto-create cache folder */
	public $CREATE_DIRS	= true;
	/** @var int Number of levels of subdirs, set to 0 to store everything in plain dir */
	public $DIR_LEVELS	= 2;
	/** @var int Number of symbols from name to use in subdirs, example: name = testme, subdir == te/st/ */
	public $DIR_STEP	= 2;
	/** @var int Octal value of cache dir and subdirs */
	public $DIR_CHMOD	= 0777;

	/**
	*/
	function _init() {
		$this->CACHE_DIR = STORAGE_PATH. 'core_cache/';
		if ($this->CREATE_DIRS && !file_exists($this->CACHE_DIR)) {
			mkdir($this->CACHE_DIR, $this->DIR_CHMOD, true);
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
		$path = $this->_dir_by_name($name). $this->FILE_PREFIX. $name. $this->FILE_EXT;
		return $this->_get_cache_file($path, $ttl);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->_dir_by_name($name). $this->FILE_PREFIX. $name. $this->FILE_EXT;
		return (bool)$this->_put_cache_file($data, $path);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->_dir_by_name($name). $this->FILE_PREFIX. $name. $this->FILE_EXT;
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
		return array_keys($keys);
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
	*/
	function _dir_by_name($name) {
		$dir = $this->CACHE_DIR;
		if (!$this->DIR_LEVELS) {
			return $dir;
		}
		$step = $this->DIR_STEP;
		for ($i = 0; $i < $this->DIR_LEVELS; $i++) {
			$dir .= substr($name, $i * $step, $step).'/';
		}
		if ($this->CREATE_DIRS && !file_exists($dir)) {
			mkdir($dir, $this->DIR_CHMOD, true);
		}
		return $dir;
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

		$data = include $path;

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
		$str = '<?'.'php'.PHP_EOL.'return '.$str.';'.PHP_EOL;
		return file_put_contents($path, $str);
	}

	/**
	*/
	function stats() {
		$usage = 0;
		foreach ($this->_get_all_files() as $file) {
			$usage += filesize($file);
		}
		return array(
			'hits'		=> null,
			'misses'	=> null,
			'uptime'	=> null,
			'mem_usage'	=> $usage,
			'mem_avail'	=> disk_free_space($this->CACHE_DIR),
		);
	}
}
