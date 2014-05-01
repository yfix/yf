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
		if (conf('USE_CACHE') === null) {
			// backwards compatibility
			if (defined('USE_CACHE')) {
				conf('USE_CACHE', USE_CACHE);
			}
			// By default we have cache enabled
			$use_cache = true;
			if (!main()->USE_SYSTEM_CACHE) {
				$use_cache = false;
			}
// TODO: add auth checking like debug auth or DEBUG_MODE checking to not allow no_cache attacks
			if ($_GET['no_core_cache'] || $_GET['no_cache']) {
				$use_cache = false;
			}
			conf('USE_CACHE', $use_cache);
		}
		define('CORE_CACHE_DIR', INCLUDE_PATH. 'core_cache/');
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
		$this->_set_current_driver($params);
		$this->_driver = null;
		if ($this->DRIVER) {
			$this->_driver = _class('cache_driver_'.$this->DRIVER, 'classes/cache/');
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
			$TTL = $this->TTL;
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
// TODO: check if we need this
/*
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
*/
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;
		$need_touch = (bool)conf('data_handlers::'.$cache_name);

/*
		if ($this->DRIVER == 'memcache') {
		}
		if ($this->DRIVER == 'file') {
			// Not locale specific
			if (empty($locales)) {
				$cache_file = CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $cache_name. $this->_file_conf['file_ext'];
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
			// Locale-specific
			foreach ((array)$locales as $_cur_locale) {
				$cache_file = CORE_CACHE_DIR. $this->_file_conf['file_prefix']. $cache_name.'___'.$_cur_locale. $this->_file_conf['file_ext'];
				if (file_exists($cache_file)) {
					if ($force_clean) {
						unlink($cache_file);
					} elseif ($need_touch) {
						@touch($cache_file, time() - $this->TTL * 2);
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
		$result = $this->_driver->del($key_name_ns);

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
	* Clean all cache entries
	*/
	function clean_all () {
// TODO: DEBUG_MODE
		return $this->_driver->clean_all();
	}

	/**
	* Clean all cache entries (alias)
	*/
	function refresh_all () {
		return $this->clean_all();
/*
		foreach ((array)conf('data_handlers') as $name => $v) {
			$this->refresh($name);
#			$this->refresh('locale:'.$name);
		}
*/
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
		return $this->clean_all();
	}

	/**
	* Clears all cache entries (do not use widely!)
	*/
	function _clear_all () {
/*
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
*/
	}

	/**
	* Get several cache entries at once
	*/
	function multi_get ($cache_names = array(), $force_ttl = 0, $params = array()) {
/*
		if ($this->_driver->implemented['multi_get']) {
			$result $this->_driver->multi_get($cache_names, $force_ttl, $params);
		} else {
			$result = array();
			foreach ((array)$cache_names as $cache_name) {
				$result[$cache_name] = $this->get($cache_name, $force_ttl, $params);
			}
		}
*/
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	* Set several cache entries at once
	*/
	function multi_set ($cache_data = array(), $TTL = 0) {
		if ($this->_driver->implemented['multi_set']) {
			$result = $this->_driver->multi_set($cache_data, $TTL);
		} else {
			$result = array();
			foreach ((array)$cache_data as $cache_name => $data) {
				$result[$cache_name] = $this->put($cache_name, $data, $TTL);
			}
		}
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	* Del several cache entries at once
	*/
	function multi_del ($cache_data = array()) {
/*
		if ($this->_driver->implemented['multi_del']) {
			$result $this->_driver->multi_del($cache_data);
		} else {
			$result = array();
			foreach ((array)$cache_data as $cache_name) {
				$result[$cache_name] = $this->del($cache_name);
			}
		}
*/
// TODO: DEBUG_MODE
		return $result;
	}

	/**
	*/
	function list_keys ($filter = '') {
		if ($this->_driver->implemented['list_keys']) {
			return $this->_driver->list_keys($filter);
		}
		return null;
	}
}
