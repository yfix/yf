<?php

/**
* Cache handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_cache {

	/** @var int Cache entries time-to-live (in seconds) */
	public $TTL					= 3600;
	/** @var string Cache driver to use */
	public $DRIVER				= 'memcache';
	/** @var string Namespace for drivers other than 'file' */
	public $CACHE_NS			= '';
	/** @var bool Allows to turn off cache at any moment. Useful for unit tests and complex situations. */
	public $NO_CACHE			= false;
	/** @var bool Forcing to delete elements */
	public $FORCE_REBUILD_CACHE	= false;
	/** @var bool Add random value for each entry TTL (to avoid one-time cache invalidation problems) */
	public $RANDOM_TTL_ADD		= true;
	/** @var bool Force cache class to generate unique namespace, based on project_path. Usually needed to separate projects within same cache storage (memcached as example) */
	public $AUTO_CACHE_NS		= false;
	/** @var int Max number of items to log when DEBUG_MODE is enabled, this limit needed to prevent stealing all RAM when we have high number of cache entries at once. */
	public $LOG_MAX_ITEMS		= 200;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_driver)) {
			return call_user_func_array(array($this->_driver, $name), $args);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init ($params = array()) {
		if (isset($this->_init_complete)) {
			return true;
		}
		$this->_init_settings();
		$this->_connect($params);
		$this->_init_complete = true;
	}

	/**
	*/
	function _init_settings ($params = array()) {
		// backwards compatibility
		if ($this->FILES_TTL) {
			$this->TTL = $this->FILES_TTL;
		}
		$conf_cache_ns = conf('CACHE_NS');
		// Cache namespace need to be unique, especially when using memcached shared between several projects
		if (!$conf_cache_ns && $this->AUTO_CACHE_NS) {
			$this->CACHE_NS = substr(md5(PROJECT_PATH), 0, 8).'_';
		}
		if ($conf_cache_ns) {
			$this->CACHE_NS = $conf_cache_ns;
		}
		// backwards compatibility
		if (defined('USE_CACHE') && ! USE_CACHE) {
			$this->NO_CACHE = true;
		}
		if (!main()->USE_SYSTEM_CACHE) {
			$this->NO_CACHE = true;
		}
// TODO: add auth checking like debug auth or DEBUG_MODE checking to not allow no_cache attacks, main()->CACHE_CONTROL_FROM_URL
		if ($_GET['no_core_cache'] || $_GET['no_cache']) {
			$this->NO_CACHE = true;
		}
		if ($_GET['refresh_cache'] || $_GET['rebuild_core_cache']) {
			$this->FORCE_REBUILD_CACHE = true;
		}
		$this->FORCE_REBUILD_CACHE = false;
	}

	/**
	*/
	function _connect ($params = array()) {
		if (!$this->DRIVER) {
			return false;
		}
		if (isset($this->_tried_to_connect)) {
			return $this->_driver;
		}
		$this->_driver = null;
		$this->_driver_ok = false;
		$driver = $this->_set_current_driver($params);
		if ($driver) {
			$this->_driver = _class('cache_driver_'.$driver, 'classes/cache/');
			$this->_driver_ok = $this->_driver->is_ready();
			$implemented = array();
			foreach (get_class_methods($this->_driver) as $method) {
				if ($method[0] != '_') {
					$implemented[$method] = $method;
				}
			}
			$this->_driver->implemented = $implemented;
		} else {
			trigger_error('CACHE: empty driver name, will not use cache', E_USER_WARNING);
		}
		$this->_tried_to_connect = true;
		return $this->_driver;
	}

	/**
	*/
	function _set_current_driver ($params = array()) {
		$avail_drivers = $this->_get_avail_drivers_list();
		$driver = '';
		$want = isset($params['driver']) ? $params['driver'] : $this->DRIVER;
		if (!$want || $want == 'auto') {
			$want = 'memcache';
		}
		if (isset($avail_drivers[$want])) {
			$driver = $want;
		}
		$this->DRIVER = $driver;
		return $driver;
	}

	/**
	*/
	function _get_avail_drivers_list () {
		$paths = array(
			'project'	=> PROJECT_PATH. 'classes/cache/',
			'yf_core'	=> YF_PATH. 'classes/cache/',
			'yf_plugins'=> YF_PATH. 'plugins/*/classes/cache/',
		);
		$prefix = 'cache_driver_';
		$suffix = '.class.php';
		$plen = strlen($prefix);
		$slen = strlen($suffix);
		$drivers = array();
		foreach ($paths as $path) {
			foreach (glob($path.'*'.$prefix.'*'.$suffix) as $f) {
				$f = basename($f);
				$name = substr($f, strpos($f, $prefix) + $plen, -$slen);
				if ($name) {
					$drivers[$name] = $name;
				}
			}
		}
		return $drivers;
	}

	/**
	* Get data from cache
	*/
	function get ($cache_name = '', $force_ttl = 0, $params = array()) {
		if (!$this->_driver_ok) {
			return false;
		}
		if (empty($cache_name) || $this->NO_CACHE) {
			return false;
		}
		if ($this->FORCE_REBUILD_CACHE) {
			$this->del($cache_name, true);
			return false;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		$key_name_ns = $this->CACHE_NS. $cache_name;

		$result = $this->_driver->get($key_name_ns, $force_ttl, $params);

		if (DEBUG_MODE) {
			$all_debug = debug('cache_get');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_get::'.$debug_index, array(
					'name'		=> $cache_name,
					'name_real'	=> $key_name_ns,
					'data'		=> $result,
					'driver'	=> $this->DRIVER,
					'params'	=> $params,
					'force_ttl'	=> $force_ttl,
					'time'		=> round(microtime(true) - $time_start, 5),
					'trace'		=> main()->trace_string(),
				));
			}
		}
// TODO: add DEBUG_MODE checking here to not allow refresh_cache attacks, maybe add check for: conf('cache_refresh_token', 'something_random')
		if ($_GET['refresh_cache']) {
			return false;
		}
		return $result;
	}

	/**
	* Set data into cache
	*/
	function set ($cache_name = '', $data = null, $ttl = 0) {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->NO_CACHE) {
			return false;
		}
		$ttl = intval($ttl ?: $this->TTL);
		if ($this->RANDOM_TTL_ADD) {
			$ttl += mt_rand(1, 15);
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Do not put empty data if database could not connect
// TODO: remove me, as cache class should not care about database, maybe use cache()->NO_CACHE in that case
#		if (empty($data) && is_object($GLOBALS['db']) && !$GLOBALS['db']->_connected) {
#			return false;
#		}

// TODO: decide if we need this
#		if ($this->_no_cache[$cache_name]) {
#			return true;
#		}

		$key_name_ns = $this->CACHE_NS. $cache_name;
		$result = $this->_driver->set($key_name_ns, $data, $ttl);

		if (DEBUG_MODE) {
			$all_debug = debug('cache_set');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_set::'.$debug_index, array(
					'name'		=> $cache_name,
					'name_real'	=> $key_name_ns,
					'data'		=> $data,
					'driver'	=> $this->DRIVER,
					'time'		=> round(microtime(true) - $time_start, 5),
					'trace'		=> main()->trace_string(),
				));
			}
		}
		return $result;
	}

	/**
	* Delete selected cache entry
	*/
	function del ($cache_name = '', $force_clean = false) {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->NO_CACHE) {
			return false;
		}
		if (is_array($cache_name)) {
			foreach ((array)$cache_name as $name) {
				$result[$name] = $this->refresh($name, $force_clean);
			}
			return $result;
		}
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}

		$key_name_ns = $this->CACHE_NS. $cache_name;
		$result = $this->_driver->del($key_name_ns);

		if (DEBUG_MODE) {
			$all_debug = debug('cache_del');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				debug('cache_del::'.$debug_index, array(
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
	function refresh ($cache_name = '') {
		return $this->del($cache_name, true);
	}

	/**
	* Clean selected cache entry (alias)
	*/
	function clean ($cache_name = '') {
		return $this->del($cache_name, true);
	}

	/**
	* Clean selected cache entry (alias)
	*/
	function clear ($cache_name = '') {
		return $this->del($cache_name, true);
	}

	/**
	* Put data into cache (alias for 'set')
	*/
	function put ($cache_name = '', $data = null, $ttl = 0) {
		return $this->set($cache_name, $data, $ttl);
	}

	/**
	* Clean all cache entries
	*/
	function flush () {
		if (!$this->_driver_ok) {
			return false;
		}
// TODO: DEBUG_MODE
		return $this->_driver->flush();
	}

	/**
	* Clean all cache entries (alias)
	*/
	function clean_all () {
// TODO: DEBUG_MODE
		return $this->flush();
	}

	/**
	* Clean all cache entries (alias)
	*/
	function clear_all () {
		return $this->flush();
	}

	/**
	* Clean all cache entries (alias)
	*/
	function refresh_all () {
		return $this->flush();
	}

	/**
	* Clears all cache entries (alias)
	*/
	function _clear_cache_files () {
		return $this->flush();
	}

	/**
	* Get several cache entries at once
	*/
	function multi_get ($cache_names = array(), $force_ttl = 0, $params = array()) {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->_driver->implemented['multi_get']) {
			$result = $this->_driver->multi_get($cache_names, $force_ttl, $params);
		} else {
			$result = array();
			foreach ((array)$cache_names as $cache_name) {
				$result[$cache_name] = $this->get($cache_name, $force_ttl, $params);
			}
		}
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	* Set several cache entries at once
	*/
	function multi_set ($cache_data = array(), $ttl = 0) {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->_driver->implemented['multi_set']) {
			$result = $this->_driver->multi_set($cache_data, $ttl);
		} else {
			$result = array();
			foreach ((array)$cache_data as $cache_name => $data) {
				$result[$cache_name] = $this->put($cache_name, $data, $ttl);
			}
		}
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	* Del several cache entries at once
	*/
	function multi_del ($cache_data = array()) {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->_driver->implemented['multi_del']) {
			$result = $this->_driver->multi_del($cache_data);
		} else {
			$result = array();
			foreach ((array)$cache_data as $cache_name) {
				$result[$cache_name] = $this->del($cache_name);
			}
		}
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	*/
	function list_keys ($filter = '') {
		if (!$this->_driver_ok) {
			return false;
		}
		if ($this->_driver->implemented['list_keys']) {
			return $this->_driver->list_keys($filter);
		}
		return null;
	}

	/**
	* Process given rule name
	*/
// TODO: remove me or move out
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
}
