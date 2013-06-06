<?php

/**
* Admin modules list handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_admin_modules {

	/** @var array */
	var $_MODULES_TO_SKIP	= array(
	);
	/** @var string @conf_skip Pattern for files */
	var $_include_pattern	= array("", "#\.(php|stpl)\$#");
	/** @var string @conf_skip Description file pattern */
	var $_desc_file_pattern	= "#[a-z0-9_]\.xml\$#i";
	/** @var string @conf_skip Class method pattern */
	var $_method_pattern	= "/function ([a-zA-Z_][a-zA-Z0-9_]+)/is";
	/** @var string @conf_skip Class extends pattern */
	var $_extends_pattern	= "/class (\w+)? extends (\w+)? \{/";
	/** @var string */
	var $TEMP_DIR			= "uploads/tmp/";
	/** @var bool */
	var $USE_UNIQUE_TMP_DIR	= 0;
	/** @var bool Auto-find modules (old-style) */
	var $AUTO_FIND_MODULES	= 0;
	/** @var int Number of modules to display on one page */
	var $MODULES_PER_PAGE	= 200;
	/** @var bool Parse core "module" class in get_methods */
	var $PARSE_PROFY_MODULE	= false;

	/**
	* Framework constructor
	*/
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"file_format"		=> 'radio_box("file_format",$this->_file_formats,		$selected, true, 2, "", false)',
			"module"			=> 'select_box("module",	$this->_modules,			$selected, false, 2, "", false)',
		);
		// Prepare "file_formats" box
		$this->_file_formats = array(
			"zip"	=> t('Zip'),
		);
		$this->_zlib_extension_loaded	= extension_loaded("zlib");
		if ($this->_zlib_extension_loaded) {
			$this->_file_formats["gz"] = t("Tar GZ");
		}
		$this->_bz2_extension_loaded	= extension_loaded("bz2");
		if ($this->_bz2_extension_loaded) {
			$this->_file_formats["bz2"] = t("Tar BZ");
		}
		// Get list of available modules
		$this->_modules = $this->_get_modules();
		unset($this->_modules[""]);
	}

	/**
	* Default method
	*/
	function show () {
		// Connect pager
		$sql = "SELECT * FROM `".db('admin_modules')."` ORDER BY `name` ASC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", $this->MODULES_PER_PAGE);
		// Get records from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$is_in_project		= file_exists(ADMIN_REAL_PATH. ADMIN_MODULES_DIR. $A["name"]. CLASS_EXT);
			$is_in_project2		= file_exists(ADMIN_REAL_PATH. "priority2/". ADMIN_MODULES_DIR. $A["name"]. CLASS_EXT);
			$is_in_framework	= file_exists(PF_PATH. ADMIN_MODULES_DIR. PF_PREFIX. $A["name"]. CLASS_EXT);
			$is_in_framework2	= file_exists(PF_PATH. "priority2/". ADMIN_MODULES_DIR. PF_PREFIX. $A["name"]. CLASS_EXT);
			$locations = array();
			if ($is_in_project) {
				$locations[] = array(
					"name"	=> "project",
					"link"	=> "./?object=file_manager&action=edit_item&f_=".$A["name"].".class.php"."&dir_name=".urlencode(INCLUDE_PATH. "modules"),
				);
			}
			if ($is_in_project2) {
				$locations[] = array(
					"name"	=> "project_p2",
					"link"	=> "./?object=file_manager&action=edit_item&f_=".$A["name"].".class.php"."&dir_name=".urlencode(INCLUDE_PATH. "priority2/modules"),
				);
			}
			if ($is_in_framework) {
				$locations[] = array(
					"name"	=> "framework",
					"link"	=> "./?object=file_manager&action=edit_item&f_="."yf_".$A["name"].".class.php"."&dir_name=".urlencode(PF_PATH. "modules"),
				);
			}
			if ($is_in_framework2) {
				$locations[] = array(
					"name"	=> "framework_p2",
					"link"	=> "./?object=file_manager&action=edit_item&f_="."yf_".$A["name"].".class.php"."&dir_name=".urlencode(PF_PATH. "priority2/modules"),
				);
			}
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"name"				=> _prepare_html($A["name"]),
				"pretty_name"		=> _prepare_html(ucwords(str_replace("_", " ", $A["name"]))),
				"desc"				=> _prepare_html($A["description"]),
				"active"			=> intval((bool) $A["active"]),
				"locations"			=> $locations,
				"active_link"		=> "./?object=".$_GET["object"]."&action=change_activity&id=".$A["name"],
				"uninstall_link"	=> $is_in_project ? "./?object=".$_GET["object"]."&action=uninstall&id=".$A["name"] : "",
				"settings_link"		=> "./?object=conf_editor&action=admin_modules&id=".$A["name"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"total"			=> intval($total),
			"pages"			=> $pages,
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_action",
			"import_link"	=> "./?object=".$_GET["object"]."&action=import",
			"export_link"	=> "./?object=".$_GET["object"]."&action=export",
			"refresh_link"	=> "./?object=".$_GET["object"]."&action=refresh_modules_list",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Delete module (uninstall)
	*/
	function mass_action () {
		if (!empty($_POST["names"])) {
			if ($_POST["activate"]) {
				db()->UPDATE("admin_modules", array("active" => 1), "`name` IN('".implode("','", _es($_POST["names"]))."')");
			} elseif ($_POST["deactivate"]) {
				db()->UPDATE("admin_modules", array("active" => 0), "`name` IN('".implode("','", _es($_POST["names"]))."')");
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("admin_modules");
			}
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Delete module (uninstall)
	*/
	function uninstall () {
		$OBJ = $this->_load_sub_module("admin_modules_install");
		return is_object($OBJ) ? $OBJ->_uninstall() : "";
	}

	/**
	* Change module activity status
	* 
	*/
	function change_activity () {
		// Try to find such module in db
		if (!empty($_GET["id"])) {
			$module_info = db()->query_fetch("SELECT * FROM `".db('admin_modules')."` WHERE `name`='"._es($_GET["id"])."' LIMIT 1");
		}
		// Do change activity status
		if (!empty($module_info)) {
			db()->UPDATE("admin_modules", array("active" => (int)!$module_info["active"]), "`id`=".intval($module_info["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("admin_modules");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($module_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Refresh modules list (try to find modules automatically)
	* 
	*/
	function refresh_modules_list () {
		// Cleanup duplicate records
		$Q = db()->query(
			"SELECT `name`, COUNT(*) AS `num` 
			FROM `".db('admin_modules')."` 
			GROUP BY `name` 
			HAVING `num` > 1"
		);
		while ($A = db()->fetch_assoc($Q)) {
			db()->query(
				"DELETE FROM `".db('admin_modules')."` 
				WHERE `name`='"._es($A["name"])."' 
				LIMIT ".intval($A["num"] - 1)
			);
		}
		// Get current modules list
		$Q = db()->query("SELECT * FROM `".db('admin_modules')."`");
		while ($A = db()->fetch_assoc($Q)) $all_admin_modules_array[$A["name"]] = $A["name"];
		// Do parse modules dir
		$refreshed_modules = $this->_get_modules_from_files(1);
		// Try to find new modules
		foreach ((array)$refreshed_modules as $cur_module_name) {
			if (isset($all_admin_modules_array[$cur_module_name])) {
				continue;
			}
			// Add record to db
			db()->INSERT("admin_modules", array(
				"name"		=> _es($cur_module_name),
				"active"	=> 0,
			));
		}
		// Check for missing modules
		foreach ((array)$all_admin_modules_array as $cur_module_name) {
			// Do delete missing modules records
			if (!isset($refreshed_modules[$cur_module_name])) {
				db()->query("DELETE FROM `".db('admin_modules')."` WHERE `name`='"._es($cur_module_name)."'");
			}
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("admin_modules");
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Import module
	* 
	*/
	function import () {
		$OBJ = $this->_load_sub_module("admin_modules_install");
		return is_object($OBJ) ? $OBJ->_import() : "";
	}

	/**
	* Export module
	* 
	*/
	function export () {
		$OBJ = $this->_load_sub_module("admin_modules_install");
		return is_object($OBJ) ? $OBJ->_export() : "";
	}

	/**
	* Get available modules
	*/
	function _get_modules ($params = array()) {
		$with_all			= isset($params["with_all"]) ? $params["with_all"] : 1;
		$with_sub_modules	= isset($params["with_sub_modules"]) ? $params["with_sub_modules"] : 0;
		$admin_modules_array	= array();
		// Insert value for all modules
		if ($with_all) {
			$admin_modules_array[""] = t("-- ALL --");
		}
		// Need to prevent multiple calls
		if (isset($GLOBALS['admin_modules_array'])) {
			return $GLOBALS['admin_modules_array'];
		}
		// If auto-find is turned off - then get modules from db
		if ($this->AUTO_FIND_MODULES) {
			// Do get modules list from source dir
			$admin_modules_array = $this->_get_modules_from_files();
			// Prepare sql
			foreach ((array)$admin_modules_array as $cur_module_name) {
				$sql_array[$cur_module_name] = "('"._es($cur_module_name)."','1')";
			}
			// Do update table
			if (!empty($sql_array)) {
				ksort($sql_array);
				db()->query("TRUNCATE TABLE `".db('admin_modules')."`");
				db()->query("INSERT INTO `".db('admin_modules')."` (`name`,`active`) VALUES ".implode(",", $sql_array));
			}
		// Do get installed modules list
		} else {
			$Q = db()->query("SELECT * FROM `".db('admin_modules')."` WHERE `active`='1'");
			while ($A = db()->fetch_assoc($Q)) $admin_modules_array[$A["name"]] = $A["name"];
		}
		// Sort modules list
		ksort($admin_modules_array);
		$GLOBALS['admin_modules_array'] = $admin_modules_array;
		unset($GLOBALS['admin_modules_array'][""]);
		return $admin_modules_array;
	}

	/**
	* Get available modules from files
	*/
	function _get_modules_from_files ($include_framework = true, $with_sub_modules = false) {
		$admin_modules_array = array();
		$dir_to_scan = ADMIN_REAL_PATH. ADMIN_MODULES_DIR;
		foreach ((array)_class("dir")->scan_dir($dir_to_scan) as $k => $v) {
			$v = str_replace("//", "/", $v);
			if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
				continue;
			}
			if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), "/")) {
				continue;
			}
			$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
			$module_name = str_replace(ADMIN_CLASS_PREFIX, "", $module_name);
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}
		$dir_to_scan = ADMIN_REAL_PATH. "priority2/". ADMIN_MODULES_DIR;
		foreach ((array)_class("dir")->scan_dir($dir_to_scan) as $k => $v) {
			$v = str_replace("//", "/", $v);
			if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
				continue;
			}
			if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), "/")) {
				continue;
			}
			$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
			$module_name = str_replace(ADMIN_CLASS_PREFIX, "", $module_name);
			if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
				continue;
			}
			$admin_modules_array[$module_name] = $module_name;
		}
		// Do parse files from the framework
		if ($include_framework) {
			$dir_to_scan = PF_PATH. ADMIN_MODULES_DIR;
			foreach ((array)_class("dir")->scan_dir($dir_to_scan) as $k => $v) {
				$v = str_replace("//", "/", $v);
				if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
					continue;
				}
				if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), "/")) {
					continue;
				}
				$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
				$module_name = str_replace(PF_PREFIX, "", $module_name);
				$module_name = str_replace(ADMIN_CLASS_PREFIX, "", $module_name);
				$module_name = str_replace(SITE_CLASS_PREFIX, "", $module_name);
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}
			$dir_to_scan = PF_PATH. "priority2/". ADMIN_MODULES_DIR;
			foreach ((array)_class("dir")->scan_dir($dir_to_scan) as $k => $v) {
				$v = str_replace("//", "/", $v);
				if (substr($v, -strlen(CLASS_EXT)) != CLASS_EXT) {
					continue;
				}
				if (!$with_sub_modules && false !== strpos(substr($v, strlen($dir_to_scan)), "/")) {
					continue;
				}
				$module_name = substr(basename($v), 0, -strlen(CLASS_EXT));
				$module_name = str_replace(PF_PREFIX, "", $module_name);
				$module_name = str_replace(ADMIN_CLASS_PREFIX, "", $module_name);
				$module_name = str_replace(SITE_CLASS_PREFIX, "", $module_name);
				if (in_array($module_name, $this->_MODULES_TO_SKIP)) {
					continue;
				}
				$admin_modules_array[$module_name] = $module_name;
			}
		}
		ksort($admin_modules_array);
		return $admin_modules_array;
	}

	/**
	* Get available methods
	*/
	function _get_methods ($params = array()) {
		$ONLY_PRIVATE_METHODS = $params["private"];
		$methods_by_modules = array();
		foreach ((array)$GLOBALS['admin_modules_array'] as $user_module_name) {
			// Remove admin prefix from module name here
			if (substr($user_module_name, 0, strlen(ADMIN_CLASS_PREFIX)) == ADMIN_CLASS_PREFIX) {
				$user_module_name = substr($user_module_name, strlen(ADMIN_CLASS_PREFIX));
			}
			$file_text = "";
			$_with_admin_prefix = false;
			$file_name = ADMIN_REAL_PATH. ADMIN_MODULES_DIR. $user_module_name. CLASS_EXT;
			// Try to get file from the framework
			if (!file_exists($file_name)) {
				$file_name = PF_PATH. ADMIN_MODULES_DIR. PF_PREFIX. $user_module_name. CLASS_EXT;
			}
			if (!file_exists($file_name)) {
				$file_name = PF_PATH. "priority2/". ADMIN_MODULES_DIR. PF_PREFIX. $user_module_name. CLASS_EXT;
			}
			// Try with ADMIN_CLASS_PREFIX
			if (!file_exists($file_name)) {
				$file_name = ADMIN_REAL_PATH. ADMIN_MODULES_DIR. ADMIN_CLASS_PREFIX. $user_module_name. CLASS_EXT;
				$_with_admin_prefix = true;
			}
			// Give up
			if (!file_exists($file_name)) {
				continue;
			}
			$file_text = file_get_contents($file_name);
			// Try to get methods from parent classes (if exist one)
			$methods_by_modules[$user_module_name] = $this->_recursive_get_methods_from_extends($file_text, ($_with_admin_prefix ? ADMIN_CLASS_PREFIX : ""). $user_module_name, $ONLY_PRIVATE_METHODS);
			// Try to match methods in the current file
			foreach ((array)$this->_get_methods_names_from_text($file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
				$method_name = str_replace(PF_PREFIX, "", $method_name);
				// Skip constructors in PHP4 style
				if ($method_name == $user_module_name) {
					continue;
				}
				// Add into array
				$methods_by_modules[$user_module_name][$method_name] = $method_name;
			}
		}
		ksort($methods_by_modules);
		return $methods_by_modules;
	}

	/**
	* Get methods names from given source text
	*/
	function _recursive_get_methods_from_extends ($file_text = "", $user_module_name = "", $_type = "admin", $ONLY_PRIVATE_METHODS = false) {
		$extends_file_path = "";
		$methods = array();
		// Check if cur class extends some other class
		if (preg_match($this->_extends_pattern, $file_text, $matches_extends)) {
			$class_name_1 = $matches_extends[1];
			$class_name_2 = $matches_extends[2];
			// Check if we need to extends file from framework
			$_extends_from_fwork = (substr($class_name_2, 0, strlen(PF_PREFIX)) == PF_PREFIX);
			// Parsing classes inside admin section
			if ($_type == "admin") {
				// Special admin prefix
				if (substr($class_name_1, 0, strlen(ADMIN_CLASS_PREFIX)) == ADMIN_CLASS_PREFIX) {
					if ($_extends_from_fwork) {
						$extends_file_path = PF_PATH. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
						$extends_file_path2 = PF_PATH. "priority2/". ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
					} else {
						$extends_file_path = INCLUDE_PATH. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
						$extends_file_path2 = INCLUDE_PATH. "priority2/". USER_MODULES_DIR. $class_name_2. CLASS_EXT;
						$_type = "user";
					}
					$user_module_name = substr($user_module_name, strlen(ADMIN_CLASS_PREFIX));
				// Check if we parsing current class
				} elseif ($class_name_1 == $user_module_name || str_replace(PF_PREFIX, "", $class_name_1) == $user_module_name) {
					$extends_file_path = PF_PATH. ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
					$extends_file_path2 = PF_PATH. "priority2/". ADMIN_MODULES_DIR. $class_name_2. CLASS_EXT;
				}
			} elseif ($_type == "user") {
				if ($class_name_1 == $user_module_name || str_replace(PF_PREFIX, "", $class_name_1) == $user_module_name) {
					$extends_file_path = PF_PATH. USER_MODULES_DIR. $class_name_2. CLASS_EXT;
					$extends_file_path2 = PF_PATH. "priority2/". USER_MODULES_DIR. $class_name_2. CLASS_EXT;
				}
			}
			// Special processing of the "yf_module"
			if ($this->PARSE_PROFY_MODULE && $class_name_2 == PF_PREFIX. "module") {
				$extends_file_path = PF_PATH. "classes/". PF_PREFIX. "module". CLASS_EXT;
			}
			if (!empty($extends_file_path) && file_exists($extends_file_path)) {
				$extends_file_text = file_get_contents($extends_file_path);
			} elseif (!empty($extends_file_path2) && file_exists($extends_file_path2)) {
				$extends_file_text = file_get_contents($extends_file_path2);
			}
			// Try to parse extends file for the public methods
			foreach ((array)$this->_get_methods_names_from_text($extends_file_text, $ONLY_PRIVATE_METHODS) as $method_name) {
				// Skip constructors in PHP4 style
				if ($method_name == $user_module_name) {
					continue;
				}
				// Add into array
				$methods[$method_name] = $method_name;
			}
			// Try to find extends other module
			if (!empty($extends_file_text)) {
				foreach ((array)$this->_recursive_get_methods_from_extends($extends_file_text, $class_name_2, $_type) as $method_name) {
					$methods[$method_name] = $method_name;
				}
			}
			// Garbage collect
			$extends_file_text = "";
		}
		ksort($methods);
		return $methods;
	}

	/**
	* Get methods names from given source text
	*/
	function _get_methods_names_from_text ($text = "", $ONLY_PRIVATE_METHODS = false) {
		$methods = array();
		if (empty($text)) return $methods;
		preg_match_all($this->_method_pattern, $text, $matches);
		foreach ((array)$matches[1] as $method_name) {
			$_is_private_method = ($method_name[0] == "_");
			// Skip non-needed methods
			if ($ONLY_PRIVATE_METHODS && !$_is_private_method) {
				continue;
			}
			if (!$ONLY_PRIVATE_METHODS && $_is_private_method) {
				continue;
			}
			$methods[$method_name] = $method_name;
		}
		ksort($methods);
		return $methods;
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Try to load sub_module
	*/
	function _load_sub_module ($module_name = "") {
		return _class($module_name, "admin_modules/admin_modules/");
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Refresh modules list",
				"url"	=> "./?object=".$_GET["object"]."&action=refresh_modules_list",
			),
			array(
				"name"	=> "Install",
				"url"	=> "./?object=".$_GET["object"]."&action=import",
			),
			array(
				"name"	=> "Export",
				"url"	=> "./?object=".$_GET["object"]."&action=export",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = _ucfirst(t("Admin modules editor"));
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
