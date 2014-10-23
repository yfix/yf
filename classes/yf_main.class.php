<?php

/**
* Core main class
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_main {

	/**
	* @var string Type of initialization @conf_skip
	*	- user  (for user section)
	*	- admin (for control panel)
	*/
	public $type					= 'user';
	/***/
	public $CONSOLE_MODE			= false;
	/** @var bool Use database for translation or language files */
	public $LANG_USE_DB				= false;
	/** @var bool Use custom error handler */
	public $USE_CUSTOM_ERRORS		= false;
	/** @var bool Sytem tables caching */
	public $USE_SYSTEM_CACHE		= false;
	/** @var bool Task manager on/off */
	public $USE_TASK_MANAGER		= false;
	/** @var bool Output caching on/off */
	public $OUTPUT_CACHING			= false;
	/** @var bool Send no-cache headers */
	public $NO_CACHE_HEADERS		= true;
	/** @var bool Strict init modules check (if turned on - then module need to be installed not only found) */
	public $STRICT_MODULES_INIT		= false;
	/** @var bool Session custom handler ('db','files','memcached','eaccelerator','apc','xcache' or false for 'none') */
	public $SESSION_CUSTOM_HANDLER	= false;
	/** @var string Custom session save dir (leave ampty to skip), example: 'session_data/' */
	public $SESSION_SAVE_DIR		= '';
	/** @var int Session life time (in seconds) */
	public $SESSION_LIFE_TIME		= 18000; // 5 hours
	/** @var string */
	public $SESSION_DOMAIN			= ''; // Default empty, means current domain
	/** @var string */
	public $SESSION_COOKIE_PATH		= '/';
	/** @var bool */
	public $SESSION_COOKIE_SECURE	= false;
	/** @var bool */
	public $SESSION_COOKIE_HTTPONLY	= false;
	/** @var string */
	public $SESSION_REFERER_CHECK	= ''; // WEB_PATH
	/** @var string */
	public $SESSION_DESTROY_EXPIRED	= false;
	/** @var string Custom session name */
	public $SESSION_USE_UNIQUE_NAME	= true;
	/** @var bool Auto-detect spiders */
	public $SPIDERS_DETECTION		= false;
	/** @var bool Allow to load source code from db */
	public $ALLOW_SOURCE_FROM_DB	= false;
	/** @var bool Allow to use overload protection methods inside user section (we will disable some heavy methods and/or queries) */
	public $OVERLOAD_PROTECTION		= false;
	/** @var int Overloading protection turns on (if allowed) when CPU load is higher tha this value*/
	public $OVERLOAD_CPU_LOAD		= 1;
	/** @var bool Switch standard graphics processing on/off */
	public $NO_GRAPHICS				= false;
	/** @var bool Set if no database connection needed */
	public $NO_DB_CONNECT			= false;
	/** @var bool Allow fast (but not complete) init */
	public $ALLOW_FAST_INIT			= false;
	/** @var bool Allow Geo IP tracking */
	public $USE_GEO_IP				= false;
	/** @var bool Allow to use PHPIDS (intrusion detection system) http://php-ids.org/ @experimental */
	public $INTRUSION_DETECTION		= false;
	/** @var bool Inline edit locale vars */
	public $INLINE_EDIT_LOCALE		= false;
	/** @var bool Hide total ids where possible @experimental */
	public $HIDE_TOTAL_ID			= false;
	/** @var bool Switch between traditional mode and user info with dynamic fields */
	public $USER_INFO_DYNAMIC		= false;
	/** @var bool Static pages as objects routing (eq. for URL like /terms/ instead of /static_pages/show/terms/) */
	public $STATIC_PAGES_ROUTE_TOP	= false;
	/** @var string 'Acces denied' redirect url */
	public $REDIR_URL_DENIED		= './?object=login_form&go_url=%%object%%;%%action%%%%add_get_vars%%';
	/** @var string 'Not found' redirect url, also supports internal redirect, sample: array('object' => 'help', 'action' => 'show') or array('stpl' => 'my_404_page') */
	public $REDIR_URL_NOT_FOUND		= './';
	/** @var bool Use only HTTPS protocol and check if not - the redirect to the HTTPS */
	public $USE_ONLY_HTTPS			= false;
	/** @var array List of patterns for https-enabled pages */
	public $HTTPS_ENABLED_FOR		= array( /* 'object=shop', */ );
	/** @var bool Track user last visit */
	public $TRACK_USER_PAGE_VIEWS	= false;
	/** @var bool Track online status */
	public $TRACK_ONLINE_STATUS     = false;
	/** @var bool Track details (online status=true is needed too) */
	public $TRACK_ONLINE_DETAILS	= false;
	/** @var bool Notify module setting */
	public $ENABLE_NOTIFICATIONS_USER	= false;
	/** @var bool Notify module setting */
	public $ENABLE_NOTIFICATIONS_ADMIN	= false;
    /** @var bool Paid options global switch used by lot of other code @experimental */
	public $ALLOW_PAID_OPTIONS		= false;
	/** @var bool Allow cache control from url modifiers */
	public $CACHE_CONTROL_FROM_URL	= false;
	/** @var bool Check server health status and return 503 if not OK (great to use with nginx upstream) */
	public $SERVER_HEALTH_CHECK		= false;
	/** @var bool Definies if we should connect firephp library */
	public $FIREPHP_ENABLE			= false;
	/** @var bool Logging of every engine call */
	public $LOG_EXEC				= false;
	/** @var int Execute method cache lifetime (in seconds), set to 0 to use cache module default value */
	public $EXEC_CACHE_TTL			= 600;
	/** @var string Template for exec cache name */
	public $EXEC_CACHE_NAME_TPL		= '[FUNCTION]_[CLASS]_[METHOD]_[LANG]_[DOMAIN]_[CATEGORY]_[DEBUG]';
	/** @var string Path to the server health check result */
	public $SERVER_HEALTH_FILE		= '/tmp/isok.txt';
	/** @var string @conf_skip Custom module handler method name */
	public $MODULE_CUSTOM_HANDLER	= '_module_action_handler';
	/** @var string @conf_skip Module (not class) constructor name */
	public $MODULE_CONSTRUCT		= '_init';
	/** @var int @conf_skip Current user session info */
	public $USER_ID					= 0;
	/** @var int @conf_skip Current user session info */
	public $USER_GROUP				= 0;
	/** @var array @conf_skip Current user session info */
	public $USER_INFO				= null;
	/** @var array List of objects/actions for which no db connection is required. @example: 'object' => array('action1', 'action2') */
	public $NO_DB_FOR				= array('internal' => array(), 'dynamic' => array('php_func'));
	/** @var int Error reporting level for production/non-debug mode (int from built-in constants) */
	public $ERROR_REPORTING_PROD	= 0;
	/** @var int Error reporting level for DEBUG_MODE enabled */
	public $ERROR_REPORTING_DEBUG	= 22519; // 22519 = E_ALL & ~E_NOTICE & ~E_DEPRECATED;
	/** @var string Log errors switcher, keep empty to disable logging */
	public $ERROR_LOG_PATH			= '{PROJECT_PATH}error_php.log';
	/** @var mixed Development mode, enable dev overrides layer, can containg string with developer name */
	public $DEV_MODE				= false;
	/** @var string Server host name */
	public $HOSTNAME				= '';
	/** @var int @conf_skip Multi-site mode option */
	public $SITE_ID					= null;
	/** @var int @conf_skip Multi-server mode option */
	public $SERVER_ID				= null;
	/** @var string @conf_skip Multi-server mode option */
	public $SERVER_ROLE				= null;
	/** @var bool */
	public $ALLOW_DEBUG_PROFILING	= false;
	/** @var bool @conf_skip */
	public $PROFILING				= false;

	/**
	* Engine constructor
	* Depends on type that is given to it initialize user section or administrative backend
	*/
	function __construct ($type = 'user', $no_db_connect = false, $auto_init_all = false) {
		if (!isset($this->_time_start)) {
			$this->_time_start = microtime(true);
		}
		global $CONF;
		if (defined('DEBUG_MODE') && DEBUG_MODE && ($this->ALLOW_DEBUG_PROFILING || $CONF['main']['ALLOW_DEBUG_PROFILING'])) {
			$this->PROFILING = true;
		}
		if ($_SERVER['argc'] && !isset($_SERVER['REQUEST_METHOD'])) {
			$this->CONSOLE_MODE = true;
		}
		error_reporting(0); // Remove all errors initially

		define('YF_CLS_EXT', '.class.php');
		define('YF_PREFIX', 'yf_'); // Prefix to the all framework classes
		define('YF_ADMIN_CLS_PREFIX', 'adm__'); // Prefix for the admin files (optional, to inherit user class with the same name)
		define('YF_SITE_CLS_PREFIX', 'site__'); // Prefix for the site files (optional, to inherit project level user class with the same name)

		$this->type = $type; // Initialization type (user or admin)
		define('MAIN_TYPE', $this->type); // Alias
		define('MAIN_TYPE_USER', $this->type == 'user'); // Alias
		define('MAIN_TYPE_ADMIN', $this->type == 'admin'); // Alias
		$this->NO_DB_CONNECT = (bool) $no_db_connect;
		$GLOBALS['main'] = &$this; // To allow links to the incomplete initialized class
		try {
			$this->init_conf_functions();
			$this->_before_init_hook();
			$this->init_constants();
			$this->init_php_params();
			$this->set_module_conf('main', $this); // // Load project config for self
			$this->init_firephp();
			$this->init_server_health();
			$this->try_fast_init();
			$this->init_modules_base();
			$this->init_main_functions();
			$this->init_events();
			$this->init_cache();
			$this->init_files();
			$this->init_db();
			$this->init_common();
			$this->init_class('graphics', 'classes/');
			$this->load_class_file('module', 'classes/');
			$this->init_error_reporting();
			$this->init_site_id();
			$this->init_server_id();
			$this->init_server_role();
			$this->init_settings();
			$this->spider_detection();
			$this->init_session();
			$this->init_locale();
			$this->init_tpl();
			$this->_after_init_hook();
			if ($auto_init_all) {
				$this->init_auth();
				$this->init_content();
			}
			register_shutdown_function(array($this, '_framework_destruct'));
		} catch (Exception $e) {
			$msg = 'MAIN: Caught exception: '.print_r($e->getMessage(), 1). PHP_EOL. '<pre>'.$e->getTraceAsString().'</pre>';
			trigger_error($msg, E_USER_WARNING);
		}
		return true;
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return $this->extend_call($this, $name, $args);
	}

	/**
	* Micro-framework 'fast_init' inside big YF framework. We use it when some actions need to be done at top speed.
	*/
	function try_fast_init () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (!$this->ALLOW_FAST_INIT) {
			return false;
		}
		$paths = array(
			'app'		=> APP_PATH.'share/fast_init.php',
			'project'	=> PROJECT_PATH.'share/fast_init.php',
			'yf'		=> YF_PATH.'share/fast_init.php',
		);
		foreach ($paths as $path) {
			if (file_exists($path)) {
				include_once $path;
				return true;
			}
		}
		return false;
	}

	/**
	* Allows to call code here before we begin initializing engine parts
	*/
	function _before_init_hook () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->NO_GRAPHICS = $GLOBALS['no_graphics'];
		$GLOBALS['no_graphics'] = &$this->NO_GRAPHICS;
		if (defined('DEBUG_MODE') && DEBUG_MODE) {
			ini_set('display_errors', 'on');
		}
	}

	/**
	*/
	function _check_site_maintenance () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (MAIN_TYPE_USER && !$this->is_console() && !DEBUG_MODE && conf('site_maintenance')) {
			$this->NO_GRAPHICS = true;
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 300');
			echo common()->show_empty_page( tpl()->parse('site_maintenance') );
			exit();
		}
	}

	/**
	* Allows to call code here before we begin with graphics
	*/
	function _after_init_hook () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->_check_site_maintenance();

		$this->_do_rewrite();
        
        if ($this->TRACK_ONLINE_STATUS) {
			_class('online_users', 'classes/')->process();
		}
		if ($this->type == 'admin' && $this->ENABLE_NOTIFICATIONS_ADMIN) {
			_class('notifications', 'modules/')->_prepare();
        } elseif ($this->type == 'user' && $this->ENABLE_NOTIFICATIONS_USER) {
			_class('notifications', 'modules/')->_prepare();
        }
		$this->_init_cur_user_info($this);

		if ($this->TRACK_USER_PAGE_VIEWS && $this->USER_ID) {
			$this->_add_shutdown_code(function(){
				if (!main()->NO_GRAPHICS) {
					db()->update('user', array('last_view' => time(), 'num_views' => ++$this->_user_info['num_views']), $this->USER_ID);
				}
			});
		}
		conf('filter_hidden', $_COOKIE['filter_hidden'] ? 1 : 0);
		conf('qm_hidden', $_COOKIE['qm_hidden'] ? 1 : 0);

		$https_needed = $this->USE_ONLY_HTTPS;
		if (!$https_needed) {
			$query_string = $this->_server('QUERY_STRING');
			foreach ((array)$this->HTTPS_ENABLED_FOR as $item) {
				if (is_callable($item)) {
					if ($item($query_string)) {
						$https_needed = true;
						break;
					}
				} elseif (preg_match('@'.$item.'@ims', $query_string)) {
					$https_needed = true;
					break;
				}
			}
		}
		if ($https_needed && !$this->is_console() && !($this->_server('HTTPS') || $this->_server('SSL_PROTOCOL'))) {
			$redirect_url = str_replace('http://', 'https://', WEB_PATH). $this->_server('QUERY_STRING');
			return js_redirect(process_url($redirect_url));
		}
		if ($this->INTRUSION_DETECTION) {
			$this->modules['common']->intrusion_detection();
		}
	}

	/**
	* Url rewriting engine init and apply if rewrite is enabled
	*/
    function _do_rewrite() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if ($this->is_console() || MAIN_TYPE_ADMIN || !module_conf('tpl', 'REWRITE_MODE')) {
			return false;
		}
        $host = $_SERVER['HTTP_HOST'];
		$request_uri = $_SERVER['REQUEST_URI'];
		// Override by WEB_PATH
		if (defined('WEB_PATH') && ! $this->web_path_was_not_defined) {
			$w = parse_url(WEB_PATH);
			$w_host = $w['host'];
			$w_port = $w['port'];
			$w_path = $w['path'];
			$host = $w_host. (strlen($w_port) > 1 ? ':'.$w_port : ''). (strlen($w_path) > 1 ? $w_path : '');
			if ($w_path != '/' && substr($request_uri, 0, strlen($w_path)) == $w_path) {
				$request_uri = substr($request_uri, strlen($w_path));
				$request_uri = '/'.ltrim($request_uri, '/');
			}
		}
        if (isset($_GET['host']) && !empty($_GET['host'])) {
            $host = $_GET['host'];
        }
        list($u,) = explode('?', trim($request_uri, '/'));
        $u_arr = explode('/', preg_replace('/\.htm.*/', '', $u));

		$orig_object = $_GET['object'];
		$orig_action = $_GET['action'];

        unset($_GET['object']);
        unset($_GET['action']);

		$arr = _class('rewrite')->REWRITE_PATTERNS['yf']->_parse($host, $u_arr, $_GET);

        foreach ((array)$arr as $k => $v) {
            if ($k != '%redirect_url%') {
                $_GET[$k] = $v;
            }
        }
        foreach ((array)$_GET as $k => $v) {
			if ($v == '') {
				unset($_GET[$k]);
			}
		}
		if (!isset($_GET['action'])) {
			$_GET['action'] = 'show';
		}
        $_SERVER['QUERY_STRING'] = http_build_query((array)$_GET);
    }

	/**
	* conf(), module_conf() wrappers
	*/
	function init_conf_functions () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$path = dirname(__DIR__).'/share/functions/yf_conf.php';
		if (file_exists($path)) {
			$this->include_module($path, 1);
		}
	}

	/**
	* main(), _class(), module(), db(), tpl(), common() wrappers and more
	*/
	function init_main_functions () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$path = dirname(__DIR__).'/share/functions/yf_aliases.php';
		if (file_exists($path)) {
			$this->include_module($path, 1);
		}
	}

	/**
	* Initialization of required files
	*/
	function init_files () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$include_files = array();
		$required_files = array();
		$this->events->fire('main.before_files');
		if ($this->NO_DB_CONNECT == 0) {
			$include_files[] = CONFIG_PATH. 'db_setup.php';
			$include_files[] = PROJECT_PATH. 'db_setup.php';
		}
		foreach ((array)conf('include_files::'.MAIN_TYPE) as $path) {
			$include_files[] = $path;
		}
		foreach ((array)conf('required_files::'.MAIN_TYPE) as $path) {
			$required_files[] = $path;
		}
		$common_funcs_path	= PROJECT_PATH.'share/functions/common_funcs.php';
		$fwork_funcs_path	= YF_PATH.'share/functions/'.YF_PREFIX.'common_funcs.php';
		if (file_exists($common_funcs_path)) {
			$required_files[] = $common_funcs_path;
		} elseif (file_exists($fwork_funcs_path)) {
			$required_files[] = $fwork_funcs_path;
		}
		foreach ((array)$include_files as $path) {
			$this->include_module($this->_replace_core_paths($path), $_requried = false);
		}
		foreach ((array)$required_files as $path) {
			$this->include_module($this->_replace_core_paths($path), $_requried = true);
		}
	}

	/**
	*/
	function init_modules_base () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->modules = array();
		$GLOBALS['modules'] = &$this->modules; // Compatibility with old code
	}

	/**
	*/
	function init_db() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_db');
		// Check if current object/action not required db connection
		$get_object = $_GET['object'];
		$get_action = $_GET['action'];
		if ($this->NO_DB_FOR && $get_object && isset($this->NO_DB_FOR[$get_object])) {
			if (empty($this->NO_DB_FOR[$get_object]) || ($get_action && in_array($get_action, $this->NO_DB_FOR[$get_object]))) {
				$this->NO_DB_CONNECT = true;
			}
		}
		if ($this->NO_DB_CONNECT) {
			return false;
		}
		if (!isset($GLOBALS['db'])) {
			$this->init_class('db', 'classes/');
			$GLOBALS['db'] = &$this->modules['db'];
		} else {
			$this->set_module_conf('db', $this->modules['db']);
		}
		$this->db = &$this->modules['db'];
		$this->events->fire('main.after_db');
	}

	/**
	*/
	function init_events () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events = &$this->init_class('core_events', 'classes/');
		// Load event listeners from supported locations
		$ext = '.listener.php';
		$globs = array(
			'yf_core'			=> YF_PATH. 'share/events/*'.$ext,
			'yf_plugins'		=> YF_PATH. 'plugins/*/share/events/*'.$ext,
			'project_core'		=> PROJECT_PATH. 'share/events/*'.$ext,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*/share/events/*'.$ext,
			'app_core'			=> APP_PATH. 'share/events/*'.$ext,
			'app_plugins'		=> APP_PATH. 'plugins/*/share/events/*'.$ext,
		);
		foreach ($globs as $glob) {
			foreach (glob($glob) as $path) {
				require_once $path;
			}
		}
	}

	/**
	*/
	function init_common() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->common = &$this->init_class('common', 'classes/');
		$GLOBALS['common'] = &$this->common;
	}

	/**
	*/
	function init_tpl() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_tpl');
		$this->tpl = &$this->init_class('tpl', 'classes/');
		$GLOBALS['tpl'] = &$this->tpl;
		$this->events->fire('main.after_tpl');
	}

	/**
	*/
	function init_content () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_content');

		$this->tpl->init_graphics();

		$this->is_post() && $this->events->fire('main.on_post');
		$this->is_ajax() && $this->events->fire('main.on_ajax');
		$this->is_console() && $this->events->fire('main.on_console');
		$this->is_redirect() && $this->events->fire('main.on_redirect');

		$this->events->fire('main.after_content');
	}

	/**
	*/
	function init_cache() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_cache');
		$CACHE_DRIVER = conf('CACHE_DRIVER');
		if ($CACHE_DRIVER) {
			conf('cache::DRIVER', $CACHE_DRIVER);
		}
		$this->cache = &$this->init_class('cache', 'classes/');
		$GLOBALS['cache'] = &$this->cache;
		$this->events->fire('main.after_cache');
	}

	/**
	*/
	function init_error_reporting() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if ($this->USE_CUSTOM_ERRORS) {
			$this->error_handler = &$this->init_class('core_errors', 'classes/');
		}
		if ($this->ERROR_LOG_PATH) {
			ini_set('error_log', $this->_replace_core_paths($this->ERROR_LOG_PATH));
		}
	}

	/**
	*/
	function init_server_health() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		// Server health result (needed to correctly self turn off faulty box from frontend requests)
		if (!$this->is_console() && $this->SERVER_HEALTH_CHECK && $this->SERVER_HEALTH_FILE && file_exists($this->SERVER_HEALTH_FILE)) {
			$health_result = file_get_contents($this->SERVER_HEALTH_FILE);
			if ($health_result != 'OK') {
				header($this->_server('SERVER_PROTOCOL').' 503 Service Unavailable');
				exit();
			}
		}
		// Get current server load value (only for user section)
		if ($this->OVERLOAD_PROTECTION && MAIN_TYPE_USER && !OS_WINDOWS) {
			$load = sys_getloadavg();
			conf('HIGH_CPU_LOAD', $load[0] > $this->OVERLOAD_CPU_LOAD ? 1 : 0);
		} else {
			conf('HIGH_CPU_LOAD', 0);
		}
	}

	/**
	*/
	function init_firephp () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (!$this->FIREPHP_ENABLE) {
			return false;
		}
		if (function_exists('fb') && class_exists('FirePHP')) {
			return true;
		}
		$f = YF_PATH.'libs/firephp-core/lib/FirePHPCore/fb.php';
		if (file_exists($f)) {
			include_once $f;
		}
	}

	/**
	*/
	function spider_detection () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (!$this->SPIDERS_DETECTION) {
			return false;
		}
		$_spider_name = conf('SPIDER_NAME');
		if (isset($_spider_name)) {
			return $_spider_name;
		}
		$SPIDER_NAME = $this->modules['common']->_is_spider($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
		if (empty($SPIDER_NAME)) {
			if (preg_match('/(bot|spider|crawler|curl|wget)/ims', $USER_AGENT)) {
				$SPIDER_NAME = 'Unknown spider';
			}
		}
		if (!empty($SPIDER_NAME)) {
			conf('IS_SPIDER', true);
			conf('SPIDER_NAME',	$SPIDER_NAME);
		}
		return $SPIDER_NAME;
	}

	/**
	*/
	function init_session () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_session');
		$skip = false;
		if (isset($this->_session_init_complete) || $this->is_console() || conf('SESSION_OFF') || $this->SESSION_OFF) {
			$skip = true;
		} elseif ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
			$skip = true;
		}
		if (!$skip) {
			_class('session')->start();
		}
		$this->events->fire('main.after_session');
	}

	/**
	* Initialization settings stored in the database
	*/
	function init_settings() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$lang = 'en'; // default lang
		if (defined('DEFAULT_LANG') && DEFAULT_LANG != '') {
			$lang = DEFAULT_LANG;
		}
		conf('language', $lang);
		conf('charset',	'utf-8');
		$output_caching = conf('output_caching');
		if (isset($output_caching)) {
			$this->OUTPUT_CACHING = $output_caching;
		}
		$this->events->fire('main.settings');
	}

	/**
	* Try to find current site if not done yet
	*/
	function init_site_id() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (!conf('SITE_ID')) {
			$site_id = 1;
			foreach ((array)$this->get_data('sites') as $site) {
				if ($site['name'] == $_SERVER['HTTP_HOST']) {
					$site_id = $site['id'];
					break;
				}
			}
			conf('SITE_ID', (int)$site_id);
			$this->SITE_ID = (int)$site_id;
		}
		return $this->SITE_ID;
	}

	/**
	* Try to find current server if not done yet
	*/
	function init_server_id() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$servers = $this->get_data('servers');
		$this->SERVER_ID = 0;
		if (!conf('SERVER_ID') && ($servers || DEBUG_MODE)) {
			$self_ips = explode(' ', exec('hostname --all-ip-addresses'));
			if ($self_ips) {
				$self_ips = array_combine($self_ips, $self_ips);
				$this->_server_self_ips = $self_ips;
			}
			foreach ((array)$servers as $server) {
				if ($server['hostname'] == $this->HOSTNAME) {
					$this->SERVER_ID = (int)$server['id'];
					break;
				}
				$server_ips = array();
				if ($self_ips) {
					foreach (explode(',', str_replace(array(',',';',PHP_EOL,"\t",' '), ',', trim($server['ip']))) as $v) {
						$v = trim($v);
						$v && $server_ips[$v] = $v; 
					}
					if ($server_ips && array_intersect($self_ips, $server_ips)) {
						$this->SERVER_ID = (int)$server['id'];
						break;
					}
				}
			}
		}
		conf('SERVER_ID', (int)$this->SERVER_ID);
		if ($this->SERVER_ID) {
			$this->SERVER_INFO = $servers[$this->SERVER_ID];
		}
		return $this->SERVER_ID;
	}

	/**
	* Try to find current server role if not done yet
	*/
	function init_server_role() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->SERVER_ROLE = 'default';
		if (!conf('SERVER_ROLE') && $this->SERVER_INFO['role']) {
			$this->SERVER_ROLE = $this->SERVER_INFO['role'];
			conf('SERVER_ROLE', $this->SERVER_ROLE);
		}
		return $this->SERVER_ROLE;
	}

	/**
	* Starting localization engine
	*/
	function init_locale () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if ($_GET['no_lang'] || conf('no_locale')) {
			return false;
		}
		_class('i18n')->init_locale();
	}

	/**
	* Init authentication
	*/
	function init_auth () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		$this->events->fire('main.before_auth');
		if (defined('SITE_DEFAULT_PAGE')) {
			conf('SITE_DEFAULT_PAGE', SITE_DEFAULT_PAGE);
		}
		if (conf('no_internal_auth')) {
			$def_page = conf('SITE_DEFAULT_PAGE');
			if ($def_page) {
				parse_str(substr($def_page, 3), $_tmp);
				foreach ((array)$_tmp as $k => $v) {
					$_GET[$k] = $v;
				}
			}
			return false;
		}
		if ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
			return false;
		}
		$auth_module_name = 'auth_'.(MAIN_TYPE_ADMIN ? 'admin' : 'user');
		$auth_loaded_module_name = $this->load_class_file($auth_module_name, 'classes/auth/');
		if ($auth_loaded_module_name) {
			$this->auth = new $auth_loaded_module_name();
			$this->set_module_conf($auth_module_name, $this->auth);
			$this->auth->init();
		}
		if (!is_object($this->auth)) {
			return trigger_error('MAIN: Cannot load needed auth module', E_USER_ERROR);
		}
	}

	/**
	* Include module file
	*/
	function include_module($path_to_module = '', $is_required = false) {
		if (DEBUG_MODE) {
			$_time_start = microtime(true);
		}
		// Will throw E_FATAL_ERROR if not found
		$file_exists = file_exists($path_to_module);
		if ($is_required) {
			if ($file_exists) {
				include_once $path_to_module;
			} else {
				if (DEBUG_MODE) {
					echo '<b>YF FATAL ERROR</b>: Required file not found: '.$path_to_module.'<br>\n<pre>'.$this->trace_string().'</pre>';
				}
				exit();
			}
		// Here we do not want any errors if file is missing
		} elseif ($file_exists) {
			include_once ($path_to_module);
		}
		if (DEBUG_MODE) {
			debug('included_files[]', array(
				'path'		=> $path_to_module,
				'exists'	=> (int)$file_exists,
				'required'	=> (int)$is_required,
				'size'		=> $file_exists ? filesize($path_to_module) : '',
				'time'		=> round(microtime(true) - $_time_start, 5),
				'trace'		=> $this->trace_string(),
			));
		}
	}

// TODO: implement spl_autoload_register ([ callable $autoload_function [, bool $throw = true [, bool $prepend = false ]]] )
	/**
	* Module(class) loader, based on singleton pattern
	* Initialize new class object or return reference to existing one
	*/
	function &init_class ($class_name, $custom_path = '', $params = '') {
		$class_name = $this->get_class_name($class_name);
		if (isset($this->modules[$class_name]) && is_object($this->modules[$class_name])) {
			return $this->modules[$class_name];
		}
		if (empty($class_name)) {
			return false;
		}
		if ($class_name == 'main') {
			return $this;
		}
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		// Strict installed modules check (currently only for user modules)
		if ($this->STRICT_MODULES_INIT && empty($custom_path)) {
			if (!isset($this->installed_user_modules)) {
				$this->installed_user_modules = $this->get_data('user_modules');
			}
			if (MAIN_TYPE_USER) {
				$skip_array = array(
					'rewrite',
				);
				if (!in_array($class_name, $skip_array) && !isset($this->installed_user_modules[$class_name])) {
					return false;
				}
			} elseif (MAIN_TYPE_ADMIN) {
				if (!isset($this->installed_admin_modules)) {
					$this->installed_admin_modules = $this->get_data('admin_modules');
				}
				$skip_array = array();
				if (!in_array($class_name, $skip_array)	&& !isset($this->installed_admin_modules[$class_name]) && !isset($this->installed_user_modules[$class_name])) {
					return false;
				}
			}
		}
		$class_name_to_load = $this->load_class_file($class_name, $custom_path);
		if ($class_name_to_load) {
			$this->modules[$class_name] = new $class_name_to_load($params);
			$this->set_module_conf($class_name, $this->modules[$class_name], $params);
		}
		if (is_object($this->modules[$class_name])) {
			return $this->modules[$class_name];
		} else {
			return null;
		}
	}

	/**
	*/
	function _preload_plugins_list($force = false) {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (isset($this->_plugins) && !$force) {
			return $this->_plugins;
		}
		$white_list = (array)$this->_plugins_white_list;
		$black_list = (array)$this->_plugins_black_list;
		// Order matters for plugins_classes !!
		$sets = array(
			'framework'	=> YF_PATH.'plugins/*/',
			'project'	=> PROJECT_PATH.'plugins/*/',
			'app'		=> APP_PATH.'plugins/*/',
		);
		$_plen = strlen(YF_PREFIX);
		$plugins = array();
		$plugins_classes = array();
		$ext = YF_CLS_EXT; // default is .class.php
		foreach ((array)$sets as $set => $pattern) {
			foreach ((array)glob($pattern, GLOB_ONLYDIR|GLOB_NOSORT) as $d) {
				$pname = basename($d);
				if ($white_list && wildcard_compare($white_list, $pname)) {
					// result is good, do not check black list if name found here, inside white list
				} elseif ($black_list && wildcard_compare($black_list, $pname)) {
					// Do not load files from this plugin
					break;
				}
				$dlen = strlen($d);
				$classes = array();
				foreach (array_merge(glob($d.'*/*'.$ext), glob($d.'*/*/*'.$ext)) as $f) {
					$cname = str_replace($ext, '', basename($f));
					$cdir = dirname(substr($f, $dlen)).'/';
					if (substr($cname, 0, $_plen) == YF_PREFIX) {
						$cname = substr($cname, $_plen);
					}
					$classes[$cname][$cdir] = $f;
					$plugins_classes[$cname] = $pname;
				}
				$plugins[$pname][$set] = $classes;
			}
		}
		ksort($plugins);
		$this->_plugins = $plugins;
		ksort($plugins_classes);
		$this->_plugins_classes = $plugins_classes;
		return $this->_plugins;
	}

	/**
	*/
	function _class_exists($class_name = '', $custom_path = '', $force_storage = '') {
		$loaded = $this->load_class_file($class_name, $custom_path, $force_storage);
		return (bool)$loaded;
	}

	/**
	* Load module file
	*/
	function load_class_file($class_name = '', $custom_path = '', $force_storage = '') {
		if (empty($class_name) || $class_name == 'main') {
			return false;
		}
		$cur_hook_prefix = MAIN_TYPE_ADMIN ? YF_ADMIN_CLS_PREFIX : YF_SITE_CLS_PREFIX;
		$loaded_class_name	= false;
		// Site loaded class have top priority
		$site_class_name = $cur_hook_prefix. $class_name;
		if (class_exists($site_class_name)) {
			return $site_class_name;
		}
		if (class_exists($class_name)) {
			return $class_name;
		}
		if (class_exists($cur_hook_prefix. $class_name)) {
			return $cur_hook_prefix. $class_name;
		}
		if (class_exists(YF_PREFIX. $class_name)) {
			return YF_PREFIX. $class_name;
		}
		if (substr($class_name, 0, strlen(YF_PREFIX)) === YF_PREFIX) {
			$class_name = substr($class_name, strlen(YF_PREFIX));
		}
		if (DEBUG_MODE) {
			$_time_start = microtime(true);
		}
		$class_file = $class_name. YF_CLS_EXT;
		// Developer part of path is related to hostname to be able to make different code overrides for each
		$dev_path = '.dev/'. $this->HOSTNAME.'/';
		// additional path variables
		$SITE_PATH = MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH;
		if (MAIN_TYPE_USER) {
			if (empty($custom_path)) {
				$site_path			= USER_MODULES_DIR;
				$site_path_dev		= $dev_path. USER_MODULES_DIR;
				$project_path		= USER_MODULES_DIR;
				$project_path_dev	= $dev_path. USER_MODULES_DIR;
				$fwork_path			= USER_MODULES_DIR;
			} else {
				if (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH)) {
					$site_path			= $custom_path;
					$site_path_dev		= $dev_path. $custom_path;
					$project_path		= $custom_path;
					$project_path_dev	= $dev_path. $custom_path;
					$fwork_path			= $custom_path;
				} else {
					$site_path			= $custom_path;
				}
			}
		} elseif (MAIN_TYPE_ADMIN) {
			if (empty($custom_path)) {
				$site_path			= ADMIN_MODULES_DIR;
				$site_path_dev		= $dev_path. ADMIN_MODULES_DIR;
				$project_path		= ADMIN_MODULES_DIR;
				$project_path_dev	= $dev_path. ADMIN_MODULES_DIR;
				$fwork_path			= ADMIN_MODULES_DIR;
				$project_path2		= USER_MODULES_DIR;
			} else {
				if (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH) && false === strpos($custom_path, ADMIN_SITE_PATH)) {
					$site_path			= $custom_path;
					$site_path_dev		= $dev_path. $custom_path;
					$project_path		= $custom_path;
					$project_path_dev	= $dev_path. $custom_path;
					$fwork_path			= $custom_path;
				} else {
					$site_path			= $custom_path;
				}
			}
		}
		if (!isset($this->_plugins)) {
			$this->_preload_plugins_list();
		}
		$yf_plugins = &$this->_plugins;
		$yf_plugins_classes = &$this->_plugins_classes;

		// Order of storages matters a lot!
		$storages = array();
		if (conf('DEV_MODE')) {
			if ($site_path_dev && $site_path_dev != $project_path_dev) {
				$storages['dev_site'] = array($SITE_PATH. $site_path_dev);
			}
			$storages['dev_app'] = array(APP_PATH. $project_path_dev);
			$storages['dev_project'] = array(PROJECT_PATH. $project_path_dev);
		}
		if (strlen($SITE_PATH. $site_path) && ($SITE_PATH. $site_path) != (PROJECT_PATH. $project_path)) {
			$storages['site'] = array($SITE_PATH. $site_path);
		}
		$storages['app_site_hook'] = array(APP_PATH. $site_path, $cur_hook_prefix);
		$storages['app'] = array(APP_PATH. $project_path);
		$storages['project_site_hook'] = array($SITE_PATH. $site_path, $cur_hook_prefix);
		$storages['project'] = array(PROJECT_PATH. $project_path);
		$plugin_name = '';
		if (isset($yf_plugins[$class_name]) || isset($yf_plugins_classes[$class_name])) {
			if (isset($yf_plugins_classes[$class_name])) {
				$plugin_name = $yf_plugins_classes[$class_name];
			} elseif (isset($yf_plugins[$class_name])) {
				$plugin_name = $class_name;
			}
		}
		if ($plugin_name) {
			$plugin_info = $yf_plugins[$plugin_name];
			$plugin_subdir = 'plugins/'.$plugin_name.'/';

			if ($site_path && $site_path != $project_path) {
				$storages['plugins_site'] = array($SITE_PATH. $plugin_subdir. $site_path);
			}
			if (isset($plugin_info['app'])) {
				$storages['plugins_app'] = array(APP_PATH. $plugin_subdir. $project_path);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_app']	= array(APP_PATH. $plugin_subdir. $project_path2);
				}
			} elseif (isset($plugin_info['project'])) {
				$storages['plugins_project'] = array(PROJECT_PATH. $plugin_subdir. $project_path);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_project']	= array(PROJECT_PATH. $plugin_subdir. $project_path2);
				}
			}
		}
		$storages['framework'] = array(YF_PATH. $fwork_path, YF_PREFIX);
		if ($plugin_name) {
			if (isset($plugin_info['framework'])) {
				$storages['plugins_framework'] = array(YF_PATH. $plugin_subdir. $fwork_path, YF_PREFIX);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_framework'] = array(YF_PATH. $plugin_subdir. USER_MODULES_DIR, YF_PREFIX);
				}
			}
		}
		if (MAIN_TYPE_ADMIN) {
			$storages['admin_user_app']	= array(APP_PATH. $project_path2);
			$storages['admin_user_project']	= array(PROJECT_PATH. $project_path2);
			$storages['admin_user_framework'] = array(YF_PATH. USER_MODULES_DIR, YF_PREFIX);
		}
		// Extending storages on-the-fly. Examples:
		// main()->_custom_class_storages = array(
		//     'film_model' => array('unit_tests' => array(__DIR__.'/model/other_fixtures/')),
		//     '*_model' => array('unit_tests' => array(__DIR__.'/model/fixtures/')),
		// );
		// $film_model = _class('film_model');
		foreach ((array)$this->_custom_class_storages as $_class_name => $_storages) {
			// Have support for wildcards: * ? [abc]
			if (!fnmatch($_class_name, $class_name)) {
				continue;
			}
			foreach ((array)$_storages as $sname => $sinfo) {
				$storages[$sname] = $sinfo;
			}
		}
		$storage = '';
		$loaded_path = '';
		foreach ((array)$storages as $_storage => $v) {
			$_path		= strval($v[0]);
			$_prefix	= strval($v[1]);
			if (empty($_path)) {
				continue;
			}
			if ($force_storage && $force_storage != $_storage) {
				continue;
			}
			$this->include_module($_path. $_prefix. $class_file);
			if (class_exists($_prefix. $class_name)) {
				$loaded_class_name	= $_prefix. $class_name;
				$storage = $_storage;
				$loaded_path = $_path. $_prefix. $class_file;
				break;
			}
		}
		// Try to load classes from db
		if (empty($loaded_class_name) && $this->ALLOW_SOURCE_FROM_DB && is_object($this->db)) {
			$result_from_db = $this->db->query_fetch('SELECT * FROM '.db('code_source').' WHERE keyword="'._es($class_name).'"');
			if (!empty($result_from_db)) {
				eval($result_from_db['source']);
			}
			if (class_exists($class_name)) {
				$loaded_class_name	= $class_name;
				$storage = 'db';
			}
		}
		if (DEBUG_MODE) {
			debug('main_load_class[]', array(
				'class_name'		=> $class_name,
				'loaded_class_name'	=> $loaded_class_name,
				'loaded_path'		=> $loaded_path,
				'storage'			=> $storage,
				'storages'			=> $storages,
				'time'				=> (microtime(true) - $_time_start),
				'trace'				=> $this->trace_string(),
			));
		}
		return $loaded_class_name;
	}

	/**
	* Main $_GET tasks handler
	*/
	function tasks($allowed_check = false) {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		return $this->init_class('core_blocks', 'classes/')->tasks($allowed_check);
	}

	/**
	* Prepare name for call_class_method cache
	*/
	function _get_exec_cache_name($class_name = '', $custom_path = '', $method_name = '', $method_params = '', $tpl_name = '') {
		$params = array(
			'[FUNCTION]'=> 'call_class_method',
			'[CLASS]'	=> $class_name,
			'[METHOD]'	=> $method_name,
			'[LANG]'	=> defined('DEFAULT_LANG') ? DEFAULT_LANG : conf('language'),
			'[DOMAIN]'	=> defined('CUR_DOMAIN_LONG') ? CUR_DOMAIN_LONG : $_SERVER['HTTP_HOST'],
			'[CATEGORY]'=> conf('current_category'),
			'[DEBUG]'	=> intval(DEBUG_MODE),
		);
		return str_replace(array_keys($params), array_values($params), $this->EXEC_CACHE_NAME_TPL);
	}

	/**
	* Try to return class method output
	*/
	function call_class_method ($class_name = '', $custom_path = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '') {
		if (!strlen($class_name) || !strlen($method_name)) {
			return false;
		}
		$class_name === '@object' && $class_name = $_GET['object'];
		$method_name === '@action' && $method_name = $_GET['action'];
		if (!$this->USE_SYSTEM_CACHE) {
			$use_cache = false;
		}
		if ($use_cache) {
			$cache_name = $this->_get_exec_cache_name($class_name, $custom_path, $method_name, $method_params, $tpl_name);
			$cache_ttl = intval($cache_ttl);
			if ($cache_ttl < 1) {
				// set to 0 to use cache module default value
				$cache_ttl = $this->EXEC_CACHE_TTL;
			}
			$cached = $this->modules['cache']->get($cache_name, $cache_ttl);
			if (!empty($cached)) {
				return $cached[0];
			}
		}
		if ($class_name == 'main') {
			$obj = $this;
		} else {
			$obj = $this->init_class($class_name, $custom_path, $method_params);
			if (!is_object($obj) && !$custom_path) {
				$custom_path = 'classes/';
				$obj = $this->init_class($class_name, $custom_path, $method_params);
			}
		}
		if (!is_object($obj)) {
			if (!$silent) {
				trigger_error('MAIN: module "'.$class_name.'" init failed'. (!empty($tpl_name) ? ' (template "'.$tpl_name.'"' : ''), E_USER_WARNING);
			}
			return false;
		}
		if (!method_exists($obj, $method_name)) {
			if (!$silent) {
				trigger_error('MAIN: no method "'.$method_name.'" in module "'.$class_name.'"'. (!empty($tpl_name) ? ' (template "'.$tpl_name.'")' : ''), E_USER_WARNING);
			}
			return false;
		}
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($method_params) && strlen($method_params)) {
			$method_params = (array)_attrs_string2array($method_params);
		}
		$result = $obj->$method_name($method_params);
		if ($use_cache) {
			$this->modules['cache']->set($cache_name, array($result));
		}
		return $result;
	}

	/**
	* Try to return class method output (usually from templates)
	*/
	function _execute ($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '') {
		if (DEBUG_MODE) {
			$_time_start = microtime(true);
		}
		$body = $this->call_class_method($class_name, $path, $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
		$this->events->fire('main.execute', array(
			'body' => &$body,
			'args' => func_get_args()
		));
		if (DEBUG_MODE) {
			debug('main_execute_block_time[]', array(
				'class'		=> $class_name,
				'method'	=> $method_name,
				'params'	=> $method_params,
				'tpl_name'	=> $tpl_name,
				'silent'	=> (int)$silent,
				'size'		=> strlen($body),
				'time'		=> round(microtime(true) - $_time_start, 5),
				'trace'		=> $this->trace_string(),
			));
		}
		return $body;
	}

	/**
	* Alias for '_execute'
	*/
	function execute ($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '') {
		return $this->_execute ($class_name, $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
	}

	/**
	* Alias for '_execute'
	*/
	function exec_cached ($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = true, $cache_ttl = -1, $cache_key_override = '') {
		return $this->_execute ($class_name, $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
	}

	/**
	* Set module properties from project conf array
	*/
	function set_module_conf($module_name = '', &$MODULE_OBJ, $params = '') {
		// Stop here if project config not set or some other things missing
		if (empty($module_name)	|| !is_object($MODULE_OBJ)) {
			return false;
		}
		global $PROJECT_CONF, $CONF;
		$module_conf_name = $module_name;
		// Allow to have separate conf entries for admin or user only modules
		if (isset($PROJECT_CONF[MAIN_TYPE.':'.$module_name])) {
			$module_conf_name = MAIN_TYPE.':'.$module_name;
		}
		if (isset($PROJECT_CONF[$module_conf_name])) {
			foreach ((array)$PROJECT_CONF[$module_conf_name] as $k => $v) {
				$MODULE_OBJ->$k = $v;
			}
		}
		// Override PROJECT_CONF with specially set CONF (from web admin panel, as example)
		if (isset($CONF[$module_conf_name]) && is_array($CONF[$module_conf_name])) {
			foreach ((array)$CONF[$module_conf_name] as $k => $v) {
				$MODULE_OBJ->$k = $v;
			}
		}
		// Implementation of hook 'init'
		if (method_exists($MODULE_OBJ, $this->MODULE_CONSTRUCT)) {
			$MODULE_OBJ->{$this->MODULE_CONSTRUCT}($params);
		}
	}

	/**
	* Get named data array
	*/
	function get_data ($name = '', $force_ttl = 0, $params = array()) {
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		if (empty($name)) {
			return null;
		}
		$cache_used = false;
		$data = null;
		$cache_obj_available = is_object($this->modules['cache']);
		if (!empty($this->USE_SYSTEM_CACHE) && $cache_obj_available) {
			$data = $this->modules['cache']->get($name, $force_ttl, $params);
			$cache_used = true;
		}
		$no_cache = false;
		if (empty($data) && !is_array($data)) {
			if (!$this->_data_handlers_loaded) {
				$this->_load_data_handlers();
			}
			$name_to_save = $name;
			$params_to_eval = '';
			// Example: geo_regions, array(country => UA)  will be saved as geo_regions_countryUA
			if (!empty($params) && is_array($params)) {
				foreach ((array)$params as $k => $v) {
					$name_to_save .= '_'.$k. $v;
				}
			}
			$handler = $this->data_handlers[$name];
			if (!empty($handler)) {
				if (is_string($handler)) {
					$data = include $handler;
					if (is_callable($data)) {
						$data = $data($params);
					}
				} elseif (is_callable($handler)) {
					$data = $handler($params);
				}
				if (!$data) {
					$data = array();
				}
			}
			// Do not put empty data if database could not connected (not hiding mistakes with empty get_data)
			if (empty($data) && is_object($this->db) && !$this->db->_connected) {
				$no_cache = true;
			}
			if ($this->USE_SYSTEM_CACHE && !$no_cache && $cache_obj_available) {
				$this->modules['cache']->set($name_to_save, $data);
			}
		}
		if (DEBUG_MODE) {
			debug('main_get_data[]', array(
				'name'		=> $name,
				'real_name'	=> $name_to_save,
				'data'		=> $data,
				'params'	=> $params,
				'cache_used'=> (int)$cache_used,
				'force_ttl'	=> $force_ttl,
				'time'		=> round(microtime(true) - $time_start, 5),
				'trace'		=> $this->trace_string(),
			));
		}
		return $data;
	}

	/**
	* Put named data array
	*/
	function put_data ($name = '', $data = array()) {
		if (empty($this->USE_SYSTEM_CACHE)) {
			return false;
		}
		if (!is_object($this->modules['cache'])) {
			return false;
		}
		return $this->modules['cache']->set($name, $data);
	}

	/**
	* Load common data handlers array from file
	*/
	function _load_data_handlers () {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if ($this->_data_handlers_loaded) {
			return false;
		}
		$this->data_handlers = array();
		$this->events->fire('main.load_data_handlers');
		$framework_rules_file_path = YF_PATH. 'share/data_handlers.php';
		if (file_exists($framework_rules_file_path)) {
			include ($framework_rules_file_path);
		}
		$rules_file_path = PROJECT_PATH. 'share/data_handlers.php';
		if (file_exists($rules_file_path)) {
			include ($rules_file_path);
		}
		$rules_file_path = PROJECT_PATH. 'cache_rules.php';
		if (file_exists($rules_file_path)) {
			include ($rules_file_path);
		}
		$this->_data_handlers_loaded = true;
	}

	/**
	* Simple trace without dumping whole objects
	*/
	function trace() {
		$trace = array();
		foreach (debug_backtrace() as $k => $v) {
			if (!$k) {
				continue;
			}
			$v['object'] = isset($v['object']) && is_object($v['object']) ? get_class($v['object']) : null;
			$trace[$k - 1] = $v;
		}
		return $trace;
	}

	/**
	* Print nice 
	*/
	function trace_string() {
		$e = new Exception();
		return implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 1, -1));
	}

	/**
	* Search for sites configuration overrides (in subfolder ./sites/)
	*/
	function _find_site($sites_dir = '') {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		if (empty($sites_dir)) {
			$sites_dir = PROJECT_PATH.'sites/';
		}
		if (is_array($sites_dir)) {
			$dirs = $sites_dir;
		} else {
			if (!file_exists($sites_dir)) {
				return '';
			}
			$dirs = array_merge(
				glob($sites_dir.'*', GLOB_ONLYDIR),
				glob($sites_dir.'.*', GLOB_ONLYDIR)
			);
		}
		$sites = $sites1 = $sites2 = array();
		foreach((array)$dirs as $v) {
			$v = strtolower(basename($v));
			if ($v == '.' || $v == '..') {
				continue;
			}
			if (preg_match('#^([0-9]+\.|:[0-9]+)#', $v)) {
				$sites1[$v] = $v;
			} else {
				$sites2[$v] = $v;
			}
		}
		$sort_by_length = function($a, $b) {
			return (strlen($a) < strlen($b)) ? +1 : -1;
		};
		uksort($sites1, $sort_by_length);
		uksort($sites2, $sort_by_length);
		$sites = $sites1 + $sites2;
		$found_site = $this->_find_site_path_best_match($sites, $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_HOST']);
		return $found_site;
	}

	/**
	* Trying to find site matching current environment
	* Examples: 127.0.0.1  192.168.  192.168.1.5  :443  :81  example.com  .example.com  .dev  .example.dev  .example.dev:443  .example.dev:81
	*	 subdomain. subdomain.:443 sub1.sub2. sub1.sub2.:443
	*/
	function _find_site_path_best_match($sites, $server_ip, $server_port, $server_host) {
		$sip = explode('.', $server_ip);
		$sh = array_reverse(explode('.', $server_host));
		$sh2 = explode('.', $server_host);
		$variants = array(
			$server_ip.':'.$server_port,
			$server_ip,
			$sip[0].'.'.$sip[1].'.'.$sip[2].'.:'.$server_port,
			$sip[0].'.'.$sip[1].'.'.$sip[2].'.',
			$sip[0].'.'.$sip[1].'.:'.$server_port,
			$sip[0].'.'.$sip[1].'.',
			$sip[0].'.:'.$server_port,
			$sip[0].'.',
			$server_host.':'.$server_port,
			$server_host,
			'.'.$sh[0].':'.$server_port,
			'.'.$sh[0],
			'.'.$sh[1].'.'.$sh[0].':'.$server_port,
			'.'.$sh[1].'.'.$sh[0],
			$sh2[0].'.'.$sh2[1].'.:'.$server_port,
			$sh2[0].'.'.$sh2[1].'.',
			$sh2[0].'.:'.$server_port,
			$sh2[0].'.',
			':'.$server_port,
		);
		foreach (array_intersect($sites, $variants) as $sname) {
			return $sname;
		}
		return ''; // Found nothing
	}

	/**
	* Check and try to fix required constants
	*/
	function init_constants() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		// Save current working directory (to restore it later when execute shutdown functions)
		$this->_CWD = getcwd();

		if (!defined('DEBUG_MODE')) {
			define('DEBUG_MODE', false);
		}
		if (DEBUG_MODE) {
			ini_set('display_errors', 'stdout');
		}
		if (!is_null($_SERVER['SERVER_ADDR'])) {
			$_SERVER['SERVER_ADDR'] = preg_replace('#[^0-9\.]+#', '', trim( $_SERVER['SERVER_ADDR'] ));
		}
		if (!is_null($_SERVER['SERVER_PORT'])) {
			$_SERVER['SERVER_PORT'] = intval( $_SERVER['SERVER_PORT'] );
		}
		if (!is_null($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = strtolower(str_replace('..', '.', preg_replace('#[^0-9a-z\-\.]+#', '', trim( $_SERVER['HTTP_HOST'] ))));
		}
		if (!is_null($_SERVER['REQUEST_URI'])) {
			// Possible bug when apache sends full url into request_uri, like this: "http://test.dev/" instead of "/"
			$p = parse_url($_SERVER['REQUEST_URI']);
			if (isset($p['scheme']) || isset($p['host'])) {
				$_SERVER['REQUEST_URI'] = ($p['path'] ?: '/'). ($p['query'] ? '?'.$p['query'] : '');
				if ($_SERVER['QUERY_STRING'] != $p['query']) {
					$_SERVER['QUERY_STRING'] = $p['query'];
					parse_str($p['query'], $_get);
					foreach ((array)$_get as $k => $v) {
						$_GET[$k] = $v;
					}
				}
			}
		}
		define('OS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');

		if (defined('DEV_MODE')) {
			conf('DEV_MODE', DEV_MODE);
		}
		$this->DEV_MODE = conf('DEV_MODE');
		$this->HOSTNAME = php_uname('n');
		// Check required params
		if (!defined('INCLUDE_PATH')) {
			if ($this->is_console()) {
				$_trace = debug_backtrace();
				$_trace = $_trace[1];
				$_path = dirname($_trace['file']);
				define('INCLUDE_PATH', (MAIN_TYPE_ADMIN ? dirname($_path) : $_path).'/');
			} else {
				$cur_script_path = dirname(realpath(getenv('SCRIPT_FILENAME')));
				define('INCLUDE_PATH', str_replace(array("\\",'//'), array('/','/'), (MAIN_TYPE_ADMIN ? dirname($cur_script_path) : $cur_script_path).'/'));
			}
		}
		// Alias
		define('PROJECT_PATH',	INCLUDE_PATH);
		// Website inside project FS base path. Recommended to use from now instead of REAL_PATH
		if (!defined('SITE_PATH')) {
			$sites_dir = PROJECT_PATH.'sites/';
			$found_site = $this->_find_site($sites_dir);
			define('SITE_PATH', $found_site ? $sites_dir.$found_site.'/' : PROJECT_PATH);
		}
		// Alias of SITE_PATH. Compatibility with old code. DEPRECATED
		if (!defined('REAL_PATH')) {
			define('REAL_PATH', SITE_PATH);
		}
		// Framework root filesystem path
		if (!defined('YF_PATH')) {
			define('YF_PATH', dirname(PROJECT_PATH).'/yf/');
		}
		// Project-level application path, where will be other important subfolders like: APP_PATH.'www/', APP_PATH.'docs/', APP_PATH.'tests/',
		if (!defined('APP_PATH')) {
			define('APP_PATH', dirname(PROJECT_PATH).'/');
		}
		// Filesystem path for configuration files, including db_setup.php and so on
		if (!defined('CONFIG_PATH')) {
			define('CONFIG_PATH', APP_PATH.'config/');
		}
		// Filesystem path for various storage needs: logs, sessions, other files that should not be accessible from WEB
		if (!defined('STORAGE_PATH')) {
			define('STORAGE_PATH', APP_PATH.'storage/');
		}
		// Filesystem path to logs, usually should be at least one level up from WEB_PATH to be not accessible from web server
		if (!defined('LOGS_PATH')) {
			define('LOGS_PATH', STORAGE_PATH.'logs/');
		}
		// Uploads path should be used for various uploaded content accessible from WEB_PATH
		if (!defined('UPLOADS_PATH')) {
			define('UPLOADS_PATH', PROJECT_PATH.'uploads/');
		}
		// Set WEB_PATH (if not done yet)
		if (!defined('WEB_PATH'))	{
			$request_uri	= $_SERVER['REQUEST_URI'];
			$cur_web_path	= '';
			if ($request_uri[strlen($request_uri) - 1] == '/') {
				$cur_web_path	= substr($request_uri, 0, -1);
			} else {
				$cur_web_path	= dirname($request_uri);
			}
			$host = '';
			$conf_domains = conf('DOMAINS');
			if ($_SERVER['HTTP_HOST']) {
				$host = $_SERVER['HTTP_HOST'];
			} elseif (is_array($conf_domains)) {
				$host = (string) current($conf_domains);
			} else {
				$host = '127.0.0.1';
			}
			$this->web_path_was_not_defined = true;
			define('WEB_PATH',
				(($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL']) ? 'https://' : 'http://')
				.$host
				.str_replace(array("\\",'//'), array('/','/'), (MAIN_TYPE_ADMIN ? dirname($cur_web_path) : $cur_web_path).'/')
			);
		}
		// Should be different that WEB_PATH to distribute static content from other subdomain
		if (!defined('MEDIA_PATH')) {
			define('MEDIA_PATH', WEB_PATH);
		}
		if (!defined('ADMIN_SITE_PATH')) {
			define('ADMIN_SITE_PATH', SITE_PATH.'admin/');
		}
		if (!defined('ADMIN_WEB_PATH')) {
			define('ADMIN_WEB_PATH', WEB_PATH.'admin/');
		}
		// Check if current page is called via AJAX call from javascript
		conf('IS_AJAX', (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || !empty($_GET['ajax_mode'])) ? 1 : 0);

		define('USER_MODULES_DIR', 'modules/');
		define('ADMIN_MODULES_DIR', 'admin_modules/');
		// Set console-specific options
		if ($this->is_console()) {
			ini_set('memory_limit', -1);
			set_time_limit(0);
			// Send PHP warnings and errors to stderr instead of stdout. This aids in diagnosing problems, while keeping messages out of redirected output.
			if (ini_get('display_errors')) {
				ini_set('display_errors', 'stderr');
			}
			// Parse console options into $_GET array
			foreach ((array)$_SERVER['argv'] as $v) {
				if (substr($v, 0, 2) != '--') {
					continue;
				}
				$v = substr($v, 2);
				list($_name, $_val) = explode('=', $v);
				$_name	= trim($_name);
				if (strlen($_name)) {
					$_GET[$_name] = trim($_val);
				}
			}
		}
		// Filter object and action from $_GET
		if ($_GET['action'] == $_GET['object']) {
			$_GET['action'] = '';
		}
		$_GET['object'] = str_replace(array('yf_','-'), array('','_'), preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim( $_GET['object'] ))));
		$_GET['action'] = str_replace('-', '_', preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim( $_GET['action'] ))));
		if (!$_GET['action']) {
			$_GET['action'] = defined('DEFAULT_ACTION') ? DEFAULT_ACTION : 'show';
		}
		if ($this->USER_INFO_DYNAMIC) {
			module_conf('user_data', 'MODE', 'DYNAMIC');
		}
	}

	/**
	* Try to set required PHP runtime params
	*/
	function init_php_params() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		error_reporting(DEBUG_MODE ? $this->ERROR_REPORTING_DEBUG : $this->ERROR_REPORTING_PROD);
		ini_set('url_rewriter.tags', '');
		ini_set('magic_quotes_runtime',	0);
		ini_set('magic_quotes_sybase', 0);
		date_default_timezone_set(conf('timezone') ?: 'Europe/Kiev');
		// Prepare GPC data. get_magic_quotes_gpc always return 0 starting from PHP 5.4+
		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
			$_GET		= $this->_strip_quotes_recursive($_GET);
			$_POST		= $this->_strip_quotes_recursive($_POST);
			$_COOKIE	= $this->_strip_quotes_recursive($_COOKIE);
			$_REQUEST	= array_merge((array)$_GET, (array)$_POST, (array)$_COOKIE);
		}
	}

	/**
	* Send main headers
	*/
	function _send_main_headers($content_length = 0) {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		return $this->init_class('graphics', 'classes/')->_send_main_headers($content_length);
		$this->events->fire('main.http_headers');
	}

	/**
	* Recursive method for stripping quotes from given data (string or array)
	*/
	function _strip_quotes_recursive($mixed) {
		if (is_array($mixed)) {
			return array_map(array($this, __FUNCTION__), $mixed);
		} else {
			return stripslashes($mixed);
		}
	}

	/**
	*/
	function _init_cur_user_info(&$obj) {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), array());
		if (MAIN_TYPE_ADMIN) {
			$obj->USER_ID		= $_GET['user_id'];
			$obj->ADMIN_ID		= (int)$_SESSION['admin_id'];
			$obj->ADMIN_GROUP	= (int)$_SESSION['admin_group'];
		} elseif (MAIN_TYPE_USER) {
			$obj->USER_ID		= (int)$_SESSION['user_id'];
			$obj->USER_GROUP	= (int)$_SESSION['user_group'];
		}
		if (isset($obj->USER_ID) && !empty($obj->USER_ID)) {
			if (!isset($this->_current_user_info)) {
				$this->_current_user_info = user($obj->USER_ID);
			}
			$obj->_user_info = &$this->_current_user_info;
			if (!$obj->USER_GROUP) {
				$obj->USER_GROUP = $this->_current_user_info['group'];
			}
		}
		$this->events->fire('main.user_info');
	}

	/**
	* Unified method to replace core paths inside configuration directives. Examples: YF_PATH, {YF_PATH}, %YF_PATH%
	*/
	function _replace_core_paths($str) {
		if (strpos($str, '_PATH') === false) {
			return $str;
		}
		if (!isset($this->_paths_replace_pairs)) {
			$pairs = array();
			// Note: order matters
			$path_names = array(
				'ADMIN_WEB_PATH',
				'ADMIN_SITE_PATH',
				'UPLOADS_PATH',
				'WEB_PATH',
				'MEDIA_PATH',
				'YF_PATH',
				'APP_PATH',
				'PROJECT_PATH',
				'SITE_PATH',
				'CONFIG_PATH',
				'STORAGE_PATH',
				'LOGS_PATH',
			);
			foreach ($path_names as $name) {
				$val = constant($name);
				$pairs[$name] = $val; // Example: YF_PATH
				$pairs['{'.$name.'}'] = $val; // Example: {YF_PATH}
				$pairs['%'.$name.'%'] = $val; // Example: %YF_PATH%
			}
			$this->_paths_replace_pairs = $pairs;
			unset($pairs);
		}
		return str_replace(array_keys($this->_paths_replace_pairs), $this->_paths_replace_pairs, $str);
	}

	/**
	* Evaluate given code as PHP code
	*/
	function _eval_code ($code_text = '', $as_string = 1) {
		return eval('return '.($as_string ? '"'.$code_text.'"' : $code_text).' ;');
	}

	/**
	* Adds code to execute on shutdown
	*/
	function _add_shutdown_code($code = '') {
		if (!empty($code)) {
			$this->_SHUTDOWN_CODE_ARRAY[] = $code;
		}
	}

	/**
	* Framework destructor handler
	*/
	function _framework_destruct() {
		$this->PROFILING && $this->_timing[] = array(microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args());
		// Restore startup working directory
		chdir($this->_CWD);

		$this->events->fire('main.shutdown');

		if ($this->CATCH_FATAL_ERRORS) {
			$error = error_get_last();
			if (in_array($error, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR))) {
				$info = '['.gmdate('Y-m-d H:i:s').'] [SHUTDOWN] file:'.$error['file'].' | ln:'.$error['line'].' | msg:'.$error['message'] .PHP_EOL;
				file_put_contents(PROJECT_PATH. 'fatal_log.txt', $info, FILE_APPEND);
				echo $info;
			}
		}
		foreach ((array)$this->_SHUTDOWN_CODE_ARRAY as $_cur_code) {
			call_user_func($_cur_code);
		}
	}

	/**
	*/
	function _get($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->get($key, $val);
	}

	/**
	*/
	function _post($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->post($key, $val);
	}

	/**
	*/
	function _session($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->session($key, $val);
	}

	/**
	*/
	function _server($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->server($key, $val);
	}

	/**
	*/
	function _cookie($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->cookie($key, $val);
	}

	/**
	*/
	function _env($key = null, $val = null) {
		return $this->init_class('input', 'classes/')->env($key, $val);
	}

	/**
	* Checks whether current page was requested with POST method
	*/
	function is_post() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	* Checks whether current page was requested with AJAX
	*/
	function is_ajax() {
		return (bool)conf('IS_AJAX');
	}

	/**
	* Checks whether current page was requested from console
	*/
	function is_console() {
		return (bool)$this->CONSOLE_MODE;
	}

	/**
	* Checks whether current page is a redirect
	*/
	function is_redirect() {
		return (bool)$this->_IS_REDIRECTING;
	}

	/**
	* Checks whether current page is not a special page (no ajax, no redirects, no console, no post, etc)
	*/
	function is_common_page() {
		return !($this->is_post() || $this->is_ajax() || $this->is_redirect() || $this->is_console());
	}

	/**
	* Checks whether current page is in unit testing mode
	*/
	function is_unit_test() {
		return (bool)defined('YF_IN_UNIT_TESTS');
	}

	/**
	* Return class name of the object, stripping all YF-related prefixes
	* Needed to ensure singleton pattern and extending classes with same name
	*/
	function get_class_name($name) {
		if (is_object($name)) {
			$name = get_class($name);
		}
		if (strpos($name, YF_PREFIX) === 0) {
			$name = substr($name, strlen(YF_PREFIX));
		} elseif (strpos($name, YF_ADMIN_CLS_PREFIX) === 0) {
			$name = substr($name, strlen(YF_ADMIN_CLS_PREFIX));
		} elseif (strpos($name, YF_SITE_CLS_PREFIX) === 0) {
			$name = substr($name, strlen(YF_SITE_CLS_PREFIX));
		}
		return $name;
	}

	/**
	*/
	function extend($module, $name, $func) {
		$module = $this->get_class_name($module);
		$this->_extend[$module][$name] = $func;
	}

	/**
	*/
	function extend_call($that, $name, $args, $return_obj = false) {
		$module = $this->get_class_name($that);
		$func = null;
		if (isset( $that->_extend[$name] )) {
			$func = $that->_extend[$name];
		} elseif (isset( $this->_extend[$module][$name] )) {
			$func = $this->_extend[$module][$name];
		}
		if ($func) {
			$out = $func($args[0], $args[1], $args[2], $args[3], $that);
			return $return_obj ? $that : $out;
		}
		trigger_error($module.': No method '.$name, E_USER_WARNING);
		return $return_obj ? $that : false;
	}
}
