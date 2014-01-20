<?php

/**
* Core ProEngine class
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
	/** @var bool GZIP compression for output buffer on/off */
	public $OUTPUT_GZIP_COMPRESS	= false;
	/** @var bool Send no-cache headers */
	public $NO_CACHE_HEADERS		= true;
	/** @var bool Strict init modules check (if turned on - then module need to be installed not only found) */
	public $STRICT_MODULES_INIT		= false;
	/** @var bool Session custom handler ('db','files','memcached','eaccelerator','apc','xcache' or false for 'none') */
	public $CUSTOM_SESSION_HANDLER	= false;
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
	/** @var string Custom session name */
	public $USE_UNIQUE_SESSION_NAME	= true;
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
	/** @var bool Auto-pack PHP code and use it @experimental */
	public $AUTO_PACK_PHP_CODE		= false;
	/** @var bool Paid options global switch used by lot of other code @experimental */
	public $ALLOW_PAID_OPTIONS		= false;
	/** @var bool Allow cache control from url modifiers */
	public $CACHE_CONTROL_FROM_URL	= false;
	/** @var bool Check server health status and return 503 if not OK (great to use with nginx upstream) */
	public $SERVER_HEALTH_CHECK		= false;
	/** @var bool Definies if we should connect firephp library */
	public $FIREPHP_ENABLE			= false;
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
	/** @var array */
	public $_auto_info_skip_modules = array('user_data','db','cache','errors','spider_detect','user_profile');
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

	/**
	* Engine constructor
	* Depends on type that is given to it initialize user section or administrative backend
	*/
	function __construct ($type = 'user', $no_db_connect = false, $auto_init_all = false) {
		if (!isset($this->_time_start)) {
			$this->_time_start = microtime(true);
		}
		if ($this->_server('argc') && !array_key_exists('REQUEST_METHOD', $this->_server())) {
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
		$GLOBALS['main'] = &$this; // To allow links to the incomplete initialized class
		try {
			$this->init_conf_functions();
			$this->_before_init_hook();
			$this->NO_DB_CONNECT = (bool) $no_db_connect;
			$this->fix_required_constants();
			$this->set_required_php_params();
			$this->_set_module_conf('main', $this); // // Load project config for self
			$this->init_firephp();
			$this->init_server_health();
			$this->try_fast_init();
			$this->init_modules_base();
			$this->init_cache_functions();
			$this->init_files();
			$this->init_db();
			$this->init_common();
			$this->init_class('graphics', 'classes/');
			$this->load_class_file('module', 'classes/');
			$this->init_error_reporting();
			$this->init_cache();
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
				$this->tpl->init_graphics();
			}
			// Add framework destructor functionality (allows to execute custom code before shutdown)
			register_shutdown_function(array($this, '_framework_destruct'));
		} catch (Exception $e) {
// TODO: show pretty html message with Exception contents
			$msg = 'MAIN: Caught exception: '.print_r($e->getMessage(), 1). PHP_EOL. '<pre>'.$e->getTraceAsString().'</pre>';
			trigger_error($msg, E_USER_WARNING);
		}
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Micro-framework 'fast_init' inside big YF framework. We use it when some actions need to be done at top speed.
	*/
	function try_fast_init () {
		if (!$this->ALLOW_FAST_INIT) {
			return false;
		}
		$fast_init_file = PROJECT_PATH.'share/fast_init.php';
		if (file_exists($fast_init_file)) {
			include ($fast_init_file);
			return true;
		}
		$fast_init_file = YF_PATH.'share/fast_init.php';
		if (file_exists($fast_init_file)) {
			include ($fast_init_file);
			return true;
		}
	}

	/**
	* Allows to call code here before we begin initializing engine parts
	*/
	function _before_init_hook () {
		$this->NO_GRAPHICS = $GLOBALS['no_graphics'];
		$GLOBALS['no_graphics'] = &$this->NO_GRAPHICS;
		if (defined('DEBUG_MODE') && DEBUG_MODE) {
			ini_set('display_errors', 'on');
		}
	}

	/**
	* Allows to call code here before we begin with graphics
	*/
	function _after_init_hook () {
		$this->_do_rewrite();

		$this->_init_cur_user_info($this);

		if ($this->TRACK_USER_PAGE_VIEWS && $this->USER_ID) {
			$this->_add_shutdown_code(function(){
				if (!main()->NO_GRAPHICS) {
					update_user($this->USER_ID, array('last_view' => time(), 'num_views' => ++$this->_user_info['num_views']));
				}
			});
		}
		conf('filter_hidden', $this->_cookie('filter_hidden') ? 1 : 0);
		conf('qm_hidden', $this->_cookie('qm_hidden') ? 1 : 0);

		$https_needed = $this->USE_ONLY_HTTPS;
		if (!$https_needed) {
			foreach ((array)$this->HTTPS_ENABLED_FOR as $_item) {
				if (preg_match('/'.$_item.'/ims', $this->_server('QUERY_STRING'))) {
					$https_needed = true;
					break;
				}
			}
		}
		if ($https_needed && !$this->CONSOLE_MODE && !($this->_server('HTTPS') || $this->_server('SSL_PROTOCOL'))) {
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
		if ($this->CONSOLE_MODE || MAIN_TYPE_ADMIN || !module_conf('tpl', 'REWRITE_MODE')) {
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

		$arr = module('rewrite')->REWRITE_PATTERNS['yf']->_parse($host, $u_arr, $_GET);

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
		$fwork_conf_path = dirname(dirname(__FILE__)).'/share/functions/yf_conf.php';
		if (file_exists($fwork_conf_path)) {
			$this->include_module($fwork_conf_path, 1);
		}
	}

	/**
	* cache_set(), cache_get(), cache_del() wrappers
	*/
	function init_cache_functions () {
		$fwork_cache_path	= dirname(dirname(__FILE__)).'/share/functions/yf_cache.php';
		if (file_exists($fwork_cache_path)) {
			$this->include_module($fwork_cache_path, 1);
		}
	}

	/**
	* Initialization of required files
	*/
	function init_files () {
		$include_files = array();
		$required_files = array();
		if ($this->NO_DB_CONNECT == 0) {
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
		// Needed to have support for config vars inside paths
		$replace = array(
			'{SITE_PATH}'	=> SITE_PATH,
			'{PROJECT_PATH}'=> PROJECT_PATH,
			'{YF_PATH}'		=> YF_PATH,
		);
		foreach ((array)$include_files as $path) {
			$path = str_replace(array_keys($replace), array_values($replace), $path);
			$this->include_module($path, $_requried = false);
		}
		foreach ((array)$required_files as $path) {
			$path = str_replace(array_keys($replace), array_values($replace), $path);
			$this->include_module($path, $_requried = true);
		}
	}

	/**
	*/
	function init_modules_base () {
		$this->modules = array();
		$GLOBALS['modules'] = &$this->modules; // Compatibility with old code
	}

	/**
	*/
	function init_db() {
		// Check if current object/action not required db connection
		$get_object = $this->_get('object');
		$get_action = $this->_get('action');
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
			$GLOBALS['db'] =& $this->modules['db'];
		} else {
			$this->_set_module_conf('db', $this->modules['db']);
		}
		$this->db =& $this->modules['db'];
	}

	/**
	*/
	function init_common() {
		$this->init_class('common', 'classes/');
		$this->common =& $this->modules['common'];
		$GLOBALS['common'] =& $this->modules['common'];
	}

	/**
	*/
	function init_tpl() {
		$this->init_class('tpl', 'classes/');
		$this->tpl =& $this->modules['tpl'];
		$GLOBALS['tpl'] =& $this->modules['tpl'];
	}

	/**
	*/
	function init_cache() {
		if ($this->CACHE_CONTROL_FROM_URL && $this->_get('no_core_cache')) {
			$this->USE_SYSTEM_CACHE = false;
		}
		if ($this->USE_SYSTEM_CACHE) {
			$this->init_class('cache', 'classes/');
			if (method_exists($this->modules['cache'], '_init_from_main')) {
				$this->_set_module_conf('cache', $this->modules['cache']);
				$this->modules['cache']->_init_from_main();
			}
			$this->sys_cache =& $this->modules['cache'];
			$GLOBALS['sys_cache'] =& $this->modules['cache'];
		}
	}

	/**
	*/
	function init_error_reporting() {
		if ($this->USE_CUSTOM_ERRORS) {
			$this->init_class('errors', 'classes/');
			$this->error_handler =& $this->modules['errors'];
		}
		if ($this->ERROR_LOG_PATH) {
			$replace = array(
				'{YF_PATH}'		=> YF_PATH,
				'{PROJECT_PATH}'=> PROJECT_PATH,
				'{SITE_PATH}'	=> SITE_PATH,
			);
			ini_set('error_log', str_replace(array_keys($replace), array_values($replace), $this->ERROR_LOG_PATH));
		}
	}

	/**
	*/
	function init_server_health() {
		// Server health result (needed to correctly self turn off faulty box from frontend requests)
		if (!$this->CONSOLE_MODE && $this->SERVER_HEALTH_CHECK && $this->SERVER_HEALTH_FILE && file_exists($this->SERVER_HEALTH_FILE)) {
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
	* FirePHP connection
	*/
	function init_firephp () {
		if (!$this->FIREPHP_ENABLE) {
			return false;
		}
		// Already initialized earlier
		if (function_exists('fb') && class_exists('FirePHP')) {
			return true;
		}
		$f = YF_PATH.'priority2/libs/firephp-core/lib/FirePHPCore/fb.php';
		if (file_exists($f)) {
			include_once $f;
		}
	}

	/**
	* Try to detect spider
	*/
	function spider_detection () {
		if (!$this->SPIDERS_DETECTION) {
			return false;
		}
		$_spider_name = conf('SPIDER_NAME');
		if (isset($_spider_name)) {
			return $_spider_name;
		}
		$SPIDER_NAME = $this->modules['common']->_is_spider($this->_server('REMOTE_ADDR'), $this->_server('HTTP_USER_AGENT'));
		if (empty($SPIDER_NAME)) {
			if (preg_match('/(bot|spider|crawler|curl|wget)/ims', $USER_AGENT)) {
				$SPIDER_NAME = 'Unknown spider';
			}
		}
		if (!empty($SPIDER_NAME)) {
			conf('IS_SPIDER',		1);
			conf('SPIDER_NAME',	$SPIDER_NAME);
		}
		return $SPIDER_NAME;
	}

	/**
	* Initialize session
	*/
	function init_session () {
		if (isset($this->_session_init_complete) || $this->CONSOLE_MODE) {
			return false;
		}
		if ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
			return false;
		}
		if (conf('SESSION_OFF') || $this->SESSION_OFF) {
			return false;
		}
		// Set custom session name
		if ($this->USE_UNIQUE_SESSION_NAME) {
			$force_name_path = conf('_SESSION_FORCE_NAME_PATH');
			$_name_path = isset($force_name_path) ? $force_name_path : (MAIN_TYPE_ADMIN ? ADMIN_SITE_PATH : SITE_PATH);
			$_name_path = str_replace("\\", '/', OS_WINDOWS ? strtolower($_name_path) : $_name_path);
			$this->SESSION_NAME = 'sid_'.intval(abs(crc32($_name_path)));
			session_name($this->SESSION_NAME);
		} else {
			$this->SESSION_NAME = ini_get('session.name'); // Usually PHPSESSID
		}
		if (session_id() !== '') { // (session_status() == PHP_SESSION_ACTIVE) => PHP 5.4+ only
			return true;
		}
		@ini_set('session.use_trans_sid',	0); // We need @ here to avoid error when session already started
		ini_set('url_rewriter.tags',		'');
		if (!empty($this->SESSION_LIFE_TIME)) {
			ini_set('session.gc_maxlifetime',	$this->SESSION_LIFE_TIME);
			ini_set('session.cookie_lifetime',	$this->SESSION_LIFE_TIME);
		}
		ini_set('session.use_cookies',		1);
		ini_set('session.use_only_cookies',	1);
		if ($this->SESSION_COOKIE_PATH) {
			ini_set('session.cookie_path', $this->SESSION_COOKIE_PATH);
		}
		if ($this->SESSION_DOMAIN) {
			ini_set('session.cookie_domain', $this->SESSION_DOMAIN);
		}
		if ($this->SESSION_COOKIE_SECURE) {
			ini_set('session.cookie_secure', 1);
		}
		if ($this->SESSION_COOKIE_HTTPONLY) {
			ini_set('session.cookie_httponly', 1);
		}
		if ($this->SESSION_REFERER_CHECK) { // WEB_PATH
			ini_set('session.referer_check', $this->SESSION_REFERER_CHECK);
		}
		conf('COOKIES_ENABLED', !is_null($this->_cookie($this->SESSION_NAME)) ? 1 : 0);
		// Check if we have valid session name
		if (!is_null($this->_cookie($this->SESSION_NAME))) {
			$_test_result = preg_replace('/[^a-z0-9]/i', '', $this->_cookie($this->SESSION_NAME));
			if ($_test_result !== $this->_cookie($this->SESSION_NAME)) {
				$this->_cookie($this->SESSION_NAME, abs(crc32(microtime(true))));
				session_id($this->_cookie($this->SESSION_NAME));
			}
		}
		if (!empty($this->SESSION_SAVE_DIR)) {
			$s_path = PROJECT_PATH.$this->SESSION_SAVE_DIR;
			if (!file_exists($s_path)) {
				mkdir($s_path, 0755, true);
			}
			session_save_path($s_path);
		}
		if ($this->CUSTOM_SESSION_HANDLER) {
			$session_class_name = 'session_'.$this->CUSTOM_SESSION_HANDLER;
			$session_loaded_class_name = $this->load_class_file($session_class_name, 'classes/session/');
			if (empty($session_loaded_class_name)) {
				return false;
			}
			$this->session = new $session_loaded_class_name();
			$this->_set_module_conf($session_class_name, $this->session);
			// Change the save_handler to use the class functions
			session_set_save_handler (
				array($this->session, '_open'),
				array($this->session, '_close'),
				array($this->session, '_read'),
				array($this->session, '_write'),
				array($this->session, '_destroy'),
				array($this->session, '_gc')
			);
		}
		session_start();
		// Instruct bots to totally ignore current page
		if (DEBUG_MODE || MAIN_TYPE_ADMIN) {
			header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		}
		$now = gmmktime();
		$last_update = $this->_session('last_update');
		if ($last_update) {
			$diff = $now - $last_update;
			$percent = $diff / $this->SESSION_LIFE_TIME * 100;
			// Session expired
			if ($percent > 100) {
				session_destroy();
				session_start();
			// Session need to be regenerated
			} elseif ($percent > 10) {
				session_regenerate_id();
				$this->_session('last_update', $now);
			}
		} else {
			$this->_session('last_update', $now);
		}
		$this->_session_init_complete = true;
	}

	/**
	* Initialization settings stored in the database
	*/
	function init_settings() {
		$this->set_default_settings();
		// Overriding default settings with the values stored in database
		foreach ((array)$this->get_data('settings') as $k => $v) {
			conf($k, $v);
		}
		// Overriding default settings with the values stored in database
		foreach ((array)$this->get_data('conf') as $k => $v) {
			conf($k, $v);
		}
		$output_caching = conf('output_caching');
		if (isset($output_caching)) {
			$this->OUTPUT_CACHING = $output_caching;
		}
		$gzip_compress = conf('gzip_compress');
		if (isset($gzip_compress)) {
			$this->OUTPUT_GZIP_COMPRESS = $gzip_compress;
		}
		if ($this->OUTPUT_GZIP_COMPRESS) {
			if (version_compare( phpversion(), '5.2.4' ) >= 0) {
				if (ini_get('display_errors')) {
					ini_set('display_errors', 'stderr');
				}
			}
			if (!extension_loaded('zlib') ||
				strpos($this->_server('HTTP_ACCEPT_ENCODING'), 'gzip') === false ||
				(bool)ini_get('zlib.output_compression') ||
				(bool)ini_get('zend_accelerator.compress_all') ||
				ini_get('output_handler') == 'ob_gzhandler'
			) {
				$this->OUTPUT_GZIP_COMPRESS = false;
			} else {
				ini_set('zlib.output_compression_level', 3);
			}
		}
	}

	/**
	* Default settings container
	*/
	function set_default_settings() {
		$lang = 'en'; // default lang
		if (defined('DEFAULT_LANG') && DEFAULT_LANG != '') {
			$lang = DEFAULT_LANG;
		}
		conf('language',	$lang);
		conf('charset',		'utf-8');
		conf('site_enabled',1);
	}

	/**
	* Try to find current site if not done yet
	*/
	function init_site_id() {
		if (!conf('SITE_ID')) {
			$site_id = 1;
			foreach ((array)$this->get_data('sites') as $site) {
				if ($site['name'] == $this->_server('HOST_NAME')) {
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
		$servers = $this->get_data('servers');
		$this->SERVER_ID = 0;
		if (!conf('SERVER_ID')) {
			foreach ((array)$servers as $server) {
// TODO: try to also get server id from console: "hostname --all-ip-addresses"
// TODO: this need to be cached to not fork exec on every request
#		$ips = exec('hostname --all-ip-addresses');
				if ($server['hostname'] == $this->HOSTNAME) {
					$this->SERVER_ID = (int)$server['id'];
					break;
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
		$this->SERVER_ROLE = 'default';
		if (!conf('SERVER_ROLE') && $this->SERVER_INFO['role']) {
			$this->SERVER_ROLE = $this->SERVER_INFO['role'];
			conf('SERVER_ROLE', $this->SERVER_ROLE);
		}
		return $this->SERVER_ROLE;
	}

	/**
	* Starting language engine
	*/
	function init_locale () {
		if ($this->_get('no_lang') || conf('no_locale')) {
			return false;
		}
		_class('i18n')->init_locale();
	}

	/**
	* Init authentication
	*/
	function init_auth () {
		if (defined('SITE_DEFAULT_PAGE')) {
			conf('SITE_DEFAULT_PAGE', SITE_DEFAULT_PAGE);
		}
		// Stop here if needed
		if (conf('no_internal_auth')) {
			$def_page = conf('SITE_DEFAULT_PAGE');
			if ($def_page) {
				parse_str(substr($def_page, 3), $_tmp);
				foreach ((array)$_tmp as $k => $v) {
					$this-_get($k, $v);
				}
			}
			return false;
		}
		// Do not use auth for the spiders
		if ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
			return false;
		}
		// Default stop-list
		if (in_array($this->_get('object'), array('site_links'))) {
			return false;
		}
		$auth_module_name = 'auth_'.(MAIN_TYPE_ADMIN ? 'admin' : 'user');
		$auth_loaded_module_name = $this->load_class_file($auth_module_name, AUTH_MODULES_DIR);
		if ($auth_loaded_module_name) {
			$this->auth = new $auth_loaded_module_name();
			$this->_set_module_conf($auth_module_name, $this->auth);
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
				include_once ($path_to_module);
			} else {
				if (DEBUG_MODE) {
					echo '<b>YF FATAL ERROR</b>: Required file not found: '.$path_to_module.'<br>\n<pre>'.$this->trace_string().'</pre>';
				}
				exit();
			}
		// Here we do not want any errors if file is missing
		} elseif ($file_exists) {
			include_once ($path_to_module);
		} else {
// TODO: log E_USER_NOTICE when module not found
		}
		if (DEBUG_MODE) {
			$path_prepared = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', str_replace("\\", '/', $path_to_module)));
			debug('include_files_exec_time::'.$path_prepared, round(microtime(true) - $_time_start, 5));
			debug('include_files_trace::'.$path_prepared, $this->trace_string());
		}
	}

	/**
	* Module class loader
	* Initialize new class object or return reference to existing one
	*/
	function &init_class ($class_name, $custom_path = '', $params = '') {
		if (empty($class_name)) {
			return false;
		}
		if (!isset($this->modules)) {
			$this->modules = array();
		}
		// Return reference to the module object
		if ($class_name == 'main') {
			return $this;
		}
		if (isset($this->modules[$class_name]) && is_object($this->modules[$class_name])) {
			return $this->modules[$class_name];
		}
		// Strict installed modules check (currently only for user modules)
		if ($this->STRICT_MODULES_INIT && empty($custom_path)) {
			if (!isset($this->installed_user_modules)) {
				$this->installed_user_modules = $this->get_data('user_modules');
			}
			if (MAIN_TYPE_USER) {
				$skip_array = array(
					'rewrite',
				);
				// Check if given module name is installed correctly
				if (!in_array($class_name, $skip_array) && !isset($this->installed_user_modules[$class_name])) {
					return false;
				}
			} elseif (MAIN_TYPE_ADMIN) {
				if (!isset($this->installed_admin_modules)) {
					$this->installed_admin_modules = $this->get_data('admin_modules');
				}
				$skip_array = array();
				// Check if given module name is installed correctly
				if (!in_array($class_name, $skip_array)	&& !isset($this->installed_admin_modules[$class_name]) && !isset($this->installed_user_modules[$class_name])) {
					return false;
				}
			}
		}
		$class_name_to_load = $this->load_class_file($class_name, $custom_path);
		// Try to create instance of the class
		if ($class_name_to_load) {
			$this->modules[$class_name] = new $class_name_to_load($params);
			// make this usable only for main() to save resources
#			$this->_init_cur_user_info($this->modules[$class_name]);
			$this->_set_module_conf($class_name, $this->modules[$class_name], $params);
		}
		// Return reference to the module object
		if (is_object($this->modules[$class_name])) {
			return $this->modules[$class_name];
		} else {
			return null;
		}
	}

	/**
	*/
	function _preload_plugins_list() {
		if (isset($this->_plugins)) {
			return $this->_plugins;
		}
		$sets = array(
			'project'	=> PROJECT_PATH.'plugins/*/',
			'framework'	=> YF_PATH.'plugins/*/',
		);
		$_plen = strlen(YF_PREFIX);
		$plugins = array();
		$plugins_classes = array();
		foreach ((array)$sets as $set => $pattern) {
			foreach ((array)glob($pattern, GLOB_ONLYDIR|GLOB_NOSORT) as $d) {
				$pname = basename($d);
				$dlen = strlen($d);
				$classes = array();
				foreach (array_merge(glob($d.'*/*.class.php'), glob($d.'*/*/*.class.php')) as $f) {
					$cname = str_replace(YF_CLS_EXT, '', basename($f));
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
		$this->_plugins = $plugins;
		$this->_plugins_classes = $plugins_classes;
		return $this->_plugins;
	}

	/**
	* Load module file
	*/
	function load_class_file($class_name = '', $custom_path = '', $force_storage = '') {
		if (empty($class_name) || $class_name == 'main') {
			return false;
		}
		$cur_hook_prefix = MAIN_TYPE_ADMIN ? YF_ADMIN_CLS_PREFIX : YF_SITE_CLS_PREFIX;
		// By default thinking that class not exists
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
				$fwork_path2		= 'priority2/'. USER_MODULES_DIR;
			} elseif (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH)) {
				$site_path			= $custom_path;
				$site_path_dev		= $dev_path. $custom_path;
				$project_path		= $custom_path;
				$project_path_dev	= $dev_path. $custom_path;
				$fwork_path			= $custom_path;
				$fwork_path2		= 'priority2/'. $custom_path;
			} else {
				$site_path			= $custom_path;
			}
		} elseif (MAIN_TYPE_ADMIN) {
			if (empty($custom_path)) {
				$site_path			= ADMIN_MODULES_DIR;
				$site_path_dev		= $dev_path. ADMIN_MODULES_DIR;
				$project_path		= ADMIN_MODULES_DIR;
				$project_path_dev	= $dev_path. ADMIN_MODULES_DIR;
				$fwork_path			= ADMIN_MODULES_DIR;
				$fwork_path2		= 'priority2/'. ADMIN_MODULES_DIR;
				$project_path2		= USER_MODULES_DIR;
			} elseif (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH) && false === strpos($custom_path, ADMIN_SITE_PATH)) {
				$site_path			= $custom_path;
				$site_path_dev		= $dev_path. $custom_path;
				$project_path		= $custom_path;
				$project_path_dev	= $dev_path. $custom_path;
				$fwork_path			= $custom_path;
				$fwork_path2		= 'priority2/'. $custom_path;
			} else {
				$site_path			= $custom_path;
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
				$storages['dev_site']	= array($SITE_PATH. $site_path_dev);
			}
			$storages['dev_project'] = array(PROJECT_PATH. $project_path_dev);
		}
		if (strlen($SITE_PATH. $site_path) && ($SITE_PATH. $site_path) != (PROJECT_PATH. $project_path)) {
			$storages['site'] 		= array($SITE_PATH. $site_path);
		}
		$storages['site_hook']		= array($SITE_PATH. $site_path, $cur_hook_prefix);
		$storages['project']		= array(PROJECT_PATH. $project_path);
		$storages['framework']		= array(YF_PATH. $fwork_path, YF_PREFIX);
		$storages['framework_p2']	= array(YF_PATH. $fwork_path2, YF_PREFIX);
		if (MAIN_TYPE_ADMIN) {
			$storages['admin_user_project']		= array(PROJECT_PATH. $project_path2);
			$storages['admin_user_framework']	= array(YF_PATH. USER_MODULES_DIR, YF_PREFIX);
		}
		if (isset($yf_plugins[$class_name]) || isset($yf_plugins_classes[$class_name])) {
			if (isset($yf_plugins[$class_name])) {
				$plugin_name = $class_name;
			} else {
				$plugin_name = $yf_plugins_classes[$class_name];
			}
			$plugin_info = $yf_plugins[$plugin_name];
			$plugin_subdir = 'plugins/'.$plugin_name.'/';

			if ($site_path && $site_path != $project_path) {
				$storages['plugins_site']	= array($SITE_PATH. $plugin_subdir. $site_path);
			}
			if (isset($plugin_info['project'])) {
				$storages['plugins_project']	= array(PROJECT_PATH. $plugin_subdir. $project_path);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_project']	= array(PROJECT_PATH. $plugin_subdir. $project_path2);
				}
			} elseif (isset($plugin_info['framework'])) {
				$storages['plugins_framework']	= array(YF_PATH. $plugin_subdir. $fwork_path, YF_PREFIX);
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_admin_user_framework'] = array(YF_PATH. $plugin_subdir. USER_MODULES_DIR, YF_PREFIX);
				}
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
				if ($force_storage == 'framework' && $_storage == 'framework_p2') {
					// Do nothing, need to try to load from framework priority2
				} else {
					continue;
				}
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
				'time'				=> (microtime(true) - $_time_start),
				'trace'				=> $this->trace_string(),
			));
		}
		return $loaded_class_name;
	}

	/**
	* Main $_GET tasks handler
	*/
	function tasks($CHECK_IF_ALLOWED = false) {
		if ($this->CONSOLE_MODE) {
			$this->NO_GRAPHICS = true;
		}
		return $this->init_class('graphics', 'classes/')->tasks($CHECK_IF_ALLOWED);
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
			'[DOMAIN]'	=> defined('CUR_DOMAIN_LONG') ? CUR_DOMAIN_LONG : $this->_server('HTTP_HOST'),
			'[CATEGORY]'=> conf('current_category'),
			'[DEBUG]'	=> intval(DEBUG_MODE),
		);
		return str_replace(array_keys($params), array_values($params), $this->EXEC_CACHE_NAME_TPL);
	}

	/**
	* Try to return class method output
	*/
	function call_class_method ($class_name = '', $custom_path = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '') {
		// Check required params
		if (!strlen($class_name) || !strlen($method_name)) {
			return false;
		}
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
		// Try to get object for the given $class_name
		if ($class_name == 'main') {
			$OBJ = $this;
		} else {
			$OBJ = $this->init_class($class_name, $custom_path, $method_params);
		}
		if (!is_object($OBJ)) {
			if (!$silent) {
				trigger_error('MAIN: module "'.$class_name.'" init failed'. (!empty($tpl_name) ? ' (template "'.$tpl_name.'"'.$this->modules['tpl']->_search_stpl_line($class_name, $method_name, $method_params, $tpl_name).')' : ''), E_USER_WARNING);
			}
			return false;
		}
		// Try to find given class method
		if (!method_exists($OBJ, $method_name)) {
			if (!$silent) {
				trigger_error('MAIN: no method "'.$method_name.'" in module "'.$class_name.'"'. (!empty($tpl_name) ? ' (template "'.$tpl_name.'"'.$this->modules['tpl']->_search_stpl_line($class_name, $method_name, $method_params, $tpl_name).')' : ''), E_USER_WARNING);
			}
			return false;
		}
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($method_params) && strlen($method_params)) {
			$tmp_params		= explode(';', $method_params);
			$method_params	= array();
			// Convert params string into array
			foreach ((array)$tmp_params as $v) {
				$attrib_name = '';
				$attrib_value = '';
				if (false !== strpos($v, '=')) {
					list($attrib_name, $attrib_value) = explode('=', trim($v));
				}
				$method_params[trim($attrib_name)] = trim($attrib_value);
			}
		}
		$result = $OBJ->$method_name($method_params);
		if ($use_cache) {
			$this->modules['cache']->put($cache_name, array($result));
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
		$body = '';
		// Special widgets processing
		$widget_name = false;
		if (substr($method_name, 0, 8) == '_widget_') {
			$widget_name = 'widget_'.conf('language').'_'. $class_name. '_'. substr($method_name, 8);
			// Get widget params
			if ($this->USE_SYSTEM_CACHE) {
				if (!isset($this->widgets_params)) {
					// Available params: allow_cache, cache_ttl, object, action
					$this->widgets_params = $this->get_data('widgets_params');
				}
				$_cur_params = $this->widgets_params[$class_name][$method_name];
			} else {
				$_cur_params = $this->call_class_method($class_name, 'modules/', $method_name, array('describe' => '1'), $use_cache, $cache_ttl, $cache_key_override);
			}
			// First check if widget is for special _GET['object']
			if (isset($_cur_params['object']) && $_cur_params['object'] && !in_array($this->_get('object'), explode(',', $_cur_params['object']))) {
				return false;
			}
			if (isset($_cur_params['action']) && $_cur_params['action'] && !in_array($this->_get('action'), explode(',', $_cur_params['action']))) {
				return false;
			}
		}
		if ($widget_name && $this->USE_SYSTEM_CACHE) {
			$cache_ttl = isset($_cur_params['cache_ttl']) && $_cur_params['cache_ttl'] ? (int)$_cur_params['cache_ttl'] : 0;
			// Check if we allow to cache this widget
			if ( ! (isset($_cur_params['allow_cache']) && !$_cur_params['allow_cache'])) {
				$body = $this->modules['cache']->get($widget_name, $cache_ttl);
				$body = $body[0];
			}
		}
		if (empty($body)) {
			$body = $this->call_class_method($class_name, in_array($class_name, array('graphics')) ? 'classes/' : '', $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
			if ($widget_name && $this->USE_SYSTEM_CACHE) {
				$this->modules['cache']->put($widget_name, $body);
			}
		}
		if (DEBUG_MODE) {
			debug('main_execute_block_time[]', array(
				'class'		=> $class_name,
				'method'	=> $method_name,
				'params'	=> $method_params,
				'tpl_name'	=> $tpl_name,
				'silent'	=> (int)$silent,
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
	function _set_module_conf($module_name = '', &$MODULE_OBJ, $params = '') {
		// Stop here if project config not set or some other things missing
		if (empty($module_name)	|| !is_object($MODULE_OBJ)) {
			return false;
		}
		$module_conf_name = $module_name;
		$project_conf = &$GLOBALS['PROJECT_CONF'];
		// Allow to have separate conf entries for admin or user only modules
		if (isset($project_conf[MAIN_TYPE.':'.$module_name])) {
			$module_conf_name = MAIN_TYPE.':'.$module_name;
		}
		if (isset($project_conf[$module_conf_name])) {
			foreach ((array)$project_conf[$module_conf_name] as $k => $v) {
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
	function get_data ($handler_name = '', $force_ttl = 0, $params = array()) {
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		if (!conf('data_handlers')) {
			$this->_load_data_handlers();
		}
		$data_to_return = null;
		if (empty($handler_name)) {
			return $data_to_return;
		}
		$cache_obj_available = is_object($this->modules['cache']);
		if (!empty($this->USE_SYSTEM_CACHE) && $cache_obj_available) {
			$data_to_return = $this->modules['cache']->get($handler_name, $force_ttl, $params);
		}
		$no_cache = false;
		if (empty($data_to_return) && !is_array($data_to_return)) {
			$locale_handler_name = '';
			if (strpos($handler_name, 'locale:') === 0) {
				$handler_name = substr($handler_name, 7);
				$locale_handler_name = $handler_name.'___'.conf('language');
			}
			$handler_php_source = conf('data_handlers::'.$handler_name);
			if (is_callable($handler_php_source)) {
				$data_to_return = $handler_php_source($handler_name, $params);
			} elseif (is_string($handler_php_source)) {
				$data_to_return = eval( ($locale_handler_name ? '$locale="'.conf('language').'";' : ''). $handler_php_source. '; return isset($data) ? $data : null;' );
			}
			if (!empty($this->USE_SYSTEM_CACHE) && !$no_cache && $cache_obj_available) {
				$this->modules['cache']->put($locale_handler_name ? $locale_handler_name : $handler_name, $data_to_return);
			}
		}
		if (DEBUG_MODE) {
			debug('main_get_data[]', array(
				'name'		=> $handler_name,
				'data'		=> '<pre><small>'._prepare_html(substr(var_export($data_to_return, 1), 0, 1000)).'</small></pre>',
				'params'	=> $params,
				'force_ttl'	=> $force_ttl,
				'time'		=> round(microtime(true) - $time_start, 5),
				'trace'		=> $this->trace_string(),
			));
		}
		return $data_to_return;
	}

	/**
	* Put named data array
	*/
	function put_data ($handler_name = '', $data = array()) {
		if (empty($this->USE_SYSTEM_CACHE)) {
			return false;
		}
		if (!is_object($this->modules['cache'])) {
			return false;
		}
		return $this->modules['cache']->put($handler_name, $data);
	}

	/**
	* Load common data handlers array from file
	*/
	function _load_data_handlers () {
		if (conf('data_handlers')) {
			return false;
		}
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
		if (empty($sites_dir)) {
			$sites_dir = PROJECT_PATH.'sites/';
		}
		if (!file_exists($sites_dir)) {
			return '';
		}
		$sites = $sites1 = $sites2 = array();
		foreach(array_merge(glob($sites_dir.'*', GLOB_ONLYDIR), glob($sites_dir.'.*', GLOB_ONLYDIR)) as $v) {
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
		function _sort_by_length($a, $b) {
			return (strlen($a) < strlen($b)) ? +1 : -1;
		}
		uksort($sites1, _sort_by_length);
		uksort($sites2, _sort_by_length);
		$sites = $sites1 + $sites2;
		$found_site = $this->_find_site_path_best_match($sites, $this->_server('SERVER_ADDR'), $this->_server('SERVER_PORT'), $this->_server('HTTP_HOST'));
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
			$sip[0].'.'.$sip[1].'.'.$sip[2],
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
	function fix_required_constants() {
		// Save current working directory (to restore it later when execute shutdown functions)
		$this->_CWD = getcwd();

		if (!defined('DEBUG_MODE')) {
			define('DEBUG_MODE', false);
		}
		if (DEBUG_MODE) {
			ini_set('display_errors', 'stdout');
		}
		if (!is_null($this->_server('SERVER_ADDR'))) {
			$this->_server('SERVER_ADDR', preg_replace('#[^0-9\.]+#', '', trim( $this->_server('SERVER_ADDR') )));
		}
		if (!is_null($this->_server('SERVER_PORT'))) {
			$this->_server('SERVER_PORT', intval( $this->_server('SERVER_PORT') ));
		}
		if (!is_null($this->_server('HTTP_HOST'))) {
			$this->_server('HTTP_HOST', strtolower(str_replace('..', '.', preg_replace('#[^0-9a-z\-\.]+#', '', trim( $this->_server('HTTP_HOST') )))));
		}
		if (defined('DEV_MODE')) {
			conf('DEV_MODE', DEV_MODE);
		}
		$this->DEV_MODE = conf('DEV_MODE');
		$this->HOSTNAME = php_uname('n');
		// Check if we are running in 'server' or 'cmd line' (or 'cli') mode
		define('IS_CLI', php_sapi_name() == 'cli' || !$this->_server('PHP_SELF') ? 1 : 0);
		// Get server OS
		define('OS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');
		// Check required params
		if (!defined('INCLUDE_PATH')) {
			if ($this->CONSOLE_MODE) {
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
		// Define default framework path
		if (!defined('YF_PATH')) {
			define('YF_PATH', dirname(PROJECT_PATH).'/yf/');
		}
		// Alias
		if (!defined('YF_PATH')) {
			define('YF_PATH', YF_PATH);
		}
		// Set WEB_PATH (if not done yet)
		if (!defined('WEB_PATH'))	{
			$request_uri	= $this->_server('REQUEST_URI');
			$cur_web_path	= '';
			if ($request_uri[strlen($request_uri) - 1] == '/') {
				$cur_web_path	= substr($request_uri, 0, -1);
			} else {
				$cur_web_path	= dirname($request_uri);
			}
			$host = '';
			$conf_domains = conf('DOMAINS');
			if ($this->_server('HTTP_HOST')) {
				$host = $this->_server('HTTP_HOST');
			} elseif (is_array($conf_domains)) {
				$host = (string) current($conf_domains);
			} else {
				$host = '127.0.0.1';
			}
			$this->web_path_was_not_defined = true;
			define('WEB_PATH',
				(($this->_server('HTTPS') || $this->_server('SSL_PROTOCOL')) ? 'https://' : 'http://')
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
		conf('IS_AJAX', (strtolower($this->_server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest' || !empty($_GET['ajax_mode'])) ? 1 : 0);

		define('USER_MODULES_DIR', 'modules/');
		define('ADMIN_MODULES_DIR', 'admin_modules/');
		define('AUTH_MODULES_DIR', 'classes/auth/');
		// Set console-specific options
		if ($this->CONSOLE_MODE) {
			ini_set('memory_limit', -1);
			set_time_limit(0);
			if (version_compare( phpversion(), '5.2.4' ) >= 0) {
				// Send PHP warnings and errors to stderr instead of stdout. This aids in diagnosing problems, while keeping messages out of redirected output.
				if (ini_get('display_errors')) {
					ini_set('display_errors', 'stderr');
				}
			}
			// Parse console options into $_GET array
			foreach ((array)$this->_server('argv') as $v) {
				if (substr($v, 0, 2) != '--') {
					continue;
				}
				$v = substr($v, 2);
				list($_name, $_val) = explode('=', $v);
				$_name	= trim($_name);
				if (strlen($_name)) {
					$this->_get($_name, trim($_val));
				}
			}
		}
		// Filter object and action from $_GET
		if ($this->_get('action') == $this->_get('object')) {
			$this->_get('action', '');
		}
		$this->_get('object', str_replace(array('yf_','-'), array('','_'), preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim( $this->_get('object') )))));
		$this->_get('action', str_replace('-', '_', preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim( $this->_get('action') )))));
		if (!$this->_get('action')) {
			$this->_get('action', defined('DEFAULT_ACTION') ? DEFAULT_ACTION : 'show');
		}
		if ($this->USER_INFO_DYNAMIC) {
			module_conf('user_data', 'MODE', 'DYNAMIC');
		}
	}

	/**
	* Try to set required PHP runtime params
	*/
	function set_required_php_params() {
		error_reporting(DEBUG_MODE ? $this->ERROR_REPORTING_DEBUG : $this->ERROR_REPORTING_PROD);
		// Set path to PEAR
		ini_set('url_rewriter.tags', '');
		ini_set('magic_quotes_runtime',	0);
		ini_set('magic_quotes_sybase', 0);
		// Prepare GPC data. get_magic_quotes_gpc always return 0 starting from PHP 5.4+
		if (get_magic_quotes_gpc()) {
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
		return $this->init_class('graphics', 'classes/')->_send_main_headers($content_length);
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
	* Init user info
	*/
// TODO: make this usable only for main() to save resources
	function _init_cur_user_info(&$OBJ) {
		if (in_array($this->_get('object'), (array)$this->_auto_info_skip_modules)) {
			return false;
		}
		if (in_array(str_replace('yf_', '', get_class($OBJ)), (array)$this->_auto_info_skip_modules)) {
			return false;
		}
		if (MAIN_TYPE_ADMIN) {
			$OBJ->USER_ID		= $this->_get('user_id');
			$OBJ->ADMIN_ID		= (int)$this->_session('admin_id');
			$OBJ->ADMIN_GROUP	= (int)$this->_session('admin_group');
		} elseif (MAIN_TYPE_USER) {
			$OBJ->USER_ID		= (int)$this->_session('user_id');
			$OBJ->USER_GROUP	= (int)$this->_session('user_group');
		}
		// Select user details
		if (isset($OBJ->USER_ID) && !empty($OBJ->USER_ID)) {
			if (!isset($this->_current_user_info)) {
				$this->_current_user_info = user($OBJ->USER_ID);
			}
			$OBJ->_user_info = &$this->_current_user_info;
			if (!$OBJ->USER_GROUP) {
				$OBJ->USER_GROUP = $this->_current_user_info['group'];
			}
		}
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
		// Restore startup working directory
		chdir($this->_CWD);
		// Currently disabled by default
		if ($this->CATCH_FATAL_ERRORS) {
			$error = error_get_last();
			if (in_array($error, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR))) {
				$info = '['.gmdate('Y-m-d H:i:s').'] [SHUTDOWN] file:'.$error['file'].' | ln:'.$error['line'].' | msg:'.$error['message'] .PHP_EOL;
				file_put_contents(PROJECT_PATH. 'fatal_log.txt', $info, FILE_APPEND);
				echo $info;
			}
		}
#		$this->_pack_php_code();
#		foreach ((array)$this->_SHUTDOWN_CODE_ARRAY as $_cur_code) {
#			eval($_cur_code);
#		}
	}

	/**
	* PHP code compression
	*/
	function _pack_php_code () {
		if (!$this->AUTO_PACK_PHP_CODE || MAIN_TYPE_ADMIN) {
			return false;
		}
		$OBJ = $this->init_class('project_packer', 'classes/');
	}

# TODO: in DEBUG_MODE log/cleanup/catch reads/writes to these methods
// TODO: move them into separate class (input ?) and create shortcut ( ex: input()->get() )

	/**
	* Helper to get/set GET vars
	*/
	function _get($key = null, $val = null) {
		if (!is_null($val)) {
			$_GET[$key] = $val;
		}
		return $key === null ? $_GET : $_GET[$key];
	}

	/**
	* Helper to get/set POST vars
	*/
	function _post($key = null, $val = null) {
		if (!is_null($val)) {
			$_POST[$key] = $val;
		}
		return $key === null ? $_POST : $_POST[$key];
	}

	/**
	* Helper to get/set SESSION vars
	*/
	function _session($key = null, $val = null) {
		if (!is_null($val)) {
			$_SESSION[$key] = $val;
		}
		return $key === null ? $_SESSION : $_SESSION[$key];
	}

	/**
	* Helper to get/set SERVER vars
	*/
	function _server($key = null, $val = null) {
		if (!is_null($val)) {
			$_SERVER[$key] = $val;
		}
		return $key === null ? $_SERVER : $_SERVER[$key];
	}

	/**
	* Helper to get/set COOKIE vars
	*/
	function _cookie($key = null, $val = null) {
		if (!is_null($val)) {
# TODO: check and use main() settings for cookies
			setcookie($key, $val);
		}
		return $key === null ? $_COOKIE : $_COOKIE[$key];
	}

	/**
	* Checks whether current page was requested with POST method
	*/
	function is_post() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	*/
	function event_subscribe($name, $func, $params = array()) {
// TODO: events system
	}

	/**
	*/
	function event_fire($name, $extra = array()) {
// TODO: events system
	}

	/**
	*/
	function event_find_hooks() {
// TODO: will search through active modules for _event_hook() methods, where class/method can subscribe to any events
// TODO: events system
	}
}
