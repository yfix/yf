<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_files extends yf_cache_driver {

	/** @var array config for file driver @conf_skip */
	public $_file_conf				= array(
		'auto_header'	=> "<?php\n",
		'auto_footer'	=> "\n?>",
		'file_prefix'	=> 'cache_',
		'file_ext'		=> '.php',
	);
	/** @var bool Auto-create cache folder */
	public $AUTO_CREATE_CACHE_DIR	= true;

	function _init() {
		$this->CORE_CACHE_DIR = PROJECT_PATH. 'core_cache/';
		if (!file_exists($this->CORE_CACHE_DIR) && $this->AUTO_CREATE_CACHE_DIR) {
// TODO: add 1-2 levels of subdirs to store 100 000+ entries easily in files (no matters when use memcached)
			mkdir($this->CORE_CACHE_DIR, 0777, true);
		}
// TODO
	}
	function get($name, $ttl = 0, $params = array()) {
// TODO
	}
	function set($name, $data, $ttl = 0) {
// TODO
	}
	function del($name) {
// TODO
	}
	function clean($name) {
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
		$TTL = intval($force_ttl ? $force_ttl : $this->FILES_TTL);
		if ($last_modified < (time() - $TTL)) {
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
