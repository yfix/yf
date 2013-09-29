<?php

/**
* Cache handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_cache {

	/** @var array config for file driver @conf_skip */
	public $_file_conf				= array(
		'auto_header'	=> "<?php\n",
		'auto_footer'	=> "\n?>",
		'file_prefix'	=> 'cache_',
		'file_ext'		=> '.php',
	);
	/** @var int Cache files TimeToLive value (in seconds) */
	public $FILES_TTL				= 3600;
	/** @var int Add random value for each entry TTL (to avoid one-time cache invalidation problems) */
	public $RANDOM_TTL_ADD			= true;
	/** @var string Cache rules file */
	public $RULES_FILE				= 'cache_rules.php';
	/** @var bool Allow or not custom rules (not found in rules array) */
	public $ALLOW_CUSTOM_RULES		= true;
	/** @var bool Auto-create cache folder */
	public $AUTO_CREATE_CACHE_DIR	= true;
	/** @var bool Include cache files (or simply read as string) */
	public $INCLUDE_CACHE_FILES	= true;
	/** @var string Cache driver enum('','auto','file','eaccelerator','apc','xcache','memcache') */
	public $DRIVER					= 'file';
	/** @var string Namespace for drivers other than 'file' */
	public $CACHE_NS				= '';
	/** @var array internal @conf_skip */
	public $MEMCACHE_DEF_PARAMS	= array(
		'port'		=> 11211,
		'host'		=> '127.0.0.1', // !!! DO NOT USE 'localhost' on Ubuntu 10.04 (and maybe others) due to memcached bug
		'persistent'=> true,
	);
	/** @var object internal @conf_skip */
	public $_memcache				= null;
	/** @var bool */
	public $FORCE_REBUILD_CACHE	= false;
	/** @var array symbols to escape for debug */
	public $_debug_escape_symbols = array(
		'{'	=> '&#123;',
		'}'	=> '&#125;',
		"\\"=> '&#92;',
		'(' => '&#40;',
		')' => '&#41;',
		'?' => '&#63;',
	);
	/** @var bool We need this instead of global constant DEBUG_MODE to be able to early init cache when main is still not available */
	public $DEBUG_MODE	= false;
	/** @var int Max number of items to log when DEBUG_MODE is enabled, this limit needed to prevent stealing all RAM 
		when we have high number of cache entries at once. Applied separately for 'get', 'set', 'refresh'.
	*/
	public $LOG_MAX_ITEMS	= 200;

	/**
	* Framework constructor
	*/
	function _init ($params = array()) {
		$conf_mc_host = conf('MEMCACHED_HOST');
		if ($conf_mc_host) {
			$this->MEMCACHE_DEF_PARAMS['host'] = $conf_mc_host;
		}
		$conf_mc_port = conf('MEMCACHED_PORT');
		if ($conf_mc_host) {
			$this->MEMCACHE_DEF_PARAMS['port'] = $conf_mc_port;
		}
		// Cache namespace need to be unique, especially when using memcached shared between several projects
#		$this->CACHE_NS = 'core_'.intval(abs(crc32(defined('INCLUDE_PATH') ? INCLUDE_PATH : __FILE__)));
		$conf_cache_ns = conf('CACHE_NS');
		if ($conf_cache_ns) {
			$this->CACHE_NS = $conf_cache_ns;
		}
		$this->_main_exists = (isset($GLOBALS['main']) && method_exists($GLOBALS['main'], 'init_class'));
		if (defined('DEBUG_MODE')) {
			$this->DEBUG_MODE = DEBUG_MODE;
		}
		if (conf('USE_CACHE') === null) {
			if (defined('USE_CACHE')) {
				conf('USE_CACHE', USE_CACHE);
			}
			// By default we have cache enabled
			$use_cache = true;
			if (isset($GLOBALS['PROJECT_CONF']['main']['USE_SYSTEM_CACHE'])) {
				$use_cache = (bool)$GLOBALS['PROJECT_CONF']['main']['USE_SYSTEM_CACHE'];
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
		if ($this->DRIVER == 'memcache') {
			$this->_memcache = null;
			$mc_obj = null;
			if (class_exists('Memcached')) {
				$mc_obj = new Memcached();
			} elseif (class_exists('Memcache')) {
				$mc_obj = new Memcache();
			} else {
				$client_path = YF_PATH.'libs/memcached/memcached_client.class.php';
				if (file_exists($client_path)) {
					include $client_path;
				}
				if (class_exists('memcached_client')) {
					$mc_obj = new memcached_client();
				}
			}
			if (is_object($mc_obj)) {
				$mc_params = (isset($params['memcache']) && !empty($params['memcache'])) 
					? (is_array($params['memcache']) ? $params['memcache'] : array($params['memcache'])) 
					: array($this->MEMCACHE_DEF_PARAMS);
				$failed = true;
				foreach ((array)$mc_params as $server) {
					if (!is_array($server) || !isset($server['host'])) {
						continue;
					}
					$server['port'] = isset($server['port']) ? (int)$server['port'] : 11211;
					$server['persistent'] = isset($server['persistent']) ? (bool) $server['persistent'] : true;
					if ($mc_obj->addServer($server['host'], $server['port'], $server['persistent'])) {
						$failed = false;
					}
				}
			}
			if (is_object($mc_obj)) {
				$this->_memcache = $mc_obj;
			} else {
				$this->_memcache = null;
				$this->DRIVER = 'file';
			}
		}
		if ($this->DRIVER == 'memcache') {
			$this->_memcache_new_extension = method_exists($this->_memcache, 'getMulti');
		}
		if ($this->DRIVER == 'file' && !file_exists(CORE_CACHE_DIR) && $this->AUTO_CREATE_CACHE_DIR) {
// TODO: add 1-2 levels of subdirs to store 100 000+ entries easily in files (no matters when use memcached)
			mkdir(CORE_CACHE_DIR, 0777, true);
		}
		$this->_init_complete = true;
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Run init from main class if that exists
	*/
	function _init_from_main () {
		// We need this uplicated piece of code to ensure cache will work after early init without main class and after that
		$this->_main_exists = (method_exists(main(), 'init_class'));
		if (defined('DEBUG_MODE')) {
			$this->DEBUG_MODE = DEBUG_MODE;
		}
		$this->FORCE_REBUILD_CACHE = false;
		if ($this->_main_exists && main()->CACHE_CONTROL_FROM_URL && $_GET['rebuild_core_cache']) {
			$this->FORCE_REBUILD_CACHE = true;
		}
		// Try to load data handlers array
		if ($this->_main_exists && !conf('data_handlers')) {
			main()->_load_data_handlers();
		}
		if (defined('DEBUG_MODE')) {
			$this->DEBUG_MODE = DEBUG_MODE;
		}
	}

	/**
	* Get data from cache
	*/
	function get ($cache_name = '', $force_ttl = 0, $params = array()) {
		if (empty($cache_name)) {
			return false;
		}
		if ($this->FORCE_REBUILD_CACHE) {
			return $this->refresh($cache_name, true);
		}
		if ($this->DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Check if handler is locale-specific
		$locale_cache_name = '';
		if (strpos($cache_name, 'locale:') === 0) {
			$cache_name = substr($cache_name, 7);
			$locale_cache_name = $cache_name.'___'.conf('language');
		}
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;
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
		if ($this->DEBUG_MODE) {
			$all_debug = debug('_core_cache_debug::get');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				$_time = microtime(true) - $time_start;

				ob_start();
				var_dump($result);
				$_debug_data = substr(ob_get_contents(), 0, 150);
				ob_end_clean();
				$_pos = strpos($_debug_data, ')');

				debug('_core_cache_debug::get::'.$debug_index, array(
					'name'		=> $cache_name,
					'time'		=> round($_time, 5),
					'data'		=> $_pos ? '<b>'.substr($_debug_data, 0, $_pos + 1). '</b>'. $this->_debug_escape(substr($_debug_data, $_pos + 1)) : $_debug_data,
					'trace'		=> $this->trace_string(),
					'driver'	=> $this->DRIVER,
					'params'	=> $params,
					'force_ttl'	=> $force_ttl,
				));
			}
		}
		if (!conf('USE_CACHE')) {
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
		if (!$TTL) {
			$TTL = $this->FILES_TTL;
		}
		// Add random value for each entry TTL (to avoid 'at once' cache invalidation problems)
		if ($this->RANDOM_TTL_ADD) {
			$TTL += mt_rand(1, 15);
		}
		if ($this->DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// Check if handler is locale-specific
		if (strpos($cache_name, 'locale:') === 0) {
			$cache_name	= substr($cache_name, 7);
			$locale_cache_name = $cache_name.'___'.conf('language');
		}
		$key_name = $locale_cache_name ? $locale_cache_name : $cache_name;
		$key_name_ns = $this->CACHE_NS. $key_name;
		// Stop here if custom rules not allowed
		if (!$this->ALLOW_CUSTOM_RULES && !conf('data_handlers::'.$cache_name)) {
			return false;
		}
		if (is_null($data)) {
			$data = $this->_process_rule($cache_name, $locale_cache_name ? 1 : 0);
		}
		// Do not put empty data if database could not connect
		if (empty($data) && is_object($GLOBALS['db']) && !$GLOBALS['db']->_connected) {
			return false;
		}
		if ($this->_no_cache[$cache_name]) {
			return true;
		}
		if ($this->DRIVER != 'file') {
			$data_to_put = is_array($data) ? serialize($data) : $data;
		}
		if ($this->DRIVER == 'memcache') {
			if (isset($this->_memcache)) {
				// Solved set() trouble with many servers.
				// http://www.php.net/manual/ru/function.memcache-set.php#84032
				if ($this->_memcache_new_extension) {
					if (!$this->_memcache->replace($key_name_ns, $data_to_put, $TTL)) {
						$result = $this->_memcache->set($key_name_ns, $data_to_put, $TTL);
					}
				} else {
					if (!$this->_memcache->replace($key_name_ns, $data_to_put, /*MEMCACHE_COMPRESSED*/ null, $TTL)) {
						$result = $this->_memcache->set($key_name_ns, $data_to_put, /*MEMCACHE_COMPRESSED*/null, $TTL);
					}
				}
			} else {
				$this->DRIVER = 'file';
			}
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
		if ($this->DEBUG_MODE) {
			$all_debug = debug('_core_cache_debug::set');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				$_time = microtime(true) - $time_start;

				ob_start();
				var_dump($data);
				$_debug_data = substr(ob_get_contents(), 0, 150);
				ob_end_clean();
				$_pos = strpos($_debug_data, ')');

				debug('_core_cache_debug::set::'.$debug_index, array(
					'name'		=> $cache_name,
					'time'		=> round($_time, 5),
					'data'		=> $_pos ? '<b>'.substr($_debug_data, 0, $_pos + 1). '</b>'. $this->_debug_escape(substr($_debug_data, $_pos + 1)) : $_debug_data,
					'trace'		=> $this->trace_string(),
					'driver'	=> $this->DRIVER,
				));
			}
		}
		return $result;
	}

	/**
	* Get several cache entries at once (speedup when use memcached)
	*/
// TODO: optimize me for memcache, using native getMultiByKey() method
	function multi_get ($cache_names = array(), $force_ttl = 0, $params = array()) {
		$result = array();
		foreach ((array)$cache_names as $cache_name) {
			$result[$cache_name] = $this->get($cache_name, $force_ttl, $params);
		}
		return $result;
	}

	/**
	* Set several cache entries at once (speedup when use memcached)
	*/
// TODO: optimize me for memcache, using native setMultiByKey() method
	function multi_set ($cache_data = array(), $TTL = 0) {
		$result = array();
		foreach ((array)$cache_data as $cache_name => $data) {
			$result[$cache_name] = $this->put($cache_name, $data, $TTL);
		}
		return $result;
	}

	/**
	* Del several cache entries at once (speedup when use memcached)
	*/
// TODO: optimize me for memcache
	function multi_del ($cache_data = array()) {
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
		if ($this->DEBUG_MODE) {
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
		if ($this->DRIVER == 'memcache') {
			if (isset($this->_memcache)) {
				$result = $this->_memcache->delete($key_name_ns);
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
		if ($this->DEBUG_MODE) {
			$all_debug = debug('_core_cache_debug::refresh');
			$debug_index = count($all_debug);
			if ($debug_index < $this->LOG_MAX_ITEMS) {
				$time_end = microtime(true);
				debug('_core_cache_debug::refresh::'.$debug_index, array(
					'name'			=> $cache_name,
					'force_clean'	=> $force_clean,
					'driver'		=> $this->DRIVER,
					'time'			=> $time_end - $time_start,
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
		if ($this->INCLUDE_CACHE_FILES) {
			$data = array();
			if ($this->DEBUG_MODE) {
				$_time_start = microtime(true);
			}

			include ($cache_file);

			if ($this->DEBUG_MODE) {
				$_cf = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $cache_file));
				debug('include_files_exec_time::'.$_cf, microtime(true) - $_time_start);
			}
			return $data;
		} else {
			$output = eval('return '.substr(file_get_contents($cache_file), strlen($this->_file_conf['auto_header']), -strlen($this->_file_conf['auto_footer'])).';');
			// Check if file has parse error
			if ($output === false) {
				trigger_error('CACHE: Parse error in file "'.basename($cache_file).'"', E_USER_WARNING);
			}
			return $output;
		}
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
				$this->_memcache->flush();
			} else {
				$this->DRIVER = 'file';
			}
		}
		if ($this->DRIVER == 'file') {
			$dh = @opendir(CORE_CACHE_DIR);
			if (!$dh) {
				return false;
			}
			while (($f = readdir($dh)) !== false) {
				if ($f == '.' || $f == '..' || !is_file(CORE_CACHE_DIR.$f)) {
					continue;
				}
				if (_class('common')->get_file_ext($f) != 'php') {
					continue;
				}
				if (substr($f, 0, strlen($this->_file_conf['file_prefix'])) != $this->_file_conf['file_prefix']) {
					continue;
				}
				if (file_exists(CORE_CACHE_DIR.$f)) {
					unlink(CORE_CACHE_DIR.$f);
				}
			}
			@closedir($dh);
		} elseif ($this->DRIVER == 'eaccelerator') {
			eaccelerator_clear();
		} elseif ($this->DRIVER == 'apc') {
			apc_clear_cache();
		} elseif ($this->DRIVER == 'xcache') {
			xcache_clear_cache();
		}
	}

	/**
	* Escape html and framework specific symbols to display in debug console
	*/
	function _debug_escape($string = '') {
		return str_replace(array_keys($this->_debug_escape_symbols), array_values($this->_debug_escape_symbols), htmlspecialchars($string, ENT_QUOTES));
	}

	/**
	* Print nice 
	*/
	function trace_string() {
		$e = new Exception();
		$data = implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), 1, -1));
		return $data;
	}
}
