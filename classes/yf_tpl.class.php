<?php

/**
* Stadard Framework template engine
*
* @package	  YF
* @author	  YFix Team <yfix.dev@gmail.com>
* @version	  1.0
*/
class yf_tpl {

	/** @var string @conf_skip Path to the templates (including current theme path) */
	public $TPL_PATH			   = '';
	/** @var bool Compressing output by cutting '\t','\r','\n','  ','   ' */
	public $COMPRESS_OUTPUT		= false;
	/** @var bool Using SEO - friendly URLs (All links need to be absolute) */
	public $REWRITE_MODE		   = false;
	/** @var bool Custom meta information (customizable for every page) : page titles, meta keywords, description */
	public $CUSTOM_META_INFO	   = false;
	/** @var bool Exit after sending main content */
	public $EXIT_AFTER_ECHO		= false;
	/** @var bool Use database to store templates */
	public $GET_STPLS_FROM_DB	  = false;
	/** @var bool SECURITY: allow or not eval php code (with _PATTERN_INCLUDE) */
	public $ALLOW_EVAL_PHP_CODE	= true;
	/** @var array @conf_skip
		For '_process_conditions',
		Will be availiable in conditions with such form: {if('get.object' eq 'login_form')} Hello from login form {/if}
	*/
	public $_avail_arrays	  = array(
		'get'	   => '_GET',
		'post'	  => '_POST',
	);
	/** @var bool Get all templates from db or not (1 query or multiple)
	*   (NOTE: If true - Slow PHP processing but just 1 db query)
	*/
	public $FROM_DB_GET_ALL		= false;
	/** @var array @conf_skip Temporary storage for all templates parsed from db */
	public $_TMP_FROM_DB	   = null;
	/** @var array @conf_skip Array of output filters (will be called just before throwing output to user) */
	public $_OUTPUT_FILTERS	= array();
	/** @var bool Catch any output before gzipped content (works only with GZIP) */
	public $_OB_CATCH_CONTENT  = true;
	/** @var bool Use or not Tidy to cleanup output */
	public $TIDY_OUTPUT		= false;
	/** @var array Configuration for Tidy */
	public $_TIDY_CONFIG	   = array(
		'alt-text'	  => '',
		'output-xhtml'  => true,
	);
	/** @var bool Use backtrace to get STPLs source (where called from) FOR DEBUG_MODE ONLY ! */
	public $USE_SOURCE_BACKTRACE	   = true;
	/** @var bool If available - use packed STPLs without checking if some exists in project */
	public $AUTO_LOAD_PACKED_STPLS	 = false;
	/** @var bool Allow custom filter for all parsed stpls */
	public $ALLOW_CUSTOM_FILTER		= false;
	/** @var bool Allow language-based special stpls */
	public $ALLOW_LANG_BASED_STPLS	 = false;
	/** @var bool Allow inline debug */
	public $ALLOW_INLINE_DEBUG		 = false;
	/** @var bool Allow skin inheritance (only one level used) */
	public $ALLOW_SKIN_INHERITANCE	 = true;
	/** @var bool Allow to compile templates */
	public $COMPILE_TEMPLATES		  = false;
	/** @var bool Compile templates folder */
	public $COMPILED_DIR			   = 'stpls_compiled/';
	/** @var bool TTL for compiled stpls */
	public $COMPILE_TTL				= 3600;
	/** @var bool TTL for compiled stpls */
	public $COMPILE_CHECK_STPL_CHANGED = false;
	/** @var bool Allow pure php templates */
	public $ALLOW_PHP_TEMPLATES		= false;
	/** @var bool Use paths cache (check and save what stpl files we have and where) */
	public $USE_PATHS_CACHE			= false;
	/** @var bool */
	public $DEBUG_STPL_VARS			= false;
	/** @var string @conf_skip */
	public $_STPL_EXT		  = '.stpl';
	/** @var string @conf_skip */
	public $_THEMES_PATH	   = 'templates/';
	/** @var string @conf_skip */
	public $_IMAGES_PATH	   = 'images/';
	/** @var string @conf_skip */
	public $_UPLOADS_PATH	  = 'uploads/';
	/** @var array Global scope tags (included in any parsed template) */
	public $_global_tags	   = array();
	/** @var STPL location codes (binary for less memory) */
	public $_stpl_loc_codes = array(
		'site'				=> 1,
		'project'			=> 2,
		'framework'			=> 4,
		'framework_user'	=> 8,
		'user_section'		=> 16,
		'inherit_project'   => 32,
		'lang_project'		=> 64,
		'inherit_project2'	=> 128,
	);
	/** @var string Current tempalte engine dirver to use */
	public $DRIVER_NAME = 'yf';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Framework constructor
	*/
	function _init () {
		if (defined('IS_FRONT')) {
			conf('IS_FRONT', (bool)IS_FRONT);
		}
		$this->IS_FRONT = (bool)conf('IS_FRONT');
		// Set custom skin
		if (!empty($_SESSION['user_skin']) && MAIN_TYPE_USER) {
			conf('theme', $_SESSION['user_skin']);
		} elseif (defined('DEFAULT_SKIN')) {
			conf('theme', DEFAULT_SKIN);
		}
		if (!conf('theme')) {
			conf('theme', MAIN_TYPE);
		}
		// Directory where themes are stored
		conf('THEMES_PATH', $this->_THEMES_PATH);
		// Template files extensions
		conf('_STPL_EXT',   $this->_STPL_EXT);
		// Set path to the templates including selected skin
		$this->TPL_PATH = $this->_THEMES_PATH. conf('theme'). '/';

		if ($this->COMPRESS_OUTPUT) {
			$this->register_output_filter(array($this, '_simple_cleanup_callback'), 'simple_cleanup');
		}
		if ($this->ALLOW_LANG_BASED_STPLS) {
			$this->_lang_theme_path = PROJECT_PATH. $this->_THEMES_PATH. conf('theme'). '.'.conf('language').'/';
			if (!file_exists($this->_lang_theme_path)) {
				$this->ALLOW_LANG_BASED_STPLS = false;
				$this->_lang_theme_path = '';
			}
		}
		if ($this->ALLOW_SKIN_INHERITANCE) {
			if (defined('INHERIT_SKIN')) {
				conf('INHERIT_SKIN', INHERIT_SKIN);
			}
			if (conf('INHERIT_SKIN') != conf('theme')) {
				$this->_INHERITED_SKIN = conf('INHERIT_SKIN');
			}
			if (defined('INHERIT_SKIN2')) {
				conf('INHERIT_SKIN2', INHERIT_SKIN2);
			}
			if (conf('INHERIT_SKIN2') != conf('theme')) {
				$this->_INHERITED_SKIN2 = conf('INHERIT_SKIN2');
			}
		}
		if (isset($_SESSION['force_gzip'])) {
			main()->OUTPUT_GZIP_COMPRESS = $_SESSION['force_gzip'];
		}
		// Turn off CPU expensive features on overloading
		if (conf('HIGH_CPU_LOAD') == 1) {
			main()->OUTPUT_GZIP_COMPRESS = false;
			$this->COMPRESS_OUTPUT  = false;
			$this->TIDY_OUTPUT		= false;
			$this->FROM_DB_GET_ALL  = false;
		}
		// Force inline debug setting
		if (isset($_SESSION['stpls_inline_edit'])) {
			$this->ALLOW_INLINE_DEBUG = intval((bool)$_SESSION['stpls_inline_edit']);
		}
		$this->_init_global_tags();

		if ($this->USE_PATHS_CACHE) {
			$this->_prepare_paths_cache();
		}
		if (DEBUG_MODE) {
			$this->register_output_filter(array($this, '_debug_mode_callback'), 'debug_mode');
		}
		if (main()->CONSOLE_MODE) {
			$this->_OB_CATCH_CONTENT = false;
		}
		$this->_set_default_driver($this->DRIVER_NAME);
	}

	/**
	*/
	function _set_default_driver ($name = '') {
		if (!$name) {
			$name = $this->DRIVER_NAME;
		}
		if (!$name) {
			$name = 'yf';
		}
		$this->DRIVER_NAME = $name;
		$this->driver = _class('tpl_driver_'.$name, 'classes/tpl/');
	}

	/**
	* Global scope tags
	*/
	function _init_global_tags () {
		$data = array(
			'is_logged_in'  => intval((bool) main()->USER_ID),
			'is_spider'     => (int)conf('IS_SPIDER'),
			'is_https'      => isset($_SERVER['HTTPS']) || isset($_SERVER['SSL_PROTOCOL']) ? 1 : 0,
			'site_id'       => (int)conf('SITE_ID'),
			'lang_id'       => conf('language'),
			'debug_mode'    => (int)((bool)DEBUG_MODE),
			'tpl_path'      => MEDIA_PATH. $this->TPL_PATH,
		);
		foreach ($data as $k => $v) {
			$this->_global_tags[$k] = $v;
		}
	}

	/**
	* Initialization of the main content
	* Throws one 'echo' at the end
	*/
	function init_graphics () {
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
		if (!main()->NO_GRAPHICS) {
			if ($this->_OB_CATCH_CONTENT) {
				ob_start();
			}
			// Trying to get default task
			if ($init_type == 'user' && !empty($_SESSION['user_id']) && !empty($_SESSION['user_group'])) {
				$go = conf('default_page_user');
			} elseif ($init_type == 'admin') {
				$go = conf('default_page_admin');
			}
			// If setting exists - assign it to the location
			if (!empty($go) && empty($_GET['object'])) {
				$go = str_replace(array('./?','./'), '', $go);
				$tmp_array = array();
				parse_str($go, $tmp_array);
				foreach ((array)$tmp_array as $k => $v) {
					$_GET[$k] = $v;
				}
			}
			$skip_prefetch = false;
			// Determine what template need to be loaded in the center area
			$tpl_name = 'main';
			if ($init_type == 'admin' && (empty($_SESSION['admin_id']) || empty($_SESSION['admin_group']))) {
				$tpl_name = 'login';
				if (!main()->CONSOLE_MODE) {
					$skip_prefetch = true;
				}
			}
			if ($this->GET_STPLS_FROM_DB && $this->FROM_DB_GET_ALL) {
				$Q = db()->query('SELECT name,text FROM '.db('templates').' WHERE theme_name="'.conf('theme').'" AND active="1"');
				while ($A = db()->fetch_assoc($Q)) {
					$this->_TMP_FROM_DB[$A['name']] = stripslashes($A['text']);
				}
			}
			if (DEBUG_MODE && $this->ALLOW_INLINE_DEBUG || main()->INLINE_EDIT_LOCALE) {
				conf('inline_js_edit', true);
			}
			if (!$skip_prefetch) {
				if (main()->CONSOLE_MODE) {
					// Skip security checks for console mode
					_class('core_blocks')->tasks(false);
				} else {
					_class('core_blocks')->prefetch_center();
				}
			}
		}
		if (!main()->NO_GRAPHICS) {
			$body['content'] = $this->_init_main_stpl($tpl_name);
			$this->_CENTER_RESULT = '';
			if ($this->CUSTOM_META_INFO && $init_type == 'user') {
				$this->register_output_filter(array($this, '_custom_replace_callback'), 'custom_replace');
			}
			if ($init_type == 'user' && _class('graphics')->IFRAME_CENTER && (false === strpos($_SERVER['QUERY_STRING'], 'center_area=1'))) {
				$this->register_output_filter(array($this, '_replace_for_iframe_callback'), 'replace_for_iframe');
			}
		}
		if (!main()->NO_GRAPHICS) {
			// Replace images paths with their absolute ones
			if ($this->REWRITE_MODE && $init_type != 'admin') {
				$this->register_output_filter(array($this, '_rewrite_links_callback'), 'rewrite_links');
			}
			if ($this->TIDY_OUTPUT && $init_type != 'admin') {
				$this->register_output_filter(array($this, '_tidy_cleanup_callback'), 'tidy_cleanup');
			}

			$body['content'] = $this->_apply_output_filters($body['content']);

			if (main()->OUTPUT_GZIP_COMPRESS && !conf('no_gzip')) {
				if ($this->_OB_CATCH_CONTENT && ob_get_level()) {
					$old_content = ob_get_contents();
					ob_end_clean();
				}
				ob_start('ob_gzhandler');
				conf('GZIP_ENABLED', true);
				if ($this->_OB_CATCH_CONTENT) {
					$body['content'] = $old_content.$body['content'];
				}
				// Count number of compressed bytes (not exactly accurate)
				if (DEBUG_MODE) {
					debug('gzip_page::size_original', strlen($body['content']));
					debug('gzip_page::size_gzipped', strlen(gzencode($body['content'], 3, FORCE_GZIP)));
				}
			}
			if (main()->OUTPUT_CACHING && $init_type == 'user' && $_SERVER['REQUEST_METHOD'] == 'GET') {
				_class('output_cache')->_put_page_to_output_cache($body);
			}
#			if (DEBUG_MODE || conf('exec_time')) {
#				$body['exec_time'] = common()->_show_execution_time();
#			}
			if (DEBUG_MODE && !main()->CONSOLE_MODE && !main()->is_ajax()) {
				$body['debug_info'] = common()->show_debug_info();
				if ($this->ALLOW_INLINE_DEBUG || main()->INLINE_EDIT_LOCALE) {
					$body['debug_info'] .= $this->parse('system/js_inline_editor');
				}
				$_last_pos = strpos($body['content'], '</body>');
				if ($_last_pos) {
					$body['content'] = substr($body['content'], 0, $_last_pos). $body['exec_time']. $body['debug_info']. '</body></html>';
					$body['debug_info'] = '';
					$body['exec_time']  = '';
				}
			}
			$output = implode('', $body);
			$this->_output_body_length = strlen($output);
			main()->_send_main_headers($this->_output_body_length);
			// Throw generated output to user
			echo $output;
		}
		if (DEBUG_MODE && main()->NO_GRAPHICS && !main()->CONSOLE_MODE && !main()->is_ajax()) {
#			echo common()->_show_execution_time();
			echo common()->show_debug_info();
		}
		// Output cache for 'no graphics' content
		if (main()->NO_GRAPHICS && main()->OUTPUT_CACHING && $init_type == 'user' && $_SERVER['REQUEST_METHOD'] == 'GET') {
			_class('output_cache')->_put_page_to_output_cache(ob_get_contents());
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

	/**
	* Process output filters for the given text
	*/
	function _apply_output_filters ($text = '') {
		foreach ((array)$this->_OUTPUT_FILTERS as $cur_filter) {
			if (is_callable($cur_filter)) {
				$text = call_user_func($cur_filter, $text);
			}
		}
		return $text;
	}

	/**
	* Initialization of the main template in the theme (could be overwritten to match design)
	* Return contents of the main template
	*/
	function _init_main_stpl ($tpl_name = '') {
		return $this->parse($tpl_name);
	}

	/**
	* Simple template parser (*.stpl)
	*/
	function parse($name, $replace = array(), $params = array()) {
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
		if (!is_array($params)) {
			$params = array();
		}
		$string = $params['string'] ?: false;
		$params['replace_images'] = $params['replace_images'] ?: true;
		$params['no_cache'] = $params['no_cache'] ?: false;
		$params['get_from_db'] = $params['get_from_db'] ?: false;
		$params['no_include'] = $params['no_include'] ?: false;
		if (DEBUG_MODE) {
			$stpl_time_start = microtime(true);
		}
		$replace = (array)$replace + (array)$this->_global_tags;
		$replace['error'] = $this->_parse_get_user_errors($name, $replace['error']);
		if (isset($replace[''])) {
			unset($replace['']);
		}
		if ($this->ALLOW_CUSTOM_FILTER) {
			$this->_custom_filter($name, $replace);
		}
		// Allowing to override driver
		if ($params['driver'] && $params['driver'] != $this->DRIVER_NAME) {
			$string = _class('tpl_driver_'.$params['driver'], 'classes/tpl/')->parse($name, $replace, $params);
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
	* Wrapper to parse given template string
	*/
	function parse_string($string = '', $replace = array(), $name = '', $params = array()) {
		if (!strlen($string)) {
			$string = ' ';
		}
		if (!$name) {
			$name = 'auto__'.abs(crc32($string));
		}
		$params['string'] = $string;
		return $this->parse($name, $replace, $params);
	}

	/**
	* Wrapper on parse(), silently failing if template not exists
	*/
	function parse_if_exists($name, $replace = array(), $params = array()) {
		return $this->exists($name) ? $this->parse($name, $replace, $params) : '';
	}

	/**
	*/
	function _parse_get_user_errors($name, $err) {
		if (isset($err)) {
			return $err;
		}
		$err = '';
		if ($name != 'main' && common()->_error_exists()) {
			if (!isset($this->_user_error_msg)) {
				$this->_user_error_msg = common()->_show_error_message('', false);
			}
			$err = $this->_user_error_msg;
		}
		return $err;
	}

	/**
	*/
	function _parse_set_debug_info($name = '', $replace = array(), $params = array(), $string = '', $stpl_time_start) {
		if (!DEBUG_MODE) {
			return false;
		}
		if (!isset($this->driver->CACHE[$name]['exec_time'])) {
			$this->driver->CACHE[$name]['exec_time'] = 0;
		}
		$this->driver->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
		// For debug store information about variables used while processing template
		if ($this->DEBUG_STPL_VARS) {
			debug('STPL_REPLACE_VARS::'.$name.'[]', $replace);
		}
		if ($this->USE_SOURCE_BACKTRACE) {
			debug('STPL_TRACES::'.$name, main()->trace_string());
		}
		if ($this->ALLOW_INLINE_DEBUG && strlen($string) > 20 && !in_array($name, array('main', 'system/debug_info', 'system/js_inline_editor')) ) {
			if (preg_match('/^<([^>]*?)>/ims', ltrim($string), $m)) {
				$string = '<'.$m[1].' stpl_name="'.$name.'">'.substr(ltrim($string), strlen($m[0]));
			}
		}
		return true;
	}

	/**
	* Alias
	*/
	function exists ($stpl_name = '', $get_from_db = false) {
		return (bool)$this->_stpl_exists($stpl_name, $get_from_db);
	}

	/**
	* Check if template exists (simple wrapper for the '_get_template_file')
	*/
	function _stpl_exists ($stpl_name = '', $get_from_db = false) {
		return (bool)$this->_get_template_file($stpl_name, $get_from_db, 1);
	}

	/**
	* Alias
	*/
	function get ($file_name = '', $get_from_db = false, $JUST_CHECK_IF_EXISTS = false, $RETURN_TEMPLATE_PATH = false) {
		return $this->_get_template_file($file_name, $get_from_db, $JUST_CHECK_IF_EXISTS, $RETURN_TEMPLATE_PATH);
	}

	/**
	* Read template file contents (or get it from DB)
	*/
	function _get_template_file ($file_name = '', $get_from_db = false, $JUST_CHECK_IF_EXISTS = false, $RETURN_TEMPLATE_PATH = false) {
		$string	 = false;
		$NOT_FOUND  = false;
		$storage	= 'inline';
		// Support for the framework calls
		$l = strlen(YF_PREFIX);
		if (substr($file_name, 0, $l) == YF_PREFIX) {
			$file_name = substr($file_name, $l);
		}
		$class_name = '';
		if (false !== strpos($file_name, '/')) {
			$class_name = current(explode('/', $file_name));
		}
		$file_name  .= $this->_STPL_EXT;
		// Fix double extesion
		$file_name  = str_replace($this->_STPL_EXT.$this->_STPL_EXT, $this->_STPL_EXT, $file_name);
		$stpl_name  = str_replace($this->_STPL_EXT, '', $file_name);
		if ($this->GET_STPLS_FROM_DB || $get_from_db) {
			if ($this->FROM_DB_GET_ALL) {
				if (!empty($this->_TMP_FROM_DB[$stpl_name])) {
					$string = $this->_TMP_FROM_DB[$stpl_name];
					unset($this->_TMP_FROM_DB[$stpl_name]);
				} else {
					$NOT_FOUND = true;
				}
			} else {
				$text = db()->get_one('SELECT text FROM '.db('templates').' WHERE theme_name="'.conf('theme').'" AND name="'._es($stpl_name).'" AND active="1"');
				if (isset($text)) {
					$string = stripslashes($text);
				} else {
					$NOT_FOUND = true;
				}
			}
			$storage = 'db';
		} else {
			if (!isset($this->_yf_plugins)) {
				$this->_yf_plugins = main()->_preload_plugins_list();
				$this->_yf_plugins_classes = main()->_plugins_classes;
			}
			// Storages are defined in specially crafted `order`, so do not change it unless you have strong reason
			$storages = array();
			$site_path = (MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH);
			$dev_path = '.dev/'.main()->HOSTNAME.'/';
			// Developer overrides
			if (conf('DEV_MODE')) {
				if ($site_path && $site_path != PROJECT_PATH) {
					$storages['dev_site']   = $site_path. $dev_path. $this->TPL_PATH. $file_name;
				}
				$storages['dev_project']	= PROJECT_PATH. $dev_path. $this->TPL_PATH. $file_name;
			}
			if ($this->ALLOW_LANG_BASED_STPLS) {
				$storages['lang_project']   = $this->_lang_theme_path. $file_name;
			}
			if ($site_path && $site_path != PROJECT_PATH) {
				$storages['site']		   = $site_path. $this->TPL_PATH. $file_name;
			}
			$storages['project']			= PROJECT_PATH. $this->TPL_PATH. $file_name;
			if ($this->_INHERITED_SKIN) {
				$storages['inherit_project']= PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN. '/'. $file_name;
			}
			if ($this->_INHERITED_SKIN2) {
				$storages['inherit_project2']= PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN2. '/'. $file_name;
			}
			$storages['framework']		  = YF_PATH. $this->_THEMES_PATH. MAIN_TYPE.'/'. $file_name;
			$storages['framework_p2']	   = YF_PATH. 'priority2/'. $this->_THEMES_PATH. MAIN_TYPE.'/'. $file_name;
			// user section within admin
			if (MAIN_TYPE_ADMIN) {
				$storages['user_section']	   = PROJECT_PATH. $this->_THEMES_PATH. $this->_get_def_user_theme(). '/'. $file_name;
				$storages['framework_user']	 = YF_PATH. $this->_THEMES_PATH. 'user/'. $file_name;
				$storages['framework_user_p2']  = YF_PATH. 'priority2/'. $this->_THEMES_PATH. 'user/'. $file_name;
			}
			// Load template from plugins. Should stay in subdir like this:  
			// YF_PATH.'plugins/news/templates/user/news/main.stpl' => tpl()->parse('news/main')
			if ($class_name && (isset($this->_yf_plugins[$class_name]) || isset($this->_yf_plugins_classes[$class_name]))) {
				if (isset($this->_yf_plugins[$class_name])) {
					$plugin_subdir = 'plugins/'.$class_name.'/';
				} else {
					$plugin_subdir = 'plugins/'.$this->_yf_plugins_classes[$class_name].'/';
				}
				$storages['plugins_project'] = PROJECT_PATH. $plugin_subdir. $this->TPL_PATH. $file_name;
				if ($this->_INHERITED_SKIN) {
					$storages['plugins_inherit_project'] = PROJECT_PATH. $plugin_subdir. $this->_THEMES_PATH. $this->_INHERITED_SKIN. '/'. $file_name;
				}
				if ($this->_INHERITED_SKIN2) {
					$storages['plugins_inherit_project2'] = PROJECT_PATH. $plugin_subdir. $this->_THEMES_PATH. $this->_INHERITED_SKIN2. '/'. $file_name;
				}
				$storages['plugins_framework'] = YF_PATH. $plugin_subdir. $this->TPL_PATH. $file_name;
				if (MAIN_TYPE_ADMIN) {
					$storages['plugins_user_section']	 = PROJECT_PATH. $plugin_subdir. $this->_THEMES_PATH. $this->_get_def_user_theme(). '/'. $file_name;
					$storages['plugins_framework_user']	 = YF_PATH. $plugin_subdir. $this->_THEMES_PATH. 'user/'. $file_name;
				}
			}
			// Try storages one-by-one in inheritance `order`, stop when found
			$storage = '';
			foreach ((array)$storages as $_storage => $file_path) {
				if (!$this->_stpl_path_exists($file_path, $stpl_name, $_storage)) {
					continue;
				}
				$string = file_get_contents($file_path);
				if ($string !== false) {
					$storage = $_storage;
					break;
				}
			}
			// Last try from cache (preloaded templates)
			if ($string === false) {
				$compiled_stpl = conf('_compiled_stpls::'.$stpl_name);
// TODO: maybe move this uppper to have much more inheritance priority
				if ($compiled_stpl) {
					$string	 = $compiled_stpl;
					$storage = 'compiled_cache';
				}
			}
			if ($string === false) {
				$NOT_FOUND = true;
			}
		}
		if ($RETURN_TEMPLATE_PATH) {
			return $file_path;
		}
		// If we just checking template existance - then stop here
		if ($JUST_CHECK_IF_EXISTS) {
			return !$NOT_FOUND;
		}
		// Log error message if template file was not found
		if ($NOT_FOUND) {
			trigger_error('STPL: template "'.$file_name.'" in theme "'.conf('theme').'" not found.', E_USER_WARNING);
		} else {
			$this->driver->CACHE[str_replace($this->_STPL_EXT, '', $file_name)]['storage'] = $storage;
		}
		return $string;
	}

	/**
	* Get default user theme (for admin section)
	*/
	function _get_def_user_theme () {
		if (!empty($this->_def_user_theme)) {
			return $this->_def_user_theme;
		}
		$SITES_INFO = _class('sites_info', 'classes/')->info;
		$FIRST_SITE_INFO = array_shift($SITES_INFO);
		if (file_exists(PROJECT_PATH. $this->_THEMES_PATH. $FIRST_SITE_INFO['DEFAULT_SKIN']. '/')) {
			$this->_def_user_theme = $FIRST_SITE_INFO['DEFAULT_SKIN'];
		}
		if (empty($this->_def_user_theme)) {
			$this->_def_user_theme = 'new_1';
		}
		return $this->_def_user_theme;
	}

	/**
	* Check if given template exists
	*/
	function _stpl_path_exists ($file_name = '', $stpl_name = '', $location = '') {
		if ($this->USE_PATHS_CACHE) {
			if ($this->_stpls_paths_cache[$stpl_name] & $this->_stpl_loc_codes[$location]) {
				return true;
			}
			return false;
		} else {
			return file_exists($file_name);
		}
	}

	/**
	* Prepare paths cache
	*/
	function _prepare_paths_cache () {
		if (!$this->USE_PATHS_CACHE || $this->_stpls_paths_cache) {
			return false;
		}
		$stpls_paths = array();
		$CACHE_NAME = 'stpls_paths_'.(MAIN_TYPE_ADMIN ? 'admin' : 'site_'.conf('SITE_ID'));
		$stpls_paths = cache_get($CACHE_NAME);
		// Create full array (cache is empty or turned off)
		if (empty($stpls_paths)) {
			if (MAIN_TYPE_ADMIN) {
				$def_user_theme = $this->_get_def_user_theme();
				$paths = array(
					'framework'	 	=> YF_PATH. $this->_THEMES_PATH. 'admin'. '/',
					'framework_user'=> YF_PATH. $this->_THEMES_PATH. 'user'. '/',
					'user_section'  => INLCUDE_PATH. $this->_THEMES_PATH. $def_user_theme. '/',
				);
			} else {
				$paths = array(
					'site'		 		=> SITE_PATH. $this->_THEMES_PATH. conf('theme'). '/',
					'project'	  		=> PROJECT_PATH. $this->_THEMES_PATH. conf('theme'). '/',
					'framework'		 	=> YF_PATH. $this->_THEMES_PATH. 'user'. '/',
					'inherit_project'	=> $this->_INHERITED_SKIN ? PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN. '/'. $file_name : '',
					'inherit_project2'	=> $this->_INHERITED_SKIN2 ? PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN2. '/'. $file_name : '',
				);
			}
			$ext_length = strlen($this->_STPL_EXT);
			// Process paths
			foreach ((array)$paths as $_location => $_path) {
				if (empty($_path)) {
					continue;
				}
				$_path_length = strlen($_path);
				foreach ((array)_class('dir')->scan_dir($_path, 1, array('', '/\.stpl$/i'), '/(svn|git)/') as $_cur_path) {
					$_cur_path = substr($_cur_path, $_path_length, -$ext_length);
					if ($_cur_path) {
						$stpls_paths[$_cur_path] += $this->_stpl_loc_codes[$_location];
					}
				}
			}
			ksort($stpls_paths);
			cache_set($CACHE_NAME, $stpls_paths);
		}
		$this->_stpls_paths_cache = $stpls_paths;
	}

	/**
	*/
	function _process_clear_unused($string, $replace = array(), $name = '') {
		// If content need to be cleaned from unused tags - do that
		return preg_replace('/\{[\w_]+\}/i', '', $string);
	}

	/**
	*/
	function _process_eval_string($string, $replace = array(), $name = '') {
		eval('$string = "'.str_replace('"', '\"', $string).'";');
		return $string;
	}

	/**
	* Registers custom function to be used in templates
	*/
	function register_output_filter($callback_impl, $filter_name = '') {
		if (empty($filter_name)) {
			$filter_name = substr(abs(crc32(microtime(true))),0,8);
		}
		$this->_OUTPUT_FILTERS[$filter_name] = $callback_impl;
	}

	/**
	* Simple cleanup (compress) output
	*/
	function _simple_cleanup_callback ($text = '') {
		if (DEBUG_MODE) {
			debug('compress_output::size_original', strlen($text));
		}
		$text = str_replace(array("\r","\n","\t"), '', $text);
		$text = preg_replace('#[\s]{2,}#ms', ' ', $text);
		// Remove comments
		$text = preg_replace('#<\!--[\w\s\-\/]*?-->#ms', '', $text);
		if (DEBUG_MODE) {
			debug('compress_output::size_compressed', strlen($text));
		}
		return $text;
	}

	/**
	* Custom text replacing method
	*/
	function _custom_replace_callback ($text = '') {
		return _class('custom_meta_info')->_process($text);
	}

	/**
	* Replace method for 'IFRAME in center' mode
	*/
	function _replace_for_iframe_callback ($text = '') {
		return module('rewrite')->_replace_links_for_iframe($text);
	}

	/**
	* Rewrite links callback method
	*/
	function _rewrite_links_callback ($text = '') {
		return module('rewrite')->_rewrite_replace_links($text);
	}

	/**
	* Clenup HTML output with Tidy
	*/
	function _tidy_cleanup_callback ($text = '') {
		if (!class_exists('tidy') || !extension_loaded('tidy')) {
			return $text;
		}
		// Tidy
		$tidy = new tidy;
		$tidy->parseString($text, $this->_TIDY_CONFIG, conf('charset'));
		$tidy->cleanRepair();
		// Output
		return $tidy;
	}

	/**
	*/
	function _debug_mode_callback ($text = '') {
		if (!DEBUG_MODE) {
			return $text;
		}
		$p = "<span class='locale_tr' s_var='[^\']+?'>([^<]+?)<\/span>";
		$text = preg_replace("/(<title>)(.*?)(<\/title>)/imse", "'\\1'.strip_tags('\\2').'\\3'", $text);
		// Output
		return $text;
	}

	/**
	* Custom filter (Inherit this method and customize anything you want)
	*/
	function _custom_filter ($stpl_name = '', &$replace) {
		if ($stpl_name == 'home_page/main') {
			// example only:
			//print_r($replace);
			//$replace['recent_ads'] = '';
		}
	}

	/**
	* Wrapper function for t/translate/i18n calls inside templates
	*/
	function _i18n_wrapper ($input = '', $replace = array()) {
		if (!strlen($input)) {
			return '';
		}
		$input = stripslashes(trim($input, '"\''));
		$args = array();
		// Complex case with substitutions
		if (preg_match('/(?P<text>.+?)["\']{1},[\s\t]*%(?P<args>[a-z]+.+)$/ims', $input, $m)) {
			foreach (explode(';%', $m['args']) as $arg) {
				$attr_name = $attr_val = '';
				if (false !== strpos($arg, '=')) {
					list($attr_name, $attr_val) = explode('=', trim($arg));
				}
				$attr_name  = trim(str_replace(array("'",'"'), '', $attr_name));
				$attr_val   = trim(str_replace(array("'",'"'), '', $attr_val));
				$args['%'.$attr_name] = $attr_val;
			}
			$text_to_translate = $m['text'];
		} else {
			// Easy case that just needs to be translated
			$text_to_translate = $input;
		}
		$output = t($text_to_translate, $args);
		// Do replacement of the template vars on the last stage
		// example: @replace1 will be got from $replace['replace1'] array item
		if (false !== strpos($output, '@') && !empty($replace)) {
			$r = array();
			foreach ((array)$replace as $k => $v) {
				$r['@'.$k] = $v;
			}
			$output = str_replace(array_keys($r), array_values($r), $output);
		}
		return $output;
	}

	/**
	* Wrapper for translation method (for call from templates or other)
	*/
	function _translate_for_stpl ($string = '', $args_from_tpl = '', $lang = '') {
		$args = array();
		// Try to convert args
		if (is_string($args_from_tpl) && strlen($args_from_tpl)) {
			$args = _attrs_string2array($args_from_tpl);
		}
		return t($string, $args, $lang);
	}

	/**
	* Search template for the string that caused an error
	*/
	function _search_stpl_line ($class_name, $method_name, $method_params = '', $tpl_name) {
		// Search in site
		$stpl_file	= SITE_PATH. tpl()->TPL_PATH. $tpl_name;
		// Search in project
		if (!file_exists($stpl_file)) {
			$stpl_file = PROJECT_PATH. tpl()->TPL_PATH. $tpl_name;
		}
		// Search in framework
		if (!file_exists($stpl_file)) {
			$stpl_file = YF_PATH. tpl()->TPL_PATH. $tpl_name;
		}
		if (file_exists($stpl_file)) {
			$line_search = preg_grep("/\{execute\([\"']*".$class_name.','.$method_name.(!empty($method_params) ? ','.$method_params : '')."[\"']*\)\}/i", @file($stpl_file));
			return ' on line '.intval(array_shift(array_keys($line_search)) + 1);
		}
	}

	/**
	* Wrapper around '_generate_url' function, called like this inside templates:
	* {url(object=home_page;action=test)}
	*/
	function _generate_url_wrapper ($params = array()){
		if (!function_exists('_force_get_url')) {
			return '';
		}
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($params) && strlen($params)) {
			// Url like this: /object/action/id
			if ($params[0] == '/') {
				// Do nothing, just directly pass this to url() as string
			} elseif (false !== strpos($params, '=')) {
				$params = _attrs_string2array($params);
			} else {
				list($object, $action, $id, $page) = explode(';', str_replace(',', ';', $params));
				$params = array(
					'object'	=> $object,
					'action'	=> $action,
					'id'		=> $id,
					'page'		=> $page,
				);
			}
		}
		return url($params);
	}

	/**
	* Replace paths to images
	*/
	function _replace_images_paths ($string = '') {
		$images_path  = (MAIN_TYPE_USER ? $this->MEDIA_PATH : ADMIN_WEB_PATH). $this->TPL_PATH. $this->_IMAGES_PATH;
		$uploads_path = $this->MEDIA_PATH. $this->_UPLOADS_PATH;

		$r = array(
			'"images/'		=> '"'.$images_path,
			"'images/"		=> "'".$images_path,
			'src="uploads/'	=> 'src="'.$uploads_path,
			'"uploads/'		=> '"'.$uploads_path,
			"'uploads/"		=> "'".$uploads_path,
		);
		return str_replace(array_keys($r), array_values($r), $string);
	}

	/**
	*/
	function _process_var_filters($text = '', $filters = '') {
		if (is_string($filters) && strpos($filters, '|') !== false) {
			$filters = explode('|', $filters);
		}
		if (!is_array($filters)) {
			$filters = array($filters);
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
	*/
	function add_pattern_callback($pattern, $func) {
		$this->_custom_patterns[$pattern] = $func;
	}
}
