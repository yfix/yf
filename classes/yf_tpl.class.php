<?php

/**
* Stadard Framework template engine
*
* @package	  YF
* @author	   YFix Team <yfix.dev@gmail.com>
* @version	  1.0
* @revision $Revision$
*/
class yf_tpl {

	/** @var string @conf_skip Path to the templates (including current theme path) */
	public $TPL_PATH			   = "";
	/** @var bool Compressing output by cutting "\t","\r","\n","  ","   " */
	public $COMPRESS_OUTPUT		= false;
	/** @var bool Using SEO - friendly URLs (All links need to be absolute) */
	public $REWRITE_MODE		   = false;
	/** @var bool Custom meta information (customizable for every page) : page titles, meta keywords, description */
	public $CUSTOM_META_INFO	   = false;
	/** @var bool Exit after sending main content */
	public $EXIT_AFTER_ECHO		= false;
	/** @var bool Do save execution info */
	public $LOG_EXEC_INFO		  = false;
	/** @var bool Use database to store templates */
	public $GET_STPLS_FROM_DB	  = false;
	/** @var bool SECURITY: allow or not eval php code (with _PATTERN_INCLUDE) */
	public $ALLOW_EVAL_PHP_CODE	= true;
	/** @var bool Get all templates from db or not (1 query or multiple)
	*   (NOTE: If true - Slow PHP processing but just 1 db query)
	*/
	public $FROM_DB_GET_ALL		= false;
	/** @var int Safe limit number of replacements (to avoid dead cycles)
	*   (type "-1" for unlimited number)
	*/
	public $STPL_REPLACE_LIMIT	 = -1;
	/** @var int Cycles and conditions max recurse level
	*   (how deeply could be nested template constructs like "if")
	*/
	public $_MAX_RECURSE_LEVEL = 4;
	/** @var array @conf_skip Patterns array for the STPL engine
	*   (you can add additional patterns if you need)
	*/
	public $_STPL_PATTERNS	 = array(
		// Insert constant here (cutoff for eval_code)
		// EXAMPLE:	 {const("SITE_NAME")}
		'/(\{const\(["\']{0,1})([a-z_][a-z0-9_]+?)(["\']{0,1}\)\})/ie'
			=> 'defined(\'$2\') ? main()->_eval_code(\'$2\', 0) : ""',
		// Configuration item
		// EXAMPLE:	 {conf("TEST_DOMAIN")}
		'/(\{conf\(["\']{0,1})([a-z_][a-z0-9_:]+?)(["\']{0,1}\)\})/ie'
			=> 'conf(\'$2\')',
		// Translate some items if needed
		// EXAMPLE:	 {t("Welcome")}
		'/\{(t|translate|i18n)\(["\']{0,1}(.*?)["\']{0,1}\)\}/imse'
			=> 'tpl()->_i18n_wrapper(\'$2\', $replace)',
		// Trims whitespaces, removes
		// EXAMPLE:	 {cleanup()}some content here{/cleanup}
		'/\{cleanup\(\)\}(.*?)\{\/cleanup\}/imse'
			=> 'trim(str_replace(array("\r","\n","\t"),"",stripslashes(\'$1\')))',
		// Display help tooltip
		// EXAMPLE:	 {tip('register.login')} or {tip('form.some_field',2)}
		'/\{tip\(["\']{0,1}([\w\-\.]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\)\}/imse'
			=> 'main()->_execute("graphics", "_show_help_tip", array("tip_id"=>"$1","tip_type"=>"$2"))',
		// Display help tooltip inline
		// EXAMPLE:	 {tip('register.login')} or {tip('form.some_field',2)}
		'/\{itip\(["\']{0,1}([^"\'\)\}]*)["\']{0,1}\)\}/imse'
			=> 'main()->_execute("graphics", "_show_inline_tip", array("text"=>"$1"))',
		// Display user level single (inline) error message by its name (keyword)
		// EXAMPLE:	 {e('login')} or {user_error('name_field')}
		'/\{(e|user_error)\(["\']{0,1}([\w\-\.]+)["\']{0,1}\)\}/imse'
			=> 'common()->_show_error_inline(\'$2\')',
		// Display result of macro substitution
		// EXAMPLE:	 {macro('delete_button')}
		'/\{macro\(["\']{0,1}([\w\-\.]+)["\']{0,1}\)\}/ie'
			=> 'tpl()->_process_macro(\'$1\')',
		// Common box implementation
		// EXAMPLE:	 {box('country','type=radio')}
		'/\{box\(["\']{0,1}([\w\-\.]+)["\']{0,1}[,]{0,1}["\']{0,1}([^"\'\)\}]*)["\']{0,1}\)\}/ie'
			=> 'tpl()->_process_box(\'$1\',\'$2\')',
		// Advertising
		// EXAMPLE:	 {ad('AD_ID')}
		'/\{ad\(["\']{0,1}([^"\'\)\}]*)["\']{0,1}\)\}/imse'
			=> 'main()->_execute("advertising", "_show", array("ad"=>\'$1\'))',
		// Url generation with params
		// EXAMPLE:	 {url(object=home_page;action=test)}
		'/\{url\(["\']{0,1}([^"\'\)\}]*)["\']{0,1}\)\}/imse'
			=> 'tpl()->_generate_url_wrapper(\'$1\')',
	);
	/** @var array @conf_skip Show custom class method output pattern */
	public $_PATTERN_EXECUTE   = array(
		// EXAMPLE:	 {execute(graphics, translate, value = blabla; extra = strtoupper)
		'/(\{execute\(["\']{0,1})([\s\w\-]+),([\s\w\-]+)[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> 'main()->_execute(\'$2\',\'$3\',\'$4\',"{tpl_name}",0,false)',
		'/(\{exec_cached\(["\']{0,1})([\s\w\-]+),([\s\w\-]+)[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> 'main()->_execute(\'$2\',\'$3\',\'$4\',"{tpl_name}",0,true)',
	);
	/** @var array @conf_skip Include template pattern */
	public $_PATTERN_INCLUDE   = array(
		// EXAMPLE:	 {include("forum/custom_info")}, {include("forum/custom_info", value = blabla; extra = strtoupper)}
		'/(\{include\(["\']{0,1})([\s\w\\/]+)["\']{0,1}?[,]{0,1}([^"\'\)\}]*)(["\']{0,1}\)\})/ie'
			=> '$this->_include_stpl(\'$2\',\'$3\')',
	);
	/** @var array @conf_skip Evaluate custom PHP code pattern */
	public $_PATTERN_EVAL	  = array(
		// EXAMPLE:	 {eval_code(print_r(_class('forum')))}
		'/(\{eval_code\()([^\}]+?)(\)\})/ie'
			=> 'main()->_eval_code(\'$2\', 0)',
	);
	/** @var array @conf_skip Evaluate custom PHP code pattern special for the DEBUG_MODE */
	public $_PATTERN_DEBUG	 = array(
		// EXAMPLE:	 {_debug_get_replace()}
		'/(\{_debug_get_replace\(\)\})/ie'
			=> 'is_array($replace) ? "<pre>".print_r(array_keys($replace),1)."</pre>" : "";',
		// EXAMPLE:	 {_debug_stpl_vars()}
		'/(\{_debug_get_vars\(\)\})/ie'
			=> '$this->_debug_get_vars($string)',
	);
	/** @var array @conf_skip Catch dynamic content into variable */
	// EXAMPLE: {catch("widget_blog_last_post")} {execute(blog,_widget_last_post)} {/catch}
	public $_PATTERN_CATCH	 = '/\{catch\(["\']{0,1}([a-z0-9_\-]+?)["\']{0,1}\)\}(.*?)\{\/catch\}/ims';
	/** @var array @conf_skip STPL internal comment pattern */
	// EXAMPLE:	 {{-- some content you want to comment inside template only --}}
	public $_PATTERN_COMMENT   = '/(\{\{--.*?--\}\})/ims';
	/** @var string @conf_skip Conditional pattern */
	// EXAMPLE: {if("name" eq "New")}<h1 style="color: white;">NEW</h1>{/if}
	public $_PATTERN_IF		= '/\{if\(["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}([^\(\)\{\}\n]*)\)\}/ims';
	/** @var string @conf_skip pattern for multi-conditions */
	public $_PATTERN_MULTI_COND= '/["\']{0,1}([\w\s\.\-\+\%]+?)["\']{0,1}[\s\t]+(eq|ne|gt|lt|ge|le)[\s\t]+["\']{0,1}([\w\s\-\#]*)["\']{0,1}/ims';
	/** @var string @conf_skip Cycle pattern */
	// EXAMPLE: {foreach ("lala")}<li>{lala.value1}</li>{/foreach}
	public $_PATTERN_FOREACH   = '/\{foreach\(["\']{0,1}([\w\s\.\-]+)["\']{0,1}\)\}((?![^\{]*?\{foreach\(["\']{0,1}?).*?)\{\/foreach\}/is';
	/** @var array @conf_skip For "_process_conditions" */
	public $_cond_operators	= array("eq"=>"==","ne"=>"!=","gt"=>">","lt"=>"<","ge"=>">=","le"=>"<=");
	/** @var array @conf_skip For "_process_conditions" */
	public $_math_operators	= array("and"=>"&&","xor"=>"xor","or"=>"||","+"=>"+","-"=>"-");
	/** @var array @conf_skip
		For "_process_conditions",
		Will be availiable in conditions with such form: {if("get.object" eq "login_form")} Hello from login form {/if}
	*/
	public $_avail_arrays	  = array(
		"get"	   => "_GET",
		"post"	  => "_POST",
	);
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
		'alt-text'	  => "",
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
	public $COMPILED_DIR			   = "stpls_compiled/";
	/** @var bool TTL for compiled stpls */
	public $COMPILE_TTL				= 3600;
	/** @var bool TTL for compiled stpls */
	public $COMPILE_CHECK_STPL_CHANGED = false;
	/** @var bool Use paths cache (check and save what stpl files we have and where) */
	public $USE_PATHS_CACHE			= false;
	/** @var bool */
	public $DEBUG_STPL_VARS			= false;
	/** @var string @conf_skip */
	public $_STPL_EXT		  = ".stpl";
	/** @var string @conf_skip */
	public $_THEMES_PATH	   = "templates/";
	/** @var string @conf_skip */
	public $_IMAGES_PATH	   = "images/";
	/** @var string @conf_skip */
	public $_UPLOADS_PATH	  = "uploads/";
	/** @var array Global scope tags (included in any parsed template) */
	public $_global_tags	   = array();
	/** @var STPL location codes (binary for less memory) */
	public $_stpl_loc_codes = array(
		"site"			  => 1,
		"project"		   => 2,
		"framework"		 => 4,
		"framework_user"	=> 8,
		"user_section"	  => 16,
		"inherit_project"   => 32,
		"lang_project"	  => 64,
		"inherit_project2"  => 128,
	);

	/**
	* Constructor
	*
	* @access   public
	* @return   void
	*/
	function __construct () {
		if (defined("IS_FRONT")) {
			conf('IS_FRONT', (bool)IS_FRONT);
		}
		$this->IS_FRONT = (bool)conf('IS_FRONT');
		// Cache array (JUST DECLARATION, DO NOT CHANGE!)
		$this->CACHE = array("stpl" => array());
		// Special code for the compiled framework mode
		if (defined("FRAMEWORK_IS_COMPILED")) {
			conf('FRAMEWORK_IS_COMPILED', (bool)FRAMEWORK_IS_COMPILED);
		}
		if (conf('FRAMEWORK_IS_COMPILED') && $this->AUTO_LOAD_PACKED_STPLS) {
			foreach ((array)conf('_compiled_stpls') as $_cur_name => $_cur_text) {
				$this->CACHE[$_cur_name] = array(
					"string"	=> $_cur_text,
					"calls"	 => 0,
					"storage"   => "cache",
				);
			}
		}
		// Try to find PCRE module
		if (!function_exists('preg_match_all')) {
			trigger_error("STPL: PCRE Extension is REQUIRED for the template engine", E_USER_ERROR);
		}
		// Set custom skin
		if (!empty($_SESSION["user_skin"]) && MAIN_TYPE_USER) {
			conf('theme', $_SESSION["user_skin"]);
		} elseif (defined('DEFAULT_SKIN')) {
			conf('theme', DEFAULT_SKIN);
		}
		if (!conf('theme')) {
			conf('theme', MAIN_TYPE);
		}
		// Merge eval pattern with main patterns
		if ($this->ALLOW_EVAL_PHP_CODE) {
			foreach ((array)$this->_PATTERN_EVAL as $k => $v) {
				$this->_STPL_PATTERNS[$k] = $v;
			}
		}
		if (DEBUG_MODE) {
			foreach ((array)$this->_PATTERN_DEBUG as $k => $v) {
				$this->_STPL_PATTERNS[$k] = $v;
			}
		}
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Hook "_init"
	*
	* @access   private
	* @return   void
	*/
	function _init () {
		// Directory where themes are stored
		conf("THEMES_PATH", $this->_THEMES_PATH);
		// Template files extensions
		conf("_STPL_EXT",   $this->_STPL_EXT);
		// Set path to the templates including selected skin
		$this->TPL_PATH = $this->_THEMES_PATH. conf('theme'). "/";

		if ($this->COMPRESS_OUTPUT) {
			$this->register_output_filter(array($this, "_simple_cleanup_callback"), "simple_cleanup");
		}
		if ($this->ALLOW_LANG_BASED_STPLS) {
			$this->_lang_theme_path = PROJECT_PATH. $this->_THEMES_PATH. conf('theme'). ".".conf('language')."/";
			if (!file_exists($this->_lang_theme_path)) {
				$this->ALLOW_LANG_BASED_STPLS = false;
				$this->_lang_theme_path = "";
			}
		}
		if ($this->ALLOW_SKIN_INHERITANCE) {
			if (defined("INHERIT_SKIN")) {
				conf('INHERIT_SKIN', INHERIT_SKIN);
			}
			if (conf('INHERIT_SKIN') != conf('theme')) {
				$this->_INHERITED_SKIN = conf('INHERIT_SKIN');
			}
			if (defined("INHERIT_SKIN2")) {
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
			$this->TIDY_OUTPUT	  = false;
			$this->FROM_DB_GET_ALL  = false;
			$this->LOG_EXEC_INFO	= false;
		}
		// Force inline debug setting
		if (isset($_SESSION['stpls_inline_edit']) && MAIN_TYPE_USER) {
			$this->ALLOW_INLINE_DEBUG = intval((bool)$_SESSION['stpls_inline_edit']);
		}
		$this->_init_global_tags();

		if ($this->USE_PATHS_CACHE) {
			$this->_prepare_paths_cache();
		}
		if (DEBUG_MODE) {
			$this->register_output_filter(array($this, "_debug_mode_callback"), "debug_mode");
		}
		if (main()->CONSOLE_MODE) {
			$this->_OB_CATCH_CONTENT = false;
		}
	}

	/**
	* Global scope tags
	*
	* @access   public
	* @return   void
	*/
	function _init_global_tags () {
		$this->_global_tags += array(
			"is_logged_in"  => intval((bool) main()->USER_ID),
			"is_spider"	 => (int)conf("IS_SPIDER"),
			"is_https"	  => isset($_SERVER["HTTPS"]) || isset($_SERVER["SSL_PROTOCOL"]) ? 1 : 0,
			"site_id"	   => (int)conf('SITE_ID'),
			"lang_id"	   => conf('language'),
			"debug_mode"	=> (int)((bool)DEBUG_MODE),
			"tpl_path"	  => MEDIA_PATH. $this->TPL_PATH,
		);
	}

	/**
	* Initialization of the main content
	*
	* Throws one "echo" at the end
	*
	* @access   public
	* @return   void
	*/
	function init_graphics () {
		$init_type = MAIN_TYPE;
		// Do not remove this!
		$this->_init_global_tags();
		// Default user group
		if ($init_type == "user" && empty($_SESSION['user_group'])) {
			$_SESSION['user_group'] = 1;
		}
		if (main()->OUTPUT_CACHING && $init_type == "user" && $_SERVER["REQUEST_METHOD"] == "GET") {
			_class("output_cache")->_process_output_cache();
		}
		if (!main()->NO_GRAPHICS) {
			if ($this->_OB_CATCH_CONTENT) {
				@ob_start();
			}
			// Trying to get default task
			if ($init_type == "user" && !empty($_SESSION['user_id']) && !empty($_SESSION['user_group'])) {
				$go = conf('default_page_user');
			} elseif ($init_type == "admin") {
				$go = conf('default_page_admin');
			}
			// If setting exists - assign it to the location
			if (!empty($go) && empty($_GET['object'])) {
				$go = str_replace(array("./?","./"), "", $go);
				$tmp_array = array();
				parse_str($go, $tmp_array);
				foreach ((array)$tmp_array as $k => $v) {
					$_GET[$k] = $v;
				}
			}
			$skip_prefetch = false;
			// Determine what template need to be loaded in the center area
			$tpl_name = "main";
			if ($init_type == "admin" && (empty($_SESSION['admin_id']) || empty($_SESSION['admin_group']))) {
				$tpl_name = "login";
				if (!main()->CONSOLE_MODE) {
					$skip_prefetch = true;
				}
			}
			if ($this->GET_STPLS_FROM_DB && $this->FROM_DB_GET_ALL) {
				$Q = db()->query("SELECT `name`,`text` FROM `".db('templates')."` WHERE `theme_name`='".conf('theme')."' AND `active`='1'");
				while ($A = db()->fetch_assoc($Q)) {
					$this->_TMP_FROM_DB[$A["name"]] = stripslashes($A["text"]);
				}
			}
			if (DEBUG_MODE && $this->ALLOW_INLINE_DEBUG || main()->INLINE_EDIT_LOCALE) {
				conf('inline_js_edit', true);
			}
			if (!$skip_prefetch) {
				$this->prefetch_center();
			}
		}
		if (!main()->NO_GRAPHICS) {
			$body["content"] = $this->_init_main_stpl($tpl_name);
			$this->_CENTER_RESULT = "";
			if ($this->CUSTOM_META_INFO && $init_type == "user") {
				$this->register_output_filter(array($this, "_custom_replace_callback"), "custom_replace");
			}
			if ($init_type == "user" && _class('graphics')->IFRAME_CENTER && (false === strpos($_SERVER['QUERY_STRING'], "center_area=1"))) {
				$this->register_output_filter(array($this, "_replace_for_iframe_callback"), "replace_for_iframe");
			}
		}
		if (!main()->NO_GRAPHICS) {
			// Replace images paths with their absolute ones
			if ($this->REWRITE_MODE && $init_type != "admin") {
				$this->register_output_filter(array($this, "_rewrite_links_callback"), "rewrite_links");
			}
			if ($this->TIDY_OUTPUT && $init_type != "admin") {
				$this->register_output_filter(array($this, "_tidy_cleanup_callback"), "tidy_cleanup");
			}

			$body["content"] = $this->_apply_output_filters($body["content"]);

			if (main()->OUTPUT_GZIP_COMPRESS && !conf('no_gzip')) {
				if ($this->_OB_CATCH_CONTENT && ob_get_level()) {
					$old_content = ob_get_contents();
					ob_end_clean();
				}
				@ob_start('ob_gzhandler');
				conf("GZIP_ENABLED", true);
				if ($this->_OB_CATCH_CONTENT) {
					$body["content"] = $old_content.$body["content"];
				}
				// Count number of compressed bytes (not exactly accurate)
				if (DEBUG_MODE) {
					debug('page_size_original', strlen($body["content"]));
					debug('page_size_gzipped', strlen(gzencode($body["content"], 3, FORCE_GZIP)));
				}
			}
			if (main()->OUTPUT_CACHING && $init_type == "user" && $_SERVER["REQUEST_METHOD"] == "GET") {
				_class('output_cache')->_put_page_to_output_cache($body);
			}
			if (DEBUG_MODE || conf('exec_time')) {
				$body["exec_time"] = $this->parse("system/debug_info", array("items" => common()->_show_execution_time(), "content" => "exec_time"));
			}
			if (DEBUG_MODE) {
				$body["debug_info"] = $this->parse("system/debug_info", array("items" => common()->show_debug_info(), "content" => "debug_info"));
				if ($this->ALLOW_INLINE_DEBUG || main()->INLINE_EDIT_LOCALE) {
					$body["debug_info"] .= $this->parse("system/js_inline_editor");
				}
				$_last_pos = strpos($body["content"], "</body>");
				if ($_last_pos) {
					$body["content"] = substr($body["content"], 0, $_last_pos). $body["exec_time"]. $body["debug_info"]. "</body></html>";
					$body["debug_info"] = "";
					$body["exec_time"]  = "";
				}
			}
			$output = implode("", $body);
			main()->_send_main_headers(strlen($output));
			// Throw generated output to user
			echo $output;
		}
		// Only while debugging (for non-standard content)
		if (main()->NO_GRAPHICS && DEBUG_MODE) {
			common()->show_debug_info();
		}
		// Output cache for "no graphics" content
		// Put output into cache
		if (main()->NO_GRAPHICS && main()->OUTPUT_CACHING && $init_type == "user" && $_SERVER["REQUEST_METHOD"] == "GET") {
			_class('output_cache')->_put_page_to_output_cache(ob_get_contents());
		}
		// Do log execution info (if needed)
		if ($this->LOG_EXEC_INFO) {
			common()->log_exec();
		}
		// End sending main output
		@ob_end_flush();
		if ($this->EXIT_AFTER_ECHO) {
			exit();
		}
	}

	/**
	* Try to run center block module/method if allowed
	*
	* @private
	* @return   string
	*/
	function prefetch_center ($CHECK_IF_ALLOWED = false) {
		// Skip security checks for console mode
		if (main()->CONSOLE_MODE) {
			return main()->tasks($CHECK_IF_ALLOWED);
		}
		return _class("graphics")->prefetch_center($CHECK_IF_ALLOWED);
	}

	/**
	* Process output filters for the given text
	*
	* @private
	* @param	string  Text needed to apply filters
	* @return   string
	*/
	function _apply_output_filters ($text = "") {
		foreach ((array)$this->_OUTPUT_FILTERS as $cur_filter) {
			if (is_callable($cur_filter)) {
				$text = call_user_func($cur_filter, $text);
			}
		}
		return $text;
	}

	/**
	* Initialization of the main template in the theme (could be overwritten to match design)
	*
	* Return contents of the main template
	*
	* @private
	* @param	$tpl_name   string  Desired template name (you can change it in method code by some conditions)
	* @return   string
	*/
	function _init_main_stpl ($tpl_name = "") {
		return $this->parse($tpl_name);
	}

	/**
	* Simple template parser (*.stpl)
	*
	* @public
	* @param	string  Name of the template to process
	* @param	array   Array of pairs "match => replace"
	* @param	array   Array of params
	* @return   mixed   Return contents of the processed template or false if it doesn't exists
	*/
	function parse($name, $replace = array(), $params = array()) {
		$name = strtolower($name);
		if (!is_array($params))				 { $params = array(); }
		if (isset($params["string"]))		   { $string = $params["string"]; }
		if (!isset($params["replace_images"]))  { $params["replace_images"] = true; }
		if (!isset($params["no_cache"]))		{ $params["no_cache"] = false; }
		if (!isset($params["get_from_db"]))	 { $params["get_from_db"] = false; }
		if (!isset($params["no_include"]))	  { $params["no_include"] = false; }
		if (DEBUG_MODE) {
			$stpl_time_start = microtime(true);
		}
//	  if (conf("FORCE_LOCALE") && !isset($params["no_cache"])) {
//		  $params["no_cache"] = 1;
//	  }
		$replace = my_array_merge((array)$this->_global_tags, (array)$replace);
		// User error message
		if (!isset($replace["error"])) {
			$replace["error"] = "";
			if ($name != "main" && common()->_error_exists() && !isset($replace["error"])) {
				if (!isset($this->_user_error_msg)) {
					$this->_user_error_msg = common()->_show_error_message("", false);
				}
				$replace["error"] = $this->_user_error_msg;
			}
		}
		if ($this->ALLOW_CUSTOM_FILTER) {
			$this->_custom_filter($name, $replace);
		}
		// Support for the framework calls
		if (substr($name, 0, 6) == "yf_") {
			$name = substr($name, 6);
		}
		if ($this->COMPILE_TEMPLATES) {
# TODO: add ability to use memcached or other fast cache-oriented storage instead of files => lower disk IO
			$compiled_path = PROJECT_PATH. $this->COMPILED_DIR."c_".MAIN_TYPE."_".urlencode($name).".php";
			if (file_exists($compiled_path) && ($_compiled_mtime = filemtime($compiled_path)) > (time() - $this->COMPILE_TTL)) {
				$_compiled_ok = true;

				ob_start();
				include ($compiled_path);
				$string = ob_get_contents();
				ob_end_clean();

				if ($this->COMPILE_CHECK_STPL_CHANGED) {
					$_stpl_path = $this->_get_template_file($name, $params["get_from_db"], 0, 1);
					if ($_stpl_path) {
						$_source_mtime = filemtime($_stpl_path);
					}
					if (!$_stpl_path || $_source_mtime > $_compiled_mtime) {
						$_compiled_ok = false;
						$string = false;
					}
				}
				if ($_compiled_ok) {
					$this->CACHE[$name]['calls']++;
					if (!isset($this->CACHE[$name]['string'])) {
						$this->CACHE[$name]['string']   = $string;
					}
					if (!isset($this->CACHE[$name]['s_length'])) {
						$this->CACHE[$name]['s_length'] = strlen($string);
					}
					if (DEBUG_MODE && MAIN_TYPE_USER) {
						$this->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
					}
					return $string;
				}
			}
		}
		if (isset($this->CACHE[$name]) && !$params["no_cache"]) {
			$string = $this->CACHE[$name]['string'];
			$this->CACHE[$name]['calls']++;
			if (DEBUG_MODE) {
				$this->CACHE[$name]['s_length'] = strlen($string);
			}
		} else {
			if (empty($string) && !isset($params["string"])) {
				$string = $this->_get_template_file($name, $params["get_from_db"]);
			}
			if ($string === false) {
				return false;
			}
			$string = preg_replace($this->_PATTERN_COMMENT, "", $string);
			if ($this->COMPILE_TEMPLATES) {
				$this->_compile($name, $replace, $string);
			}
			$string = $this->_process_executes($string, $replace, $name);
#		   $string = $this->_replace_std_patterns($string, $name, $replace, $params);
			$string = $this->_process_catches($string, $replace, $name);
			$string = $this->_replace_std_patterns($string, $name, $replace, $params);
			if (isset($params["no_cache"]) && !$params["no_cache"]) {
				$this->CACHE[$name]['string']   = $string;
				$this->CACHE[$name]['calls']	= 1;
			}
		}
		$string = $this->_process_executes($string, $replace, $name);
		$string = $this->_process_catches($string, $replace, $name);
		// Process std replaces again (if left some unprocessed tags)
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		$string = $this->_process_cycles($string, $replace, $name);
		$string = $this->_process_conditions($string, $replace, $name);
		if (!$params["no_include"]) {
			$string = preg_replace(array_keys($this->_PATTERN_INCLUDE), array_values($this->_PATTERN_INCLUDE), $string);
			$string = $this->_process_executes($string, $replace, $name);
		}
		// Do not allow empty tags
		if (isset($replace[""])) {
			unset($replace[""]);
		}
		// Replace given items (if exists ones)
		foreach ((array)$replace as $item => $value) {
			if (!is_array($value)) {
				$string = str_replace("{".$item."}", $value, $string);
			}
			// Allow to replace simple 1-dimensional array items (some speed loss, but might be useful)
			if (is_array($value) && !is_array(current($value))) {
				foreach ((array)$value as $_sub_key => $_sub_val) {
					$string = str_replace("{".$item.".".$_sub_key."}", $_sub_val, $string);
				}
			}
		}
		$string = $this->_replace_std_patterns($string, $name, $replace, $params);
		// If content need to be cleaned from unused tags - do that
		if (isset($params["clear_all"])) {
			$string = preg_replace("/\{[\w_]+\}/i", "", $string);
		}
		if (isset($params["eval_content"])) {
			eval("\$string = \"".str_replace('"', '\"', $string)."\";");
		}
		// Replace "images/" and "uploads/" to their full web paths
		if ($params["replace_images"]) {
			$string = common()->_replace_images_paths($string);
		}
		if (DEBUG_MODE && MAIN_TYPE_USER) {
			if (!isset($this->CACHE[$name]['exec_time'])) {
				$this->CACHE[$name]['exec_time'] = 0;
			}
			$this->CACHE[$name]['exec_time'] += (microtime(true) - $stpl_time_start);
			// For debug store information about variables used while processing template
			if ($this->DEBUG_STPL_VARS) {
				$d = debug('STPL_REPLACE_VARS::'.$name);
				$next = is_array($d) ? count($d) : 0;
				debug('STPL_REPLACE_VARS::'.$name.'::'.$next, $replace);
			}
			if ($this->USE_SOURCE_BACKTRACE) {
				$trace = debug_backtrace();
				foreach ((array)$trace as $_cur_trace_id => $_cur_trace) {
					if ($_cur_trace["function"] != "parse"
						|| !in_array($_cur_trace["class"], array("yf_tpl", "tpl"))
						|| $_cur_trace["args"][0] != $name) {
						continue;
					}
					$_cur_trace["inside_method"] = (!empty($trace[$_cur_trace_id + 1]["class"]) ? $trace[$_cur_trace_id + 1]["class"].$trace[$_cur_trace_id + 1]["type"] : "").$trace[$_cur_trace_id + 1]["function"];
					// Do save trace for debug
					$d = debug('STPL_TRACES::'.$name);
					$next = is_array($d) ? count($d) : 0;
					debug('STPL_TRACES::'.$name.'::'.$next, $_cur_trace);
					break;
				}
				// Prepare calls tree
				foreach ((array)$trace as $A) {
					if ((isset($A["class"]) && $A["class"] != __CLASS__) || (isset($A["function"]) && $A["function"] != __FUNCTION__) || $A["args"][0] == $name) {
						continue;
					}
					debug('STPL_PARENTS::'.$name, $A["args"][0]);
					break;
				}
				if ($name != "main" && !debug('STPL_PARENTS::'.$name)) {
					debug('STPL_PARENTS::'.$name, 'main');
				}
			}
			if ($this->ALLOW_INLINE_DEBUG && strlen($string) > 20
				&& !in_array($name, array("main", "system/debug_info", "system/js_inline_editor"))
			) {
				if (preg_match("/^<([^>]*?)>/ims", ltrim($string), $m)) {
					$string = "<".$m[1]." stpl_name='".$name."'>".substr(ltrim($string), strlen($m[0]));
				}
			}
		}
		return $string;
	}

	/**
	* Wrapper to parse given template string
	*
	* @param	$name		   string  Name of the template to process
	* @param	$replace		array   Array of pairs "match => replace"
	* @param	$string		 string  Force to use this string for processing
	* @return   mixed   Return processed string
	*/
	function parse_string($name = "", $replace = array(), $string = "", $params = array()) {
		if (!strlen($string)) {
			$string = " ";
		}
		$params["string"] = $string;
		return $this->parse(!empty($name) ? $name : abs(crc32($string)), $replace, $params);
	}

	/**
	* Replace "{execute" patterns
	*
	* @param	$string	 string  String where need to replace
	* @param	$name	   string  Name of the template to process
	* @return   mixed   Return processed string
	*/
	function _process_executes($string, $replace = array(), $name = "", $params = array()) {
		if (false === strpos($string, "{execute(") || empty($string)) {
			return $string;
		}
		return preg_replace(array_keys($this->_PATTERN_EXECUTE), str_replace("{tpl_name}", $name.$this->_STPL_EXT, array_values($this->_PATTERN_EXECUTE)), $string, --$this->STPL_REPLACE_LIMIT > 0 ? $this->STPL_REPLACE_LIMIT : -1);
	}

	/**
	* Replace standard patterns
	*
	* @param	$string	 string  String where need to replace
	* @param	$name	   string  Name of the template to process
	* @return   mixed   Return processed string
	*/
	function _replace_std_patterns($string, $name = "", $replace = array(), $params = array()) {
		return preg_replace(array_keys($this->_STPL_PATTERNS), str_replace("{tpl_name}", $name.$this->_STPL_EXT, array_values($this->_STPL_PATTERNS)), $string, --$this->STPL_REPLACE_LIMIT > 0 ? $this->STPL_REPLACE_LIMIT : -1);
	}

	/**
	* Process "catch" template statements
	*
	* @access   private
	* @param	string  Text to process
	* @param	array   Pairs "match => replace"
	* @return   string  Processed text
	*/
	function _process_catches ($string = "", &$replace, $stpl_name = "") {
		if (false === strpos($string, "{/catch}") || empty($string)) {
			return $string;
		}
		if (!preg_match_all($this->_PATTERN_CATCH, $string, $m)) {
			return $string;
		}
		foreach ((array)$m[0] as $k => $v) {
			$string = str_replace($v, "", $string);
			// Add replace var
			$_new_var_name  = $m[1][$k];
			$_new_var_value = $m[2][$k];
			if (!empty($_new_var_name)) {
				$replace[$_new_var_name] = trim($_new_var_value);
			}
		}
		return $string;
	}

	/**
	* Check if template exists (simple wrapper for the "_get_template_file")
	*
	* @access   private
	* @param	$stpl_name	  string  Template name to get
	* @param	$get_from_db	boolean Switch between template from db source or from files source
	* @return   bool		Return result if template exists
	*/
	function _stpl_exists ($stpl_name = "", $get_from_db = false) {
		return (bool)$this->_get_template_file($stpl_name, $get_from_db, 1);
	}

	/**
	* Read template file contents (or get it from DB)
	*
	* @access   private
	* @param	$file_name	  string  Template name to get
	* @param	$get_from_db	boolean Switch between template from db source or from files source
	* @return   string  Return template contetns
	*/
	function _get_template_file ($file_name = "", $get_from_db = false, $JUST_CHECK_IF_EXISTS = false, $RETURN_TEMPLATE_PATH = false) {
		$string	 = false;
		$NOT_FOUND  = false;
		$storage	= "inline";
		// Support for the framework calls
		$l = strlen(YF_PREFIX);
		if (substr($file_name, 0, $l) == YF_PREFIX) {
			$file_name = substr($file_name, $l);
		}
		$file_name  .= $this->_STPL_EXT;
		// Fix double extesion
		$file_name  = str_replace($this->_STPL_EXT.$this->_STPL_EXT, $this->_STPL_EXT, $file_name);
		$stpl_name  = str_replace($this->_STPL_EXT, "", $file_name);
		if ($this->GET_STPLS_FROM_DB || $get_from_db) {
			if ($this->FROM_DB_GET_ALL) {
				if (!empty($this->_TMP_FROM_DB[$stpl_name])) {
					$string = $this->_TMP_FROM_DB[$stpl_name];
					unset($this->_TMP_FROM_DB[$stpl_name]);
				} else {
					$NOT_FOUND = true;
				}
			} else {
				list($text) = db()->query_fetch("SELECT `text` AS `0` FROM `".db('templates')."` WHERE `theme_name`='".conf('theme')."' AND `name`='"._es($stpl_name)."' AND `active`='1'");
				if (isset($text)) {
					$string = stripslashes($text);
				} else {
					$NOT_FOUND = true;
				}
			}
			$storage = "db";
		} else {
			// Storages are defined in specially crafted order, so do not change it unless you have strong reason
			$storages = array();
			$site_path = (MAIN_TYPE_USER ? SITE_PATH : ADMIN_SITE_PATH);
			$dev_path = ".dev/".main()->HOSTNAME."/";
			// Developer overrides
			if (conf('DEV_MODE')) {
				if ($site_path && $site_path != PROJECT_PATH) {
					$storages["dev_site"]   = $site_path. $dev_path. $this->TPL_PATH. $file_name;
				}
				$storages["dev_project"]	= PROJECT_PATH. $dev_path. $this->TPL_PATH. $file_name;
			}
			// Special for the "mass hosting" mode
			if ($this->IS_FRONT) {
				// User folder
				$storages["hosting_user"]   = PROJECT_PATH."users/".conf("HOSTING_NAME").".".conf("HOSTING_DOMAIN")."/templates/user/". $file_name;
				// Custom user theme
				$storages["hosting_custom_theme"] = PROJECT_PATH."user_themes/".FRONT_THEME_NAME."/templates/user/". $file_name;
			}
			if ($this->ALLOW_LANG_BASED_STPLS) {
				$storages["lang_project"]   = $this->_lang_theme_path. $file_name;
			}
			if ($site_path && $site_path != PROJECT_PATH) {
				$storages["site"]		   = $site_path. $this->TPL_PATH. $file_name;
			}
			$storages["project"]			= PROJECT_PATH. $this->TPL_PATH. $file_name;
			// Skin inheritance on project level
			if ($this->_INHERITED_SKIN) {
				$storages["inherit_project"]= PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN. "/". $file_name;
			}
			if ($this->_INHERITED_SKIN2) {
				$storages["inherit_project2"]= PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN2. "/". $file_name;
			}
			$storages["framework"]		  = YF_PATH. $this->_THEMES_PATH. MAIN_TYPE."/". $file_name;
			$storages["framework_p2"]	   = YF_PATH. "priority2/". $this->_THEMES_PATH. MAIN_TYPE."/". $file_name;
			if (MAIN_TYPE_ADMIN) {
				// user section within admin
				$storages["user_section"]	   = PROJECT_PATH. $this->_THEMES_PATH. $this->_get_def_user_theme(). "/". $file_name;
				// user section from framework within admin
				$storages["framework_user"]	 = YF_PATH. $this->_THEMES_PATH. "user/". $file_name;
				// user section from framework within admin priority2
				$storages["framework_user_p2"]  = YF_PATH. "priority2/". $this->_THEMES_PATH. "user/". $file_name;
			}
			// Try storages one-by-one in inheritance order, stop when found
			$storage = "";
			foreach ((array)$storages as $_storage => $file_path) {
				if (!$this->_stpl_path_exists($file_path, $stpl_name, $_storage)) {
					continue;
				}
				$string = @file_get_contents($file_path);
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
					$storage	= "compiled_cache";
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
			trigger_error("STPL: template \"".$file_name."\" in theme \"".conf('theme')."\" not found.<pre>". main()->trace_string()."</pre>", E_USER_WARNING);
		} else {
			$this->CACHE[str_replace($this->_STPL_EXT, "", $file_name)]['storage'] = $storage;
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
		$SITES_INFO = _class("sites_info", "classes/")->info;
		$FIRST_SITE_INFO = array_shift($SITES_INFO);
		if (file_exists(PROJECT_PATH. $this->_THEMES_PATH. $FIRST_SITE_INFO["DEFAULT_SKIN"]. "/")) {
			$this->_def_user_theme = $FIRST_SITE_INFO["DEFAULT_SKIN"];
		}
		if (empty($this->_def_user_theme)) {
			$this->_def_user_theme = "new_1";
		}
		return $this->_def_user_theme;
	}

	/**
	* Check if given template exists
	*/
	function _stpl_path_exists ($file_name = "", $stpl_name = "", $location = "") {
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
		// Get from cache
		$CACHE_NAME = "stpls_paths_".(MAIN_TYPE_ADMIN ? "admin" : "site_".conf('SITE_ID'));
		if (main()->USE_SYSTEM_CACHE) {
			$stpls_paths = cache()->get($CACHE_NAME);
		}
		// Create full array (cache is empty or turned off)
		if (empty($stpls_paths)) {
			if (MAIN_TYPE_ADMIN) {
				$def_user_theme = $this->_get_def_user_theme();
				$paths = array(
					"framework"	 => YF_PATH. $this->_THEMES_PATH. "admin". "/",
					"framework_user"=> YF_PATH. $this->_THEMES_PATH. "user". "/",
					"user_section"  => INLCUDE_PATH. $this->_THEMES_PATH. $def_user_theme. "/",
				);
			} else {
				$paths = array(
					"site"		 		=> SITE_PATH. $this->_THEMES_PATH. conf('theme'). "/",
					"project"	  		=> PROJECT_PATH. $this->_THEMES_PATH. conf('theme'). "/",
					"framework"	 	=> YF_PATH. $this->_THEMES_PATH. "user". "/",
					"inherit_project"	=> $this->_INHERITED_SKIN ? PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN. "/". $file_name : "",
					"inherit_project2"	=> $this->_INHERITED_SKIN2 ? PROJECT_PATH. $this->_THEMES_PATH. $this->_INHERITED_SKIN2. "/". $file_name : "",
				);
			}
			$ext_length = strlen($this->_STPL_EXT);
			// Process paths
			foreach ((array)$paths as $_location => $_path) {
				if (empty($_path)) {
					continue;
				}
				$_path_length = strlen($_path);
				foreach ((array)_class("dir")->scan_dir($_path, 1, array("", "/\.stpl\$/i"), "/(svn|git)/") as $_cur_path) {
					$_cur_path = substr($_cur_path, $_path_length, -$ext_length);
					if ($_cur_path) {
						$stpls_paths[$_cur_path] += $this->_stpl_loc_codes[$_location];
					}
				}
			}
			ksort($stpls_paths);
			// Put into cache
			if (main()->USE_SYSTEM_CACHE) {
				cache()->put($CACHE_NAME, $stpls_paths);
			}
		}
		$this->_stpls_paths_cache = $stpls_paths;
	}

	/**
	* Conditional execution
	*
	* @access   private
	* @param	string  Text to process
	* @param	array   Pairs "match => replace"
	* @return   string  Processed text
	*/
	function _process_conditions ($string = "", $replace = array(), $stpl_name = "") {
		// Fast check for the patterns, also check for the resurse level
		if (false === strpos($string, "{/if}") || empty($string)) {
			return $string;
		}
		// Start processing
		if (!preg_match_all($this->_PATTERN_IF, $string, $m)) {
			return $string;
		}
		// Important!
		$string = str_replace(array("<"."?", "?".">"), array("&lt;?", "?&gt;"), $string);
		// Process matches
		foreach ((array)$m[0] as $k => $v) {
			$part_left	  = $this->_prepare_cond_text($m[1][$k], $replace);
			$cur_operator   = $this->_cond_operators[strtolower($m[2][$k])];
			$part_right	 = $m[3][$k];
			if ($part_right && $part_right{0} == "#") {
				$part_right = $replace[ltrim($part_right, "#")];
			}
			if (!is_numeric($part_right)) {
				$part_right = "\"".$part_right."\"";
			}
			if (empty($part_left)) {
				$part_left = "\"\"";
			}
			$part_other	 = "";
			// Possible multi-part condition found
			if ($m[4][$k]) {
				$_tmp_parts = preg_split("/[\s\t]+(and|xor|or)[\s\t]+/ims", $m[4][$k], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
				if ($_tmp_parts) {
					$_tmp_count = count($_tmp_parts);
				}
				for ($i = 1; $i < $_tmp_count; $i+=2) {
					$_tmp_parts[$i] = $this->_process_multi_conds($_tmp_parts[$i], $replace);
					if (!strlen($_tmp_parts[$i])) {
						unset($_tmp_parts[$i]);
						unset($_tmp_parts[$i - 1]);
					}
				}
				if ($_tmp_parts) {
					$part_other = " ". implode(" ", (array)$_tmp_parts);
				}
			}
			$new_code	   = "<"."?p"."hp if(".$part_left." ".$cur_operator." ".$part_right.$part_other.") { ?>";
			$string		 = str_replace($v, $new_code, $string);
		}
		$string = str_replace("{else}", "<"."?p"."hp } else { ?".">", $string);
		$string = str_replace("{/if}", "<"."?p"."hp } ?".">", $string);
		// Evaluate and catch result
		ob_start();
		$result = eval("?>".$string."<"."?p"."hp return 1;");
		$string = ob_get_contents();
		ob_clean();
		// Throw warning if result is wrong
		if (!$result) {
			trigger_error("STPL: ERROR: wrong condition in template \"".$stpl_name."\"", E_USER_WARNING);
		}
		return $string;
	}

	/**
	* Multi-condition special parser
	*/
	function _process_multi_conds ($cond_text = "", $replace = array()) {
		if (!preg_match($this->_PATTERN_MULTI_COND, $cond_text, $m)) {
			return "";
		}
		// Process matches
		$part_left	  = $this->_prepare_cond_text($m[1], $replace);
		$cur_operator   = $this->_cond_operators[strtolower($m[2])];
		$part_right	 = $m[3];
		if (strlen($part_right) && $part_right{0} == "#") {
			$part_right = $replace[ltrim($part_right, "#")];
		}
		if (!is_numeric($part_right)) {
			$part_right = "\"".$part_right."\"";
		}
		if (empty($part_left)) {
			$part_left = "\"\"";
		}
		return $part_left." ".$cur_operator." ".$part_right;
	}

	/**
	* Prepare text for "_process_conditions" method
	*
	* @access   private
	* @param	string  Text to process (usually left or right part of condition)
	* @param	array   Pairs "match => replace"
	* @return   string  Processed text
	*/
	function _prepare_cond_text ($cond_text = "", $replace = array()) {
		$prepared_array = array();
		// Try to prepare left part
		foreach (explode(" ", str_replace("\t","",$cond_text)) as $tmp_k => $tmp_v) {
			$res_v = "";
			// Value from $replace array (DO NOT replace "array_key_exists()" with "isset()" !!!)
			if (array_key_exists($tmp_v, $replace)) {
				if (is_array($replace[$tmp_v])) {
					$res_v = $replace[$tmp_v] ? "(\"1\")" : "(\"\")";
				} else {
					$res_v = "\$replace['".$tmp_v."']";
				}
			// Arithmetic operators (currently we allow only "+" and "-")
			} elseif (isset($this->_math_operators[$tmp_v])) {
				$res_v = $this->_math_operators[$tmp_v];
			// Configuration item
			} elseif (false !== strpos($tmp_v, "conf.")) {
				$res_v = "conf('".substr($tmp_v, strlen("conf."))."')";
			// Constant
			} elseif (false !== strpos($tmp_v, "const.")) {
				$res_v = substr($tmp_v, strlen("const."));
				if (!defined($res_v)) {
					$res_v = "";
				}
			// Global array element or sub array
			} elseif (false !== strpos($tmp_v, ".")) {
				$try_elm = substr($tmp_v, 0, strpos($tmp_v, "."));
				$try_elm2 = "['".str_replace(".","']['",substr($tmp_v, strpos($tmp_v, ".") + 1))."']";
				// Global array
				if (isset($this->_avail_arrays[$try_elm])) {
					$res_v = "\$".$this->_avail_arrays[$try_elm].$try_elm2;
				// Sub array
				} elseif (isset($replace[$try_elm]) && is_array($replace[$try_elm])) {
					$res_v = "\$replace['".$try_elm."']".$try_elm2;
				}
			// Simple number or string, started with "%"
			} elseif ($tmp_v{0} == "%" && strlen($tmp_v) > 1) {
				$res_v = "\"".str_replace("\"", "\\\"", substr($tmp_v, 1))."\"";
			} else {
				// Do not touch!
				// Variable or condition not found
			}
			// Add prepared element
			if ($res_v != "") {
				$prepared_array[$tmp_k] = $res_v;
			}
		}
		return implode(" ", $prepared_array);
	}

	/**
	* Cycled execution
	*
	* @access   private
	* @param	string  Text to process
	* @param	array   Pairs "match => replace"
	* @return   string  Processed text
	*/
	function _process_cycles ($string = "", $replace = array(), $stpl_name = "") {
		// Fast check for the patterns
		if (false === strpos($string, "{/foreach}") || empty($string)) {
			return $string;
		}
		// Start processing and quick exit if nothing found
		if (!preg_match_all($this->_PATTERN_FOREACH, $string, $m)) {
			return $string;
		}
		$a_for = array();
		// Prepare non-array replace values
		foreach ((array)$replace as $k5 => $v5) {
			if (is_array($v5)) {
				continue;
			}
			$non_array_replace[$k5] = $v5;
		}
		// Process matches
		foreach ((array)$m[0] as $match_id => $matched_string) {
			$output		 = "";
			$sub_array	  = array();
			$sub_replace	= array();
			$key_to_cycle   = &$m[1][$match_id];
			$sub_template   = &$m[2][$match_id];
			$sub_template   = str_replace("#.", $key_to_cycle.".", $sub_template);
			// Needed here for graceful quick exit from cycle
			$a_for[$matched_string] = "";
			// Skip empty keys
			if (empty($key_to_cycle)) {
				continue;
			}
			// Standard iteration by array
			if (is_array($replace[$key_to_cycle])) {
				$sub_array  = $replace[$key_to_cycle];
			// Simple iteration within template
			} elseif (!isset($replace[$key_to_cycle]) && is_numeric($key_to_cycle)) {
				$sub_array = range(1, $key_to_cycle);
			}
			// Skip empty arrays
			if (empty($sub_array)) {
				continue;
			}
			// Process sub template (only cycle within correct keys)
			$_total = (int)count($sub_array);
			$_i = 0;
			foreach ((array)$sub_array as $sub_k => $sub_v) {
				$_is_first  = (int)(++$_i == 1);
				$_is_last   = (int)($_i == $_total);
				$_is_odd	= (int)($_i % 2);
				$_is_even   = (int)(!$_is_odd);
				// Try to get sub keys to replace (exec only one time per one "foreach")
				if (empty($sub_replace)) {
					if (is_array($sub_v)) {
						foreach ((array)$sub_v as $k3 => $v3) {
							$sub_replace[] = "{".$key_to_cycle.".".$k3."}";
						}
					} else {
						$sub_replace = "{".$key_to_cycle.".".$key_to_cycle."}";
					}
				}
				// Add output and replace template keys with array values
				if (!empty($sub_replace)) {
					// Process output for this iteration
					$cur_output = $sub_template;
					$cur_output = str_replace($sub_replace, is_array($sub_v) ? array_values($sub_v) : $sub_v, $cur_output);
					$cur_output = str_replace(array("{_num}","{_total}"), array($_i, $_total), $cur_output);
					// For 2-dimensional arrays
					if (is_array($sub_v)) {
						$cur_output = str_replace("{_key}", $sub_k, $cur_output);
					// For 1-dimensional arrays
					} else {
						$cur_output = str_replace(array("{_key}", "{_val}") , array($sub_k, $sub_v), $cur_output);
					}
					// Prepare items for condition
					$tmp_array = $non_array_replace;
					foreach ((array)$sub_v as $k6 => $v6) {
						$tmp_array[$key_to_cycle.".".$k6] = $v6;
					}
					$tmp_array["_num"]	  = $_i;
					$tmp_array["_total"]	= $_total;
					$tmp_array["_first"]	= $_is_first;
					$tmp_array["_last"]	 = $_is_last;
					$tmp_array["_even"]	 = $_is_odd;
					$tmp_array["_odd"]	  = $_is_even;
					$tmp_array["_key"]	  = $sub_k;
					$tmp_array["_val"]	  = is_array($sub_v) ? strval($sub_v) : $sub_v;
					// Try to process conditions in every cycle
					$output .= $this->_process_conditions($cur_output, $tmp_array, $stpl_name);
				}
			}
			// Create array element to replace whole cycle
			$a_for[$matched_string] = $output;
		}
		// Replace all found template cycles with values
		if (count($a_for)) {
			$string = str_replace(array_keys($a_for), array_values($a_for), $string);
		}
		return $string;
	}

	/**
	* Wrapper for "_PATTERN_INCLUDE", allows you to include stpl, optionally pass $replace params to it
	*
	* @access   private
	* @param	string  STPL name to include
	* @param	string  params like "var1=value1;var2=value2"
	* @return   string  Processed text
	*/
	function _include_stpl ($stpl_name = "", $params = "") {
		$replace = array();
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		foreach ((array)explode(";", str_replace(array("'",''), "", $params)) as $v) {
			$attrib_name	= "";
			$attrib_value   = "";
			if (false !== strpos($v, "=")) {
				list($attrib_name, $attrib_value) = explode("=", trim($v));
			}
			$replace[trim($attrib_name)] = trim($attrib_value);
		}
		return $this->parse($stpl_name, $replace);
	}

	/**
	* Registers custom function to be used in templates
	*
	* @param string $function the name of the template function
	* @param string $function_impl the name of the PHP function to register
	*/
	function register_output_filter($callback_impl, $filter_name = "") {
		if (empty($filter_name)) {
			$filter_name = substr(abs(crc32(microtime(true))),0,8);
		}
		$this->_OUTPUT_FILTERS[$filter_name] = $callback_impl;
	}

	/**
	* Simple cleanup (compress) output
	*
	* @access   private
	* @param	string  Text to cleanup (compress)
	* @return   string  Processed text
	*/
	function _simple_cleanup_callback ($text = "") {
		if (DEBUG_MODE) {
			debug('compress_output_size_1', strlen($text));
		}
		$text = str_replace(array("\r","\n","\t"), "", $text);
		$text = preg_replace("#[\s]{2,}#ms", " ", $text);
		// Remove comments
		$text = preg_replace("#<\!--[\w\s\-\/]*?-->#ms", "", $text);
		if (DEBUG_MODE) {
			debug('compress_output_size_2', strlen($text));
		}
		return $text;
	}

	/**
	* Custom text replacing method
	*
	* @access   private
	* @param	string  Text to process
	* @return   string  Processed text
	*/
	function _custom_replace_callback ($text = "") {
		return _class("custom_meta_info")->_process($text);
	}

	/**
	* Replace method for "IFRAME in center" mode
	*
	* @access   private
	* @param	string  Text to process
	* @return   string  Processed text
	*/
	function _replace_for_iframe_callback ($text = "") {
		return module("rewrite")->_replace_links_for_iframe($text);
	}

	/**
	* Rewrite links callback method
	*
	* @access   private
	* @param	string  Text to process
	* @return   string  Processed text
	*/
	function _rewrite_links_callback ($text = "") {
		return module("rewrite")->_rewrite_replace_links($text);
	}

	/**
	* Clenup HTML output with Tidy
	*
	* @access   private
	* @param	string  Text to process
	* @return   string  Processed text
	*/
	function _tidy_cleanup_callback ($text = "") {
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
	function _debug_mode_callback ($text = "") {
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
	*
	* @access   private
	* @param	string  Text to process
	* @return   string  Processed text
	*/
	function _custom_filter ($stpl_name = "", &$replace) {
		if ($stpl_name == "home_page/main") {
			// example only:
			//print_r($replace);
			//$replace["recent_ads"] = "";
		}
	}

	/**
	* Display macro substitution result
	*/
	function _process_macro ($name = "") {
		return tpl()->parse("system/macro", array('name' => $name));
	}

	/**
	* Display common box
	*/
	function _process_box ($name = "", $params = "") {
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($params) && strlen($params)) {
			$tmp_params = explode(";", $params);
			$params	 = array();
			// Convert params string into array
			foreach ((array)$tmp_params as $v) {
				$attrib_name = "";
				$attrib_value = "";
				if (false !== strpos($v, "=")) {
					list($attrib_name, $attrib_value) = explode("=", trim($v));
				}
				$params[trim($attrib_name)] = trim($attrib_value);
			}
		}
		// Ability to override name in params
		if (empty($params["name"])) {
			$params["name"] = $name;
		}
		$params["selected"] = $_POST[$params["name"]];
		return common()->box($params);
	}

	/**
	* Collect all template vars and display in pretty way
	*/
	function _debug_get_vars ($string = "") {
		$not_replaced = array();
		$patterns = array(
			"/\{([a-z0-9\_]{1,64})\}/ims",
			"/\{if\([\'\"]*([a-z0-9\_]{1,64})[\'\"]*[^\}\)]+?\)\}/ims",
			"/\{foreach\([\'\"]*([a-z0-9\_]{1,64})[\'\"]*\)\}/ims",
		);
		// Parse simple vars
		foreach ((array)$patterns as $pattern) {
			if (!preg_match_all($pattern, $string, $m)) {
				continue;
			}
			$cur_matches = $m[1];
			foreach ((array)$cur_matches as $v) {
				$v = str_replace(array("{","}"), "", $v);
				// Skip internal vars
				if ($v{0} == "_" || $v == "else") {
					continue;
				}
				$not_replaced[$v] = $v;
			}
		}
		ksort($not_replaced);
		if (!empty($not_replaced)) {
			$body .= "<pre>array(\n";
			foreach ((array)$not_replaced as $v) {
				$body .= "\t\""._prepare_html($v, 0)."\"\t=> \"\",\n";
			}
			$body .= ");</pre>\n";
		}
		return $body;
	}

	/**
	* Compile given template into pure PHP code
	*/
	function _compile($name, $replace = array(), $string = "") {
		return _class("tpl_compile", "classes/tpl/")->_compile($name, $replace, $string);
	}

	/**
	* Wrapper function for t/translate/i18n calls inside templates
	*/
	function _i18n_wrapper ($input = "", $replace = array()) {
		if (!strlen($input)) {
			return "";
		}
		$input = stripslashes(trim($input, '"\''));
		$args = array();
		// Complex case with substitutions
		if (preg_match('/(?P<text>.+?)["\']{1},[\s\t]*%(?P<args>[a-z]+.+)$/ims', $input, $m)) {
			foreach (explode(";%", $m["args"]) as $arg) {
				$attr_name = $attr_val = "";
				if (false !== strpos($arg, "=")) {
					list($attr_name, $attr_val) = explode("=", trim($arg));
				}
				$attr_name  = trim(str_replace(array("'",'"'), "", $attr_name));
				$attr_val   = trim(str_replace(array("'",'"'), "", $attr_val));
				$args["%".$attr_name] = $attr_val;
			}
			$text_to_translate = $m["text"];
		} else {
			// Easy case that just needs to be translated
			$text_to_translate = $input;
		}
		$output = translate($text_to_translate, $args);
		// Do replacement of the template vars on the last stage
		// example: @replace1 will be got from $replace["replace1"] array item
		if (false !== strpos($output, "@") && !empty($replace)) {
			$r = array();
			foreach ((array)$replace as $k => $v) {
				$r["@".$k] = $v;
			}
			$output = str_replace(array_keys($r), array_values($r), $output);
		}
		return $output;
	}

	/**
	* Wrapper around "_generate_url" function, called like this inside templates:
	* {url(object=home_page;action=test)}
	*/
	function _generate_url_wrapper ($params = array()){
		if(!function_exists('_force_get_url')) return '';
		// Try to process method params (string like attrib1=value1;attrib2=value2)
		if (is_string($params) && strlen($params)) {
			$tmp_params	 = explode(";", $params);
			$params  = array();
			// Convert params string into array
			foreach ((array)$tmp_params as $v) {
				$attrib_name = "";
				$attrib_value = "";
				if (false !== strpos($v, "=")) {
					list($attrib_name, $attrib_value) = explode("=", trim($v));
				}
				$params[trim($attrib_name)] = trim($attrib_value);
			}
		}
		return _force_get_url($params);
	}
}
