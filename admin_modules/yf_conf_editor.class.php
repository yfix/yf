<?php

/**
* Configuration editor editor
*/
class yf_conf_editor {

	/** @var string @conf_skip */
	public $_regexp_pairs_cleanup = array(
		"/([^\s\t])[\s\t]+=>/ims"
			=> "\\1=>",
		"/(array\(|[0-9a-z\'\"]+,|=>)[\s\t]+([^\s\t])/ims"
			=> "\\1\\2",
		"/[\s\t]+\)\$/ims"
			=> ")",
	);
	/** @var string @conf_skip */
	public $_var_regexp	= "/\tvar[\s\t]{1,}\\\$([a-z_][a-z0-9_]*)[\s\t]*=[\s\t]*([^;]+);/ims";
	/** @var string @conf_skip */
	public $_info_regexp	= "/[\t\s]?\/\*\*[^@]*[\s\t]{1,}@var[\s\t]{1,}(bool|int|float|array|string|mixed|enum\s*\([^\)]+\))?(.*?)\*\/.*?[\r\n]*\tvar[\s\t]{1,}\\\$([a-z_][a-z0-9_]*)/ims";
	/** @var array  @conf_skip */
	public $_allowed_types	= array(
		"bool",
		"int",
		"float",
		"array",
		"string",
		"mixed",
		"enum",	// Sample: enum('active','inactive','waiting')
	);
	/** @var string @conf_skip */
	public $_SYSTEM_NS			= "_";
	/** @var bool Use type autodetect */
	public $USE_TYPE_AUTODETECT = true;
	/** @var bool Show arrays in default values */
	public $SHOW_ARRAYS_IN_DEFAULTS = true;
	/** @var bool Use cache for conf array or not */
	public $USE_CACHE			= true;
	/** @var int TTL for conf_array_cache */
	public $CACHE_TTL			= 600;
	/** @var bool Sort config items by varname */
	public $SORT_BY_VARNAME	= true;
	/** @var string @conf_skip Autoconf file name */
	var	$AUTO_CONF_FILE		= "_auto_conf.php";
	/** @var array @conf_skip */
	public $_auto_conf_array	= array();
	/** @var bool Convert array to usable form */
	var	$JS_ARRAYS_CONVERT	= true;

	/**
	* Framework constructor
	*/
	function _init () {

		$GLOBALS['http_headers'] = my_array_merge((array)$GLOBALS['http_headers'], array(
			"Expires"		=> "Mon, 26 Jul 1997 05:00:00 GMT",
			"Last-Modified"	=> gmdate("D, d M Y H:i:s")." GMT",
			"Cache-Control"	=> "no-store, no-cache, must-revalidate",
		));

		$this->AUTO_CONF_FILE = INCLUDE_PATH. $this->AUTO_CONF_FILE;

		if (file_exists($this->AUTO_CONF_FILE)) {
			$_patterns = array(
				"/.*?my_array_merge[\s\t]*\([^\n]+?,[\s\t]*array[\s\t]*\([\s\t]*\n/ims",
				"/\)[\s\t]*\)[\s\t]*;[^\);]*\$/ims",
			);
			$_tmp_string = file_get_contents($this->AUTO_CONF_FILE);
			if ($_tmp_string) {
				$_tmp_string = preg_replace($_patterns, "", $_tmp_string);
				$this->_auto_conf_array = @eval("return array(".$_tmp_string.");");
			}
		}

		$this->_vars_to_skip = array(
//			"forum" => "SETTINGS",
		);
	}

	/**
	* Default method
	*/
	function show () {
		$_GET["action"] = "user_modules";
		return $this->user_modules();
	}

	/**
	* Process user modules config
	*/
	function user_modules () {
		if (!$this->_user_modules) {
			$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		}

		if ($_GET["id"] && !in_array($_GET["id"], (array)$this->_user_modules)) {
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		}

		$this->conf_array = $this->_get_group_conf_array(__FUNCTION__);
		// Show first class settings
		if (!$_GET["id"] && $this->conf_array[0]) {
			$tmp = array_keys($this->conf_array[0]);
			asort($tmp);
			$_GET["id"] = current($tmp);
		}
		// Saving
		if ($_POST) {
			$this->_save_data();
		}
		// Show 
		return $this->_show($_GET["id"]);
	}

	/**
	* Process admin modules config
	*/
	function admin_modules () {
		if (!$this->_admin_modules) {
			$this->_admin_modules = main()->_execute("admin_modules", "_get_modules");
		}

		if ($_GET["id"] && !in_array($_GET["id"], (array)$this->_admin_modules)) {
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		}

		$this->conf_array = $this->_get_group_conf_array(__FUNCTION__);
		// Show first class settings
		if (!$_GET["id"] && $this->conf_array[0]) {
			$tmp = array_keys($this->conf_array[0]);
			asort($tmp);
			$_GET["id"] = current($tmp);
		}
		// Saving
		if ($_POST) {
			$this->_save_data();
		}
		// Show 
		return $this->_show($_GET["id"]);
	}

	/**
	* Process classes config
	*/
	function classes () {
		$this->_classes_tree = $this->_get_classes();
		$classes_list = array();
		foreach ((array)$this->_classes_tree as $_folder => $_folder_classes) {
			$classes_list = my_array_merge((array)$_folder_classes, $classes_list);
		}

		if ($_GET["id"] && !in_array($_GET["id"], (array)$classes_list)) {
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		}

		$this->conf_array = $this->_get_group_conf_array(__FUNCTION__);
		// Show first class settings
		if (!$_GET["id"] && $this->conf_array[0]) {
			$tmp = array_keys($this->conf_array[0]);
			asort($tmp);
			$_GET["id"] = current($tmp);
		}
		// Saving
		if ($_POST) {
			$this->_save_data();
		}
		// Show 
		return $this->_show($_GET["id"]);
	}

	/**
	* Process forum settings
	*/
	function _forum_settings () {
		// Check if forum is an active module
		if (!$this->_user_modules) {
			$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		}
		if (!in_array("forum", (array)$this->_user_modules)) {

			return false;
		}
		$this->conf_array = $this->_get_group_conf_array("forum");

		// Show 
		return $this->_show_module_conf("_forum", "_forum", "Forum Settings");
	}

	/**
	* Get available classes names groupped by folders
	*/
	function _get_classes () {
		$folders_list = array();

		// Processing framework
		$files_list = _class("dir")->scan_dir(YF_PATH. "classes/", true, array("", "/\.class\.php\$/i"), "/\.(svn|git)/i");

		foreach ((array)$files_list as $filename) {
			$module_name = basename($filename);
			$module_name = str_replace(array("yf_",".class.php"), "", $module_name);
			$modules_list[$module_name] = $filename;

			$folder_name = substr($filename,strlen(YF_PATH),-strlen(basename($filename)));
			if (!$folder_name) {
				continue;
			}
			$folders_list[$folder_name][$module_name] = $module_name;
		}
		// Processing project
		$project_files_list = _class("dir")->scan_dir(INCLUDE_PATH. "classes/", true, array("", "/\.class\.php\$/i"), "/\.(svn|git)/i");

		foreach ((array)$project_files_list as $filename) {
			$module_name = basename($filename);
			$module_name = str_replace(array("yf_",".class.php"), "", $module_name);
			$project_modules_list[$module_name] = $filename;

			$folder_name = substr($filename,strlen(INCLUDE_PATH),-strlen(basename($filename)));
			if (!$folder_name) {
				continue;
			}
			$folders_list[$folder_name][$module_name] = $module_name;
		}
		return $folders_list;
	}

	/**
	* Get configuration for given group name ("user_modules", "admin_modules", "classes")
	*/
	function _get_group_conf_array($group = "") {
		if (!$group) {
			$group = strtolower($_GET["action"]);
		}
		$CACHE_OBJ = main()->init_class("cache", "classes/");
		// Switch by group
		if ($group == "user_modules") {

			$CACHE_NAME = "usr_modules_conf_tmp";

			if ($this->USE_CACHE) {
				$conf_array = $CACHE_OBJ->get($CACHE_NAME, $this->CACHE_TTL);
			}
			if (empty($conf_array)) {
				if (!$this->_user_modules) {
					$this->_user_modules = main()->_execute("user_modules", "_get_modules");
				}
				$conf_array = $this->_collect_conf("modules/", $this->_user_modules);
				foreach ((array)$conf_array[0] as $k => $v) {
					if (!empty($v)) {
						continue;
					}
					unset($conf_array[0][$k]); // Values
					unset($conf_array[1][$k]); // Meta info
				}		
				if ($this->USE_CACHE) {
					$CACHE_OBJ->put($CACHE_NAME, $conf_array);
				}
			}
		} elseif ($group == "admin_modules") {
			
			$CACHE_NAME = "adm_modules_conf_tmp";

			if ($this->USE_CACHE) {
				$conf_array = $CACHE_OBJ->get($CACHE_NAME, $this->CACHE_TTL);
			}
			if (empty($conf_array)) {
				if (!$this->_admin_modules) {
					$this->_admin_modules = main()->_execute("admin_modules", "_get_modules");
				}
				$conf_array = $this->_collect_conf("admin_modules/", $this->_admin_modules);
				foreach ((array)$conf_array[0] as $k => $v) {
					if (!empty($v)) {
						continue;
					}
					unset($conf_array[0][$k]); // Values
					unset($conf_array[1][$k]); // Meta info
				}		
				if ($this->USE_CACHE) {
					$CACHE_OBJ->put($CACHE_NAME, $conf_array);
				}
			}

		} elseif ($group == "classes") {

			$CACHE_NAME = "classes_conf_tmp";

			if ($this->USE_CACHE) {
				$conf_array = $CACHE_OBJ->get($CACHE_NAME, $this->CACHE_TTL);
			}
			
			if (empty($conf_array)) {
				if (empty($this->_classes_tree)) {
					$this->_classes_tree = $this->_get_classes();
				}
				foreach ((array)$this->_classes_tree/*_get_classes()*/ as $_folder => $_folder_classes) {
					$tmp_folder_array = $this->_collect_conf ($_folder, $_folder_classes);
					$conf_array = my_array_merge($conf_array, (array)$tmp_folder_array);
				}

				foreach ((array)$conf_array[0] as $k => $v) {
					if (!empty($v)) {
						continue;
					}
					unset($conf_array[0][$k]); // Values
					unset($conf_array[1][$k]); // Meta info
				}		
				if ($this->USE_CACHE) {
					$CACHE_OBJ->put($CACHE_NAME, $conf_array);
				}
			}
		} elseif ($group == "forum") {

			$CACHE_NAME = "forum_conf_tmp";
	
			if ($this->USE_CACHE) {
				$conf_array = $CACHE_OBJ->get($CACHE_NAME, $this->CACHE_TTL);
			}
			if (empty($conf_array)) {
	
				// Get module file contents
				$test_string = file_get_contents(YF_PATH. "modules/yf_forum.class.php");
				preg_match("/var[\s\t]+\\\$SETTINGS[\s\t]*=[\s\t]*array\((.*?)\);/ims", $test_string, $m);
				$settings_string = str_replace(array("\r", "\n\n"), array("\n", "\n"), trim($m[1]));
				preg_match_all("/\"([^\"]+)\"[\s\t]*=>[\s\t]*(true|false|[0-9\.]+|[\"\'][^\"]+[\"\']),[\s\t]*(\/\/([^\n]+)){0,1}/ims", $settings_string, $m);
				$conf_array = array();
				foreach ((array)$m[1] as $k => $var_name) {
					$conf_array[0]["_forum"][$var_name] = @eval("return ".$m[2][$k].";");
					$conf_array[1]["_forum"][$var_name]["desc"] = trim($m[4][$k]);
					$conf_array[1]["_forum"][$var_name]["type"] = $this->_get_type(@eval("return ".$m[2][$k].";"));
				}
	
				if ($this->USE_CACHE) {
					$CACHE_OBJ->put($CACHE_NAME, $conf_array);
				}
			}


		}
		return $conf_array;
	}

	/**
	* Show info to admin
	*/
	function _show ($module_name = "") {
		$_modules_names = array_keys((array)$this->conf_array[0]);
		if ($_modules_names) {
			asort($_modules_names);
		}
		foreach ((array)$_modules_names as $name) {
			$diff_counter = count(array_diff_assoc((array)$GLOBALS['PROJECT_CONF'][$name], (array)$this->conf_array[0][$name]));
			$replace2 = array(
				"is_current" 	=> $name == $module_name ? 1 : 0,
				"module_name" 	=> ucfirst(str_replace("_", " ", $name)),
				"edit_url"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$name,
				"num_conf_items"=> count((array)$this->conf_array[0][$name]),
				"num_changed"	=> $diff_counter,
			);
			$items .= tpl()->parse($_GET["object"]."/module_item", $replace2);
		}
		// Get module config to show
		if ($module_name) {
			$config_content = $this->_show_module_conf($module_name);
		}
/*
		if ($module_name == "forum") {
			$config_content .= $this->_forum_settings();
		}
*/
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"use_js_convert"	=> $this->JS_ARRAYS_CONVERT ? 1 : 0,
			"items"				=> $items,
			"config_content"	=> $config_content,
			"module_name"		=> $module_name,
			"user_modules_url"	=> "./?object=".$_GET["object"]."&action=user_modules",
			"admin_modules_url"	=> "./?object=".$_GET["object"]."&action=admin_modules",
			"classes_url"		=> "./?object=".$_GET["object"]."&action=classes",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Show configuration items
	* $post_array_name uses for creating array in form names
	*/
	function _show_module_conf ($module_name = "", $post_array_name = "", $header_text = "") {
		// Sort items if needed
		if ($this->SORT_BY_VARNAME && is_array($this->conf_array[0][$module_name])) {
			ksort($this->conf_array[0][$module_name]);
		}
		foreach ((array)$this->conf_array[0][$module_name] as $name => $val){

			// Check for vars to skip
			if ($this->_vars_to_skip[$module_name] == $name) {
				continue;
			}

			$is_changed = false;
			// Override $val by project conf value if exists
			if (isset($GLOBALS["PROJECT_CONF"][$module_name][$name]) && $val != $GLOBALS["PROJECT_CONF"][$module_name][$name]) {
				$val = $GLOBALS["PROJECT_CONF"][$module_name][$name];
				$is_changed = true;
			}

			$descr = $this->conf_array[1][$module_name][$name]["desc"];
			$type = strtolower($this->conf_array[1][$module_name][$name]["type"]);
			if ($type == "str") {
				$type = "string";
			}

			$default_val = _prepare_html($this->conf_array[0][$module_name][$name]);

			if ($type == "array") {
				$_val_orig = $val;
				$val = $this->_create_array_code($val);
				if ($this->SHOW_ARRAYS_IN_DEFAULTS) {
					$default_val = nl2br(_prepare_html($this->_create_array_code($this->conf_array[0][$module_name][$name], 0, true)));
				} else {
					$default_val = "ARRAY";
				}
			}
			$enum_box = "";
			if (substr($type, 0, 4) == "enum") {
				if (preg_match("/enum\s*\(([^\)]+)\)/ims", trim($type), $m)) {
					$type = "enum";
					$_enum_values = array();
					foreach (explode(",",str_replace(array("'", "\""), "", trim($m[1]))) as $_k) {
						$_enum_values[trim($_k)] = trim($_k);
					}
					$enum_box = common()->select_box($name."___box", $_enum_values, $val, false, 2, "", false);
				} else {
					$type = "string";
				}
			}
			$val_json = "";
			if ($type == "array") {
				$val_json = common()->json_encode($_val_orig);
			}
			$replace2 = array(
				"var_name"			=> $name,
				"form_name"			=> $post_array_name ? $post_array_name."[".$name."]" : $name,
				"value"				=> $val,
				"type"				=> $type,
				"default"			=> $default_val,
				"description"		=> _prepare_html($descr),
				"changed"			=> $is_changed,
				"enum_box"			=> $enum_box,
				"value_json"		=> $val_json,
				"post_array_name"	=> $post_array_name ? $post_array_name : "",
			);
			$items .= tpl()->parse($_GET["object"]."/config_item", $replace2);
		}
		$replace = array(
//			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"conf_items"	=> $items,
			"module_name"	=> ucfirst(str_replace("_", " ", $module_name)),
			"module_id"		=> $module_name,
			"header_text"	=> $header_text,
		);
		return tpl()->parse($_GET["object"]."/config_main", $replace);
	} 

	/**
	* Create array with conf data for whole type (admin or user)
	*/
	function _collect_conf ($dir_name = "", $modules_list = array()) {
		if (empty($dir_name) || empty($modules_list)) {
			return false;
		}
		$PARSED_CONF 	= array();
		$PARSED_META 	= array();

		foreach ((array)$modules_list as $_module_name) {
			$file_path_fwork	= YF_PATH.$dir_name.YF_PREFIX.$_module_name.CLASS_EXT;
			if ($dir_name == "admin_modules/") {
				$file_path_project	= ADMIN_REAL_PATH.$dir_name.$_module_name.CLASS_EXT;
			} else {
				$file_path_project	= INCLUDE_PATH.$dir_name.$_module_name.CLASS_EXT;
			}

			$_tmp_conf 		= array();
			$_tmp_meta 		= array();

			if (!empty($file_path_fwork) && file_exists($file_path_fwork)) {
				list($PARSED_CONF_1, $PARSED_META_1) = $this->_get_conf_from_file($file_path_fwork);
				$_tmp_conf = $PARSED_CONF_1;
				$_tmp_meta = $PARSED_META_1;
			}

			if (!empty($file_path_project) && file_exists($file_path_project)) {
				list($PARSED_CONF_2, $PARSED_META_2) = $this->_get_conf_from_file($file_path_project);
				$_tmp_conf = my_array_merge($_tmp_conf, (array)$PARSED_CONF_2);
				$_tmp_meta = my_array_merge($_tmp_meta, (array)$PARSED_META_2);
			}
			
			$PARSED_CONF[$_module_name] = $_tmp_conf;
			$PARSED_META[$_module_name] = $_tmp_meta;
		}
		return array($PARSED_CONF, $PARSED_META);
	}

	/**
	* Create array code recursive (with inheritation)
	*/
	function _get_conf_from_file ($file_path = "") {
		if (empty($file_path)) {
			return false;
		}
		$CONF = array();
		$META = array();

		$test_string = file_get_contents($file_path);
		if (empty($test_string)) {
			return false;
		}
		// Remove comments
		$test_string = preg_replace("/(\/\/.*?[\r\n])/is", "", $test_string);
		// Replace HTML special chars into temporary symbols
		$_special = array();
		if (preg_match_all("/(&([a-z]{0,4}\w{2,3}|#[0-9]{2,7}|#x[0-9]{2,7});)/i", $test_string, $m)) {
			foreach ((array)$m[0] as $v) {
				$_crc = "___".abs(crc32($v))."___";
				$_special[$v] = $_crc;
				$_special_back[$_crc] = $v;
			}
		}
		if ($_special) {
			$test_string = str_replace(array_keys($_special), array_values($_special), $test_string);
		}
		// Get conf items
		preg_match_all($this->_var_regexp, $test_string, $m);
		foreach ((array)$m[0] as $_m_id => $_m_tmp) {
			$var_name	= $m[1][$_m_id];
			$value		= $m[2][$_m_id]; 
			// Do not remove this. Needed to return back HTML special chars into string
			if ($_special_back) {
				$value = str_replace(array_keys($_special_back), array_values($_special_back), $value);
			}
			$CONF[$var_name] = @eval("return ".$value.";");
		}
		// Get conf meta
		preg_match_all($this->_info_regexp, $test_string, $m);
		foreach ((array)$m[0] as $_m_id => $_m_tmp) {
			$type		= $m[1][$_m_id];
			$desc		= $m[2][$_m_id];
			$var_name	= $m[3][$_m_id];
			// Check if current var needed to be skipped
			if (false !== strpos($desc, "@conf_skip")) {
				unset($CONF[$var_name]);
				continue;
			}
			if (!strlen($type) && !strlen($desc)) {
				continue;
			}
			// Do not remove this. Needed to return back HTML special chars into string
			if ($_special_back) {
				$desc = str_replace(array_keys($_special_back), array_values($_special_back), $desc);
			}
			// Cleanup comments new lines with "*"
			$desc = str_replace("\n\t* ", "\n", $desc);
			// Cutoff comments
			$desc = preg_replace(array("/\/\/[^\n]+/ims", "/\/\*.+?\*\//ims"), "", $desc);
			$desc = str_replace(array("//", "/*", "*/"), "", $desc);
			$META[$var_name] = array(
				"type"	=> trim($type),
				"desc"	=> trim($desc),
			);
		}
		// Autodetect types if possible
		if ($this->USE_TYPE_AUTODETECT) {
			foreach ((array)$CONF as $var_name => $value) {
				$cur_type = $META[$var_name]["type"];
				if (strlen($cur_type)) {
					continue;
				}
				$cur_type = $this->_get_type($value);

				if ($cur_type = "null") {
					unset($CONF[$var_name]);
					unset($META[$var_name]);
					continue;
				}

				$META[$var_name]["type"] = $cur_type;
			}
		}
		return array($CONF, $META);
	}

	/**
	* Create array code recursive
	*/
	function _create_array_code ($data = array(), $level = 0, $auto_format = false) {
		$_func = __FUNCTION__;
		$code = "";
		$code .= ($auto_format ? "\t" : "")."array(";
		foreach ((array)$data as $k => $v) {
			$code .= $auto_format ? "\r\n".str_repeat("\t", $auto_format + 1) : "";
			$code .= is_int($k) || is_float($k) ? $k : "'".$this->_put_safe_slashes($k, $auto_format)."'";
			$code .= ($auto_format ? "\t" : "")."=>".($auto_format ? "\t" : "");
			$code .= is_array($v) ? $this->$_func($v, $level + 1, $auto_format ? $auto_format + 1 : false) : "'". $this->_put_safe_slashes($v, $auto_format). "',";
		}
		$code .= ($auto_format ? "\r\n".str_repeat("\t", $auto_format) : "").")".($level != 0 ? "," : "").($auto_format && $level != 0 ? "\r\n" : "");
		return $code;
	}

	/**
	* Create array code (for file) recursive
	*/
	function _create_array_code2 ($data = array()) {
		$_func = __FUNCTION__;
		$code = "array(";
		foreach ((array)$data as $k => $v) {
			$code .= "'".$this->_put_safe_slashes($k)."'=>";
			$code .= is_array($v) ? $this->$_func($v) : "'". $this->_put_safe_slashes($v). "',";
		}
		$code .= "),";
		return $code;
	}

	/**
	* Prepare text to store it in cache
	*/
	function _put_safe_slashes ($text = "", $auto_format = false) {
		if (!$auto_format) {
			$text = str_replace("'", "\\\'", $text);
		} else {
			$text = str_replace("'", "\'", $text);
		}
		if (substr($text, -1) == "\\" && substr($text, -2, 1) != "\\") {
			$text .= "\\";
		}
		return $text;
	}

	/**
	* Save data to auto-conf file
	*/
	function _save_data () {
		$module_name = $_POST["module_id"];
		unset($_POST["module_id"]);

		// Get merged conf for all content groups
		$conf_array = array();
		foreach ((array)$this->_get_group_conf_array("user_modules") as $k => $v) {
			$conf_array[$k] = my_array_merge((array)$conf_array[$k], (array)$v);
		}
		foreach ((array)$this->_get_group_conf_array("admin_modules") as $k => $v) {
			$conf_array[$k] = my_array_merge((array)$conf_array[$k], (array)$v);
		}
		foreach ((array)$this->_get_group_conf_array("classes") as $k => $v) {
			$conf_array[$k] = my_array_merge((array)$conf_array[$k], (array)$v);
		}
/*
		foreach ((array)$this->_get_group_conf_array("forum") as $k => $v) {
			$conf_array[$k] = my_array_merge((array)$conf_array[$k], (array)$v);
		}
*/
		// Correctly merge auto conf with new posted ones
		$merged_conf = $this->_auto_conf_array;
		foreach ((array)$_POST as $_var_name => $posted_value) {
			$cur_value = null;
			$default_value		= $conf_array[0][$module_name][$_var_name];
			if (isset($default_value)) {
				$cur_value = $default_value;
			} else {
				continue;
			}
			$proj_conf_value	= $GLOBALS['PROJECT_CONF'][$module_name][$_var_name];
			$auto_conf_value	= $this->_auto_conf_array[$module_name][$_var_name];

			if (isset($proj_conf_value) && $proj_conf_value != $posted_value) {
				$cur_value = $proj_conf_value;
			}

			if (isset($auto_conf_value)) {
				if ($posted_value == $default_value) {
					unset($merged_conf[$module_name][$_var_name]);
					continue;
				} else {
					$cur_value = $auto_conf_value;
				}
			}

			// Check for correct value
			if (!isset($cur_value) || $posted_value == $cur_value) {
				continue;
			}
			// Store for saving
			$merged_conf[$module_name][$_var_name] = $posted_value;
		}
// TODO: forum settings and forum rights
/*
		if (!empty($_POST["_forum"])) {
//print_R($conf_array);
print_R($merged_conf);
//print_r($conf_array);
//			print_r($this->_get_group_conf_array("forum"));
		}
*/
		ksort($merged_conf);
		// Do prepare data for saving
		$data = "<?p"."hp\n";
		$data .= "// CONFIG VARS\n\$GLOBALS[\"PROJECT_CONF\"] = my_array_merge((array)\$GLOBALS[\"PROJECT_CONF\"], array(\n";
		foreach ((array)$merged_conf as $_cur_module_name => $_module_conf) {
			$_items_data = "";
			foreach ((array)$_module_conf as $_var_name => $posted_value) {
				if (!strlen($_var_name)) {
					continue;
				}
				// Get current used value
				$cur_value = null;
				$default_value		= $conf_array[0][$_cur_module_name][$_var_name];
				if (isset($default_value)) {
					$cur_value = $default_value;
				}
				$proj_conf_value	= $GLOBALS['PROJECT_CONF'][$_cur_module_name][$_var_name];
				if (isset($proj_conf_value)) {
					$cur_value = $proj_conf_value;
				}
				$auto_conf_value	= $this->_auto_conf_array[$_cur_module_name][$_var_name];

				$type = $conf_array[1][$_cur_module_name][$_var_name]["type"];

				if ($type == "float") {
					$posted_value = str_replace(",", ".", $posted_value);
				}
				// Switch by var type
				if (is_numeric($posted_value)) {
					//
				} elseif (is_array($posted_value) || is_array($_tmp_array = eval("return ".$posted_value.";"))) {
					if (!isset($_tmp_array)) {
						$_tmp_array = $posted_value;
					}
					if (!is_array($cur_value)) {
						continue;
					}
					if (!empty($_tmp_array)) {
						$diff_array = array_diff_assoc((array)$cur_value, (array)$_tmp_array);
						if (isset($auto_conf_value) && $_tmp_array == $default_value) {
							continue;
						}
						if (empty($diff_array)) {
							continue;
						}
						$posted_value = $this->_create_array_code($_tmp_array, 0, 2);
					}
				} elseif (is_string($posted_value)) {
					 $posted_value = "\"".$posted_value."\"";
				} else {
					//
				}

				if ($type == "bool") {
					$posted_value = $posted_value ? "true" : "false";
				}

				if ($posted_value == "array()") {
					continue;
				}
				$_items_data .= "\t\t\"".$_var_name."\" => ".$posted_value.",\r\n";
			}
			if (!empty($_items_data)) {
				$data .= "\t\"".$_cur_module_name. "\"	=> array(\n";
				$data .= substr($_items_data, 0, -2);
				$data .= "\n\t),\n";
			}
		}
		$data .= "));\n?".">";
		// Save file
		file_put_contents($this->AUTO_CONF_FILE, $data);
		// Cleanup cache
		$CACHE_OBJ = main()->init_class("cache", "classes/");
		$CACHE_OBJ->clean("usr_modules_conf_tmp");
		$CACHE_OBJ->clean("adm_modules_conf_tmp");
		$CACHE_OBJ->clean("classes_conf_tmp");
		$CACHE_OBJ->clean("forum_conf_tmp");
		// Return user back
		return redirect($_SERVER["HTTP_REFERER"], 0);
	}
/*
	function _show_header () {
		// Count total number of settings in this group
		$total = 0;
		foreach ((array)$this->conf_array[0] as $v) {
			$total += count($v);
		}
// TODO
	}
*/
	
	/**
	* Type defining function
	*/
	function _get_type ($value) {
		if (is_null($value)) {
			$type = "null";
		} elseif (is_numeric($value)) {
			$type = "int";
		} elseif (is_float($value)) {
			$type = "float";
		} elseif (is_string($value)) {
			$type = "string";
		} elseif (is_array($value)) {
			$type = "array";
		} elseif (is_bool($value)) {
			$type = "bool";
		}
		return $type;
	}

}
