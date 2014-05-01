<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_files extends yf_cache_driver {

	/** @var array config for file driver @conf_skip */
	public $_file_conf = array(
		'auto_header'	=> "<?php\n",
		'auto_footer'	=> "\n?>",
		'file_prefix'	=> 'cache_',
		'file_ext'		=> '.php',
	);
	/** @var bool Auto-create cache folder */
	public $AUTO_CREATE_CACHE_DIR	= true;

	/**
	*/
	function _init() {
		$this->CACHE_DIR = PROJECT_PATH. 'core_cache/';
		if (!file_exists($this->CACHE_DIR) && $this->AUTO_CREATE_CACHE_DIR) {
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
		$path = $this->CACHE_DIR. $this->_file_conf['file_prefix']. $name. $this->_file_conf['file_ext'];
		return $this->_get_cache_file($path, $ttl);
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$path = $this->CACHE_DIR. $this->_file_conf['file_prefix']. $name. $this->_file_conf['file_ext'];
		return $this->_put_cache_file($data, $path);
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
			// Not locale specific
			if (empty($locales)) {
				$cache_file = $this->CACHE_DIR. $this->_file_conf['file_prefix']. $cache_name. $this->_file_conf['file_ext'];
				if (file_exists($cache_file)) {
					if ($force_clean) {
						unlink($cache_file);
					} elseif ($need_touch) {
						@touch($cache_file, time() - $this->TTL * 2);
					}
				} elseif (!$force_clean) {
					$this->put($cache_name);
				}
			}
	}

	/**
	*/
	function flush($name) {
		if (!$this->is_ready()) {
			return null;
		}
// TODO: use glob() for this, also support for subdirs
			$dh = opendir($this->CACHE_DIR);
			if (!$dh) {
				return false;
			}
			while (($f = readdir($dh)) !== false) {
				if ($f == '.' || $f == '..' || !is_file($this->CACHE_DIR.$f)) {
					continue;
				}
				if (pathinfo($f, PATHINFO_EXTENSION) != 'php') {
					continue;
				}
				if (substr($f, 0, strlen($this->_file_conf['file_prefix'])) != $this->_file_conf['file_prefix']) {
					continue;
				}
				if (file_exists($this->CACHE_DIR.$f)) {
					unlink($this->CACHE_DIR.$f);
				}
			}
			closedir($dh);
			return true;
	}

	/**
	*/
	function list_keys($filter = '') {
		if (!$this->is_ready()) {
			return null;
		}
// TODO
	}

	/**
	* Do get cache file contents
	*/
	function _get_cache_file ($cache_file = '', $force_ttl = 0) {
		if (empty($cache_file)) {
			return null;
		}
		if (!file_exists($cache_file)) {
			return null;
		}
		// Delete expired cache files
		$last_modified = filemtime($cache_file);
#		$ttl = intval($force_ttl ? $force_ttl : $this->TTL);
		if ($last_modified < (time() - $ttl)) {
			return null;
		}
		$data = array();
		if (DEBUG_MODE) {
			$_time_start = microtime(true);
		}

		include ($cache_file);

		if (DEBUG_MODE) {
			$_cf = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $cache_file));
			debug('include_files_exec_time::'.$_cf, microtime(true) - $_time_start);
		}
		return $data;
	}

	/**
	* Do put cache file contents
	*/
	function _put_cache_file ($data = array(), $cache_file = '') {
		if (empty($cache_file)) {
			return false;
		}
		return file_put_contents($cache_file, 
			$this->_file_conf['auto_header']
			.'$data = '.str_replace(' => '.PHP_EOL.'array (', '=>array(', preg_replace('/^\s+/m', '', var_export($data, 1))).';'
			.$this->_file_conf['auto_footer']
		);
	}
}
