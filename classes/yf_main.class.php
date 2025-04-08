<?php

/**
 * Core main class.
 *
 * @author        YFix Team <yfix.dev@gmail.com>
 * @version        1.0
 */
#[AllowDynamicProperties]
class yf_main
{
    /**
     * @var string Type of initialization @conf_skip
     *    - user  (for user section)
     *    - admin (for control panel)
     */
    public $type = 'user';
    /***/
    public $CONSOLE_MODE = false;
    /** @var bool Use database for translation or language files */
    public $LANG_USE_DB = false;
    /** @var bool Sytem tables caching */
    public $USE_SYSTEM_CACHE = false;
    /** @var bool Task manager on/off */
    public $USE_TASK_MANAGER = false;
    /** @var bool Output caching on/off */
    public $OUTPUT_CACHING = false;
    /** @var bool Send no-cache headers */
    public $NO_CACHE_HEADERS = true;
    /** @var bool Strict init modules check (if turned on - then module need to be installed not only found) */
    public $STRICT_MODULES_INIT = false;
    /** @var bool Session custom handler ('db','files','memcached','eaccelerator','apc','xcache' or false for 'none') */
    public $SESSION_CUSTOM_HANDLER = false;
    /** @var string Custom session save dir (leave ampty to skip), example: 'session_data/' */
    public $SESSION_SAVE_DIR = '';
    /** @var int Session life time (in seconds) */
    public $SESSION_LIFE_TIME = 18000; // 5 hours
    /** @var string */
    public $SESSION_DOMAIN = ''; // Default empty, means current domain
    /** @var string */
    public $SESSION_COOKIE_PATH = '/';
    /** @var bool */
    public $SESSION_COOKIE_SECURE = false;
    /** @var bool */
    public $SESSION_COOKIE_HTTPONLY = true;
    /** @var string */
    public $SESSION_REFERER_CHECK = ''; // WEB_PATH
    /** @var string */
    public $SESSION_DESTROY_EXPIRED = false;
    /** @var string Custom session name */
    public $SESSION_USE_UNIQUE_NAME = true;
    /** @var bool Auto-detect spiders */
    public $SPIDERS_DETECTION = false;
    /** @var bool Allow to load source code from db */
    public $ALLOW_SOURCE_FROM_DB = false;
    /** @var bool Allow to use overload protection methods inside user section (we will disable some heavy methods and/or queries) */
    public $OVERLOAD_PROTECTION = false;
    /** @var int Overloading protection turns on (if allowed) when CPU load is higher tha this value */
    public $OVERLOAD_CPU_LOAD = 1;
    /** @var bool Switch standard graphics processing on/off */
    public $NO_GRAPHICS = false;
    /** @var bool Set if no database connection needed */
    public $NO_DB_CONNECT = false;
    /** @var bool Allow fast (but not complete) init */
    public $ALLOW_FAST_INIT = false;
    /** @var bool Allow Geo IP tracking */
    public $USE_GEO_IP = false;
    /** @var bool Allow to use PHPIDS (intrusion detection system) http://php-ids.org/ @experimental */
    public $INTRUSION_DETECTION = false;
    /** @var bool Inline edit locale vars */
    public $INLINE_EDIT_LOCALE = false;
    /** @var bool Hide total ids where possible @experimental */
    public $HIDE_TOTAL_ID = false;
    /** @var bool Static pages as objects routing (eq. for URL like /terms/ instead of /static_pages/show/terms/) */
    public $STATIC_PAGES_ROUTE_TOP = false;
    /** @var string 'Acces denied' redirect url */
    public $REDIR_URL_DENIED = './?object=login_form&go_url=%%object%%;%%action%%%%add_get_vars%%';
    /** @var string 'Not found' redirect url, also supports internal redirect, sample: array('object' => 'help', 'action' => 'show') or array('stpl' => 'my_404_page') */
    public $REDIR_URL_NOT_FOUND = './';
    /** @var bool Use only HTTPS protocol and check if not - the redirect to the HTTPS */
    public $USE_ONLY_HTTPS = false;
    /** @var array List of patterns for https-enabled pages */
    public $HTTPS_ENABLED_FOR = [/* 'object=shop', */];
    /** @var bool Track user last visit */
    public $TRACK_USER_PAGE_VIEWS = false;
    /** @var bool Track online status */
    public $TRACK_ONLINE_STATUS = false;
    /** @var bool Track details (online status=true is needed too) */
    public $TRACK_ONLINE_DETAILS = false;
    /** @var bool Notify module setting */
    public $ENABLE_NOTIFICATIONS_USER = false;
    /** @var bool Notify module setting */
    public $ENABLE_NOTIFICATIONS_ADMIN = false;
    /** @var bool Paid options global switch used by lot of other code @experimental */
    public $ALLOW_PAID_OPTIONS = false;
    /** @var bool Allow cache control from url modifiers */
    public $CACHE_CONTROL_FROM_URL = false;
    /** @var bool Check server health status and return 503 if not OK (great to use with nginx upstream) */
    public $SERVER_HEALTH_CHECK = false;
    /** @var bool Logging of every engine call */
    public $LOG_EXEC = false;
    /** @var int Execute method cache lifetime (in seconds), set to 0 to use cache module default value */
    public $EXEC_CACHE_TTL = 600;
    /** @var string Template for exec cache name */
    public $EXEC_CACHE_NAME_TPL = '[FUNCTION]_[CLASS]_[METHOD]_[LANG]_[DOMAIN]_[CATEGORY]_[DEBUG]';
    /** @var string Path to the server health check result */
    public $SERVER_HEALTH_FILE = '/tmp/isok.txt';
    /** @var string @conf_skip Custom module handler method name */
    public $MODULE_ACTION_HANDLER = '_module_action_handler';
    /** @var string @conf_skip Module (not class) constructor name */
    public $MODULE_CONSTRUCT = '_init';
    /** @var int @conf_skip Current user session info */
    public $USER_ID = 0;
    /** @var int @conf_skip Current user session info */
    public $USER_GROUP = 0;
    /** @var array @conf_skip Current user session info */
    public $USER_INFO = null;
    /** @var array List of objects/actions for which no db connection is required. @example: 'object' => array('action1', 'action2') */
    public $NO_DB_FOR = ['internal' => [], 'dynamic' => ['php_func']];
    /** @var mixed Development mode, enable dev overrides layer, can containg string with developer name */
    public $DEV_MODE = false;
    /** @var string Server host name */
    public $HOSTNAME = '';
    /** @var int @conf_skip Multi-site mode option */
    public $SITE_ID = null;
    /** @var int @conf_skip Multi-server mode option */
    public $SERVER_ID = null;
    /** @var string @conf_skip Multi-server mode option */
    public $SERVER_ROLE = null;
    /** @var bool */
    public $CATCH_FATAL_ERRORS = false;
    /** @var bool */
    public $ALLOW_DEBUG_PROFILING = false;
    /** @var bool @conf_skip */
    public $PROFILING = false;

    public $_current_user_info = null;
    public $_custom_class_storages = [];
    public $_CWD = null;
    public $_data_handlers_loaded = null;
    public $_extend = [];
    public $_getset_cache = [];
    public $_is_mobile = null;
    public $_ORIGINAL_VARS_GET = [];
    public $_ORIGINAL_VARS_SERVER = [];
    public $_paths_replace_pairs = [];
    public $_plugins = null;
    public $_plugins_classes = null;
    public $_server_self_ips = null;
    public $_SHUTDOWN_CODE_ARRAY = [];
    public $_time_start = null;
    public $_timing = [];
    public $_user_info = null;
    public $auth = null;
    public $BLOCKS_TASK_403 = null;
    public $BLOCKS_TASK_404 = null;
    public $cache = null;
    public $common = null;
    public $data_handlers = null;
    public $db = null;
    public $error_handler = null;
    public $events = null;
    public $graphics = null;
    public $installed_admin_modules = null;
    public $installed_user_modules = null;
    public $IS_403 = null;
    public $IS_404 = null;
    public $IS_503 = null;
    public $IS_BANNED = null;
    public $is_console = null;
    public $modules = [];
    public $SERVER_INFO = [];
    public $SESSION_OFF = null;
    public $tpl = null;
    public $web_path_was_not_defined = null;
    public $_unique_widget_ids = null;
    public $_IS_REDIRECTING = null;
    public $USER_ROLE = null;
    public $NO_SIDE_AREA_TOGGLER = null;
    public $AUTO_BAN_CHECKING = null;
    public $ADMIN_ID = null;
    public $_IN_OUTPUT_CACHE = null;
    public $_ARGS_DIRTY = [];

    /**
     * Engine constructor
     * Depends on type that is given to it initialize user section or administrative backend.
     * @param mixed $type
     * @param mixed $no_db_connect
     * @param mixed $auto_init_all
     * @param mixed $_conf
     */
    public function __construct($type = 'user', $no_db_connect = false, $auto_init_all = false, $_conf = [])
    {
        if (! isset($this->_time_start)) {
            $this->_time_start = microtime(true);
        }
        global $CONF;
        // Inject configuration directly, usually inside unit tests
        if ($CONF === null && ! empty($_conf)) {
            $CONF = $_conf;
        }
        if (defined('DEBUG_MODE') && DEBUG_MODE && ($this->ALLOW_DEBUG_PROFILING || ( $CONF['main']['ALLOW_DEBUG_PROFILING'] ?? false))) {
            $this->PROFILING = true;
        }
        if (@$_SERVER['argc'] && ! isset($_SERVER['REQUEST_METHOD'])) {
            $this->CONSOLE_MODE = true;
        }
        // error_reporting(0); // Remove all errors initially

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
            $this->set_module_conf('main', $this); // // Load project config for self
            $conf_tz = conf('timezone'); # 'Europe/Kiev'
            $conf_tz && date_default_timezone_set($conf_tz);
            $this->init_main_functions();
            $this->error_handler = $this->_class('core_errors');
            $this->init_server_health();
            $this->try_fast_init();
            $this->init_modules_base();
            $this->init_events();
            $this->init_cache();
            $this->init_files();
            $this->init_db();
            $this->init_common();
            $this->_class('graphics');
            $this->load_class_file('module', 'classes/');
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
            register_shutdown_function([$this, '_framework_destruct']);
        } catch (Exception $e) {
            $msg = 'MAIN: Caught exception: ' . print_r($e->getMessage(), 1) . PHP_EOL . $e->getTraceAsString();
            trigger_error($msg, E_USER_WARNING);
        }
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     * @return bool|mixed
     */
    public function __call($name, $args)
    {
        return $this->extend_call($this, $name, $args);
    }

    /**
     * Get named data with callback with optional caching.
     * @param mixed $name
     * @param callable $func
     * @param mixed $ttl
     * @param array $params
     */
    public function getset($name, callable $func, $ttl = 0, array $params = [])
    {
        if (! is_string($name) || ! $name) {
            return null;
        }
        if (! is_array($params)) {
            $params = [];
        }
        $refresh = $params['refresh_cache'] ?? false;
        $refresh && $params['no_cache'] = true;

        $enabled = $this->USE_SYSTEM_CACHE && ! ($params['no_cache'] ?? false);
        // speed optimization with 2nd layer of caching
        $memory_enabled = (($params['no_cache'] ?? false) || $refresh || $this->is_console()) ? false : true;
        if ($memory_enabled && isset($this->_getset_cache[$name])) {
            return $this->_getset_cache[$name]['result'];
        }
        if ($enabled || $refresh) {
            $cache = cache();
        }
        $enabled && $result = $cache->get($name, $ttl, $params);
        $need_result = true;
        if ($result) {
            $need_result = false;
        } elseif (is_array($result) || (is_string($result) && ($result === '' || $result === '0' || $result === 'false'))) {
            $need_result = false;
        }
        if ($need_result) {
            $result = $func($name, $ttl, $params);
            if ($enabled || $refresh) {
                $cache->set($name, $result, $ttl);
            }
        }
        if ($memory_enabled || $refresh) {
            $this->_getset_cache[$name]['result'] = $result;
        }
        return $result;
    }

    /**
     * Micro-framework 'fast_init' inside big YF framework. We use it when some actions need to be done at top speed.
     */
    public function try_fast_init()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (! $this->ALLOW_FAST_INIT) {
            return false;
        }
        global $CONF; // Do not remove this, it is needed for extending fast init

        $paths = [
            'app' => APP_PATH . 'share/fast_init.php',
            'yf' => YF_PATH . 'plugins/fast_init/share/fast_init.php',
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                include_once $path;
                return true;
            }
        }
        return false;
    }

    /**
     * Allows to call code here before we begin initializing engine parts.
     */
    public function _before_init_hook()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->NO_GRAPHICS = @$GLOBALS['no_graphics'];
        $GLOBALS['no_graphics'] = &$this->NO_GRAPHICS;
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            ini_set('display_errors', 'on');
        }
    }

    public function _check_site_maintenance()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (MAIN_TYPE_USER && ! $this->is_console() && ! DEBUG_MODE && conf('site_maintenance')) {
            $this->NO_GRAPHICS = true;
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 300');
            echo common()->show_empty_page(tpl()->parse('site_maintenance'));
            exit();
        }
    }

    /**
     * Allows to call code here before we begin with graphics.
     */
    public function _after_init_hook()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.after_init_begin');
        $this->_check_site_maintenance();

        $this->_do_rewrite();

        $this->_init_cur_user_info($this);
        if ($this->TRACK_ONLINE_STATUS) {
            $this->_class('online_users')->process();
        }
        if ($this->type == 'admin' && $this->ENABLE_NOTIFICATIONS_ADMIN) {
            $this->_module('notifications')->_prepare();
        } elseif ($this->type == 'user' && $this->ENABLE_NOTIFICATIONS_USER) {
            $this->_module('notifications')->_prepare();
        }

        if ($this->TRACK_USER_PAGE_VIEWS && $this->USER_ID) {
            $this->_add_shutdown_code(function () {
                if (! main()->NO_GRAPHICS) {
                    db()->update_safe('user', ['last_view' => time(), 'num_views' => ++$this->_user_info['num_views']], $this->USER_ID);
                }
            });
        }
        conf('filter_hidden', ($_COOKIE['filter_hidden'] ?? false) ? 1 : 0);
        conf('qm_hidden', ($_COOKIE['qm_hidden'] ?? false) ? 1 : 0);

        $https_needed = $this->USE_ONLY_HTTPS;
        if (! $https_needed) {
            $query_string = $this->_server('QUERY_STRING');
            foreach ((array) $this->HTTPS_ENABLED_FOR as $item) {
                if (is_callable($item)) {
                    if ($item($query_string)) {
                        $https_needed = true;
                        break;
                    }
                } elseif (preg_match('@' . $item . '@ims', $query_string)) {
                    $https_needed = true;
                    break;
                }
            }
        }
        if ($https_needed && ! $this->is_console() && ! ($this->_server('HTTPS') || $this->_server('SSL_PROTOCOL'))) {
            $redirect_url = str_replace('http://', 'https://', WEB_PATH) . $this->_server('QUERY_STRING');
            return js_redirect(process_url($redirect_url));
        }
        if ($this->INTRUSION_DETECTION) {
            $this->modules['common']->intrusion_detection();
        }
        $this->events->fire('main.after_init');
    }

    /**
     * Url rewriting engine init and apply if rewrite is enabled.
     */
    public function _do_rewrite()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if ($this->is_console() || MAIN_TYPE_ADMIN || ! module_conf('tpl', 'REWRITE_MODE')) {
            return false;
        }
        $this->events->fire('main.before_rewrite');
        $host = $_SERVER['HTTP_HOST'];
        $request_uri = $_SERVER['REQUEST_URI'];
        // Override by WEB_PATH
        if (defined('WEB_PATH') && ! $this->web_path_was_not_defined) {
            $w = parse_url(WEB_PATH);
            $w_host = $w['host'];
            $w_port = $w['port'];
            $w_path = $w['path'];
            $host = $w_host . (strlen($w_port ?? '') > 1 ? ':' . $w_port : '') . (strlen($w_path ?? '') > 1 ? $w_path : '');
            if ($w_path != '/' && strpos($request_uri, $w_path) === 0) {
                $request_uri = substr($request_uri, strlen($w_path));
                $request_uri = '/' . ltrim($request_uri, '/');
            }
        }
        if (isset($_GET['host']) && ! empty($_GET['host'])) {
            $host = $_GET['host'];
        }
        list($u) = explode('?', trim($request_uri, '/'));
        $u_arr = explode('/', preg_replace('/\.htm.*/', '', $u));

        $orig_object = $_GET['object'];
        $orig_action = $_GET['action'];

        unset($_GET['object'], $_GET['action']);

        $class_rewrite = $this->_class('rewrite');
        $arr = $class_rewrite->REWRITE_PATTERNS['yf']->_parse($host, $u_arr, $_GET, '', $class_rewrite);

        foreach ((array) $arr as $k => $v) {
            if ($k != '%redirect_url%') {
                $_GET[$k] = $v;
            }
        }
        foreach ((array) $_GET as $k => $v) {
            if ($v == '') {
                unset($_GET[$k]);
            }
        }
        if (! isset($_GET['action'])) {
            $_GET['action'] = 'show';
        }
        if (! $this->is_console() && ! isset($_SESSION['utm_source'])) {
            $utm_source = $_GET['utm_source'] ?? null;
            $utm_source = $utm_source ?? $_POST['utm_source'] ?? null;
            $utm_source = $utm_source ?? $_COOKIE['utm_source'] ?? null;
            if (! $utm_source && ($_SERVER['HTTP_REFERER'] ?? false)) {
                $cur_domain = trim($_SERVER['HTTP_HOST']);
                $ref_domain = trim(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));
                if ($ref_domain && $ref_domain != $cur_domain) {
                    $utm_source = $ref_domain;
                }
            }
            if ($utm_source) {
                $_SESSION['utm_source'] = trim(preg_replace('~[^a-z0-9\.\/_@-]~ims', '', strtolower(substr($utm_source, 0, 255))));
            }
        }
        $_SERVER['QUERY_STRING'] = http_build_query((array) $_GET);
        $this->events->fire('main.after_rewrite');
    }

    /**
     * conf(), module_conf() wrappers.
     */
    public function init_conf_functions()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $path = dirname(__DIR__) . '/functions/yf_conf.php';
        if (file_exists($path)) {
            $this->include_module($path, 1);
        }
    }

    /**
     * main(), _class(), module(), db(), tpl(), common() wrappers and more.
     */
    public function init_main_functions()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $path = dirname(__DIR__) . '/functions/yf_aliases.php';
        if (file_exists($path)) {
            $this->include_module($path, 1);
        }
    }

    /**
     * Initialization of required files.
     */
    public function init_files()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $include_files = [];
        $required_files = [];
        $this->events && $this->events->fire('main.before_files');
        if ($this->NO_DB_CONNECT == 0) {
            $include_files[] = CONFIG_PATH . 'db_setup.php';
        }
        foreach ((array) conf('include_files::' . MAIN_TYPE) as $path) {
            $include_files[] = $path;
        }
        foreach ((array) conf('required_files::' . MAIN_TYPE) as $path) {
            $required_files[] = $path;
        }
        $funcs_paths = [
            'app' => APP_PATH . 'functions/common_funcs.php',
            'app_old' => APP_PATH . 'share/functions/common_funcs.php',
            'yf' => YF_PATH . 'functions/' . YF_PREFIX . 'common_funcs.php',
        ];
        foreach ($funcs_paths as $path) {
            if (file_exists($path)) {
                $required_files[] = $path;
            }
        }
        foreach ((array) $include_files as $path) {
            $this->include_module($this->_replace_core_paths($path), $_requried = false);
        }
        foreach ((array) $required_files as $path) {
            $this->include_module($this->_replace_core_paths($path), $_requried = true);
        }
    }

    public function init_modules_base()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->modules = [];
    }

    public function init_db()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
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
        if (! isset($GLOBALS['db'])) {
            $this->_class('db');
            $GLOBALS['db'] = &$this->modules['db'];
        } else {
            $this->set_module_conf('db', $this->modules['db']);
        }
        $this->db = &$this->modules['db'];
        $this->events->fire('main.after_db');
    }

    public function init_events()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events = $this->_class('core_events');
        // Load event listeners from supported locations
        $ext = '.listener.php';
        $patterns = [
            'framework' => [
                YF_PATH . 'events/*' . $ext,
                YF_PATH . 'plugins/*/events/*' . $ext,
                YF_PATH . 'share/events/*' . $ext,
                YF_PATH . 'plugins/*/share/events/*' . $ext,
            ],
            'app' => [
                APP_PATH . 'events/*' . $ext,
                APP_PATH . 'plugins/*/events/*' . $ext,
                APP_PATH . 'share/events/*' . $ext,
                APP_PATH . 'plugins/*/share/events/*' . $ext,
            ],
        ];
        $ext_len = strlen($ext);
        $names = [];
        foreach ($patterns as $gname => $paths) {
            foreach ($paths as $path) {
                foreach (glob($path) as $matchedPath) {
                    $name = substr(basename($matchedPath), 0, -$ext_len);
                    $names[$name] = $matchedPath;
                    $locations[$name][$gname] = $matchedPath;
                }
            }
        }
        // This double iterating code allows to inherit/replace listeners with same name in project
        foreach ($names as $name => $path) {
            require_once $path;
        }
    }

    public function init_common()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->common = $this->_class('common');
    }

    public function init_tpl()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.before_tpl');
        $this->tpl = $this->_class('tpl');
        $this->events->fire('main.after_tpl');
    }

    public function init_content()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.before_content');

        $this->tpl->init_graphics();

        $this->is_post() && $this->events->fire('main.on_post');
        $this->is_ajax() && $this->events->fire('main.on_ajax');
        $this->is_console() && $this->events->fire('main.on_console');
        $this->is_redirect() && $this->events->fire('main.on_redirect');

        $this->events->fire('main.after_content');
    }

    public function init_cache()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.before_cache');
        $CACHE_DRIVER = conf('CACHE_DRIVER');
        if ($CACHE_DRIVER) {
            conf('cache::DRIVER', $CACHE_DRIVER);
        }
        $this->cache = $this->_class('cache');
        $this->events->fire('main.after_cache');
    }

    public function init_server_health()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        // Server health result (needed to correctly self turn off faulty box from frontend requests)
        if (! $this->is_console() && $this->SERVER_HEALTH_CHECK && $this->SERVER_HEALTH_FILE && file_exists($this->SERVER_HEALTH_FILE)) {
            $health_result = file_get_contents($this->SERVER_HEALTH_FILE);
            if ($health_result != 'OK') {
                header($this->_server('SERVER_PROTOCOL') . ' 503 Service Unavailable');
                exit();
            }
        }
        // Get current server load value (only for user section)
        if ($this->OVERLOAD_PROTECTION && MAIN_TYPE_USER && ! OS_WINDOWS) {
            $load = sys_getloadavg();
            conf('HIGH_CPU_LOAD', $load[0] > $this->OVERLOAD_CPU_LOAD ? 1 : 0);
        } else {
            conf('HIGH_CPU_LOAD', 0);
        }
    }

    public function spider_detection()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (! $this->SPIDERS_DETECTION) {
            return false;
        }
        $_spider_name = conf('SPIDER_NAME');
        if (isset($_spider_name)) {
            return $_spider_name;
        }
        $SPIDER_NAME = $this->modules['common']->_is_spider($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        if (empty($SPIDER_NAME)) {
            if (preg_match('/(bot|spider|crawler|curl|wget)/ims', $SPIDER_NAME)) {
                $SPIDER_NAME = 'Unknown spider';
            }
        }
        if (! empty($SPIDER_NAME)) {
            conf('IS_SPIDER', true);
            conf('SPIDER_NAME', $SPIDER_NAME);
        }
        return $SPIDER_NAME;
    }

    public function init_session()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.before_session');
        $skip = false;
        if (isset($this->_session_init_complete) || $this->is_console() || conf('SESSION_OFF') || $this->SESSION_OFF) {
            $skip = true;
        } elseif ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
            $skip = true;
        }
        if (! $skip) {
            _class('session')->start();
        }
        $this->events->fire('main.after_session');
    }

    /**
     * Initialization settings stored in the database.
     */
    public function init_settings()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $output_caching = conf('output_caching');
        if (isset($output_caching)) {
            $this->OUTPUT_CACHING = $output_caching;
        }
        $this->events->fire('main.settings');
    }

    /**
     * Try to find current site if not done yet.
     */
    public function init_site_id()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (! conf('SITE_ID')) {
            $site_id = 1;
            foreach ((array) $this->get_data('sites') as $site) {
                if ($site['name'] == $_SERVER['HTTP_HOST']) {
                    $site_id = $site['id'];
                    break;
                }
            }
            conf('SITE_ID', (int) $site_id);
            $this->SITE_ID = (int) $site_id;
        }
        return $this->SITE_ID;
    }

    /**
     * Try to find current server if not done yet.
     */
    public function init_server_id()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $servers = $this->get_data('servers');
        $this->SERVER_ID = 0;
        if (! conf('SERVER_ID') && ($servers || DEBUG_MODE)) {
            $self_ips = explode(' ', exec('hostname --all-ip-addresses'));
            if ($self_ips) {
                $self_ips = array_combine($self_ips, $self_ips);
                $this->_server_self_ips = $self_ips;
            }
            foreach ((array) $servers as $server) {
                if ($server['hostname'] == $this->HOSTNAME) {
                    $this->SERVER_ID = (int) $server['id'];
                    break;
                }
                $server_ips = [];
                if ($self_ips) {
                    foreach (explode(',', str_replace([',', ';', PHP_EOL, "\t", ' '], ',', trim($server['ip']))) as $v) {
                        $v = trim($v);
                        $v && $server_ips[$v] = $v;
                    }
                    if ($server_ips && array_intersect($self_ips, $server_ips)) {
                        $this->SERVER_ID = (int) $server['id'];
                        break;
                    }
                }
            }
        }
        conf('SERVER_ID', (int) $this->SERVER_ID);
        if ($this->SERVER_ID) {
            $this->SERVER_INFO = $servers[$this->SERVER_ID];
        }
        return $this->SERVER_ID;
    }

    /**
     * Try to find current server role if not done yet.
     */
    public function init_server_role()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->SERVER_ROLE = 'default';
        if (! conf('SERVER_ROLE') && ($this->SERVER_INFO['role'] ?? false)) {
            $this->SERVER_ROLE = $this->SERVER_INFO['role'];
            conf('SERVER_ROLE', $this->SERVER_ROLE);
        }
        return $this->SERVER_ROLE;
    }

    /**
     * Starting localization engine.
     */
    public function init_locale()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (($_GET['no_lang'] ?? false) || conf('no_locale')) {
            return false;
        }
        _class('i18n')->init_locale();
    }

    /**
     * Init authentication.
     */
    public function init_auth()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        $this->events->fire('main.before_auth');
        if (defined('SITE_DEFAULT_PAGE')) {
            conf('SITE_DEFAULT_PAGE', SITE_DEFAULT_PAGE);
        }
        if (conf('no_internal_auth')) {
            $def_page = conf('SITE_DEFAULT_PAGE');
            if ($def_page) {
                parse_str(substr($def_page, 3), $_tmp);
                foreach ((array) $_tmp as $k => $v) {
                    $_GET[$k] = $v;
                }
            }
            return false;
        }
        if ($this->SPIDERS_DETECTION && conf('IS_SPIDER')) {
            return false;
        }
        $auth_module_name = 'auth_' . (MAIN_TYPE_ADMIN ? 'admin' : 'user');
        $auth_loaded_module_name = $this->load_class_file($auth_module_name, 'classes/auth/');
        if ($auth_loaded_module_name) {
            $this->auth = new $auth_loaded_module_name();
            $this->set_module_conf($auth_module_name, $this->auth);
            $this->auth->init();
        }
        if (! is_object($this->auth)) {
            return trigger_error('MAIN: Cannot load needed auth module', E_USER_ERROR);
        }
        $this->events->fire('main.after_auth');
    }

    /**
     * Include module file.
     * @param mixed $path_to_module
     * @param mixed $is_required
     */
    public function include_module($path_to_module = '', $is_required = false)
    {
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
                    echo '<b>YF FATAL ERROR</b>: Required file not found: ' . $path_to_module . '<br>\n<pre>' . $this->trace_string() . '</pre>';
                }
                exit();
            }
            // Here we do not want any errors if file is missing
        } elseif ($file_exists) {
            include_once $path_to_module;
        }
        if (DEBUG_MODE) {
            debug('included_files[]', [
                'path' => $path_to_module,
                'exists' => (int) $file_exists,
                'required' => (int) $is_required,
                'size' => $file_exists ? filesize($path_to_module) : '',
                'time' => round(microtime(true) - $_time_start, 5),
                'trace' => $this->trace_string(),
            ]);
        }
    }

    /**
     * Alias.
     * @param mixed $name
     * @param mixed $path
     * @param mixed $params
     * @return bool|null|yf_main
     */
    public function _class($name, $path = 'classes/', $params = '')
    {
        if (! $path) {
            $path = 'classes/';
        }
        return $this->init_class($name, $path, $params);
    }

    /**
     * Alias.
     * @param mixed $name
     * @param mixed $params
     * @return bool|null|yf_main
     */
    public function _module($name, $params = '')
    {
        return $this->init_class($name, '', $params);
    }

    /**
     * Module(class) loader, based on singleton pattern
     * Initialize new class object or return reference to existing one.
     * @param mixed $class_name
     * @param mixed $custom_path
     * @param mixed $params
     * @return bool|null|yf_main
     */
    public function init_class($class_name, $custom_path = '', $params = '')
    {
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
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        // Strict installed modules check (currently only for user modules)
        if ($this->STRICT_MODULES_INIT && empty($custom_path)) {
            if (! isset($this->installed_user_modules)) {
                $this->installed_user_modules = $this->get_data('user_modules');
            }
            if (MAIN_TYPE_USER) {
                $skip_array = [
                    'rewrite',
                ];
                if (! in_array($class_name, $skip_array) && ! isset($this->installed_user_modules[$class_name])) {
                    return false;
                }
            } elseif (MAIN_TYPE_ADMIN) {
                if (! isset($this->installed_admin_modules)) {
                    $this->installed_admin_modules = $this->get_data('admin_modules');
                }
                $skip_array = [];
                if (! in_array($class_name, $skip_array) && ! isset($this->installed_admin_modules[$class_name]) && ! isset($this->installed_user_modules[$class_name])) {
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
        }
        return null;
    }

    /**
     * @param mixed $force
     * @return array
     */
    public function _preload_plugins_list($force = false)
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (isset($this->_plugins) && ! $force) {
            return $this->_plugins;
        }
        $white_list = $this->_plugins_white_list ?? [];
        $black_list = $this->_plugins_black_list ?? [];
        // Order matters for plugins_classes !!
        $sets = [
            'framework' => YF_PATH . 'plugins/*/',
            'app' => APP_PATH . 'plugins/*/',
        ];
        $_plen = strlen(YF_PREFIX);
        $plugins = [];
        $plugins_classes = [];
        $ext = YF_CLS_EXT; // default is .class.php
        foreach ((array) $sets as $set => $pattern) {
            foreach ((array) glob($pattern, GLOB_ONLYDIR | GLOB_NOSORT) as $d) {
                $pname = basename($d);
                if ($white_list && wildcard_compare($white_list, $pname)) {
                    // result is good, do not check black list if name found here, inside white list
                } elseif ($black_list && wildcard_compare($black_list, $pname)) {
                    // Do not load files from this plugin
                    break;
                }
                $dlen = strlen($d);
                $classes = [];
                $patterns = [
                    $d . '*' . $ext,
                    $d . '*/' . '*' . $ext,
                    $d . '*/*/' . '*' . $ext
                ];
                $classes = [];
                foreach ($patterns as $pattern) {
                    foreach (glob($pattern) as $f) {
                        $cname = str_replace($ext, '', basename($f));
                        $cdir = dirname(substr($f, $dlen)) . '/';
                        if (strpos($cname, YF_PREFIX) === 0) {
                            $cname = substr($cname, $_plen);
                        }
                        $classes[$cname][$cdir] = $f;
                        $plugins_classes[$cname] = $pname;
                    }
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
     * @param mixed $class_name
     * @param mixed $custom_path
     * @param mixed $force_storage
     * @return bool
     */
    public function _class_exists($class_name = '', $custom_path = '', $force_storage = '')
    {
        $loaded = $this->load_class_file($class_name, $custom_path, $force_storage);
        return (bool) $loaded;
    }

    /**
     * Load module file.
     * @param mixed $class_name
     * @param mixed $custom_path
     * @param mixed $force_storage
     * @return bool|mixed|string
     */
    public function load_class_file($class_name = '', $custom_path = '', $force_storage = '')
    {
        if (empty($class_name) || $class_name == 'main') {
            return false;
        }
        $cur_hook_prefix = MAIN_TYPE_ADMIN ? YF_ADMIN_CLS_PREFIX : YF_SITE_CLS_PREFIX;
        $loaded_class_name = false;
        // Site loaded class have top priority
        $site_class_name = $cur_hook_prefix . $class_name;
        if (class_exists($site_class_name)) {
            return $site_class_name;
        }
        if (class_exists($class_name)) {
            return $class_name;
        }
        if (class_exists($cur_hook_prefix . $class_name)) {
            return $cur_hook_prefix . $class_name;
        }
        if (class_exists(YF_PREFIX . $class_name)) {
            return YF_PREFIX . $class_name;
        }
        if (strpos($class_name, YF_PREFIX) === 0) {
            $class_name = substr($class_name, strlen(YF_PREFIX));
        }
        if (DEBUG_MODE) {
            $_time_start = microtime(true);
        }
        $class_file = $class_name . YF_CLS_EXT;
        // Developer part of path is related to hostname to be able to make different code overrides for each
        $dev_path = '.dev/' . $this->HOSTNAME . '/';
        // additional path variables
        $project_path2 = null;
        $SITE_PATH = MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH;
        if (MAIN_TYPE_USER) {
            if (empty($custom_path)) {
                $site_path = USER_MODULES_DIR;
                $site_path_dev = $dev_path . USER_MODULES_DIR;
                $project_path = USER_MODULES_DIR;
                $project_path_dev = $dev_path . USER_MODULES_DIR;
                $fwork_path = USER_MODULES_DIR;
            } else {
                if (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH)) {
                    $site_path = $custom_path;
                    $site_path_dev = $dev_path . $custom_path;
                    $project_path = $custom_path;
                    $project_path_dev = $dev_path . $custom_path;
                    $fwork_path = $custom_path;
                } else {
                    $site_path = $custom_path;
                }
            }
        } elseif (MAIN_TYPE_ADMIN) {
            if (empty($custom_path)) {
                $site_path = ADMIN_MODULES_DIR;
                $site_path_dev = $dev_path . ADMIN_MODULES_DIR;
                $project_path = ADMIN_MODULES_DIR;
                $project_path_dev = $dev_path . ADMIN_MODULES_DIR;
                $fwork_path = ADMIN_MODULES_DIR;
                $project_path2 = USER_MODULES_DIR;
            } else {
                if (false === strpos($custom_path, SITE_PATH) && false === strpos($custom_path, PROJECT_PATH) && false === strpos($custom_path, ADMIN_SITE_PATH)) {
                    $site_path = $custom_path;
                    $site_path_dev = $dev_path . $custom_path;
                    $project_path = $custom_path;
                    $project_path_dev = $dev_path . $custom_path;
                    $fwork_path = $custom_path;
                } else {
                    $site_path = $custom_path;
                }
            }
        }
        if (! isset($this->_plugins)) {
            $this->_preload_plugins_list();
        }
        $yf_plugins = &$this->_plugins;
        $yf_plugins_classes = &$this->_plugins_classes;

        // Order of storages matters a lot!
        $storages = [];
        if (conf('DEV_MODE')) {
            if ($site_path_dev && $site_path_dev != $project_path_dev) {
                $storages['dev_site'] = [$SITE_PATH . $site_path_dev];
            }
            $storages['dev_app'] = [APP_PATH . $project_path_dev];
            $storages['dev_project'] = [PROJECT_PATH . $project_path_dev];
        }
        if (strlen($site_path)) {
            $def_path = 'classes/';
            if (strpos($site_path, $def_path) !== 0) {
                if (strlen(YF_PATH) > 3 && strpos($site_path, YF_PATH) === 0) {
                    $storages['site'] = [$site_path];
                } elseif (strlen(APP_PATH) > 3 && strpos($site_path, APP_PATH) === 0) {
                    $storages['site'] = [$site_path];
                } elseif (strlen(PROJECT_PATH) > 3 && strpos($site_path, PROJECT_PATH) === 0) {
                    $storages['site'] = [$site_path];
                }
            }
            if (! isset($storages['site']) && strlen($SITE_PATH . $site_path) && ($SITE_PATH . $site_path) != (PROJECT_PATH . $project_path)) {
                $storages['site'] = [$SITE_PATH . $site_path];
            }
        }
        $storages['app_site_hook'] = [APP_PATH . $site_path, $cur_hook_prefix];
        $storages['app'] = [APP_PATH . $project_path];
        $storages['project_site_hook'] = [$SITE_PATH . $site_path, $cur_hook_prefix];
        $storages['project'] = [PROJECT_PATH . $project_path];
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
            $plugin_subdir = 'plugins/' . $plugin_name . '/';

            if ($site_path && $site_path != $project_path) {
                $storages['plugins_site'] = [$SITE_PATH . $plugin_subdir . $site_path];
            }
            if (isset($plugin_info['app'])) {
                $storages['plugins_app'] = [APP_PATH . $plugin_subdir . $project_path];
                if (MAIN_TYPE_ADMIN) {
                    $storages['plugins_admin_user_app'] = [APP_PATH . $plugin_subdir . $project_path2];
                }
            } elseif (isset($plugin_info['project'])) {
                $storages['plugins_project'] = [PROJECT_PATH . $plugin_subdir . $project_path];
                if (MAIN_TYPE_ADMIN) {
                    $storages['plugins_admin_user_project'] = [PROJECT_PATH . $plugin_subdir . $project_path2];
                }
            }
        }
        $storages['framework'] = [YF_PATH . $fwork_path, YF_PREFIX];
        if ($plugin_name) {
            if (isset($plugin_info['framework'])) {
                $storages['plugins_framework'] = [YF_PATH . $plugin_subdir . $fwork_path, YF_PREFIX];
                if (MAIN_TYPE_ADMIN) {
                    $storages['plugins_admin_user_framework'] = [YF_PATH . $plugin_subdir . USER_MODULES_DIR, YF_PREFIX];
                }
            }
        }
        if (MAIN_TYPE_ADMIN) {
            $storages['admin_user_app'] = [APP_PATH . $project_path2];
            $storages['admin_user_project'] = [PROJECT_PATH . $project_path2];
            $storages['admin_user_framework'] = [YF_PATH . USER_MODULES_DIR, YF_PREFIX];
        }
        // Extending storages on-the-fly. Examples:
        // main()->_custom_class_storages = array(
        //     'film_model' => array('unit_tests' => array(__DIR__.'/model/other_fixtures/')),
        //     '*_model' => array('unit_tests' => array(__DIR__.'/model/fixtures/')),
        // );
        // $film_model = _class('film_model');
        foreach ((array) $this->_custom_class_storages as $_class_name => $_storages) {
            // Have support for wildcards: * ? [abc]
            if (! fnmatch($_class_name, $class_name)) {
                continue;
            }
            foreach ((array) $_storages as $sname => $sinfo) {
                $storages[$sname] = $sinfo;
            }
        }
        $storage = '';
        $loaded_path = '';
        foreach ((array) $storages as $_storage => $v) {
            $_path = (string) $v[0];
            $_prefix = strval($v[1] ?? '');
            if (empty($_path)) {
                continue;
            }
            if ($force_storage && $force_storage != $_storage) {
                continue;
            }
            $this->include_module($_path . $_prefix . $class_file);
            if (class_exists($_prefix . $class_name)) {
                $loaded_class_name = $_prefix . $class_name;
                $storage = $_storage;
                $loaded_path = $_path . $_prefix . $class_file;
                break;
            }
        }
        // Try to load classes from db
        if (empty($loaded_class_name) && $this->ALLOW_SOURCE_FROM_DB && is_object($this->db)) {
            $result_from_db = $this->db->query_fetch('SELECT * FROM ' . db('code_source') . ' WHERE keyword="' . _es($class_name) . '"');
            if (! empty($result_from_db)) {
                eval($result_from_db['source']);
            }
            if (class_exists($class_name)) {
                $loaded_class_name = $class_name;
                $storage = 'db';
            }
        }
        if (DEBUG_MODE) {
            debug('main_load_class[]', [
                'class_name' => $class_name,
                'loaded_class_name' => $loaded_class_name,
                'loaded_path' => $loaded_path,
                'storage' => $storage,
                'storages' => $storages,
                'time' => microtime(true) - $_time_start,
                'trace' => $this->trace_string(),
            ]);
        }
        return $loaded_class_name;
    }

    /**
     * Main $_GET tasks handler.
     * @param mixed $allowed_check
     * @return
     */
    public function tasks($allowed_check = false)
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        return $this->_class('core_blocks')->tasks($allowed_check);
    }

    /**
     * Prepare name for call_class_method cache.
     * @param mixed $class_name
     * @param mixed $custom_path
     * @param mixed $method_name
     * @param mixed $method_params
     * @param mixed $tpl_name
     * @return mixed
     */
    public function _get_exec_cache_name($class_name = '', $custom_path = '', $method_name = '', $method_params = '', $tpl_name = '')
    {
        $params = [
            '[FUNCTION]' => 'call_class_method',
            '[CLASS]' => $class_name,
            '[METHOD]' => $method_name,
            '[LANG]' => defined('DEFAULT_LANG') ? DEFAULT_LANG : conf('language'),
            '[DOMAIN]' => defined('CUR_DOMAIN_LONG') ? constant('CUR_DOMAIN_LONG') : $_SERVER['HTTP_HOST'],
            '[CATEGORY]' => conf('current_category'),
            '[DEBUG]' => (int) DEBUG_MODE,
        ];
        return str_replace(array_keys($params), array_values($params), $this->EXEC_CACHE_NAME_TPL);
    }

    /**
     * Try to return class method output.
     * @param mixed $class_name
     * @param mixed $custom_path
     * @param mixed $method_name
     * @param mixed $method_params
     * @param mixed $tpl_name
     * @param mixed $silent
     * @param mixed $use_cache
     * @param mixed $cache_ttl
     * @param mixed $cache_key_override
     * @return bool
     */
    public function call_class_method($class_name = '', $custom_path = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '')
    {
        if (! strlen($class_name) || ! strlen($method_name)) {
            return false;
        }
        $class_name === '@object' && $class_name = $_GET['object'];
        $method_name === '@action' && $method_name = $_GET['action'];
        if (! $this->USE_SYSTEM_CACHE) {
            $use_cache = false;
        }
        if ($use_cache) {
            $cache_name = $this->_get_exec_cache_name($class_name, $custom_path, $method_name, $method_params, $tpl_name);
            $cache_ttl = (int) $cache_ttl;
            if ($cache_ttl < 1) {
                // set to 0 to use cache module default value
                $cache_ttl = $this->EXEC_CACHE_TTL;
            }
            $cached = $this->modules['cache']->get($cache_name, $cache_ttl);
            if (! empty($cached)) {
                return $cached[0];
            }
        }
        if ($class_name == 'main') {
            $obj = $this;
        } else {
            $obj = $this->init_class($class_name, $custom_path, $method_params);
            if (! is_object($obj) && ! $custom_path) {
                $custom_path = 'classes/';
                $obj = $this->init_class($class_name, $custom_path, $method_params);
            }
        }
        if (! is_object($obj)) {
            if (! $silent) {
                trigger_error('MAIN: module "' . $class_name . '" init failed' . (! empty($tpl_name) ? ' (template "' . $tpl_name . '")' : ''), E_USER_WARNING);
            }
            return false;
        }
        if (! method_exists($obj, $method_name)) {
            if (! $silent) {
                trigger_error('MAIN: no method "' . $method_name . '" in module "' . $class_name . '"' . (! empty($tpl_name) ? ' (template "' . $tpl_name . '")' : ''), E_USER_WARNING);
            }
            return false;
        }
        // Try to process method params (string like attrib1=value1;attrib2=value2)
        if (is_string($method_params) && strlen($method_params)) {
            $method_params = (array) _attrs_string2array($method_params);
        }
        $result = $obj->$method_name($method_params);
        if ($use_cache) {
            $this->modules['cache']->set($cache_name, [$result]);
        }
        return $result;
    }

    /**
     * Try to return class method output (usually from templates).
     * @param mixed $class_name
     * @param mixed $method_name
     * @param mixed $method_params
     * @param mixed $tpl_name
     * @param mixed $silent
     * @param mixed $use_cache
     * @param mixed $cache_ttl
     * @param mixed $cache_key_override
     * @return bool|string
     */
    public function _execute($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '')
    {
        if (DEBUG_MODE) {
            $_time_start = microtime(true);
        }
        $body = $this->call_class_method($class_name, '', $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
        if (! $body) {
            $body = '';
        }
        $this->events->fire('main.execute', [
            'body' => &$body,
            'args' => func_get_args(),
        ]);
        if (DEBUG_MODE) {
            debug('main_execute_block_time[]', [
                'class' => $class_name,
                'method' => $method_name,
                'params' => $method_params,
                'tpl_name' => $tpl_name,
                'silent' => (int) $silent,
                'size' => strlen(is_array($body) ? implode($body) : $body),
                'time' => round(microtime(true) - $_time_start, 5),
                'trace' => $this->trace_string(),
            ]);
        }
        return $body;
    }

    /**
     * Alias for '_execute'.
     * @param mixed $class_name
     * @param mixed $method_name
     * @param mixed $method_params
     * @param mixed $tpl_name
     * @param mixed $silent
     * @param mixed $use_cache
     * @param mixed $cache_ttl
     * @param mixed $cache_key_override
     * @return bool|string
     */
    public function execute($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = false, $cache_ttl = -1, $cache_key_override = '')
    {
        return $this->_execute($class_name, $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
    }

    /**
     * Alias for '_execute'.
     * @param mixed $class_name
     * @param mixed $method_name
     * @param mixed $method_params
     * @param mixed $tpl_name
     * @param mixed $silent
     * @param mixed $use_cache
     * @param mixed $cache_ttl
     * @param mixed $cache_key_override
     * @return bool|string
     */
    public function exec_cached($class_name = '', $method_name = '', $method_params = '', $tpl_name = '', $silent = false, $use_cache = true, $cache_ttl = -1, $cache_key_override = '')
    {
        return $this->_execute($class_name, $method_name, $method_params, $tpl_name, $silent, $use_cache, $cache_ttl, $cache_key_override);
    }

    /**
     * Set module properties from project conf array.
     * @param mixed $module_name
     * @param $MODULE_OBJ
     * @param mixed $params
     * @return bool
     */
    public function set_module_conf($module_name = '', &$MODULE_OBJ = null, $params = '')
    {
        // Stop here if project config not set or some other things missing
        if (empty($module_name) || ! is_object($MODULE_OBJ)) {
            return false;
        }
        global $PROJECT_CONF, $CONF;
        $module_conf_name = $module_name;
        // Allow to have separate conf entries for admin or user only modules
        if (isset($PROJECT_CONF[MAIN_TYPE . ':' . $module_name])) {
            $module_conf_name = MAIN_TYPE . ':' . $module_name;
        }
        if (isset($PROJECT_CONF[$module_conf_name])) {
            foreach ((array) $PROJECT_CONF[$module_conf_name] as $k => $v) {
                $MODULE_OBJ->$k = $v;
            }
        }
        // Override PROJECT_CONF with specially set CONF (from web admin panel, as example)
        if (isset($CONF[$module_conf_name]) && is_array($CONF[$module_conf_name])) {
            foreach ((array) $CONF[$module_conf_name] as $k => $v) {
                $MODULE_OBJ->$k = $v;
            }
        }
        // Implementation of hook 'init'
        if (method_exists($MODULE_OBJ, $this->MODULE_CONSTRUCT)) {
            $MODULE_OBJ->{$this->MODULE_CONSTRUCT}($params);
        }
        return true;
    }

    /**
     * Get named data array.
     * @param mixed $name
     * @param mixed $force_ttl
     * @param mixed $params
     */
    public function get_data($name = '', $force_ttl = 0, $params = [])
    {
        DEBUG_MODE && $time_start = microtime(true);
        if (empty($name)) {
            return null;
        }
        $cache_name = MAIN_TYPE . ':' . $name;
        // Example: geo_regions, ["country" => "UA", "lang" => "ru"] will be saved as geo_regions:country_UA:lang_ru
        if (! empty($params) && is_array($params)) {
            foreach ((array) $params as $k => $v) {
                strlen($k) && strlen($v) && $cache_name .= ':' . $k . '_' . $v;
            }
        }
        if (is_object($this->db) && ! $this->db->_connected) {
            //			$params['no_cache'] = true;
        }
        $data = $this->getset('get_data:' . $cache_name, function () use ($name, $params) {
            ! $this->_data_handlers_loaded && $this->_load_data_handlers();
            if (! isset($this->data_handlers[$name])) {
                return [];
            }
            $handler = $this->data_handlers[$name];
            if (is_string($handler)) {
                $data = include $handler;
                if (is_callable($data)) {
                    $data = $data($params);
                }
            } elseif (is_callable($handler)) {
                $data = $handler($params);
            }
            return $data ?: [];
        }, $force_ttl, $params);

        if (DEBUG_MODE) {
            debug('main_get_data[]', [
                'name' => $name,
                'cache_name' => $cache_name,
                'data' => $data,
                'params' => $params,
                'force_ttl' => $force_ttl,
                'time' => round(microtime(true) - $time_start, 5),
                'trace' => $this->trace_string(),
            ]);
        }
        return $data;
    }

    /**
     * Put named data array.
     * @param mixed $name
     * @param mixed $data
     * @return bool
     */
    public function put_data($name = '', $data = [])
    {
        if (empty($this->USE_SYSTEM_CACHE)) {
            return false;
        }
        if (! is_object($this->modules['cache'])) {
            return false;
        }
        return $this->modules['cache']->set($name, $data);
    }

    /**
     * Load common data handlers array from file.
     */
    public function _load_data_handlers()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if ($this->_data_handlers_loaded) {
            return false;
        }
        $this->data_handlers = [];
        $handlers = [];
        $this->events && $this->events->fire('main.load_data_handlers');

        $suffix = '.php';
        $patterns = [
            'framework' => [
                YF_PATH . 'data_handlers/*' . $suffix,
                YF_PATH . 'plugins/*/data_handlers/*' . $suffix,
                YF_PATH . 'share/data_handlers/*' . $suffix,
                YF_PATH . 'plugins/*/share/data_handlers/*' . $suffix,
            ],
            'app' => [
                APP_PATH . 'data_handlers/*' . $suffix,
                APP_PATH . 'plugins/*/data_handlers/*' . $suffix,
                APP_PATH . 'share/data_handlers/*' . $suffix,
                APP_PATH . 'plugins/*/share/data_handlers/*' . $suffix,
            ],
        ];
        $strlen_suffix = strlen($suffix);
        $handlers = [];
        foreach ($patterns as $gname => $paths) {
            foreach ($paths as $path) {
                foreach (glob($path) as $matchedPath) {
                    $name = substr(basename($matchedPath), 0, -$strlen_suffix);
                    $handlers[$name] = $matchedPath;
                }
            }
        }
        $aliases = [
            'category_sets' => 'cats_blocks',
            'sys_sites' => 'sites',
            'sys_servers' => 'servers',
        ];
        foreach ((array) $aliases as $from => $to) {
            $handlers[$from] = $handlers[$to];
        }
        $this->data_handlers = $handlers;

        $this->_data_handlers_loaded = true;
        return $this->data_handlers;
    }

    /**
     * Simple trace without dumping whole objects.
     */
    public function trace()
    {
        $trace = [];
        foreach (debug_backtrace() as $k => $v) {
            if (! $k) {
                continue;
            }
            $v['object'] = isset($v['object']) && is_object($v['object']) ? get_class($v['object']) : null;
            $trace[$k - 1] = $v;
        }
        return $trace;
    }

    /**
     * Print nice.
     */
    public function trace_string()
    {
        $e = new Exception();
        return implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 1, -1));
    }

    /**
     * Search for sites configuration overrides (in subfolder ./sites/).
     * @param mixed $sites_dir
     * @return array
     */
    public function _find_site($sites_dir = '')
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        if (! $sites_dir) {
            $try_paths = [
                APP_PATH . 'sites/',
                PROJECT_PATH . 'sites/',
            ];
            $sites_dir = '';
            foreach ($try_paths as $try_path) {
                if (file_exists($try_path)) {
                    $sites_dir = $try_path;
                    break;
                }
            }
        }
        if (! $sites_dir) {
            return [];
        }
        // Array of sites passed here
        if (is_array($sites_dir)) {
            $dirs = $sites_dir;
        } else {
            if (!file_exists($sites_dir)) {
                return [];
            }
            $dirs = array_merge(
                glob($sites_dir . '*', GLOB_ONLYDIR),
                glob($sites_dir . '.*', GLOB_ONLYDIR)
            );
        }
        $sites = $sites1 = $sites2 = [];
        foreach ((array) $dirs as $v) {
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
        $sort_by_length = function ($a, $b) {
            return (strlen($a) < strlen($b)) ? +1 : -1;
        };
        uksort($sites1, $sort_by_length);
        uksort($sites2, $sort_by_length);
        $sites = $sites1 + $sites2;
        $found_site = $this->_find_site_path_best_match($sites, $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], $_SERVER['HTTP_HOST']);
        return [$found_site, $sites_dir];
    }

    /**
     * Trying to find site matching current environment
     * Examples: 127.0.0.1  192.168.  192.168.1.5  :443  :81  example.com  .example.com  .dev  .example.dev  .example.dev:443  .example.dev:81
     *     subdomain. subdomain.:443 sub1.sub2. sub1.sub2.:443.
     * @param mixed $sites
     * @param mixed $server_ip
     * @param mixed $server_port
     * @param mixed $server_host
     * @return string
     */
    public function _find_site_path_best_match($sites, $server_ip, $server_port, $server_host)
    {
        $sip = explode('.', $server_ip);
        $sh = array_reverse(explode('.', $server_host));
        $sh2 = explode('.', $server_host);
        $variants = [
            $server_ip . ':' . $server_port,
            $server_ip,
            $sip[0] . '.' . $sip[1] . '.' . $sip[2] . '.:' . $server_port,
            $sip[0] . '.' . $sip[1] . '.' . $sip[2] . '.',
            $sip[0] . '.' . $sip[1] . '.:' . $server_port,
            $sip[0] . '.' . $sip[1] . '.',
            $sip[0] . '.:' . $server_port,
            $sip[0] . '.',
            $server_host . ':' . $server_port,
            $server_host,
            '.' . $sh[0] . ':' . $server_port,
            '.' . $sh[0],
            '.' . $sh[1] . '.' . $sh[0] . ':' . $server_port,
            '.' . $sh[1] . '.' . $sh[0],
            $sh2[0] . '.' . $sh2[1] . '.:' . $server_port,
            $sh2[0] . '.' . $sh2[1] . '.',
            $sh2[0] . '.:' . $server_port,
            $sh2[0] . '.',
            ':' . $server_port,
        ];
        foreach (array_intersect($sites, $variants) as $sname) {
            return $sname;
        }
        return ''; // Found nothing
    }

    /**
     * Check and try to fix required constants.
     */
    public function init_constants()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        // Save current working directory (to restore it later when execute shutdown functions)
        $this->_CWD = getcwd();

        $this->_ORIGINAL_VARS_GET = $_GET;
        $this->_ORIGINAL_VARS_SERVER = $_SERVER;

        ! defined('DEBUG_MODE') && define('DEBUG_MODE', false);
        if (DEBUG_MODE) {
            ini_set('display_errors', 'stdout');
        }
        if (@$_SERVER['SERVER_ADDR'] !== null) {
            $_SERVER['SERVER_ADDR'] = preg_replace('#[^0-9\.]+#', '', trim($_SERVER['SERVER_ADDR']));
        }
        if (@$_SERVER['SERVER_PORT'] !== null) {
            $_SERVER['SERVER_PORT'] = (int) $_SERVER['SERVER_PORT'];
        }
        if (@$_SERVER['HTTP_HOST'] !== null) {
            if (false !== ($pos = strpos($_SERVER['HTTP_HOST'], ':'))) {
                $_SERVER['HTTP_HOST'] = substr($_SERVER['HTTP_HOST'], 0, $pos);
            }
            $_SERVER['HTTP_HOST'] = strtolower(str_replace('..', '.', preg_replace('#[^0-9a-z\-\.]+#', '', trim($_SERVER['HTTP_HOST']))));
        }
        if (@$_SERVER['REQUEST_URI'] !== null) {
            // Possible bug when apache sends full url into request_uri, like this: "http://test.dev/" instead of "/"
            $p = parse_url($_SERVER['REQUEST_URI']);
            if (isset($p['scheme']) || isset($p['host'])) {
                $_SERVER['REQUEST_URI'] = ($p['path'] ?: '/') . ($p['query'] ? '?' . $p['query'] : '');
                if ($_SERVER['QUERY_STRING'] != $p['query']) {
                    $_SERVER['QUERY_STRING'] = $p['query'];
                    parse_str($p['query'], $_get);
                    foreach ((array) $_get as $k => $v) {
                        $_GET[$k] = $v;
                    }
                }
            }
        }
        defined('DEV_MODE') && conf('DEV_MODE', constant('DEV_MODE'));
        $this->DEV_MODE = conf('DEV_MODE');

        define('OS_WINDOWS', strpos(PHP_OS, 'WIN') === 0);

        $this->HOSTNAME = php_uname('n');

        if (! defined('INCLUDE_PATH')) {
            if ($this->is_console()) {
                $_trace = debug_backtrace();
                $_trace = $_trace[1];
                $_path = dirname($_trace['file']);
                define('INCLUDE_PATH', (MAIN_TYPE_ADMIN ? dirname($_path) : $_path) . '/');
            } else {
                $cur_script_path = dirname(realpath(getenv('SCRIPT_FILENAME')));
                define('INCLUDE_PATH', str_replace(['\\', '//'], ['/', '/'], (MAIN_TYPE_ADMIN ? dirname($cur_script_path) : $cur_script_path) . '/'));
            }
        }
        // Alias
        define('PROJECT_PATH', INCLUDE_PATH);
        // Framework root filesystem path
        ! defined('YF_PATH') && define('YF_PATH', dirname(PROJECT_PATH) . '/yf/');
        // Project-level application path, where will be other important subfolders like: APP_PATH.'www/', APP_PATH.'docs/', APP_PATH.'tests/',
        ! defined('APP_PATH') && define('APP_PATH', dirname(PROJECT_PATH) . '/');
        // Filesystem path for configuration files, including db_setup.php and so on
        ! defined('CONFIG_PATH') && define('CONFIG_PATH', APP_PATH . 'config/');
        // Filesystem path for various storage needs: logs, sessions, other files that should not be accessible from WEB
        ! defined('STORAGE_PATH') && define('STORAGE_PATH', APP_PATH . 'storage/');
        // Filesystem path to logs, usually should be at least one level up from WEB_PATH to be not accessible from web server
        ! defined('LOGS_PATH') && define('LOGS_PATH', STORAGE_PATH . 'logs/');
        // Uploads path should be used for various uploaded content accessible from WEB_PATH
        ! defined('UPLOADS_PATH') && define('UPLOADS_PATH', PROJECT_PATH . 'uploads/');
        // Website inside project FS base path. Recommended to use from now instead of REAL_PATH
        if (! defined('SITE_PATH')) {
            $_site_tmp = $this->_find_site();
            $found_site = $_site_tmp[0] ?? '';
            $found_dir = $_site_tmp[1] ?? '';
            define('SITE_PATH', $found_site ? $found_dir . $found_site . '/' : PROJECT_PATH);
        }
        // Alias of SITE_PATH. Compatibility with old code. DEPRECATED
        ! defined('REAL_PATH') && define('REAL_PATH', SITE_PATH);
        // Set WEB_PATH (if not done yet)
        if (! defined('WEB_PATH')) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $cur_web_path = '';
            if ($request_uri[strlen($request_uri) - 1] == '/') {
                $cur_web_path = substr($request_uri, 0, -1);
            } else {
                $cur_web_path = dirname($request_uri);
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
            define(
                'WEB_PATH',
                ($this->is_https() ? 'https://' : 'http://')
                    . $host . ($_SERVER['SERVER_PORT'] && ! in_array($_SERVER['SERVER_PORT'], ['80', '443']) ? ':' . $_SERVER['SERVER_PORT'] : '')
                    . str_replace(['\\', '//'], '/', (MAIN_TYPE_ADMIN ? dirname($cur_web_path) : $cur_web_path) . '/')
            );
        }
        if (! defined('WEB_DOMAIN') && defined('WEB_PATH') && strlen(WEB_PATH)) {
            define('WEB_DOMAIN', parse_url(WEB_PATH, PHP_URL_HOST));
        }
        // Should be different that WEB_PATH to distribute static content from other subdomain
        ! defined('MEDIA_PATH') && define('MEDIA_PATH', WEB_PATH);
        ! defined('ADMIN_SITE_PATH') && define('ADMIN_SITE_PATH', SITE_PATH . 'admin/');
        ! defined('ADMIN_WEB_PATH') && define('ADMIN_WEB_PATH', WEB_PATH . 'admin/');
        // Check if current page is called via AJAX call from javascript
        conf('IS_AJAX', (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest' || ! empty($_GET['ajax_mode'] ?? '')) ? 1 : 0);

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
            foreach ((array) $_SERVER['argv'] as $v) {
                if (strpos($v, '--') !== 0) {
                    continue;
                }
                $v = substr($v, 2);
                list($_name, $_val) = explode('=', $v);
                $_name = trim($_name);
                if (strlen($_name)) {
                    $_GET[$_name] = trim($_val);
                }
            }
        }
        // Filter object and action from $_GET
        if (($_GET['action'] ?? '') == ($_GET['object'] ?? '')) {
            $_GET['action'] = '';
        }
        $_GET['object'] = str_replace(['yf_', '-'], ['', '_'], preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim($_GET['object'] ?? ''))));
        $_GET['action'] = str_replace('-', '_', preg_replace('/[^a-z_\-0-9]*/', '', strtolower(trim($_GET['action'] ?? ''))));
        if (! $_GET['action']) {
            $_GET['action'] = defined('DEFAULT_ACTION') ? constant('DEFAULT_ACTION') : 'show';
        }
        ! conf('css_framework') && conf('css_framework', 'bs3');
    }

    /**
     * Send main headers.
     * @param mixed $content_length
     * @return
     */
    public function _send_main_headers($content_length = 0)
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        return $this->_class('graphics')->_send_main_headers($content_length);
        $this->events->fire('main.http_headers');
    }

    /**
     * Recursive method for stripping quotes from given data (string or array).
     * @param mixed $mixed
     * @return array|string
     */
    public function _strip_quotes_recursive($mixed)
    {
        if (is_array($mixed)) {
            return array_map([$this, __FUNCTION__], $mixed);
        }
        return stripslashes($mixed);
    }

    public function _init_cur_user_info(&$obj)
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), []];
        if (MAIN_TYPE_ADMIN) {
            $obj->USER_ID = intval($_GET['user_id'] ?? 0);
            $obj->ADMIN_ID = intval($_SESSION['admin_id'] ?? 0);
            $obj->ADMIN_GROUP = intval($_SESSION['admin_group'] ?? 0);
        } elseif (MAIN_TYPE_USER) {
            $obj->USER_ID = intval($_SESSION['user_id'] ?? 0);
            $obj->USER_GROUP = intval($_SESSION['user_group'] ?? 0);
        }
        if (isset($obj->USER_ID) && ! empty($obj->USER_ID)) {
            if (! isset($this->_current_user_info)) {
                $this->_current_user_info = user($obj->USER_ID);
            }
            $obj->_user_info = &$this->_current_user_info;
            if (! $obj->USER_GROUP) {
                $obj->USER_GROUP = $this->_current_user_info['group'];
            }
        }
        $this->events->fire('main.user_info');
    }

    /**
     * Unified method to replace core paths inside configuration directives. Examples: YF_PATH, {YF_PATH}, %YF_PATH%.
     * @param mixed $str
     * @return mixed
     */
    public function _replace_core_paths($str)
    {
        if (strpos($str, '_PATH') === false) {
            return $str;
        }
        if (empty($this->_paths_replace_pairs)) {
            $pairs = [];
            // Note: order matters
            $path_names = [
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
            ];
            foreach ($path_names as $name) {
                $val = constant($name);
                $pairs[$name] = $val; // Example: YF_PATH
                $pairs['{' . $name . '}'] = $val; // Example: {YF_PATH}
                $pairs['%' . $name . '%'] = $val; // Example: %YF_PATH%
            }
            $this->_paths_replace_pairs = $pairs;
            unset($pairs);
        }
        return str_replace(array_keys($this->_paths_replace_pairs), $this->_paths_replace_pairs, $str);
    }

    /**
     * Evaluate given code as PHP code.
     * @param mixed $code_text
     * @param mixed $as_string
     * @return mixed
     */
    public function _eval_code($code_text = '', $as_string = 1)
    {
        return eval('return ' . ($as_string ? '"' . $code_text . '"' : $code_text) . ' ;');
    }

    /**
     * Adds code to execute on shutdown.
     * @param mixed $code
     */
    public function _add_shutdown_code($code = '')
    {
        if (! empty($code)) {
            $this->_SHUTDOWN_CODE_ARRAY[] = $code;
        }
    }

    /**
     * Framework destructor handler.
     */
    public function _framework_destruct()
    {
        $this->PROFILING && $this->_timing[] = [microtime(true), __CLASS__, __FUNCTION__, $this->trace_string(), func_get_args()];
        // Restore startup working directory
        chdir($this->_CWD);

        $this->events->fire('main.shutdown');

        if ($this->CATCH_FATAL_ERRORS) {
            $error = error_get_last();
            if (in_array($error, [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
                $info = '[' . gmdate('Y-m-d H:i:s') . '] [SHUTDOWN] file:' . $error['file'] . ' | ln:' . $error['line'] . ' | msg:' . $error['message'] . PHP_EOL;
                file_put_contents(LOGS_PATH . 'fatal_log.txt', $info, FILE_APPEND);
                echo $info;
            }
        }
        if (isset($this->_SHUTDOWN_CODE_ARRAY)) {
            foreach ((array) $this->_SHUTDOWN_CODE_ARRAY as $_cur_code) {
                $_cur_code();
            }
        }
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _get($key = null, $val = null)
    {
        return $this->_class('input')->get($key, $val);
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _post($key = null, $val = null)
    {
        return $this->_class('input')->post($key, $val);
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _session($key = null, $val = null)
    {
        return $this->_class('input')->session($key, $val);
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _server($key = null, $val = null)
    {
        return $this->_class('input')->server($key, $val);
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _cookie($key = null, $val = null)
    {
        return $this->_class('input')->cookie($key, $val);
    }

    /**
     * @param null|mixed $key
     * @param null|mixed $val
     * @return
     */
    public function _env($key = null, $val = null)
    {
        return $this->_class('input')->env($key, $val);
    }

    /**
     * @param null|mixed $val
     * @return bool|mixed|null
     */
    public function no_graphics($val = null)
    {
        if ($val !== null) {
            $this->NO_GRAPHICS = $val;
        }
        return $this->NO_GRAPHICS;
    }

    public function is_db()
    {
        return is_object($this->db) ? true : false;
    }

    /**
     * Checks whether current page was requested with POST method.
     */
    public function is_post()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') == 'POST';
    }

    /**
     * Checks whether current page was requested with AJAX.
     */
    public function is_ajax()
    {
        return (bool) conf('IS_AJAX');
    }

    /**
     * Checks whether current page was requested from console.
     */
    public function is_console()
    {
        return (bool) $this->CONSOLE_MODE;
    }

    /**
     * Checks whether current page is a redirect.
     */
    public function is_redirect()
    {
        return (bool) $this->_IS_REDIRECTING;
    }

    /**
     * Checks whether current page is not a special page (no ajax, no redirects, no console, no post, etc).
     */
    public function is_common_page()
    {
        return ! ($this->is_post() || $this->is_ajax() || $this->is_redirect() || $this->is_console());
    }

    /**
     * Checks whether current page is in unit testing mode.
     */
    public function is_unit_test()
    {
        return (bool) defined('YF_IN_UNIT_TESTS');
    }

    public function is_logged_in()
    {
        return MAIN_TYPE_ADMIN ? $this->ADMIN_ID : $this->USER_ID;
    }

    public function is_spider()
    {
        return (bool) conf('IS_SPIDER');
    }

    public function is_https()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) != 'off')
            || (isset($_SERVER['SSL_PROTOCOL']) && $_SERVER['SSL_PROTOCOL'])
            // Non-standard header used by Microsoft applications and load-balancers:
            || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) == 'on')
            // http://docs.aws.amazon.com/ElasticLoadBalancing/latest/DeveloperGuide/x-forwarded-headers.html
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
            // http://stackoverflow.com/questions/16042647/whats-the-de-facto-standard-for-a-reverse-proxy-to-tell-the-backend-ssl-is-used
            || (isset($_SERVER['HTTP_X_URL_SCHEME']) && strtolower($_SERVER['HTTP_X_URL_SCHEME']) == 'https')
            // http://serverfault.com/questions/302282/how-can-i-use-haproxy-with-ssl-and-get-x-forwarded-for-headers-and-tell-php-that
            || (isset($_SERVER['HTTP_SCHEME']) && strtolower($_SERVER['HTTP_SCHEME']) == 'https');
    }

    public function is_dev()
    {
        return (defined('DEVELOP') && DEVELOP) || (defined('TEST_MODE') && TEST_MODE);
    }

    public function is_debug()
    {
        return defined('DEBUG_MODE') && DEBUG_MODE;
    }

    public function is_banned()
    {
        return (bool) $this->IS_BANNED;
    }

    public function is_site_path()
    {
        return defined('SITE_PATH') && SITE_PATH != '' && SITE_PATH != PROJECT_PATH;
    }

    public function is_403()
    {
        return (bool) ($this->IS_403 || $this->BLOCKS_TASK_403);
    }

    public function is_404()
    {
        return (bool) ($this->IS_404 || $this->BLOCKS_TASK_404);
    }

    public function is_blocks_task_403()
    {
        return (bool) $this->BLOCKS_TASK_403;
    }

    public function is_blocks_task_404()
    {
        return (bool) $this->BLOCKS_TASK_404;
    }

    public function is_503()
    {
        return (bool) $this->IS_503;
    }

    public function is_cache_on()
    {
        return (bool) (($this->USE_SYSTEM_CACHE || conf('USE_CACHE')) && ! cache()->NO_CACHE);
    }

    public function is_output_cache_on()
    {
        return (bool) $this->OUTPUT_CACHING;
    }

    public function is_mobile()
    {
        if (isset($this->_is_mobile)) {
            return $this->_is_mobile;
        }
        $this->_is_mobile = false;
        if (! $this->is_console()) {
            try {
                require_php_lib('mobile_detect');
                $detect = new Mobile_Detect([], strtolower($_SERVER['HTTP_USER_AGENT']));
                $this->_is_mobile = (bool) $detect->isMobile();
            } catch (Exception $e) {
            }
        }
        return $this->_is_mobile;
    }

    /**
     * Return class name of the object, stripping all YF-related prefixes
     * Needed to ensure singleton pattern and extending classes with same name.
     * @param mixed $name
     * @return bool|mixed|string
     */
    public function get_class_name($name)
    {
        if (is_object($name)) {
            $name = get_class($name);
        }
        if( !$name ) { return( $name ); }
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
     * @param mixed $module
     * @param mixed $name
     * @param mixed $func
     */
    public function extend($module, $name, $func)
    {
        $module = $this->get_class_name($module);
        $this->_extend[$module][$name] = $func;
    }

    /**
     * @param mixed $that
     * @param mixed $name
     * @param mixed $args
     * @param mixed $return_obj
     * @return bool|mixed
     */
    public function extend_call($that, $name, $args, $return_obj = false)
    {
        $module = $this->get_class_name($that);
        $func = null;
        if (isset($that->_extend[$name])) {
            $func = $that->_extend[$name];
        } elseif (isset($this->_extend[$module][$name])) {
            $func = $this->_extend[$module][$name];
        }
        if ($func) {
            $out = $func($args[0], $args[1], $args[2], $args[3], $that);
            return $return_obj ? $that : $out;
        }
        trigger_error($module . ': No method ' . $name, E_USER_WARNING);
        return $return_obj ? $that : false;
    }

    /**
     * @param mixed $name
     * @param mixed $params
     * @return
     */
    public function require_php_lib($name, $params = [])
    {
        return $this->_class('services')->require_php_lib($name, $params);
    }
}
