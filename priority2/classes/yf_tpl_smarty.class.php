<?php

/**
 * Wrapper for SMARTY Template engine
 * 
 * @package		YF
 * @author		Yuri Vysotskiy <profy.net@gmail.com>
 * @version		1.0
 * @revision	$Revision$
 */
class yf_tpl_smarty {

	// Path to the templates (including current theme path)
	var $TPL_PATH 				= "";
	// Compressing output by cutting "\t","\r","\n","  ","   "
	var $COMPRESS_OUTPUT		= 0; // default value
	// Using SEO - friendly URLs (All links need to be absolute)
	var $REWRITE_MODE			= 0; // default value
	// Custom meta information (could be unique for every page) : page titles, meta keywords, description
	var $CUSTOM_META_INFO		= 1;

	/**
	 * Constructor (PHP 4.x)
	 *
	 * @access	public
	 * @return	void
	 */
	function yf_tpl_smarty () {
		return $this->__construct();
	}

	/**
	 * Constructor (PHP 5.x)
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct () {
		// Directory where themes are stored
		define("SMARTY_THEMES_PATH", "templates/");
		// Template files extensions
		define("_SMARTY_TPL_EXT",	".tpl");
		// Rewriting URLs mode (need to create full paths to images and links)
		$rewrite_mode = conf('rewrite_mode');
		if (isset($rewrite_mode)) {
			$this->REWRITE_MODE	= $rewrite_mode;
		}
		// Set custom skin
		if (!empty($_SESSION["user_skin"]) && MAIN_TYPE_USER) {
			conf('theme', $_SESSION["user_skin"]);
		} elseif (defined('DEFAULT_SKIN')) {
			conf('theme', DEFAULT_SKIN);
		}
		// Seth path to the templates including selected skin
//		$this->TPL_PATH = SMARTY_THEMES_PATH. conf('theme'). "/";
		$this->TPL_PATH = INCLUDE_PATH. SMARTY_THEMES_PATH. conf('theme'). "/";
		// Init Smarty Engine
		define('SMARTY_DIR', PF_PATH.'libs/smarty/libs/');
		// Folder for smarty cache, compiled templates and configs
		$this->SMARTY_VAR_DIR = INCLUDE_PATH. 'smarty_var/';
		// Smarty sub folders (absolute paths)
		$this->smarty_template_dir	= $this->TPL_PATH; 
		$this->smarty_compile_dir	= $this->SMARTY_VAR_DIR. 'templates_c/';
		$this->smarty_config_dir	= $this->SMARTY_VAR_DIR. 'configs/';
		$this->smarty_cache_dir		= $this->SMARTY_VAR_DIR. 'cache/';

		$GLOBALS['tpl_smarty']		= &$this;
	}

	/**
	 * Initialization of the main content
	 * Throws one "echo" at the end
	 *
	 * @access	public
	 * @return	void
	 */
	function init_graphics () {
		$init_type = MAIN_TYPE;
		// If no_graphics flag is set before - do not process
		if (!main()->NO_GRAPHICS) {
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
			// Determine what template need to be loaded in the center area
			$tpl_name = ($init_type == "admin" && (empty($_SESSION['admin_id']) || empty($_SESSION['admin_group']))) ? "login" : "main";
			// Process selected template
			$body["content"] = $this->parse($tpl_name, array("is_logged_in" => intval((bool) $_SESSION["user_id"])));
			// Process custom meta info if needed
			if ($this->CUSTOM_META_INFO && $init_type == "user") {
				$c_meta_info = main()->init_class("custom_meta_info");
				if (is_object($c_meta_info)) $body["content"] = $c_meta_info->_process($body["content"]);
			}
		}
		// Stop processing if needed
		if (!main()->NO_GRAPHICS) {
			// Replace images paths with their absolute ones
			if ($this->REWRITE_MODE && $init_type != "admin") {
				$RW = main()->init_class("rewrite");
				// Replace relative links to their full paths
				if (is_object($RW)) $body["content"] = $RW->_rewrite_replace_links($body["content"]);
			}
			// Show execution time if needed
			if (DEBUG_MODE || conf('exec_time')) {
				$body["exec_time"] = $this->parse("system/debug_info", array("items" => common()->_show_execution_time()));
			}
			// Only while debugging
			if (DEBUG_MODE) {
				$body["debug_info"] = $this->parse("system/debug_info", array("items" => common()->show_debug_info()));
			}
			// Collect all output parts
			$output = implode("", $body);
			// Send main headers
			main()->_send_main_headers(strlen($output));
			// Cleanup output from php code
			$output = preg_replace("/<\?php.*?\?>?/i","",$output);
			// Throw generated output to user
			echo $output;
		}
		// Only while debugging
		if (main()->NO_GRAPHICS && DEBUG_MODE) {
			common()->show_debug_info();
		}
	}

	/**
	 * Parse given template name and return result string
	 */
	function parse($name, $replace = array(), $clear_all = false, $eval_content = false, $replace_images = true, $get_from_db = false, $string = "") {
		// Try to init engine if was not yet (prevent auto-load)
		if (!is_object($this->smarty)) $this->_init_smarty_engine();
		// Clear all assigned before
		$old_vars = $this->smarty->_tpl_vars;
		$this->smarty->clear_all_assign();
		// Assign all passed vars
		foreach ((array)$replace as $k => $v) {
			$this->smarty->assign($k, $v);
		}
		$string = $this->smarty->fetch($name. _SMARTY_TPL_EXT);
		$this->smarty->_tpl_vars = $old_vars;
		// Replace "images/" and "uploads/" to their full web paths
		if ($replace_images) $string = main()->_replace_images_paths($string);
		return $string;
	}

	/**
	 * Execute module method with params
	 */
	function _execute($params) {
		return main()->_execute($params["class"], $params["method"], $params["params"], $tpl_name = "");
	}

	/**
	 * Translate given text
	 */
	function _translate($params) {
		return translate($params["text"]);
	}

	/**
	 * Init Smarty class
	 */
	function _init_smarty_engine () {
		// Stop here if Smarty was loaded
		if (is_object($this->smarty)) return true;
		// Create Smarty class instance (singleton)
		require SMARTY_DIR. 'Smarty.class.php';
		$this->smarty = &new Smarty;
		// Set smarty config params
		$this->smarty->template_dir		= $this->smarty_template_dir; 
		$this->smarty->compile_dir		= $this->smarty_compile_dir;
		$this->smarty->config_dir		= $this->smarty_config_dir;
		$this->smarty->cache_dir		= $this->smarty_cache_dir;
//		$this->smarty->use_sub_dirs		= true;
		$this->smarty->use_sub_dirs		= false;
		$this->smarty->compile_check	= true;
		$this->smarty->debugging		= false;
//		$this->smarty->force_compile	= true;
		$this->smarty->caching			= false;
		$this->smarty->register_function("execute", array(&$this, "_execute"));
		$this->smarty->register_function("translate", array(&$this, "_translate"));
		$this->smarty->register_function("i18n", array(&$this, "_translate"));
		return true;
	}
}
