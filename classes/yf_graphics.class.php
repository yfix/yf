<?php

/**
* ProEngine Content loader class
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_graphics {

	/** @var bool Insert css styleshhet contents into every page or link it as separate file */
	public $EMBED_CSS				= false;
	/** @var bool Cache CSS file in project from framework (works only if EMBED_CSS is off) */
	public $CACHE_CSS				= false;
	/** @var int */
	public $CACHE_CSS_TTL			= 3600;
	/** @var bool */
	public $CSS_FIXES_FOR_IE		= true;
	/** @var bool Try to use "link" tag for CSS */
	public $CSS_USE_LINK_TAG		= true;
	/** @var bool Only one file for CSS */
	public $CSS_USE_ONE_FILE		= true;
	/** @var bool */
	public $CSS_ADD_RESET			= false;
	/** @var bool */
	public $CSS_ADD_BASE			= false;
	/** @var bool Cache Javascript files */
	public $CACHE_JAVASCRIPT		= false;
	/** @var int */
	public $CACHE_JS_TTL			= 3600;
	/** @var bool Add pages names to the title */
	public $ADD_TITLE_PAGES		= true;
	/** @var bool Show auto-parsed (and tried to translate) task name */
	public $SHOW_AUTO_TASK_NAME	= false;
	/** @var bool IFRAME in the center */
	public $IFRAME_CENTER			= false;
	/** @var bool Use Search Engine based keywords block */
	public $USE_SE_KEYWORDS		= false;
	/** @var string Sub-modules dir */
	public $SUB_MODULES_PATH		= "classes/graphics/";
	/** @var string Path to icons */
	public $ICONS_PATH				= "uploads/icons/";
	/** @var string Default HTML Title tag contents */
	public $META_TITLE				= "";
	/** @var string Default HTML Meta tag "keywords" */
	public $META_KEYWORDS			= "";
	/** @var string Default HTML Meta tag "description" */
	public $META_DESCRIPTION		= "";
	/** @var bool Enable quick menu */
	public $QUICK_MENU_ENABLED		= true;
	/** @var bool Use Firebug-Lite javascript debug library */
	public $USE_FIREBUG_LITE		= false;
	/** @var bool Menu hide links to disabled modules */
	public $MENU_HIDE_INACTIVE_MODULES	= false;
	/** @var bool */
	public $NOT_FOUND_RAISE_WARNING= true;
	/** @var bool */
	public $HEADER_POWERED_BY		= true;
	/** @var bool */
	public $JS_CONSOLE_ALLOW		= true;
	/** @var string Required for the compatibility with old main class */
	public $MEDIA_PATH				= "";
	/** @var */
	public $_css_loaded_from		= array();

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Framework constructor
	*/
	function _init() {
		// Default user group
		if (empty($_SESSION['user_group'])) {
			$_SESSION['user_group'] = 1;
		}
		// Try to assign class properties from global settings
		$embed_css = conf('embed_css');
		if (isset($embed_css)) {
			$this->EMBED_CSS = $embed_css;
		}
		$add_title_pages = conf('add_title_pages');
		if (isset($add_title_pages)) {
			$this->ADD_TITLE_PAGES = $add_title_pages;
		}
		$iframe_center = conf('iframe_center');
		if (isset($iframe_center)) {
			$this->IFRAME_CENTER = $iframe_center;
		}
		// Overload protection
		if (conf('HIGH_CPU_LOAD') == 1) {
			$this->USE_SE_KEYWORDS = false;
		}
		// Force hide inactive menu items inside admin section
		if (MAIN_TYPE_ADMIN) {
			$this->MENU_HIDE_INACTIVE_MODULES = true;
		}
		$this->MEDIA_PATH = WEB_PATH;
		if (defined("MEDIA_PATH")) {
			$this->MEDIA_PATH = MEDIA_PATH;
		}
	}

	/**
	* Show CSS
	*
	* @access	private
	* @return	string	CSS style or code to load it
	*/
	function show_css () {
		$body = "";
		// Reset style
		$_reset_css = "";
		if ($this->CSS_ADD_RESET) {
			$_reset_css = $this->_load_css_file("reset");
			$body .= $this->_show_css_file($_reset_css, "reset");
		}
		// Base CSS style (positioning of main elements only)
		$_base_css = $this->_load_css_file("base");
		$body .= $this->_show_css_file($_base_css, "base");
		// Main style
		$_main_css = $this->_load_css_file("style");
		$body .= $this->_show_css_file($_main_css, "style");
		// Custom module style (eg. place: "forum/forum.css")
		$_user_modules = main()->get_data("user_modules");
		if ($_user_modules && is_array($_user_modules)) {
			foreach ((array)main()->modules as $_name => $_obj) {
				if ($_name == $_GET["object"] || !isset($_user_modules[$_name])) {
					continue;
				}
				$_module_css = $this->_load_css_file($_name."/".$_name);
				$body .= $this->_show_css_file($_module_css, $_name);
			}
		}
		$_user_custom_css = "";
		$_user_custom_css_ie = "";
		$_color_theme_css = "";
		$_ie_fixes_css = "";
		// Current module CSS
		$_module_css = $this->_load_css_file($_GET['object']."/".$_GET['object']);
		$body .= $this->_show_css_file($_module_css, $_GET['object']);
		// Custom user layout
		if (MAIN_TYPE_USER) {
			$user_layout = isset($_COOKIE["layout"]) ? $_COOKIE["layout"] : "";
			// Override with default color theme (if exists one)
			if (!isset($user_layout["color_theme"]) && defined('DEFAULT_COLOR_THEME')) {
				$user_layout["color_theme"] = DEFAULT_COLOR_THEME;
			}
			// Use selected color theme
			if (isset($user_layout["color_theme"])) {
				$_color_theme_label = "color_theme_".$user_layout["color_theme"];
				$_color_theme_css = $this->_load_css_file($_color_theme_label);
				if ($_color_theme_css) {
					conf("color_theme", $user_layout["color_theme"]);
				}
			}
			// If nothing custom found - then try to use default one
			if (empty($_color_theme_css)) {
				$_color_theme_label = "color_theme_default";
				$_color_theme_css = $this->_load_css_file($_color_theme_label);
			}
			$body .= $this->_show_css_file($_color_theme_css, $_color_theme_label);
			// Custom font size
			if (isset($user_layout["font_size"])) {
				$_user_custom_css		.= "html{font-size:".$user_layout["font_size"]."%;}";
				$_user_custom_css_ie	.= "\r\nhtml{font-size:".$user_layout["font_size"]."%;}";
			}
			// Custom page width
			if (isset($user_layout["max_page_width"])) {
				$_user_custom_css		.= "body{max-width:".$user_layout["max_page_width"]."px;}";
				$_user_custom_css_ie	.= "\r\nbody{width:expression((documentElement.offsetWidth || document.body.offsetWidth) > ".$user_layout["max_page_width"]." ? '".$user_layout["max_page_width"]."px' : 'auto');}";
			}
			$body .= $this->_show_css_file($_user_custom_css, "user_custom");
		}
		// Custom module color style (eg. place: "forum/color_theme_black.css")
		if (MAIN_TYPE_USER && $_module_css && $_color_theme_label) {
			$_color_module_css = $this->_load_css_file($_GET['object']."/".$_color_theme_label);
			$body .= $this->_show_css_file($_color_module_css, $_GET['object']."_".$_color_theme_label);
		}
		// Load IE specific code
		if ($this->CSS_FIXES_FOR_IE || MAIN_TYPE_ADMIN) {
			if (!$_ie_fixes_css) {
				$_ie_fixes_css	= $this->_load_css_file("ie_only");
			}
		}
		if ($_ie_fixes_css || $_user_custom_css_ie) {
			$body .= $this->_show_css_file($_ie_fixes_css. $_user_custom_css_ie, "ie_only", "ie");
		}
		$iepngfix_url = $this->_load_css_file("iepngfix.htc");
		$body .= $iepngfix_url && strlen($iepngfix_url) < 256 ? "<!--[if lt IE 7]><style type='text/css'>img{behavior: url('".$iepngfix_url."');}</style><![endif]-->" : "";
		return $body;
	}

	/**
	* Try to load CSS file with inheritance
	*/
	function _show_css_file ($css = "", $keyword = "", $option = "") {
		if (!$css) {
			return false;
		}
		// Try to use "link" tag if applicable
		if ($this->CSS_USE_LINK_TAG && $this->_css_loaded_from[$keyword] == "project" && !$this->EMBED_CSS) {
			$body = "<link rel=\"stylesheet\" type=\"text/css\" href=\""._prepare_html(substr($css, strlen("@import url('"), -strlen("');")))."\" />";
		} else {
			$body = "<style type=\"text/css\">".($keyword ? "/*".$keyword."*/" : "")."\r\n".$css."\r\n</style>";
		}
		if ($option == "ie") {
			$body = "<!--[if lt IE 8]>".$body."<![endif]-->";
		}
		$body = "\r\n".$body."\r\n";
		return $body;
	}

	/**
	* Try to load CSS file with inheritance
	*/
	function _load_css_file ($name = "") {
		$CACHE_CSS = $this->CACHE_CSS;
		$EMBED_CSS = $this->EMBED_CSS;

		$css_name	= $name.".css";
		$_name_for_cache = str_replace(array(".css", "/"), array("", "__"), $name). "__cached.css";
		if ($name == "iepngfix.htc") {
			$css_name = $name;
			$_name_for_cache = "iepngfix__cached.htc";
			$CACHE_CSS = true; // Force not embedding .htc file
			$EMBED_CSS = false;
		}
		$TPL_PATH	= tpl()->TPL_PATH;
		$FS_PATH	= PROJECT_PATH. $TPL_PATH. $css_name;

		$_exists_in_proj = file_exists($FS_PATH);
		// Try inherited skin
		if (!$_exists_in_proj && conf('INHERIT_SKIN')) {
			$TPL_PATH = "templates/". conf('INHERIT_SKIN'). "/";
			$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
			$_exists_in_proj = file_exists($FS_PATH);
		}
		if (!$_exists_in_proj && conf('INHERIT_SKIN2')) {
			$TPL_PATH = "templates/". conf('INHERIT_SKIN2'). "/";
			$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
			$_exists_in_proj = file_exists($FS_PATH);
		}
		// Use cached file (only if no such file found in project)
		if ($CACHE_CSS && !$EMBED_CSS && !$_exists_in_proj) {
			// Try to use cached file
			$CACHED_CSS = PROJECT_PATH. $TPL_PATH. $_name_for_cache;
			$_cache_refresh = false;
			if (!file_exists($CACHED_CSS)) {
				$_cache_refresh = true;
			} elseif (filemtime($CACHED_CSS) < (time() - $this->CACHE_CSS_TTL)) {
				$_cache_refresh = true;
			}
			$FS_PATH = YF_PATH. "templates/".MAIN_TYPE."/". $css_name;
			if (file_exists($FS_PATH) && $_cache_refresh) {
				_mkdir_m(dirname($CACHED_CSS));
				if (is_writable($CACHED_CSS)) {
					file_put_contents($CACHED_CSS, file_get_contents($FS_PATH));
				}
			}
			if (file_exists($CACHED_CSS)) {
				$this->_css_loaded_from[$name] = "cache";
				$web_path = $this->MEDIA_PATH. $TPL_PATH. $_name_for_cache;
				if ($name == "iepngfix.htc") {
					return $web_path;
				} else {
					return "@import url('".$web_path."');\r\n";
				}
			}
		}
		// Common way
		$FS_PATH = PROJECT_PATH. $TPL_PATH. $css_name;
		if ($_exists_in_proj) {
			$this->_css_loaded_from[$name] = "project";
			// Force embedding CSS
			if ($EMBED_CSS) {
				return file_get_contents($FS_PATH);
			} else {
				$web_path = $this->MEDIA_PATH. $TPL_PATH. $css_name;
				if ($name == "iepngfix.htc") {
					return $web_path;
				} else {
					return "@import url('".$web_path."');";
				}
			}
		}
		// Check if main CSS exists in project
		// If true - stop trying to load other CSS files from framework
		if (isset($this->_css_loaded_from["style"]) && $this->_css_loaded_from["style"] == "project" && !$EMBED_CSS) {
			return false;
		}
		// Try to load from admin section
		if (MAIN_TYPE_ADMIN) {
			$FS_PATH = YF_PATH. "templates/admin/". $css_name;
			if (file_exists($FS_PATH)) {
				$this->_css_loaded_from[$name] = "framework_admin";
				return file_get_contents($FS_PATH);
			}
		}
		// Try framework user section
		$FS_PATH = YF_PATH. "templates/user/". $css_name;
		if (file_exists($FS_PATH)) {
			$this->_css_loaded_from[$name] = "framework_user";
			return file_get_contents($FS_PATH);
		}
		// Nothing found
		return false;
	}

	/**
	* Common javascript loader
	*
	* @access	private
	* @return	string	Output
	*/
	function show_javascript () {
		if (conf("no_js")) {
			return false;
		}
		$replace = array(
			"js_console_allow"	=> intval((bool)$this->JS_CONSOLE_ALLOW),
		);
		$body = tpl()->parse("system/main_js", $replace);

		$ie6_js_path = tpl()->TPL_PATH. "css/ie6.js";
		if (file_exists(PROJECT_PATH. $ie6_js_path)) {
			$body .= "\n<!--[if IE 6]><script type=\"text/javascript\" src=\"".$this->MEDIA_PATH. $ie6_js_path."\"></script><![endif]-->\n";
		}
		// Connect Firbug Lite
		if (DEBUG_MODE && $this->USE_FIREBUG_LITE) {
			$body .= "\n<script type='text/javascript' src='".$this->MEDIA_PATH."js/firebug-lite-compressed.js'></script>\n";
		}
		if ($this->CACHE_JAVASCRIPT) {
			$cache_file_name = "site.js";
			// Try to use cached file
			$CACHED_JS		= "js/__cache/".
				str_replace(array("templates/", "/"), array("", "_"), tpl()->TPL_PATH).
				preg_replace("#[^0-9a-z]#", "", $_SERVER["HTTP_HOST"]).
				$cache_file_name;
			$CACHED_FS_JS	= PROJECT_PATH. $CACHED_JS;
			$CACHED_WEB_JS	= $this->MEDIA_PATH. $CACHED_JS;
			$_cache_refresh = false;
			if (!file_exists($CACHED_FS_JS)) {
				$_cache_refresh = true;
			} elseif (filemtime($CACHED_FS_JS) < (time() - $this->CACHE_JS_TTL)) {
				$_cache_refresh = true;
			}
			$urls = array();

			$p = "#<script [^>]*src=[\"']{1}?(.*?)[\"']{1}?[^>]*>[^<]*?</script>#ims";
			if (preg_match_all($p, $body, $m)) {
				$web_path_len = strlen($this->MEDIA_PATH);
				foreach((array)$m[1] as $id => $_src) {
					if (false === strpos($m[0][$id], "yf:cacheable")) {
						unset($m[0][$id]);
						continue;
					}
					$_src = trim($_src);
					// Check for the current domain
					if (substr($_src, 0, $web_path_len) != $this->MEDIA_PATH) {
				//		continue;
					}
					$urls[$_src] = $_src;
				}
				$to_replace = $m[0];
			}
			if ($urls) {
				if ($_cache_refresh) {
					foreach ((array)common()->multi_request($urls) as $_src => $text) {
						$new_contents .= "\n/** source: ".$_src." */\n".$text;
					}
					if (!empty($new_contents)) {
						$new_contents = "/** cached time: ".date("YmdHis")." */\n".$new_contents;
						if (!file_exists(dirname($CACHED_FS_JS))) {
							_mkdir_m(dirname($CACHED_FS_JS));
						}
						file_put_contents($CACHED_FS_JS, $new_contents);
					}
				}
				if (file_exists($CACHED_FS_JS)) {
					$body = trim(str_replace($to_replace, "", $body));
					$body = "<script type=\"text/javascript\" src=\"".$CACHED_WEB_JS."\"></script>\n". $body;
				}
			}
		}
		return $body;
	}

	/**
	* Show site title
	*
	* @access	private
	* @return	string	Formatted site title
	*/
	function show_site_title () {
		if (defined("SITE_ADVERT_NAME")) {
			$title = SITE_ADVERT_NAME;
		}
		if (conf("SITE_ADVERT_NAME")) {
			$title = conf("SITE_ADVERT_NAME");
		}
		if (defined("SITE_TITLE")) {
			$title = SITE_TITLE;
		}
		if (!empty($this->META_TITLE)) {
			$title = $this->META_TITLE;
		}
		// For compatibility with old versions
		$website_name = conf('website_name');
		if (strlen($website_name)) {
			$title = _prepare_html($website_name);
		}
		// Add pages names to the title
		if ($this->ADD_TITLE_PAGES) {
			if (strlen($_GET['object']) && $_GET['object'] != "static_pages") {
				$title .= " :: "._ucfirst(t($_GET['object']));
			}
		}
		// Override by hook method
		$method_name = "_site_title";
		$obj = module($_GET["object"]);
		if (method_exists($obj, $method_name)) {
			$title = _prepare_html($obj->$method_name($title));
		}
		// Force by global var
		$conf_title = conf('site_title');
		if ($conf_title) {
			$title = _prepare_html($conf_title);
		}
		return $title;
	}

	/**
	* Show metatags
	*
	* @access	private
	* @return	string	Meta tags
	*/
	function show_metatags () {
		$charset = conf('charset');
		if (!$charset) {
			$charset = "utf-8";
		}
		$meta_keywords = conf('meta_keywords');
		if (!$meta_keywords) {
			$meta_keywords = $this->META_KEYWORDS;
		}
		$meta_description = conf('meta_description');
		if (!$meta_description) {
			$meta_description = $this->META_DESCRIPTION;
		}
		$meta = array(
			"charset"		=> $charset,
			"keywords"		=> $meta_keywords,
			"description"	=> $meta_description,
		);
		// Override by hook method
		$method_name = "_hook_meta_tags";
		$obj = module($_GET["object"]);
		if (method_exists($obj, $method_name)) {
			$meta = _prepare_html($obj->$method_name($meta));
		}
		if (DEBUG_MODE) {
			debug('_DEBUG_META', $meta);
		}
		$replace = array(
			"charset"		=> _prepare_html($meta["charset"]),
			"keywords"		=> $meta["keywords"],
			"description"	=> $meta["description"],
		);
		return tpl()->parse("system/meta_tags", $replace);
	}

	/**
	* Show menu (alias for the "_show_menu")
	*/
	function show_menu ($params) {
		return $this->_show_menu($params);
	}

	/**
	* Display main "center" block contents
	*
	* @access	private
	* @return	string	Block output
	*/
	function show_center () {
		if ($this->USE_SE_KEYWORDS) {
			$this->_set_se_keywords();
		}
		if ($this->IFRAME_CENTER) {
			if (false !== strpos($_SERVER['QUERY_STRING'], "center_area=1")) {
				main()->NO_GRAPHICS = true;
				$replace = array(
					"css"	=> "<link rel='stylesheet' type='text/css' href='".$this->MEDIA_PATH. tpl()->TPL_PATH. "style.css'>",
					"text"	=> $this->tasks(1),
				);
				$body = tpl()->parse("system/empty_page", $replace);
				echo module("rewrite")->_replace_links_for_iframe($body);
			} else {
				$replace = array(
					"src"	=> WEB_PATH."?".(strlen($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING']."&" : "")."center_area=1",
				);
				$body .= tpl()->parse("system/iframe", $replace);
			}
		} else {
			if (false !== strpos($_SERVER['QUERY_STRING'], "center_area=1")) {
				main()->NO_GRAPHICS = true;
				$replace = array(
					"css"	=> "<link rel='stylesheet' type='text/css' href='".$this->MEDIA_PATH. tpl()->TPL_PATH."style.css'>",
					"text"	=> $this->tasks(1),
				);
				echo tpl()->parse("system/empty_page", $replace);
			} else {
				$body = $this->tasks(1);
			}
		}
		return $body;
	}

	/**
	* Alias for the "_show_block"
	*/
	function show_block ($params = array()) {
		return $this->_show_block($params);
	}

	/**
	* Show custom block contents
	*
	* @access	private
	* @param	array	$input		Array with key "name" - name of block to show here
	* @return	string	Output
	*/
	function _show_block ($input = array()) {
		$block_name = $input["name"];
		if (empty($block_name)) {
			trigger_error("GRAPHICS: Given empty block name to show", E_USER_WARNING);
			return false;
		}
		// Try to get available blocks names
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data("blocks_names");
		}
		// Check if such block exists
		if (empty($this->_blocks_infos)) {
			if (!$this->_error_no_blocks_raised) {
				trigger_error("GRAPHICS: Blocks names not loaded", E_USER_WARNING);
				$this->_error_no_blocks_raised = true;
			}
			return false;
		}
		$BLOCK_EXISTS = false;
		foreach ((array)$this->_blocks_infos as $block_info) {
			// Skip blocks from other init type ("admin" or "user")
			if (trim($block_info["type"]) != MAIN_TYPE) {
				continue;
			}
			// Found!
			if ($block_info["name"] == $block_name) {
				$BLOCK_EXISTS = true;
				$block_id = $block_info["id"];
				break;
			}
		}
		if (!$BLOCK_EXISTS) {
			trigger_error("GRAPHICS: Block name \""._prepare_html($block_name)."\" not found in blocks list", E_USER_WARNING);
			return false;
		}
		// Block is inactive, stop here
		if (!$this->_blocks_infos[$block_id]["active"]) {
			return false;
		}
		// Check rules
		if (!$this->_check_block_rights($block_id, $_GET["object"], $_GET["action"])) {
			return $this->_action_on_block_denied($block_name);
		}
		// Set SE keywords if allowed
		if (MAIN_TYPE_USER && $block_name == "center_area" && $this->USE_SE_KEYWORDS) {
			$this->_set_se_keywords();
		}
		$cur_block_info = $this->_blocks_infos[$block_id];
		/* 	If special object method specified - then call it

			Syntax: [path_to]$class_name.$method_name

			@example "static_pages.show"
			@example "classes/minicalendar.createcalendar"
		*/
		if (!empty($cur_block_info["method_name"])) {
			$special_path = "";
			if (false !== strpos($cur_block_info["method_name"], "/")) {
				$special_path = substr($cur_block_info["method_name"], 0, strrpos($cur_block_info["method_name"], "/") + 1);
				$cur_block_info["method_name"] = substr($cur_block_info["method_name"], strrpos($cur_block_info["method_name"], "/") + 1);
			}
			list($special_class_name, $special_method_name) = explode(".", $cur_block_info["method_name"]);
			$special_params = array(
				"block_name"	=> $block_name,
				"block_id"		=> $block_id,
			);
			if (!empty($special_class_name) && !empty($special_method_name)) {
				return _class($special_class_name, $special_path)->$special_method_name($special_params);
			}
		}
		// If template name specified - then use it
		$STPL_NAME = $block_name;
		if (!empty($cur_block_info["stpl_name"])) {
			$STPL_NAME = $cur_block_info["stpl_name"];
		}
		// Show template contents
		$replace = array(
			"block_name"	=> $block_name,
			"block_id"		=> $block_id,
		);
		return tpl()->parse($STPL_NAME, $replace);
	}

	/**
	* Action to on denied block
	*
	* @access	private
	* @return	void
	*/
	function _action_on_block_denied ($block_name = "") {
		if (MAIN_TYPE_USER && !main()->USER_ID && $block_name == "center_area") {
			$redir_params = array(
				'%%object%%'		=> $_GET["object"],
				'%%action%%'		=> $_GET["action"],
				'%%add_get_vars%%'	=> str_replace("&",";",_add_get(array("object","action"))),
			);
			$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), main()->REDIR_URL_DENIED);
			if (!empty($redir_url)) {
				if ($_GET["object"] == "login_form") {
					return "Access to login form denied on center block <br />\n(graphics->_action_on_block_denied)";
				} else {
					return js_redirect($redir_url);
				}
			}
		}
		return false;
	}

	/**
	* Try to find id of the center block
	*/
	function _get_center_block_id() {
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data("blocks_names");
		}
		$center_block_id = 0;
		foreach ((array)$this->_blocks_infos as $cur_block_id => $cur_block_info) {
			if ($cur_block_info["type"] == MAIN_TYPE && trim($cur_block_info["name"]) == "center_area") {
				$center_block_id = $cur_block_id;
				break;
			}
		}
		return $center_block_id;
	}

	/**
	* Load array of blocks rules
	*
	* @access	private
	* @return	array		Array of rules for blocks
	*/
	function _load_blocks_rules () {
		// Prevent muliple calls
		if (!empty($this->_blocks_rules)) {
			return false;
		}
		// Get rules from db
		$rules = main()->get_data("blocks_rules");
		// Array of rule names to skip in pre procesing
		$rule_names_to_skip = array("id","block_id","rule_type","active","order");
		// Prepare rulses for best use
		foreach ((array)$rules as $rule_id => $rule_info) {
			foreach ((array)$rule_info as $rule_name => $rule_text) {
				// Skip rule items no needed to process
				if (in_array($rule_name, $rule_names_to_skip) || empty($rule_text)) {
					continue;
				}
				// Cleanup rule text
				$rule_text = trim(str_replace(array(" ","\t","\r","\n","\"","'",",,"), "", $rule_text), ",");
				// Convert rule into array if needed
				$rule_text = explode(",",$rule_text);
				// Save processed rule text
				$rules[$rule_id][$rule_name] = $rule_text;
			}
		}
		$this->_blocks_rules = $rules;
	}

	/**
	* Check rights for blocks
	*
	* @access	private
	* @param	string	$block_name		Name of block to check
	* @return	bool					Access result for given block
	*/
	function _check_block_rights ($block_id = 0, $OBJECT = "", $ACTION = "") {
		if (empty($block_id) || empty($OBJECT)) {
			return false;
		}
		// use default action for check rights if not assigned
		if(empty($ACTION)) {
			$ACTION = "show";
		}
		$CUR_USER_GROUP = intval(MAIN_TYPE_ADMIN ? $_SESSION["admin_group"] : $_SESSION["user_group"]);
		$CUR_USER_THEME	= conf('theme');
		$CUR_LOCALE		= conf('language');
		$CUR_SITE		= (int)conf('SITE_ID');
		$CUR_SERVER		= (int)conf('SERVER_ID');
		$RESULT = false;
		if (!isset($this->_blocks_rules)) {
			$this->_load_blocks_rules();
		}
		foreach ((array)$this->_blocks_rules as $rule_id => $rule_info) {
			// Skip rules that not match current block
			if ($rule_info["block_id"] != $block_id) {
				continue;
			}
			$matched_method		= false;
			$matched_user_group	= false;
			$matched_theme		= false;
			$matched_locale		= false;
			$matched_site		= false;
			$matched_server		= false;
			// Check matches
			if (is_array($rule_info["methods"]) && (in_array($OBJECT, $rule_info["methods"]) || in_array($OBJECT.".".$ACTION, $rule_info["methods"]))) {
				$matched_method = true;
			}
			if (is_array($rule_info["user_groups"]) && in_array($CUR_USER_GROUP, $rule_info["user_groups"])) {
				$matched_user_group = true;
			}
			if (is_array($rule_info["themes"]) && in_array($CUR_USER_THEME, $rule_info["themes"])) {
				$matched_theme = true;
			}
			if (is_array($rule_info["locales"]) && in_array($CUR_LOCALE, $rule_info["locales"])) {
				$matched_locale = true;
			}
			if (is_array($rule_info["site_ids"]) && in_array($CUR_SITE, $rule_info["site_ids"])) {
				$matched_site = true;
			}
			if (is_array($rule_info["server_ids"]) && in_array($CUR_SERVER, $rule_info["server_ids"])) {
				$matched_server = true;
			}
			if ((!is_array($rule_info["methods"])		|| $matched_method)
				&& (!is_array($rule_info["user_groups"])|| $matched_user_group)
				&& (!is_array($rule_info["themes"])		|| $matched_theme	|| !$CUR_USER_THEME)
				&& (!is_array($rule_info["locales"])	|| $matched_locale	|| !$CUR_LOCALE)
				&& (!is_array($rule_info["site_ids"])	|| $matched_site	|| !$CUR_SITE)
				&& (!is_array($rule_info["server_ids"])	|| $matched_server	|| !$CUR_SERVER)
			) {
				$RESULT = trim($rule_info["rule_type"]) == "ALLOW" ? true : false;
			}
		}
		return $RESULT;
	}

	/**
	* Try to run center block module/method if allowed
	*/
	function prefetch_center() {
		$block_name = "center_area";
		// Try to get available blocks names
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data("blocks_names");
		}
		// Check if such block exists
		if (empty($this->_blocks_infos)) {
			return false;
		}
		$BLOCK_EXISTS = false;
		foreach ((array)$this->_blocks_infos as $block_info) {
			// Skip blocks from other init type ("admin" or "user")
			if (trim($block_info["type"]) != MAIN_TYPE) {
				continue;
			}
			// Found!
			if ($block_info["name"] == $block_name) {
				$BLOCK_EXISTS = true;
				$block_id = $block_info["id"];
				break;
			}
		}
		if (!$BLOCK_EXISTS) {
			return false;
		}
		// Block is inactive, stop here
		if (!$this->_blocks_infos[$block_id]["active"]) {
			return false;
		}
		// Check rules
		if (!$this->_check_block_rights($block_id, $_GET["object"], $_GET["action"])) {
			return $this->_action_on_block_denied($block_name);
		}
		// Do run
		return $this->tasks(1);
	}

	/**
	* Task loader image
	*
	* @access	private
	* @return	string	Code to load task manager
	*/
	function _show_task_loader_image () {
		if (!main()->USE_TASK_MANAGER) {
			return false;
		}
		// Get task next run from db cache
		$db_cache_data = main()->get_data("db_cache");
		$cache_from_db = $db_cache_data["task_next_run"]["value"];
		if (!empty($cache_from_db)) {
			$cache_array = unserialize($cache_from_db);
			$this->_task_next_run = $cache_array["task_next_run"];
		}
		// Check if need to load task
		if (time() >= $this->_task_next_run) {
			$body = "<!--task--><img src='".process_url("./?object=task_loader")."' border='0' height='0' width='0' /><!--/task-->";
		}
		return $body;
	}

	/**
	* Old style Translation function
	*
	* @access	private
	* @param	array	$input	Array of params passed from template
	* @return	string	Output
	*/
	function translate ($input = "") {
		if (in_array($input['extra'], array("ucfirst","ucwords","strtoupper","strtolower"))) {
			// Use our internal function
			$func_name = "_".$input['extra'];
			return $func_name(translate($input['value']));
		} else {
			return translate($input['value']);
		}
	}

	/**
	* Welcome message method
	*
	* @access	private
	* @return	string	Output
	*/
	function show_welcome () {
		return _class("graphics_welcome", $this->SUB_MODULES_PATH)->_show_welcome();
	}

	/**
	* Welcome message for the admin section
	*
	* @access	private
	* @return	string	Output
	*/
	function _show_welcome2 () {
		return _class("graphics_welcome", $this->SUB_MODULES_PATH)->_show_welcome2();
	}

	/**
	* Show SE Keywords
	*
	* @access	private
	* @return	string	Output
	*/
	function _show_se_keywords ($input = "") {
		if (!$this->USE_SE_KEYWORDS) {
			return false;
		}
		return _class("se_keywords")->_show_search_keywords($input);
	}

	/**
	* Set SE Keywords
	*
	* @access	private
	* @return	void
	*/
	function _set_se_keywords () {
		if (!$this->USE_SE_KEYWORDS) {
			return false;
		}
		return _class("se_keywords")->_set_search_keywords();
	}

	/**
	* Show menu
	*
	* @access	private
	* @return	string	Formatted HTML menu contents
	*/
	function _show_menu ($input = array()) {
		/*
		$_item_types = array(
			1 => "Internal link",
			2 => "External link",
			3 => "Spacer",
		);
		*/
		$RETURN_ARRAY	= isset($input["return_array"]) ? $input["return_array"] : false;
		$force_stpl_name= isset($input["force_stpl_name"]) ? $input["force_stpl_name"] : false;
		$menu_name		= $input["name"];
		if (empty($menu_name)) {
			trigger_error("GRAPHICS: Given empty menu name to display", E_USER_WARNING);
			return false;
		}
		if (!isset($this->_menus_infos)) {
			$this->_menus_infos = main()->get_data("menus");
		}
		if (empty($this->_menus_infos)) {
			if (!$this->_error_no_menus_raised) {
				trigger_error("GRAPHICS: Menus info not loaded", E_USER_WARNING);
				$this->_error_no_menus_raised = true;
			}
			return false;
		}
		$MENU_EXISTS = false;
		foreach ((array)$this->_menus_infos as $menu_info) {
			if ($menu_info["type"] != MAIN_TYPE) {
				continue;
			}
			if ($menu_info["name"] == $menu_name) {
				$MENU_EXISTS = true;
				$menu_id = $menu_info["id"];
				break;
			}
		}
		if (!$MENU_EXISTS) {
			trigger_error("GRAPHICS: Menu name \""._prepare_html($menu_name)."\" not found in menus list", E_USER_WARNING);
			return false;
		}
		$cur_menu_info	= &$this->_menus_infos[$menu_id];
		if (!$cur_menu_info["active"]) {
			return false;
		}
		if (!isset($this->_menu_items)) {
			$this->_menu_items = main()->get_data("menu_items");
		}
		// Do not show menu if there is no items in it
		if (empty($this->_menu_items[$menu_id])) {
			return false;
		}
		$center_block_id = $this->_get_center_block_id();

		// Check if we need to call special menu handler
		$special_class_name = "";
		$special_method_name = "";
		if (false !== strpos($cur_menu_info["method_name"], ".")) {
			list($special_class_name, $special_method_name) = explode(".", $cur_menu_info["method_name"]);
		}
		$special_params = array(
			"menu_name"	=> $menu_name,
			"menu_id"	=> $menu_id,
		);
		$menu_items = array();
		if (!empty($special_class_name) && !empty($special_method_name)) {
			$menu_items = _class($special_class_name, $special_path)->$special_method_name($special_params);
		} else {
			$menu_items = $this->_recursive_get_menu_items($menu_id);
		}
		if ($force_stpl_name) {
			$cur_menu_info["stpl_name"] = $force_stpl_name;
		}
		$STPL_MENU_ITEM		= !empty($cur_menu_info["stpl_name"]) ? $cur_menu_info["stpl_name"]."_item" : "system/menu_item";
		$STPL_MENU_MAIN 	= !empty($cur_menu_info["stpl_name"]) ? $cur_menu_info["stpl_name"] : "system/menu_main";
		$STPL_MENU_PAD		= !empty($cur_menu_info["stpl_name"]) ? $cur_menu_info["stpl_name"]."_pad" : "system/menu_pad";
		$level_pad_text		= tpl()->parse($STPL_MENU_PAD);

		$menu_items_to_display = array();
		foreach ((array)$menu_items as $item_id => $item_info) {
			if (empty($item_info)) {
				continue;
			}
			// Check PHP conditional code for display
			if (!empty($item_info["cond_code"])) {
				$cond_result = (bool)@eval("return (".$item_info["cond_code"].");");
				if (!$cond_result) {
					continue;
				}
			}
			// Internal link
			if ($item_info["type_id"] == 1 && strlen($item_info['location']) > 0) {
				parse_str($item_info['location'], $_item_parts);
				if ($this->MENU_HIDE_INACTIVE_MODULES) {
					if (!isset($this->_active_modules)) {
						$cl_name = MAIN_TYPE_USER ? "user_modules" : "admin_modules";
						$this->_active_modules = _class($cl_name, "admin_modules/")->_get_modules();
					}
					if ($_item_parts["object"] && !isset($this->_active_modules[$_item_parts["object"]])) {
						continue;
					}
					if ($center_block_id && !$this->_check_block_rights($center_block_id, $_item_parts["object"], $_item_parts["action"]) && $_item_parts["task"] != "logout") {
						continue;
					}
				}
			}
			$menu_items_to_display[] = $item_info;
		}
		// Check for empty blocks starts with spacers
		if ($this->MENU_HIDE_INACTIVE_MODULES) {
			foreach ((array)$menu_items_to_display as $i => $item) {
				if ($item["level_num"] == 0 && $item["type_id"] == 3) {
					$next_item = $menu_items_to_display[$i + 1];
					if (!$next_item || ($next_item["level_num"] == 0 && $next_item["type_id"] == 3)) {
						unset($menu_items_to_display[$i]);
					}
				}
			}
		}
		$num_menu_items = count($menu_items_to_display);
		$_prev_level = 0;
		$_next_level = 0;
		$item_counter = 0;
		$IN_OUTPUT_CACHE = main()->_IN_OUTPUT_CACHE;
		foreach ((array)$menu_items_to_display as $i => $item_info) {
			$item_counter++;
			$_next_info	= isset($menu_items_to_display[$i + 1]) ? $menu_items_to_display[$i + 1] : array();
			$_next_level = isset($_next_info["level"]) ? (int)$_next_info["level"] : 0;
			$is_cur_page = false;
			$item_link = "";
			// Internal link
			if ($item_info["type_id"] == 1 && strlen($item_info['location']) > 0) {
				parse_str($item_info['location'], $_item_parts);
				$item_link = "./?".$item_info['location'];
				// Check if we are on the current page
				if (isset($_item_parts["object"]) && $_item_parts["object"] && $_item_parts["object"] == $_GET["object"]) {
					if (isset($_item_parts["action"]) && $_item_parts["action"]) {
						if ($_item_parts["action"] == $_GET["action"]) {
							// Needed for static pages
							if ($_item_parts["id"]) {
								if ($_item_parts["id"] == $_GET["id"]) {
									$is_cur_page = true;
								}
							} else {
								$is_cur_page = true;
							}
						}
					} else {
						$is_cur_page = true;
					}
				}
			} elseif ($item_info["type_id"] == 2) {
				$item_link = $item_info['location'];
			}
			// Prepare icon path = WEB_PATH. $this->ICONS_PATH. $item_info["icon"];
			$icon_path = "";
			if ($item_info["icon"] && file_exists(PROJECT_PATH. $this->ICONS_PATH. $item_info["icon"])) {
				$icon_path = $this->MEDIA_PATH. $this->ICONS_PATH. $item_info["icon"];
			}
			// Icon class from bootstrap icon class names 
			$icon_class = "";
			if ($item_info["icon"] && (strpos($item_info["icon"], ".") === false)) {
				$icon_class = $item_info["icon"];
			}
			$replace2 = array(
				"item_id"		=> intval($item_info['id']),
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"link"			=> !empty($IN_OUTPUT_CACHE) ? process_url($item_link) : $item_link,
				"name"			=> _prepare_html(translate($item_info['name'])),
				"level_pad"		=> str_repeat($level_pad_text, $item_info["level"]),
				"level_num"		=> intval($item_info["level"]),
				"prev_level"	=> intval($_prev_level),
				"next_level"	=> intval($_next_level),
				"type_id"		=> $item_info["type_id"],
				"icon_path"		=> $icon_path,
				"icon_class"	=> $icon_class,
				"is_first_item"	=> (int)($item_counter == 1),
				"is_last_item"	=> (int)($item_counter == $num_menu_items),
				"is_cur_page"	=> (int)$is_cur_page,
				"have_children"	=> intval((bool)$item_info["have_children"]),
				"next_level_diff"=> intval(abs($item_info["level"] - $_next_level)),
			);
			$items[$item_info["id"]] = $replace2;
			// Save current level for the next iteration
			$_prev_level = $item_info["level"];
		}
		if ($RETURN_ARRAY) {
			return $items;
		}
		foreach ((array)$items as $id => $item) {
			$items[$id] = tpl()->parse($STPL_MENU_ITEM, $item);
		}
		// Process main template
		$replace = array(
			"items" => implode("", (array)$items),
		);
		return tpl()->parse($STPL_MENU_MAIN, $replace);
	}

	/**
	* Template for the custom class method for menu block (useful to inherit)
	*/
	function _custom_menu_items($params = array()) {
		// Example what passes by params
		$params = array(
			"menu_name"	=> $menu_name,
			"menu_id"	=> $menu_id,
		);
		return false;
	}

	/**
	* Get menu items ordered array (recursively)
	*/
	function _recursive_get_menu_items($menu_id = 0, $skip_item_id = 0, $parent_id = 0, $level = 0) {
		if (empty($menu_id) || empty($this->_menu_items[$menu_id])) {
			return false;
		}
		// Get current user group
		if (MAIN_TYPE_ADMIN) {
			$CUR_USER_GROUP = $_SESSION["admin_group"];
		} elseif (MAIN_TYPE_USER) {
			$CUR_USER_GROUP = intval(!empty($this->USER_GROUP) ? $this->USER_GROUP : $_SESSION["user_group"]);
			if (empty($CUR_USER_GROUP)) {
				$CUR_USER_GROUP = 1;
			}
		}
		$CUR_SITE		= (int)conf('SITE_ID');
		$CUR_SERVER		= (int)conf('SERVER_ID');

		$items_ids		= array();
		$items_array	= array();
		// Get items from the current level
		foreach ((array)$this->_menu_items[$menu_id] as $item_info) {
			if (!is_array($item_info)) {
				continue;
			}
			// Skip items from other parents
			if ($item_info["parent_id"] != $parent_id) {
				continue;
			}
			// Skip item if needed (and all its children)
			if ($skip_item_id == $item_info["id"]) {
				continue;
			}
			// Process user groups
			$user_groups = array();
			if (!empty($item_info["user_groups"])) {
				foreach (explode(",",$item_info["user_groups"]) as $v) {
					if (empty($v)) {
						continue;
					}
					$user_groups[$v] = $v;
				}
			}
			if (!empty($user_groups) && !isset($user_groups[$CUR_USER_GROUP])) {
				continue;
			}
			// Process site ids
			$site_ids = array();
			if (!empty($item_info["site_ids"])) {
				foreach (explode(",",$item_info["site_ids"]) as $v) {
					if (empty($v)) {
						continue;
					}
					$site_ids[$v] = $v;
				}
			}
			if (!empty($site_ids) && !isset($site_ids[$CUR_SITE])) {
				continue;
			}
			// Process servers
			$server_ids = array();
			if (!empty($item_info["server_ids"])) {
				foreach (explode(",",$item_info["server_ids"]) as $v) {
					if (empty($v)) {
						continue;
					}
					$server_ids[$v] = $v;
				}
			}
			if (!empty($server_ids) && !isset($server_ids[$CUR_SERVER])) {
				continue;
			}
			// Add item to the result array
			$items_array[$item_info["id"]] = $item_info;
			$items_array[$item_info["id"]]["level"] = $level;
			// Try to find sub items
			$tmp_array = $this->_recursive_get_menu_items($menu_id, $skip_item_id, $item_info["id"], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info["id"] == $item_info["id"]) {
					continue;
				}
				$items_array[$sub_item_info["id"]] = $sub_item_info;
			}
		}
		return $items_array;
	}

	/**
	* Show Zapatec menu items
	*
	* @access	private
	* @return	string	Meta tags
	*/
	function show_zp_menu ($input = array()) {
		return _class("graphics_zp_menu", $this->SUB_MODULES_PATH)->_show($input);
	}

	/**
	* Show help tip block
	*
	* @access	private
	* @return	string	Help tip code
	*/
	function _show_help_tip ($params = array()) {
		// Tip id could be number or string
		$tip_id		= $params["tip_id"];
		// Tip type is a number
		$tip_type	= !empty($params["tip_type"]) ? intval($params["tip_type"]) : 1;
		// Tip id is required
		if (empty($tip_id)) {
			return false;
		}
		// Prepare template
		$replace = array(
			"tip_id"	=> _prepare_html($tip_id),
			"tip_type"	=> intval($tip_type),
		);
		return tpl()->parse("system/help_tip", $replace);
	}

	/**
	* Show inline tip block
	*
	* @access	private
	* @return	string	Inline tip code
	*/
	function _show_inline_tip ($params = array()) {
		$text = isset($params["text"]) ? $params["text"] : strval($params);
		// Tip text is required
		if (empty($text)) {
			return false;
		}
		// Prepare template
		$replace = array(
			"text"	=> $text,
		);
		return tpl()->parse("system/inline_tip", $replace);
	}

	/**
	* Prepare help for show
	*/
	function show_help(){
		$module_name = $_GET["object"];
		$action_name = $_GET["action"];

		$replace = array(
			"action"	=> $action_name,
		);

		$STPL_NAME = $module_name."/help";
		if (tpl()->_stpl_exists($STPL_NAME)) {
			$body = tpl()->parse($STPL_NAME, $replace);
		} else {
# TODO
			$body = "";
		}
		return tpl()->parse("system/help_wrapper", array("body" => nl2br(trim($body))));
	}

	/**
	* Get html code for external bookmarking (Yahoo, Digg, etc)
	*/
	function _show_bookmarks_button($title = "", $url = "", $only_links = 1) {
		return _class("graphics_bookmarks", $this->SUB_MODULES_PATH)->_show_bookmarks_button($title, $url, $only_links);
	}

	/**
	* Get html code for external bookmarking (Yahoo, Digg, etc)
	*/
	function _show_rss_button($feed_name = "", $feed_link = "", $only_links = 1) {
		return _class("graphics_bookmarks", $this->SUB_MODULES_PATH)->_show_rss_button($feed_name, $feed_link, $only_links);
	}

	/**
	* Main $_GET tasks handler
	*
	* @access	public
	* @return	string	$body	Output content
	*/
	function tasks($CHECK_IF_ALLOWED = false) {
		// Singleton
		$_center_result = tpl()->_CENTER_RESULT;
		if (isset($_center_result)) {
			return $_center_result;
		}
		$NOT_FOUND		= false;
		$ACCESS_DENIED	= false;
		$custom_handler_exists = false;

		$this->_route_request();
		// Check if called class method is "private" - then do not use it
		if (substr($_GET["action"], 0, 1) == "_" || $_GET['object'] == "main") {
			$ACCESS_DENIED = true;
		}
		if (!$ACCESS_DENIED) {
			$obj = module($_GET['object']);
			if (!is_object($obj)) {
				$NOT_FOUND = true;
			}
			if (!$NOT_FOUND && !method_exists($obj, $_GET['action'])) {
				$NOT_FOUND = true;
			}
			// Check if we have custom action handler in module (catch all requests to module methods)
			if (method_exists($obj, main()->MODULE_CUSTOM_HANDLER)) {
				$custom_handler_exists = true;
			}
			// Do call class method
			if (!$NOT_FOUND || $custom_handler_exists) {
				if ($custom_handler_exists) {
					$NOT_FOUND = false;
					$body = $obj->{main()->MODULE_CUSTOM_HANDLER}($_GET['action']);
				} else {
					// Automatically call output cache trigger
					$is_banned = false;
					if (MAIN_TYPE_USER && isset(main()->AUTO_BAN_CHECKING) && main()->AUTO_BAN_CHECKING) {
						$is_banned = _class("ban_status")->_auto_check(array());
					}
					if ($is_banned) {
						$body = _e();
					} else {
						$body = $obj->$_GET['action']();
					}
				}
			}
		}
		// Process errors if exiss ones
		$redir_params = array(
			'%%object%%'		=> $_GET["object"],
			'%%action%%'		=> $_GET["action"],
			'%%add_get_vars%%'	=> str_replace("&",";",_add_get(array("object","action"))),
		);
		if ($NOT_FOUND) {
			if ($this->NOT_FOUND_RAISE_WARNING) {
				trigger_error("MAIN: Task not found: ".$_GET['object']."->".$_GET['action'], E_USER_WARNING);
			}
			if (MAIN_TYPE_USER) {
				$url_not_found = main()->REDIR_URL_NOT_FOUND;
				if (is_array($url_not_found) && !empty($url_not_found)) {
					$_GET["object"] = $url_not_found["object"];
					$_GET["action"] = $url_not_found["action"];
					$_GET["id"]		= $url_not_found["id"];
					$_GET["page"]	= $url_not_found["page"];

					if (!empty($url_not_found["object"])) {
						$OBJ = main()->init_class($url_not_found["object"], $url_not_found["path"]);
						$action = $url_not_found["action"] ? $url_not_found["action"] : "show";
						if (method_exists($OBJ, $action)) {
							$body = $OBJ->$action();
						} else {
							main()->NO_GRAPHICS = true;
							echo "404: not found by main";
						}
					} elseif (isset($url_not_found["stpl"])) {
						main()->NO_GRAPHICS = true;
						echo tpl()->parse($url_not_found["stpl"]);
					}
				} else {
					$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), $url_not_found);
					if (!empty($redir_url)) {
						redirect($redir_url, 1, tpl()->parse("system/error_not_found"));
					}
				}
				$GLOBALS['task_not_found'] = true;
			}
		} elseif ($CHECK_IF_ALLOWED && $ACCESS_DENIED) {
			trigger_error("MAIN: Access denied: ".$_GET['object']."->".$_GET['action'], E_USER_WARNING);
			if (MAIN_TYPE_USER) {
				$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), main()->REDIR_URL_DENIED);
				if (!empty($redir_url)) {
					redirect($redir_url, 1, tpl()->parse("system/error_not_found"));
				}
				$GLOBALS['task_denied'] = true;
			}
		}
		// Do not touch !!!
		tpl()->_CENTER_RESULT = (string)$body;
		// Output only center content, when we are inside AJAX_MODE
		if (conf('IS_AJAX')) {
			main()->NO_GRAPHICS = true;
			print $body;
		}
		return $body;
	}

	/**
	* Method that allows to change standard tasks mapping (if needed)
	*/
	function _route_request() {
		/* // Map example
		if ($_GET["object"] == "forum") {
			$_GET = array();
			$_GET["object"] = "gallery";
			$_GET["action"] = "show";
		}
		*/
		// Custom routing for static pages (eq. for URL like /terms/ instead of /static_pages/show/terms/)
		if (!main()->STATIC_PAGES_ROUTE_TOP || MAIN_TYPE_ADMIN) {
			return false;
		}
		$_user_modules = main()->get_data("user_modules");
		// Do not override existing modules
		if (isset($_user_modules[$_GET["object"]])) {
			return false;
		}
		$static_pages_names = main()->get_data("static_pages_names");
		$replaced_obj = str_replace("_", "-", $_GET["object"]);
		if (in_array($_GET["object"], (array)$static_pages_names)) {
			$_GET["id"]		= $_GET["object"];
			$_GET["object"] = "static_pages";
			$_GET["action"] = "show";
		} elseif (in_array($replaced_obj, (array)$static_pages_names)) {
			$_GET["id"]		= $replaced_obj;
			$_GET["object"] = "static_pages";
			$_GET["action"] = "show";
		}
	}

	/**
	* Send main headers
	*
	* @access	private
	* @param	$content_length		int		Length of the content to output
	* @return	void
	*/
	function _send_main_headers($content_length = 0) {
		// Stop if some headers are sent
		if (headers_sent() || conf('no_headers')) {
			return false;
		}
		// Replace images paths with their absolute ones
		if ($this->HEADER_POWERED_BY) {
			@header("X-Powered-By: YF");
		}
		@header("Content-Type:text/html; charset=".conf('charset'));
		@header("Content-language: ".conf('language'));
		// Switch between caching on/off
		if (tpl()->REWRITE_MODE && MAIN_TYPE_USER && !main()->NO_CACHE_HEADERS) {
			// To emulate static pages need these headers (Tells that page is modified only one time per day)
			header("Last-Modified: ". gmdate("D, d M Y 00:01:01") . " GMT");
			header("Content-Length: ".intval($content_length));
		} else {
			// Date in the past
			@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			// always modified
			@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
			// HTTP/1.1
			@header("Cache-Control: no-store, no-cache, must-revalidate");
			@header("Cache-Control: post-check=0, pre-check=0", false);
			// HTTP/1.0
			@header("Pragma: no-cache");
		}
		$this->_send_custom_http_headers();
	}

	/**
	*/
	function _send_custom_http_headers() {
		// Override headers
		if (conf('http_headers')) {
			foreach (conf('http_headers') as $_name => $_value) {
				@header($_name.": ".$_value, true);
			}
		}
	}

	/**
	* Generate typos for the given text
	*/
	function _generate_typos($params = array()) {
		return _class("graphics_typos", $this->SUB_MODULES_PATH)->get_all_with_stpl($params["text"], $params);
	}

	/**
	* Display user geo location block
	*/
	function _show_user_geo_block($params = array()) {
		if (!main()->USE_GEO_IP) {
			return false;
		}
		$geo_data	= main()->_USER_GEO_DATA;
		if (empty($geo_data)) {
			return false;
		}
		// Process template
		$replace = array(
			"country_name"			=> _prepare_html($geo_data["country_name"]),
			"country_code_lower"	=> strtolower($geo_data["country_code"]),
			"region_code"			=> _prepare_html($geo_data["region_code"]),
			"region_name"			=> _prepare_html(_region_name($geo_data["region_code"], $geo_data["country_code"])),
			"city_name"				=> _prepare_html($geo_data["city_name"]),
			"change_link"			=> "./?object=geo_content&action=change_location",
		);
		return tpl()->parse("user_geo_block", $replace);
	}

	/**
	* Display header content (hook)
	*/
	function _show_header() {
		if (conf('no_page_header')) {
			return false;
		}
		$page_header = "";
		$page_subheader = "";
		// Display hook contents
		$obj = module($_GET["object"]);
		if (method_exists($obj, "_show_header")) {
			$result = $obj->_show_header();
			if (is_array($result)) {
				$page_header	= $result["header"];
				$page_subheader = $result["subheader"];
			} else {
				return strval($result);
			}
		}
		// Show default header
		if (!isset($page_header)) {
			$page_header = _ucwords(str_replace("_", " ", $_GET["object"]));
		}
		if (!isset($page_subheader)) {
			if ($_GET["action"] != "show") {
				$page_subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
			}
		}
		$replace = array(
			"header"	=> $page_header ? t($page_header) : "",
			"subheader"	=> $page_subheader ? t($page_subheader) : "",
		);
		return tpl()->parse("system/page_header", $replace);
	}

	/**
	* Display quick menu items
	*/
	function quick_menu() {
		if (!$this->QUICK_MENU_ENABLED
			|| (MAIN_TYPE_USER && (!isset($_SESSION["user_id"]) || !$_SESSION["user_id"]))
			|| (MAIN_TYPE_ADMIN && (!isset($_SESSION["admin_id"]) || !$_SESSION["admin_id"]))
		) {
			return false;
		}
		if (MAIN_TYPE_ADMIN) {
			$data[-1] = array(
				"name"	=> "module settings",
				"url"	=> "./?object=conf_editor&action=admin_modules&id=".strtolower($_GET["object"]),
			);
		}
		$method_name = "_quick_menu";
		$obj = module($_GET["object"]);
		if (method_exists($obj, $method_name)) {
			$data2 = $obj->$method_name();
		}
		$data = my_array_merge((array)$data2, (array)$data);
		if (empty($data)) {
			return false;
		}
		foreach ((array)$data as $_item) {
			if (!$_item["name"]) {
				continue;
			}
			$replace2 = array(
				"item_name"	=> _prepare_html(t($_item["name"])),
				"item_url"	=> $_item["url"],
			);
			$items .= tpl()->parse("system/quick_menu_item", $replace2);

		}
		// Display nothing if no items
		if (!$items) {
			return false;
		}
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse("system/quick_menu_main", $replace);
	}
}

