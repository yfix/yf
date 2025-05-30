<?php

/**
 * Stadard Framework template engine.
 *
 * @author	  YFix Team <yfix.dev@gmail.com>
 * @version	  1.0
 */
#[AllowDynamicProperties]
class yf_tpl
{
    /** @var string @conf_skip Path to the templates (including current theme path) */
    public $TPL_PATH = '';
    /** @var string default template name */
    public $TPL_NAME = 'main';
    /** @var bool Compressing output by cutting '\t','\r','\n','  ','   ' */
    public $COMPRESS_OUTPUT = false;
    /** @var bool Using SEO - friendly URLs (All links need to be absolute) */
    public $REWRITE_MODE = false;
    /** @var bool Custom meta information (customizable for every page) : page titles, meta keywords, description */
    public $CUSTOM_META_INFO = false;
    /** @var bool Exit after sending main content */
    public $EXIT_AFTER_ECHO = false;
    /** @var bool Use database to store templates */
    public $GET_STPLS_FROM_DB = false;
    /** @var bool SECURITY: allow or not eval php code (with _PATTERN_INCLUDE) */
    public $ALLOW_EVAL_PHP_CODE = true;
    /** @var bool Get all templates from db or not (1 query or multiple)  (NOTE: If true - Slow PHP processing but just 1 db query) */
    public $FROM_DB_GET_ALL = false;
    /** @var bool Catch any output before gzipped content (works only with GZIP) */
    public $OB_CATCH_CONTENT = true;
    /** @var bool Use or not Tidy to cleanup output */
    public $TIDY_OUTPUT = false;
    /** @var bool Use backtrace to get STPLs source (where called from) FOR DEBUG_MODE ONLY ! */
    public $USE_SOURCE_BACKTRACE = true;
    /** @var bool If available - use packed STPLs without checking if some exists in project */
    public $AUTO_LOAD_PACKED_STPLS = false;
    /** @var bool Allow custom filter for all parsed stpls */
    public $ALLOW_CUSTOM_FILTER = false;
    /** @var bool Allow language-based special stpls */
    public $ALLOW_LANG_BASED_STPLS = false;
    /** @var bool Allow skin inheritance (only one level used) */
    public $ALLOW_SKIN_INHERITANCE = true;
    /** @var bool Allow to compile templates */
    public $COMPILE_TEMPLATES = false;
    /** @var bool TTL for compiled stpls */
    public $COMPILE_TTL = 3600;
    /** @var bool TTL for compiled stpls */
    public $COMPILE_CHECK_STPL_CHANGED = false;
    /** @var bool Allow pure php templates */
    public $ALLOW_PHP_TEMPLATES = false;
    /** @var bool */
    public $DEBUG_STPL_VARS = false;
    /** @var bool Will add cur date, generation time, memory and db queries into any common page before body */
    public $ADD_QUICK_PAGE_INFO = false;
    /** @var bool Compile templates folder */
    public $COMPILED_DIR = 'stpls_compiled/';
    /** @var string @conf_skip */
    public $_STPL_EXT = '.stpl';
    /** @var string @conf_skip Ability to use files with these extensions as templates */
    public $ALLOWED_EXTS = ['tpl', 'stpl', 'html'];
    /** @var string @conf_skip */
    public $_THEMES_PATH = 'templates/';
    /** @var string @conf_skip */
    public $_IMAGES_PATH = 'images/';
    /** @var string @conf_skip */
    public $_UPLOADS_PATH = 'uploads/';
    /** @var string Current tempalte engine dirver to use */
    public $DRIVER_NAME = 'yf';
    /** @var array Global scope tags (included in any parsed template) */
    public $_global_tags = [];
    /** @var array @conf_skip  For '_process_conditions', Will be availiable in conditions with such form: {if('get.object' eq 'login_form')} Hello from login form {/if} */
    public $_avail_arrays = [
        'get' => '_GET',
        'post' => '_POST',
        'server' => '_SERVER',
        'env' => '_ENV',
    ];

    public $driver = null;
    public $IS_FRONT = false;
    public $_lang_theme_path = null;
    public $_INHERITED_SKIN = null;
    public $_INHERITED_SKIN2 = null;
    public $INHERIT_SKIN = null;
    public $INHERIT_SKIN2 = null;
    public $_TMP_FROM_DB = null;
    public $_CENTER_RESULT = null;
    public $_output_body_length = null;
    public $_user_error_msg = null;
    public $_def_user_theme = null;
    public $LOG_EXEC_INFO = [];
    public $_OUTPUT_FILTERS = [];
    public $_TIDY_CONFIG = '';
    public $MEDIA_PATH = '';
    public $_custom_patterns_funcs = [];
    public $_custom_patterns_index = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Framework constructor.
     */
    public function _init()
    {
        if (DEBUG_MODE) {
            $this->ADD_QUICK_PAGE_INFO = true;
        }
        // Needed to ensure backtracking still works on big templates (extended from 1 000 000 on 26kb stpl js() parsing)
        ini_set('pcre.backtrack_limit', '10000000');

        if (defined('IS_FRONT')) {
            conf('IS_FRONT', (bool) constant('IS_FRONT'));
        }
        $this->IS_FRONT = (bool) conf('IS_FRONT');
        // Set custom skin
        if ( ! empty($_SESSION['user_skin']) && MAIN_TYPE_USER) {
            conf('theme', $_SESSION['user_skin']);
        } elseif (defined('DEFAULT_SKIN')) {
            conf('theme', constant('DEFAULT_SKIN'));
        }
        if ( ! conf('theme')) {
            conf('theme', MAIN_TYPE);
        }
        // Directory where themes are stored
        conf('THEMES_PATH', $this->_THEMES_PATH);
        // Template files extensions
        conf('_STPL_EXT', $this->_STPL_EXT);
        // Set path to the templates including selected skin
        $this->TPL_PATH = $this->_THEMES_PATH . conf('theme') . '/';

        if ($this->COMPRESS_OUTPUT) {
            $this->register_output_filter([$this, '_simple_cleanup_callback'], 'simple_cleanup');
        }
        if ($this->ALLOW_LANG_BASED_STPLS) {
            $this->_lang_theme_path = PROJECT_PATH . $this->_THEMES_PATH . conf('theme') . '.' . conf('language') . '/';
            if ( ! file_exists($this->_lang_theme_path)) {
                $this->ALLOW_LANG_BASED_STPLS = false;
                $this->_lang_theme_path = '';
            }
        }
        if ($this->ALLOW_SKIN_INHERITANCE) {
            if (defined('INHERIT_SKIN')) {
                conf('INHERIT_SKIN', constant('INHERIT_SKIN'));
            }
            if (conf('INHERIT_SKIN') != conf('theme')) {
                $this->_INHERITED_SKIN = conf('INHERIT_SKIN');
            }
            if (defined('INHERIT_SKIN2')) {
                conf('INHERIT_SKIN2', constant('INHERIT_SKIN2'));
            }
            if (conf('INHERIT_SKIN2') != conf('theme')) {
                $this->_INHERITED_SKIN2 = conf('INHERIT_SKIN2');
            }
        }
        // Turn off CPU expensive features on overloading
        if (conf('HIGH_CPU_LOAD') == 1) {
            $this->COMPRESS_OUTPUT = false;
            $this->TIDY_OUTPUT = false;
            $this->FROM_DB_GET_ALL = false;
        }
        $this->_init_global_tags();

        if (DEBUG_MODE) {
            $this->register_output_filter([$this, '_debug_mode_callback'], 'debug_mode');
        }
        if (main()->is_console()) {
            $this->OB_CATCH_CONTENT = false;
        }
        $this->_set_default_driver($this->DRIVER_NAME);
    }

    /**
     * @param mixed $name
     */
    public function _set_default_driver($name = '')
    {
        if ( ! $name) {
            $name = $this->DRIVER_NAME;
        }
        if ( ! $name) {
            $name = 'yf';
        }
        $this->DRIVER_NAME = $name;
        $this->driver = _class('tpl_driver_' . $name, 'classes/tpl/');
    }

    /**
     * Global scope tags.
     */
    public function _init_global_tags()
    {
        $data = [
            'main_user_id' => (int) main()->USER_ID,
            'is_logged_in' => (int) ((bool) main()->USER_ID),
            'site_id' => (int) conf('SITE_ID'),
            'lang' => conf('language'),
            'tpl_path' => MEDIA_PATH . $this->TPL_PATH,
        ];
        foreach ($data as $k => $v) {
            $this->_global_tags[$k] = $v;
        }
    }

    /**
     * Initialization of the main content
     * Throws one 'echo' at the end.
     */
    public function init_graphics()
    {
        $init_type = MAIN_TYPE;
        // Do not remove this!
        $this->_init_global_tags();
        // Default user group
        if ($init_type == 'user' && empty($_SESSION['user_group'])) {
            $_SESSION['user_group'] = 1;
        }
        if (main()->OUTPUT_CACHING && $init_type == 'user' && $_SERVER['REQUEST_METHOD'] == 'GET') {
            _class('output_cache')->_process_output_cache();
        }
        if ( ! main()->no_graphics()) {
            if ($this->OB_CATCH_CONTENT) {
                ob_start();
            }
            // Trying to get default task
            if ($init_type == 'user' && ! empty($_SESSION['user_id']) && ! empty($_SESSION['user_group'])) {
                $go = conf('default_page_user');
            } elseif ($init_type == 'admin') {
                $go = conf('default_page_admin');
            }
            // If setting exists - assign it to the location
            if ( ! empty($go) && empty($_GET['object'])) {
                $go = str_replace(['./?', './'], '', $go);
                $tmp_array = [];
                parse_str($go, $tmp_array);
                foreach ((array) $tmp_array as $k => $v) {
                    $_GET[$k] = $v;
                }
            }
            $skip_prefetch = false;
            // Determine what template need to be loaded in the center area
            $tpl_name = $this->TPL_NAME;
            if ($init_type == 'admin' && (empty($_SESSION['admin_id']) || empty($_SESSION['admin_group']))) {
                $tpl_name = 'login';
                if (main()->is_ajax()) {
                    no_graphics(true);
                    main()->IS_403 = true;
                    header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 403 Forbidden');
                    $skip_prefetch = true;
                }
                if ( ! main()->is_console()) {
                    $skip_prefetch = true;
                }
            }
            if ($this->GET_STPLS_FROM_DB && $this->FROM_DB_GET_ALL) {
                $tmp = from('templates')->where('theme_name', conf('theme'))->where('active', '1')->get_2d('name,text');
                foreach ((array) $tmp as $k => $v) {
                    $tmp[$k] = stripslashes($v);
                }
                $this->_TMP_FROM_DB = $tmp;
                unset($tmp);
            }
            if ( ! $skip_prefetch) {
                if (main()->is_console()) {
                    // Skip security checks for console mode
                    _class('core_blocks')->tasks(false);
                } else {
                    _class('core_blocks')->prefetch_center();
                }
            }
        }
        if ( ! main()->no_graphics()) {
            $body['content'] = $this->_init_main_stpl($tpl_name);
            $this->_CENTER_RESULT = '';
            if ($this->CUSTOM_META_INFO && $init_type == 'user') {
                $this->register_output_filter([$this, '_custom_replace_callback'], 'custom_replace');
            }
            if ($init_type == 'user' && _class('graphics')->IFRAME_CENTER && (false === strpos($_SERVER['QUERY_STRING'], 'center_area=1'))) {
                $this->register_output_filter([$this, '_replace_for_iframe_callback'], 'replace_for_iframe');
            }
        }
        if ( ! main()->no_graphics()) {
            // Replace images paths with their absolute ones
            if ($this->REWRITE_MODE && $init_type != 'admin') {
                $this->register_output_filter([$this, '_rewrite_links_callback'], 'rewrite_links');
            }
            if ($this->TIDY_OUTPUT && $init_type != 'admin') {
                $this->register_output_filter([$this, '_tidy_cleanup_callback'], 'tidy_cleanup');
            }
            $body['content'] = $this->_apply_output_filters($body['content']);
            if (main()->OUTPUT_CACHING && $init_type == 'user' && $_SERVER['REQUEST_METHOD'] == 'GET') {
                _class('output_cache')->_put_page_to_output_cache($body);
            }
            if ( ! main()->is_console() && ! main()->is_ajax()) {
                if (DEBUG_MODE) {
                    $body['debug_info'] = common()->show_debug_info();
                }
                $_last_pos = strpos($body['content'], '</body>');
                if ($_last_pos) {
                    $body['content'] = substr($body['content'], 0, $_last_pos) . $body['debug_info'] . '</body></html>';
                    $body['debug_info'] = '';
                }
                if ($this->ADD_QUICK_PAGE_INFO) {
                    $body['exec_time'] = $this->_get_quick_page_info();
                }
            }
            $output = implode('', $body);
            $this->_output_body_length = strlen($output);
            main()->_send_main_headers($this->_output_body_length);
            // Throw generated output to user
            echo $output;
        }
        if (DEBUG_MODE && main()->no_graphics() && ! main()->is_console() && ! main()->is_ajax()) {
            echo common()->show_debug_info();
        }
        // Output cache for 'no graphics' content
        if (main()->no_graphics() && main()->OUTPUT_CACHING && $init_type == 'user' && $_SERVER['REQUEST_METHOD'] == 'GET') {
            _class('output_cache')->_put_page_to_output_cache(ob_get_clean());
        }
        if (main()->LOG_EXEC || $this->LOG_EXEC_INFO) {
            _class('logs')->log_exec();
        }
        // End sending main output
        ob_end_flush();
        if ($this->EXIT_AFTER_ECHO) {
            exit();
        }
    }


    public function _get_quick_page_info()
    {
        if ( ! $this->ADD_QUICK_PAGE_INFO) {
            return false;
        }
        return PHP_EOL . '<!-- date: ' . gmdate('Y-m-d H:i:s') . ' UTC, time: ' . round(microtime(true) - main()->_time_start, 3) . ', memory: ' . memory_get_peak_usage() . ', db: ' . (int) db()->NUM_QUERIES . ' -->' . PHP_EOL;
    }

    /**
     * Process output filters for the given text.
     * @param mixed $text
     */
    public function _apply_output_filters($text = '')
    {
        foreach ((array) $this->_OUTPUT_FILTERS as $cur_filter) {
            if (is_callable($cur_filter)) {
                $text = call_user_func($cur_filter, $text);
            }
        }
        return $text;
    }

    /**
     * Initialization of the main template in the theme (could be overwritten to match design)
     * Return contents of the main template.
     * @param mixed $tpl_name
     */
    public function _init_main_stpl($tpl_name = '')
    {
        return $this->parse($tpl_name);
    }

    /**
     * Simple template parser (*.stpl).
     * @param mixed $name
     * @param mixed $replace
     * @param mixed $params
     */
    public function parse($name, $replace = [], $params = [])
    {
        $name = strtolower(trim($name));
        // Support for the driver name in prefix, example: "twig:user/account", "smarty:user/account"
        if (strpos($name, ':') !== false) {
            list($driver, $name) = explode(':', $name);
            if ($driver) {
                $params['driver'] = $driver;
            }
        }
        // Support for the framework calls
        $yf_prefix = 'yf_';
        $yfp_len = strlen($yf_prefix);
        if (substr($name, 0, $yfp_len) == $yf_prefix) {
            $name = substr($name, $yfp_len);
        }
        if (false !== strpos($name, '@')) {
            $r = [
                '@object' => $_GET['object'],
                '@action' => $_GET['action'],
                '@id' => $_GET['id'],
            ];
            $name = str_replace(array_keys($r), array_values($r), $name);
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        $string = $params['string'] ?: false;
        $params['replace_images'] = $params['replace_images'] ?: true;
        $params['no_cache'] = $params['no_cache'] ?: false;
        $params['force_storage'] = $params['force_storage'] ?: '';
        $params['no_include'] = $params['no_include'] ?: false;
        if (DEBUG_MODE) {
            $stpl_time_start = microtime(true);
        }
        $replace = (array) $replace + (array) $this->_global_tags;
        $replace['error'] = $this->_parse_get_user_errors($name, $replace['error']);
        if (isset($replace[''])) {
            unset($replace['']);
        }
        if ($this->ALLOW_CUSTOM_FILTER) {
            $this->_custom_filter($name, $replace);
        }
        // Allowing to override driver
        if ($params['driver'] && $params['driver'] != $this->DRIVER_NAME) {
            $string = _class('tpl_driver_' . $params['driver'], 'classes/tpl/')->parse($name, $replace, $params);
        } else {
            $string = $this->driver->parse($name, $replace, $params);
        }
        if ($params['replace_images']) {
            $string = $this->_replace_images_paths($string);
        }
        if (DEBUG_MODE) {
            $this->_parse_set_debug_info($name, $replace, $params, $string, $stpl_time_start);
        }
        return $string;
    }

    /**
     * Wrapper to parse given template string.
     * @param mixed $string
     * @param mixed $replace
     * @param mixed $name
     * @param mixed $params
     */
    public function parse_string($string = '', $replace = [], $name = '', $params = [])
    {
        if ( ! strlen($string ?? '')) {
            $string = ' ';
        }
        if ( ! $name) {
            $name = 'auto__' . abs(crc32($string));
        }
        $params['string'] = $string;
        return $this->parse($name, $replace, $params);
    }

    /**
     * Wrapper on parse(), silently failing if template not exists.
     * @param mixed $name
     * @param mixed $replace
     * @param mixed $params
     */
    public function parse_if_exists($name, $replace = [], $params = [])
    {
        return $this->exists($name) ? $this->parse($name, $replace, $params) : '';
    }

    /**
     * @param mixed $name
     * @param mixed $err
     */
    public function _parse_get_user_errors($name, $err)
    {
        if (isset($err)) {
            return $err;
        }
        $err = '';
        if ($name != 'main' && common()->_error_exists()) {
            if ( ! isset($this->_user_error_msg)) {
                $this->_user_error_msg = common()->_show_error_message('', false);
            }
            $err = $this->_user_error_msg;
        }
        return $err;
    }

    /**
     * @param mixed $name
     * @param mixed $replace
     * @param mixed $params
     * @param mixed $string
     * @param mixed $stpl_time_start
     */
    public function _parse_set_debug_info($name = '', $replace = [], $params = [], $string = '', $stpl_time_start = 0)
    {
        if ( ! DEBUG_MODE) {
            return false;
        }
        if ( ! isset($this->driver->CACHE[$name]['exec_time'])) {
            $this->driver->CACHE[$name]['exec_time'] = 0;
        }
        $this->driver->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
        // For debug store information about variables used while processing template
        if ($this->DEBUG_STPL_VARS) {
            debug('STPL_REPLACE_VARS::' . $name . '[]', $replace);
        }
        if ($this->USE_SOURCE_BACKTRACE) {
            debug('STPL_TRACES::' . $name, main()->trace_string());
        }
        return true;
    }

    /**
     * Alias.
     * @param mixed $stpl_name
     * @param mixed $force_storage
     */
    public function exists($stpl_name = '', $force_storage = '')
    {
        return (bool) $this->_stpl_exists($stpl_name, $force_storage);
    }

    /**
     * Check if template exists (simple wrapper for the '_get_template_file').
     * @param mixed $stpl_name
     * @param mixed $force_storage
     */
    public function _stpl_exists($stpl_name = '', $force_storage = '')
    {
        // Exists in cache
        if ( ! $force_storage && isset($this->driver->CACHE[$stpl_name])) {
            return true;
        }
        return (bool) $this->_get_template_file($stpl_name, $force_storage, 1);
    }

    /**
     * Alias.
     * @param mixed $file_name
     * @param mixed $force_storage
     * @param mixed $JUST_CHECK_IF_EXISTS
     * @param mixed $RETURN_TEMPLATE_PATH
     */
    public function get($file_name = '', $force_storage = '', $JUST_CHECK_IF_EXISTS = false, $RETURN_TEMPLATE_PATH = false)
    {
        return $this->_get_template_file($file_name, $force_storage, $JUST_CHECK_IF_EXISTS, $RETURN_TEMPLATE_PATH);
    }


    public function _get_cached_paths()
    {
        $cache_name = __FUNCTION__ . '_' . MAIN_TYPE;
        if (isset($this->$cache_name)) {
            return $this->$cache_name;
        }
        $this->$cache_name = getset('tpl_get_cached_paths', function () {
            $allowed_exts = (array) $this->ALLOWED_EXTS;
            $templates_dir = trim($this->_THEMES_PATH, '/');
            $patterns = [
                'framework' => [
                    YF_PATH . $templates_dir . '/*/*.*',
                    YF_PATH . $templates_dir . '/*/*/*.*',
                    YF_PATH . $templates_dir . '/*/*/*/*.*',
                    YF_PATH . 'plugins/*/'. $templates_dir . '/*/*.*',
                    YF_PATH . 'plugins/*/'. $templates_dir . '/*/*/*.*',
                    YF_PATH . 'plugins/*/'. $templates_dir . '/*/*/*/*.*',
                ],
                'project' => [
                    PROJECT_PATH . $templates_dir . '/*/*.*',
                    PROJECT_PATH . $templates_dir . '/*/*/*.*',
                    PROJECT_PATH . $templates_dir . '/*/*/*/*.*',
                    PROJECT_PATH . 'plugins/*/'. $templates_dir . '/*/*.*',
                    PROJECT_PATH . 'plugins/*/'. $templates_dir . '/*/*/*.*',
                    PROJECT_PATH . 'plugins/*/'. $templates_dir . '/*/*/*/*.*',
                ],
                'app' => [
                    APP_PATH . $templates_dir . '/*/*.*',
                    APP_PATH . $templates_dir . '/*/*/*.*',
                    APP_PATH . $templates_dir . '/*/*/*/*.*',
                    APP_PATH . 'plugins/*/'. $templates_dir . '/*/*.*',
                    APP_PATH . 'plugins/*/'. $templates_dir . '/*/*/*.*',
                    APP_PATH . 'plugins/*/'. $templates_dir . '/*/*/*/*.*',
                ],
            ];
            $site_path = (MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH);
            if (is_site_path()) {
                $patterns['site'] = [
                    $site_path . $templates_dir . '/*/*.*',
                    $site_path . $templates_dir . '/*/*/*.*',
                    $site_path . $templates_dir . '/*/*/*/*.*',
                    $site_path . 'plugins/*/'. $templates_dir . '/*/*.*',
                    $site_path . 'plugins/*/'. $templates_dir . '/*/*/*.*',
                    $site_path . 'plugins/*/'. $templates_dir . '/*/*/*/*.*',
                ];
            }
            $plens = [
                'framework' => strlen(YF_PATH),
                'project' => strlen(PROJECT_PATH),
                'app' => strlen(APP_PATH),
                'site' => isset($patterns['site']) ? strlen($site_path) : null,
            ];
            $names = [];
            foreach ($patterns as $gname => $glob_patterns) {
                foreach ($glob_patterns as $glob) {
                    foreach (glob($glob, GLOB_NOSORT) as $path) {
                        $name = substr($path, $plens[$gname]);
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        if (!$ext || !in_array($ext, $allowed_exts)) {
                            continue;
                        }
                        $p = explode('/', $name);
                        if ($p[0] == 'plugins') {
                            $p = array_slice($p, 2);
                        }
                        $theme = '';
                        if ($p[0] == $templates_dir) {
                            $theme = $p[1];
                            $p = array_slice($p, 2);
                        }
                        $name = implode('/', $p);
                        $name = substr($name, 0, -strlen('.' . $ext));
                        $names[$name][$gname][$theme] = $path;
                    }
                }
            }
            return $names;
        });
        return $this->$cache_name;
    }

    /**
     * Read template file contents (or get it from DB).
     * @param mixed $file_name
     * @param mixed $force_storage
     * @param mixed $JUST_CHECK_IF_EXISTS
     * @param mixed $RETURN_TEMPLATE_PATH
     */
    public function _get_template_file($file_name = '', $force_storage = '', $JUST_CHECK_IF_EXISTS = false, $RETURN_TEMPLATE_PATH = false)
    {
        $string = false;
        $NOT_FOUND = false;
        $storage = 'inline';
        $file_name = trim(trim(trim($file_name), '/'));
        $l = strlen(YF_PREFIX);
        if (substr($file_name, 0, $l) == YF_PREFIX) {
            $file_name = substr($file_name, $l);
        }
        $stpl_ext = $this->_STPL_EXT;
        $path_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $path_ext && $path_ext = '.' . $path_ext;
        // Allowed extension overrides
        if ( ! $path_ext || ! in_array($path_ext, (array) $this->ALLOWED_EXTS)) {
            $file_name .= $stpl_ext;
        }
        // Fix double extesion
        $file_name = str_replace($stpl_ext . $stpl_ext, $stpl_ext, $file_name);
        $stpl_name = str_replace([$stpl_ext, $path_ext], '', $file_name);

        if ($this->GET_STPLS_FROM_DB || $force_storage == 'db') {
            if ($this->FROM_DB_GET_ALL) {
                if ( ! empty($this->_TMP_FROM_DB[$stpl_name])) {
                    $string = $this->_TMP_FROM_DB[$stpl_name];
                    unset($this->_TMP_FROM_DB[$stpl_name]);
                } else {
                    $NOT_FOUND = true;
                }
            } else {
                $text = from('templates')->where('theme_name', conf('theme'))->where('name', $stpl_name)->where('active', '1')->one('text');
                if (isset($text)) {
                    $string = stripslashes($text);
                } else {
                    $NOT_FOUND = true;
                }
            }
            $storage = 'db';
        } else {
            $def_theme = $this->_get_def_user_theme();
            $all_tpls_paths = $this->_get_cached_paths();
            $paths = $all_tpls_paths[$stpl_name];
            // Storages are defined in specially crafted `order`, so do not change it unless you have strong reason
            $storages = [];
            $site_path = (MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH);
            $theme = conf('theme');

            $storages = [
                'dev',
                'site_lang',
                'site',
                'site_inherit',
                'site_inherit2',
                'app_lang',
                'app',
                'app_inherit',
                'app_inherit2',
                'app_user',
                'project',
                'project_user',
                'framework',
                'framework_user',
            ];
            $storages = array_filter($storages);
            foreach ((array) $storages as $_storage) {
                if ($force_storage && $force_storage != $_storage) {
                    continue;
                }
                $file_path = '';
                $_theme = '';
                if (in_array($_storage, ['app', 'project', 'framework'])) {
                    $_theme = $_storage == 'framework' ? MAIN_TYPE : $theme;
                    if (isset($paths[$_storage][$_theme])) {
                        $file_path = $paths[$_storage][$_theme];
                    }
                } elseif (in_array($_storage, ['app_user', 'project_user', 'framework_user']) && MAIN_TYPE_ADMIN && ! in_array($stpl_name, ['main'])) {
                    $s = substr($_storage, 0, -strlen('_user'));
                    if (isset($paths[$s]['user'])) {
                        $file_path = $paths[$s]['user'];
                    }
                } elseif ($_storage == 'site') {
                    $_theme = $_storage == 'framework' ? MAIN_TYPE : $theme;
                    if (isset($paths[$_storage][$_theme])) {
                        $file_path = $paths[$_storage][$_theme];
                    }
                } elseif (in_array($_storage, ['app_lang', 'site_lang']) && $this->ALLOW_LANG_BASED_STPLS) {
                    $lang = conf('language');
                    $_theme = $theme . '.' . $lang;
                    $s = substr($_storage, 0, -strlen('_lang'));
                    if (isset($paths[$s][$_theme])) {
                        $file_path = $paths[$s][$_theme];
                    }
                } elseif (in_array($_storage, ['app_inherit', 'site_inherit']) && $this->_INHERITED_SKIN) {
                    $_theme = $this->_INHERITED_SKIN;
                    $s = substr($_storage, 0, -strlen('_inherit'));
                    if (isset($paths[$s][$_theme])) {
                        $file_path = $paths[$s][$_theme];
                    }
                } elseif (in_array($_storage, ['app_inherit2', 'site_inherit2']) && $this->_INHERITED_SKIN2) {
                    $_theme = $this->_INHERITED_SKIN2;
                    $s = substr($_storage, 0, -strlen('_inherit2'));
                    if (isset($paths[$s][$_theme])) {
                        $file_path = $paths[$s][$_theme];
                    }
                } elseif (in_array($_storage, ['dev'])) {
//					// Developer overrides
//					$dev_path = '.dev/'.main()->HOSTNAME.'/';
//					if (conf('DEV_MODE')) {
//						if ($site_path && $site_path != PROJECT_PATH) {
//							$storages['dev_site'] = $site_path. $dev_path. $this->TPL_PATH. $file_name;
//						}
//						$storages['dev_app'] = APP_PATH. $dev_path. $this->TPL_PATH. $file_name;
//						$storages['dev_project'] = PROJECT_PATH. $dev_path. $this->TPL_PATH. $file_name;
//					}
                }
                if ( ! $file_path || ! $this->_stpl_path_exists($file_path)) {
                    continue;
                }
                $string = $this->_stpl_path_get($file_path);
                if ($string !== false) {
                    $storage = $_storage;
                    break;
                }
            }
            // Last try from cache (preloaded templates)
            if ($string === false) {
                $compiled_stpl = conf('_compiled_stpls::' . $stpl_name);
                if ($compiled_stpl) {
                    $string = $compiled_stpl;
                    $storage = 'compiled_cache';
                }
            }
            if ($string === false) {
                $NOT_FOUND = true;
            }
        }
        if (DEBUG_MODE) {
            $this->driver->debug[$stpl_name]['storage'] = $storage;
            $this->driver->debug[$stpl_name]['storages'] = $paths;
        }
        if ($RETURN_TEMPLATE_PATH) {
            return $file_path;
        }
        // If we just checking template existance - then stop here
        if ($JUST_CHECK_IF_EXISTS) {
            return ! $NOT_FOUND;
        }
        // Log error message if template file was not found
        if ($NOT_FOUND) {
            trigger_error('STPL: template "' . $file_name . '" in theme "' . conf('theme') . '" not found.', E_USER_WARNING);
        }
        return $string;
    }

    /**
     * Get default user theme (for admin section).
     */
    public function _get_def_user_theme()
    {
        if ( ! empty($this->_def_user_theme)) {
            return $this->_def_user_theme;
        }
        //		$sites = conf('sites_info');
        //		$first = array_shift($sites);
        //		if (file_exists(PROJECT_PATH. $this->_THEMES_PATH. $first['DEFAULT_SKIN']. '/')) {
        //			$this->_def_user_theme = $first['DEFAULT_SKIN'];
        //		}
        if (empty($this->_def_user_theme)) {
            $this->_def_user_theme = 'user';
        }
        return $this->_def_user_theme;
    }

    /**
     * @param mixed $file_name
     */
    public function _stpl_path_get($file_name)
    {
        return file_get_contents($file_name);
    }

    /**
     * Check if given template exists.
     * @param mixed $file_name
     */
    public function _stpl_path_exists($file_name)
    {
        return file_exists($file_name);
    }

    /**
     * If content need to be cleaned from unused tags - do that.
     * @param mixed $string
     * @param mixed $replace
     * @param mixed $name
     */
    public function _process_clear_unused($string, $replace = [], $name = '')
    {
        return preg_replace('/\{[\w_]+\}/i', '', $string);
    }

    /**
     * @param mixed $string
     * @param mixed $replace
     * @param mixed $name
     */
    public function _process_eval_string($string, $replace = [], $name = '')
    {
        return eval('return "' . str_replace('"', '\"', $string) . '";');
    }

    /**
     * Registers custom function to be used in templates.
     * @param mixed $callback_impl
     * @param mixed $filter_name
     */
    public function register_output_filter($callback_impl, $filter_name = '')
    {
        if (empty($filter_name)) {
            $filter_name = substr(abs(crc32(microtime(true))), 0, 8);
        }
        $this->_OUTPUT_FILTERS[$filter_name] = $callback_impl;
    }

    /**
     * Simple cleanup (compress) output.
     * @param mixed $text
     */
    public function _simple_cleanup_callback($text = '')
    {
        if (DEBUG_MODE) {
            debug('compress_output::size_original', strlen($text));
        }
        $text = str_replace(["\r", "\n", "\t"], '', $text);
        $text = preg_replace('#[\s]{2,}#ms', ' ', $text);
        // Remove html comments
        $text = preg_replace('#<\!--[\w\s\-\/]*?-->#ms', '', $text);
        if (DEBUG_MODE) {
            debug('compress_output::size_compressed', strlen($text));
        }
        return $text;
    }

    /**
     * Custom text replacing method.
     * @param mixed $text
     */
    public function _custom_replace_callback($text = '')
    {
        return _class('custom_meta_info')->_process($text);
    }

    /**
     * Replace method for 'IFRAME in center' mode.
     * @param mixed $text
     */
    public function _replace_for_iframe_callback($text = '')
    {
        return _class('rewrite')->_replace_links_for_iframe($text);
    }

    /**
     * Rewrite links callback method.
     * @param mixed $text
     */
    public function _rewrite_links_callback($text = '')
    {
        return _class('rewrite')->_rewrite_replace_links($text);
    }

    /**
     * Clenup HTML output with Tidy.
     * @param mixed $text
     */
    public function _tidy_cleanup_callback($text = '')
    {
        if ( ! class_exists('tidy') || ! extension_loaded('tidy')) {
            return $text;
        }
        $tidy_default_config = [
            'alt-text' => '',
            'output-xhtml' => true,
        ];
        $tidy = new tidy();
        $tidy->parseString($text, $this->_TIDY_CONFIG ?: $tidy_default_config, conf('charset'));
        $tidy->cleanRepair();
        return $tidy;
    }

    /**
     * @param mixed $text
     */
    public function _debug_mode_callback($text = '')
    {
        if ( ! DEBUG_MODE) {
            return $text;
        }
        return preg_replace_callback('~(<title>)(.*?)(</title>)~ims', function ($m) {
            return $m[1] . strip_tags($m[2]) . $m[3];
        }, $text);
    }

    /**
     * Custom filter (Inherit this method and customize anything you want).
     * @param mixed $stpl_name
     */
    public function _custom_filter($stpl_name, &$replace)
    {
        if (($stpl_name ?? false) && $stpl_name == 'home_page/main') {
            // example only:
            // print_r($replace);
            // $replace['recent_ads'] = '';
        }
    }

    /**
     * Wrapper function for t/translate/i18n calls inside templates.
     * @param mixed $input
     * @param mixed $replace
     */
    public function _i18n_wrapper($input = '', $replace = [])
    {
        if ( ! strlen($input)) {
            return '';
        }
        $input = stripslashes(trim($input, '"\''));
        $args = [];
        // Complex case with substitutions
        if (preg_match('/(?P<text>.+?)["\']{1},[\s\t]*%(?P<args>[a-z]+.+)$/ims', $input, $m)) {
            foreach (explode(';%', $m['args']) as $arg) {
                $attr_name = $attr_val = '';
                if (false !== strpos($arg, '=')) {
                    list($attr_name, $attr_val) = explode('=', trim($arg));
                }
                $attr_name = trim(str_replace(["'", '"'], '', $attr_name));
                $attr_val = trim(str_replace(["'", '"'], '', $attr_val));
                $args['%' . $attr_name] = $attr_val;
            }
            $text_to_translate = $m['text'];
        } else {
            $text_to_translate = $input;
        }
        $output = t($text_to_translate, $args);
        // Do replacement of the template vars on the last stage
        // example: @replace1 will be got from $replace['replace1'] array item
        if (false !== strpos($output, '@') && ! empty($replace)) {
            $r = [];
            foreach ((array) $replace as $k => $v) {
                $r['@' . $k] = $v;
            }
            $output = str_replace(array_keys($r), array_values($r), $output);
        }
        return $output;
    }

    /**
     * Wrapper for translation method (for call from templates or other).
     * @param mixed $string
     * @param mixed $args_from_tpl
     * @param mixed $lang
     */
    public function _translate_for_stpl($string = '', $args_from_tpl = '', $lang = '')
    {
        $args = [];
        if (is_string($args_from_tpl) && strlen($args_from_tpl)) {
            $args = _attrs_string2array($args_from_tpl);
        }
        return t($string, $args, $lang);
    }

    /**
     * Wrapper around 'url()' function, called like this inside templates:
     * {url(object=home_page;action=test)}.
     * @param mixed $params
     */
    public function _url_wrapper($params = [])
    {
        // Try to process method params (string like attrib1=value1;attrib2=value2)
        if (is_string($params) && strlen($params)) {
            // Url like this: /object/action/id
            if ($params[0] == '/') {
                // Do nothing, just directly pass this to url() as string
            } elseif (false !== strpos($params, '=')) {
                $params = _attrs_string2array($params);
            } else {
                list($object, $action, $id, $page) = explode(';', str_replace(',', ';', $params));
                $params = [
                    'object' => $object,
                    'action' => $action,
                    'id' => $id,
                    'page' => $page,
                ];
            }
        }
        return url($params);
    }

    /**
     * Replace paths to images.
     * @param mixed $string
     */
    public function _replace_images_paths($string = '')
    {
        $images_path = (MAIN_TYPE_USER ? $this->MEDIA_PATH : ADMIN_WEB_PATH) . $this->TPL_PATH . $this->_IMAGES_PATH;
        $uploads_path = $this->MEDIA_PATH . $this->_UPLOADS_PATH;
        $r = [
            '"images/' => '"' . $images_path,
            '\'images/' => '\'' . $images_path,
            'src="uploads/' => 'src="' . $uploads_path,
            '"uploads/' => '"' . $uploads_path,
            '\'uploads/' => '\'' . $uploads_path,
        ];
        return str_replace(array_keys($r), array_values($r), $string);
    }

    /**
     * @param mixed $text
     * @param mixed $filters
     */
    public function _process_var_filters($text = '', $filters = '')
    {
        if (is_string($filters) && strpos($filters, '|') !== false) {
            $filters = explode('|', $filters);
        }
        if ( ! is_array($filters)) {
            $filters = [$filters];
        }
        foreach ($filters as $fname) {
            if (is_callable($fname)) {
                $text = $fname($text);
            } elseif (is_string($fname) && function_exists($fname)) {
                $text = $fname($text);
            }
        }
        return $text;
    }

    /**
     * @param mixed $name
     * @param mixed $func
     * @param mixed $params
     */
    public function add_function_callback($name, $func, $params = [])
    {
        $pattern = '/\{(?P<name>' . preg_quote($name) . ')\(\s*["\']{0,1}(?P<args>[a-z0-9_:\/\.]+?)["\']{0,1}\s*\)\}/ims';
        if ($params['only_pattern']) {
            return $pattern;
        }
        $this->add_pattern_callback($pattern, $func, $name);
    }

    /**
     * @param mixed $name
     * @param mixed $func
     * @param mixed $params
     */
    public function add_section_callback($name, $func, $params = [])
    {
        $pattern = '/\{(?P<name>' . preg_quote($name) . ')\(\s*["\']{0,1}(?P<args>[^"\'\)\}]*?)["\']{0,1}\s*\)\}\s*(?P<body>.+?)\s*{\/(\1)\}/ims';
        if ($params['only_pattern']) {
            return $pattern;
        }
        $this->add_pattern_callback($pattern, $func, $name);
    }

    /**
     * @param mixed $pattern
     * @param mixed $func
     * @param mixed $pattern_name
     */
    public function add_pattern_callback($pattern, $func, $pattern_name = '')
    {
        $this->_custom_patterns_funcs[$pattern] = $func;
        $this->_custom_patterns_index[crc32($pattern)] = $pattern;
        $pattern_name && $this->_custom_patterns_index[$pattern_name] = $pattern;
    }
}
