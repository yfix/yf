<?php

/**
* Cache handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_cache {

	/** @var int Cache files TimeToLive value (in seconds) */
// TODO: rename this into "TTL" with backwards compatibility
	public $FILES_TTL				= 3600;
	/** @var int Add random value for each entry TTL (to avoid one-time cache invalidation problems) */
	public $RANDOM_TTL_ADD			= true;
	/** @var string Cache rules file */
	public $RULES_FILE				= 'cache_rules.php';
	/** @var bool Allow or not custom rules (not found in rules array) */
	public $ALLOW_CUSTOM_RULES		= true;
	/** @var string Cache driver enum('','auto','file','eaccelerator','apc','xcache','memcache') */
	public $DRIVER					= 'file';
	/** @var string Namespace for drivers other than 'file' */
	public $CACHE_NS				= '';
	/** @var bool Allows to turn off cache at any moment. Useful for unit tests and complex situations. */
	public $NO_CACHE				= false;
	/** @var bool */
	public $FORCE_REBUILD_CACHE		= false;
	/** @var int Max number of items to log when DEBUG_MODE is enabled, this limit needed to prevent stealing all RAM 
		when we have high number of cache entries at once. Applied separately for 'get', 'set', 'refresh'.
	*/
	public $LOG_MAX_ITEMS	= 200;

// TODO: connect plugins, stored inside classes/cache/*

	/**
	* Framework constructor
	*/
	function _init ($params = array()) {
		// Cache namespace need to be unique, especially when using memcached shared between several projects
// TODO: move this into setting: $this->AUTO_CACHE_NS = false|true
#		$this->CACHE_NS = 'core_'.intval(abs(crc32(defined('INCLUDE_PATH') ? INCLUDE_PATH : __FILE__)));
		$conf_cache_ns = conf('CACHE_NS');
		if ($conf_cache_ns) {
			$this->CACHE_NS = $conf_cache_ns;
		}
		if (conf('USE_CACHE') === null) {
// TODO: remove ?
			if (defined('USE_CACHE')) {
				conf('USE_CACHE', USE_CACHE);
			}
			// By default we have cache enabled
			$use_cache = true;
			if (!main()->USE_SYSTEM_CACHE) {
				$use_cache = false;
			}
// TODO: add DEBUG_MODE checking here to not allow no_cache attacks
// TODO: add auth checking like debug auth
			if ($_GET['no_core_cache'] || $_GET['no_cache']) {
				$use_cache = false;
			}
			conf('USE_CACHE', $use_cache);
		}
		define('CORE_CACHE_DIR', INCLUDE_PATH. 'core_cache/');
		// Singleton pattern, prevents double cache init overhead when called without main and then with main class
		if (isset($this->_init_complete)) {
			return true;
		}
// TODO: get available cache systems from classes/cache/
		$cache_systems = array();
		if (function_exists('eaccelerator_get')) {
			$cache_systems[] = 'eaccelerator';
		}
		if (function_exists('apc_fetch')) {
			$cache_systems[] = 'apc';
		}
		if (function_exists('xcache_get')) {
			$cache_systems[] = 'xcache';
		}
		if (class_exists('Memcache') || class_exists('Memcached')) {
			$cache_systems[] = 'memcache';
		}
		$cache_systems[] = 'file';
		$required_cache = isset($params['driver']) ? $params['driver'] : $this->DRIVER;
		if (!$required_cache) {
			$required_cache = 'file';
		}
		if (count($cache_systems)) {
			if ($required_cache == 'auto') {
				$this->DRIVER = array_shift($cache_systems);
			} elseif (in_array($required_cache, $cache_systems)) {
				$this->DRIVER = $required_cache;
			} else {
				$this->DRIVER = 'file';
			}
		}
		// Driver load instance
// TODO: move this into _connect() like in db() to not include driver until first time called?
		$this->_driver = _class('cache_driver_'.$this->DRIVER, 'classes/cache/');

		$this->_init_complete = true;
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
// TODO: first check if this is specific method of the driver, yes - call it, not - fail like now
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Run init from main class if that exists
	*/
	function _init_from_main () {
		$this->FORCE_REBUILD_CACHE = false;
		if (main()->CACHE_CONTROL_FROM_URL && $_GET['rebuild_core_cache']) {
			$this->FORCE_REBUILD_CACHE = true;
		}
		main()->_load_data_handlers();
	}

	/**
	* Get data from cache
	*/
	function get ($cache_name = '', $force_ttl = 0, $params = array()) {
		if (empty($cache_name) || $this->NO_CACHE) {
			return false;
		}
		if ($this->FORCE_REBUILD_CACHE) {
			return $this->refresh($cache_name, true);
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
// TODO: decide if we need this or remove
/*
		// Check if handler is locale-specific
		$locale_cache_name = '';
		if (strpos($cache_name, 'locale:') === 0) {
			$cache_name = substr($cache_name, 7);
			$locale_cache_name = $cache_name.'___'.conf('language');
		}
*/
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;

/*
		if ($this->DRIVER == 'memcache') {
			if (isset($this->_memcache)) {
				$result = $this->_memcache->get($key_name_ns);
			} else {
				$this->DRIVER = 'file';
			}
		}
		if ($this->DRIVER == 'file') {
			$result = $this->_get_cache_file(CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $key_name. $this->_file_conf['file_ext'], $force_ttl);
		} elseif ($this->DRIVER == 'eaccelerator') {
			$result = eaccelerator_get($key_name_ns);
		} elseif ($this->DRIVER == 'apc') {
			$result = apc_fetch($key_name_ns);
		} elseif ($this->DRIVER == 'xcache') {
			$result = xcache_get($key_name_ns);
		}
		if ($this->DRIVER != 'file' && is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
*/
		$result = $this->_driver->get($key_name_ns, $force_ttl, $params);

		if (DEBUG_MODE) {
			$all_debug = debug('cache_get');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_get::'.$debug_index, array(
					'name'		=> $cache_name,
					'name_real'	=> $key_name_ns,
// TODO: store full data, process it later in debug class
					'data'		=> '<pre><small>'._prepare_html(substr(var_export($result, 1), 0, 1000)).'</small></pre>',
					'driver'	=> $this->DRIVER,
					'params'	=> $params,
					'force_ttl'	=> $force_ttl,
					'time'		=> round(microtime(true) - $time_start, 5),
					'trace'		=> main()->trace_string(),
				));
			}
		}
// TODO: add DEBUG_MODE checking here to not allow refresh_cache attacks
// TODO: maybe add check for: conf('cache_refresh_token', 'something_random')
		if (!conf('USE_CACHE') || $_GET['refresh_cache']) {
			return false;
		}
		return $result;
	}

	/**
	* Put data into cache (alias for 'put')
	*/
	function set ($cache_name = '', $data = null, $TTL = 0) {
		return $this->put($cache_name, $data, $TTL);
	}

	/**
	* Put data into cache
	*/
	function put ($cache_name = '', $data = null, $TTL = 0) {
		if ($this->NO_CACHE) {
			return false;
		}
		if (!$TTL) {
			$TTL = $this->FILES_TTL;
		}
		// Add random value for each entry TTL (to avoid 'at once' cache invalidation problems)
		if ($this->RANDOM_TTL_ADD) {
			$TTL += mt_rand(1, 15);
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
// TODO: decide if we need this or remove
/*
		// Check if handler is locale-specific
		if (strpos($cache_name, 'locale:') === 0) {
			$cache_name	= substr($cache_name, 7);
			$locale_cache_name = $cache_name.'___'.conf('language');
		}
*/
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;
		// Stop here if custom rules not allowed
// TODO: use main()->data_handlers for this
		if (!$this->ALLOW_CUSTOM_RULES && !conf('data_handlers::'.$cache_name)) {
			return false;
		}
		if (is_null($data)) {
			$data = $this->_process_rule($cache_name, $locale_cache_name ? 1 : 0);
		}
		// Do not put empty data if database could not connect
// TODO: remove me, as cache class should not care about database, maybe use cache()->NO_CACHE in that case
#		if (empty($data) && is_object($GLOBALS['db']) && !$GLOBALS['db']->_connected) {
#			return false;
#		}
		if ($this->_no_cache[$cache_name]) {
			return true;
		}
/*
		if ($this->DRIVER != 'file') {
			$data_to_put = is_array($data) ? serialize($data) : $data;
		}
		if ($this->DRIVER == 'memcache') {
		}
		if ($this->DRIVER == 'file') {
			$result = $this->_put_cache_file($data, CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $key_name. $this->_file_conf['file_ext']);
		} elseif ($this->DRIVER == 'eaccelerator') {
			$result = eaccelerator_put($key_name_ns, $data_to_put, $TTL);
		} elseif ($this->DRIVER == 'apc') {
			$result = apc_store($key_name_ns, $data_to_put, $TTL);
		} elseif ($this->DRIVER == 'xcache') {
			$result = xcache_set($key_name_ns, $data_to_put, $TTL);
		}
*/
		$result = $this->_driver->set($key_name_ns, $data_to_put, $TTL);

		if (DEBUG_MODE) {
			$all_debug = debug('cache_set');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_set::'.$debug_index, array(
					'name'		=> $cache_name,
					'name_real'	=> $key_name_ns,
// TODO: store full data, process it later in debug class
					'data'		=> '<pre><small>'._prepare_html(substr(var_export($data, 1), 0, 1000)).'</small></pre>',
					'driver'	=> $this->DRIVER,
					'time'		=> round(microtime(true) - $time_start, 5),
					'trace'		=> main()->trace_string(),
				));
			}
		}
		return $result;
	}

	/**
	* Get several cache entries at once
	*/
	function multi_get ($cache_names = array(), $force_ttl = 0, $params = array()) {
// TODO: DEBUG_MODE
		if ($this->_driver->implemented['multi_get']) {
			return $this->_driver->multi_get($cache_names, $force_ttl, $params);
		}
		$result = array();
		foreach ((array)$cache_names as $cache_name) {
			$result[$cache_name] = $this->get($cache_name, $force_ttl, $params);
		}
		return $result;
	}

	/**
	* Set several cache entries at once
	*/
	function multi_set ($cache_data = array(), $TTL = 0) {
// TODO: DEBUG_MODE
		if ($this->_driver->implemented['multi_set']) {
			return $this->_driver->multi_set($cache_data, $TTL);
		}
		$result = array();
		foreach ((array)$cache_data as $cache_name => $data) {
			$result[$cache_name] = $this->put($cache_name, $data, $TTL);
		}
		return $result;
	}

	/**
	* Del several cache entries at once
	*/
	function multi_del ($cache_data = array()) {
// TODO: DEBUG_MODE
		if ($this->_driver->implemented['multi_del']) {
			return $this->_driver->multi_del($cache_data);
		}
		$result = array();
		foreach ((array)$cache_data as $cache_name) {
			$result[$cache_name] = $this->del($cache_name);
		}
		return $result;
	}

	/**
	* Update selected cache entry
	*/
	function refresh ($cache_name = '', $force_clean = false) {
		if (is_array($cache_name)) {
			foreach ((array)$cache_name as $name) {
				$result[$name] = $this->_refresh($name, $force_clean);
			}
		} else {
			$result = $this->_refresh($cache_name, $force_clean);
		}
		return $result;
	}

	/**
	*/
	function _refresh ($cache_name = '', $force_clean = false) {
		if ($this->NO_CACHE) {
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Check if handler is locale-specific
		if (strpos($cache_name, 'locale:') === 0) {
			$cache_name	= substr($cache_name, 7);
			$locale_cache_name = $cache_name.'___'.conf('language');
			// get available locales
			$locales = array();
			$locale_obj = _class('locale');
			if (is_object($obj)) {
				$locales = array_keys((array)$locale_obj->LANGUAGES);
			}
		}
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;
		$need_touch = (bool)conf('data_handlers::'.$cache_name);
/*
		if ($this->DRIVER == 'memcache') {
			if (isset($this->_memcache)) {
				$result = $this->_memcache->delete($key_name_ns, 0);
			} else {
				$this->DRIVER = 'file';
			}
		}
		if ($this->DRIVER == 'file') {
			// Not locale specific
			if (empty($locales)) {
				$cache_file = CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $cache_name. $this->_file_conf['file_ext'];
				if (file_exists($cache_file)) {
					if ($force_clean) {
						unlink($cache_file);
					} elseif ($need_touch) {
						@touch($cache_file, time() - $this->FILES_TTL * 2);
					}
				} elseif (!$force_clean) {
					$this->put($cache_name);
				}
			}
			// Locale-specific
			foreach ((array)$locales as $_cur_locale) {
				$cache_file = CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $cache_name.'___'.$_cur_locale. $this->_file_conf['file_ext'];
				if (file_exists($cache_file)) {
					if ($force_clean) {
						unlink($cache_file);
					} elseif ($need_touch) {
						@touch($cache_file, time() - $this->FILES_TTL * 2);
					}
				}
			}
		} elseif ($this->DRIVER == 'eaccelerator') {
			$result = eaccelerator_rm($key_name_ns);
		} elseif ($this->DRIVER == 'apc') {
			$result = apc_delete($key_name_ns);
		} elseif ($this->DRIVER == 'xcache') {
			$result = xcache_unset($key_name_ns);
		}
*/
		if (DEBUG_MODE) {
			$all_debug = debug('cache_refresh');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_refresh::'.$debug_index, array(
					'name'			=> $cache_name,
					'name_real'		=> $key_name_ns,
					'force_clean'	=> $force_clean,
					'driver'		=> $this->DRIVER,
					'time'			=> microtime(true) - $time_start,
				));
			}
		}
		return $result;
	}

	/**
	* Delete selected cache entry (alias)
	*/
	function del ($cache_name = '') {
		return $this->refresh($cache_name, true);
	}

	/**
	* Clean selected cache entry
	*/
	function clean ($cache_name = '') {
		return $this->refresh($cache_name, true);
	}

	/**
	* Update all cache entries
	*/
	function refresh_all () {
		foreach ((array)conf('data_handlers') as $name => $v) {
			$this->refresh($name);
			$this->refresh('locale:'.$name);
		}
	}

	/**
	* Process given rule name
	*/
	function _process_rule ($rule_name = '', $locale_specific = false) {
		$data = array();
		$no_cache = false;
		$rule_data = conf('data_handlers::'.$rule_name);
		if (!empty($rule_name) && $rule_data) {
			$data = eval(
				($locale_specific ? '$locale="'.conf('language').'";' : '')
				.$rule_data
				.'; return $data;'
			);
		}
		if ($no_cache) {
			$this->_no_cache[$rule_name];
		}
		return $data;
	}

	/**
	* Clears all cache files inside cache folder
	*/
	function _clear_cache_files () {
		return $this->_clear_all();
	}

	/**
	* Clears all cache entries (do not use widely!)
	*/
	function _clear_all () {
		if ($this->DRIVER == 'memcache') {
			if (isset($this->_memcache)) {
				return $this->_memcache->flush();
			} else {
				$this->DRIVER = 'file';
			}
		}
		if ($this->DRIVER == 'file') {
			$dh = opendir(CORE_CACHE_DIR);
			if (!$dh) {
				return false;
			}
			while (($f = readdir($dh)) !== false) {
				if ($f == '.' || $f == '..' || !is_file(CORE_CACHE_DIR.$f)) {
					continue;
				}
				if (pathinfo($f, PATHINFO_EXTENSION) != 'php') {
					continue;
				}
				if (substr($f, 0, strlen($this->_file_conf['file_prefix'])) != $this->_file_conf['file_prefix']) {
					continue;
				}
				if (file_exists(CORE_CACHE_DIR.$f)) {
					unlink(CORE_CACHE_DIR.$f);
				}
			}
			closedir($dh);
			return true;
		} elseif ($this->DRIVER == 'eaccelerator') {
			return eaccelerator_clear();
		} elseif ($this->DRIVER == 'apc') {
			return apc_clear_cache();
		} elseif ($this->DRIVER == 'xcache') {
			return xcache_clear_cache();
		}
	}

	/**
	* Escape html and framework specific symbols to display in debug console
	*/
	function _debug_escape($string = '') {
// TODO: remove/move this into debug class, no need to have this responsibility in-place
		$symbols = array(
			'{'	=> '&#123;',
			'}'	=> '&#125;',
			"\\"=> '&#92;',
			'(' => '&#40;',
			')' => '&#41;',
			'?' => '&#63;',
		);
		return str_replace(array_keys($symbols), array_values($symbols), htmlspecialchars($string, ENT_QUOTES));
	}
}
