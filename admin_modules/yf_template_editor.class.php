<?php

/**
* Templates handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_template_editor {

	public $CACHE_NAME = "themes_num_stpls";

	/**
	* Framework constructor
	*/
	function _init () {
		// Physical path to the library templates
		define(TPLS_LIB_PATH, INCLUDE_PATH. tpl()->_THEMES_PATH);
		define(TPLS_LIB_PATH2, INCLUDE_PATH. "priority2/". tpl()->_THEMES_PATH);
		// Framework templates physical path
		define(F_TPLS_LIB_PATH, YF_PATH. tpl()->_THEMES_PATH);
		define(F_TPLS_LIB_PATH2, YF_PATH. "priority2/". tpl()->_THEMES_PATH);
		// Project templates physical path
		define(P_TPLS_LIB_PATH, INCLUDE_PATH. tpl()->_THEMES_PATH);
		define(P_TPLS_LIB_PATH2, INCLUDE_PATH. "priority2/". tpl()->_THEMES_PATH);

		// Try to get info about sites vars
		$SITES_INFO = _class("sites_info")->info;

		// Theme directories by location
		$this->_dir_array = array(
			"framework"		=> F_TPLS_LIB_PATH,
			"project"		=> P_TPLS_LIB_PATH,
#			"framework_p2"	=> F_TPLS_LIB_PATH2,
#			"project_p2"	=> P_TPLS_LIB_PATH2,
		);

		// Adding to 'Theme directories by location' array sitenames and paths by site
		foreach ((array)$SITES_INFO as $site_dir_array) {
			$this->_dir_array[$site_dir_array["name"]] = $site_dir_array["REAL_PATH"]."templates/";		
		}
	}

	/**
	* Default function
	*/
	function show () {
		$body = $this->_show_themes();
		return $body;
	}

	/**
	* Show themes list
	*/
	function _show_themes () {
		$themes = $this->_get_themes();

		if (main()->USE_SYSTEM_CACHE) {
			$num_stpls_array = cache()->get($this->CACHE_NAME, 60);
		}
		if (empty($num_stpls_array)) {
			foreach ((array)$themes as $theme_class => $theme_attr) {
				foreach ((array)$theme_attr as $theme_path => $theme_name) {
					$num_stpls_array[$theme_name][$this->_dir_array[$theme_class]] = count($this->_get_stpls_in_theme($theme_name, $this->_dir_array[$theme_class]));
				}
			}
			if (main()->USE_SYSTEM_CACHE) {
				cache()->put($this->CACHE_NAME, $num_stpls_array);
			}
		}
		// Process records
		foreach ((array)$themes as $theme_class => $theme_attr) {
			if (realpath(P_TPLS_LIB_PATH) == realpath($this->_dir_array[$theme_class]) && $theme_class != "project") continue;
			$replace3 = array(
				"location"			=> $theme_class,
				"themes_lib_dir"	=> realpath($this->_dir_array[$theme_class]),
				"add_url"			=> "./?object=".$_GET["object"]."&action=add_theme_form&location=".$theme_class,
			);
			$items .= tpl()->parse($_GET["object"]."/themes_location_item", $replace3);
 
			foreach ((array)$theme_attr as $theme_path => $theme_name) {
				$replace2 = array(
					"num"				=> ++$i,
					"bg_class"			=> "bg2",
					"name"				=> $theme_name,
					"num_stpls"			=> $num_stpls_array[$theme_name][$this->_dir_array[$theme_class]],
					"theme_url"			=> "./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$theme_class,
					"edit_url"			=> "./?object=".$_GET["object"]."&action=edit_theme&theme=".$theme_name."&location=".$theme_class,
					"delete_url"		=> "./?object=".$_GET["object"]."&action=delete_theme&theme=".$theme_name."&location=".$theme_class,
					"into_db_url"		=> "./?object=".$_GET["object"]."&action=put_stpls_into_db&theme=".$theme_name."&location=".$theme_class,
					"into_files_url"	=> "./?object=".$_GET["object"]."&action=put_stpls_into_files&theme=".$theme_name."&location=".$theme_class,
					"export_url"		=> "./?object=".$_GET["object"]."&action=export&theme=".$theme_name."&location=".$theme_class,
					// for determine rights of export/import
					"location"			=> $theme_class,
				);
				$items .= tpl()->parse($_GET["object"]."/themes_item", $replace2);
			}
		}
		$replace = array(
			"items" 		=> $items,
			"add_url"		=> "./?object=".$_GET["object"]."&action=add_theme_form",
			"import_url"	=> "./?object=".$_GET["object"]."&action=import",
		);
		return tpl()->parse($_GET["object"]."/themes_main", $replace);
	}

	/**
	* Add Theme Form
	*/
	function add_theme_form () {
		if ($_GET["location"] == "framework") {
			return tpl()->parse($_GET["object"]."/framework_warning");
		}
		$replace = array(
			"location"		=> $location,
			"form_action"	=> "./?object=".$_GET["object"]."&action=insert_theme&location=".$_GET["location"],
			"back_url"		=> "./?object=".$_GET["object"]."&action=show&location=".$_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/add_theme", $replace);
	}

	/**
	* Edit Theme
	*/
	function edit_theme () {
		if ($_GET["location"] == "framework") {
			return tpl()->parse($_GET["object"]."/framework_warning");
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_theme&theme=".$_GET["theme"]."&location=".$_GET["location"],
			"back_url"		=> "./?object=".$_GET["object"]."&action=show",
			"theme_name" 	=> _prepare_html($_GET["theme"]),
			"location"		=> $_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/edit_theme", $replace);
	}

	/**
	* Insert Theme
	*/
	function insert_theme () {
		if ($_GET["location"] == "framework") {
			return tpl()->parse($_GET["object"]."/framework_warning");
		}
		$new_theme_name	= $_POST["theme_name"];
		if (empty($new_theme_name)) {
			return "Theme name required!";
		}
		_mkdir_m($this->_dir_array[$_GET["location"]]. $new_theme_name, 0777, 1);
		return js_redirect("./?object=".$_GET["object"]."&action=show");
	}

	/**
	* Save Theme
	*/
	function save_theme () {
		$new_theme_name	= $_POST["theme_name"];
		if ($_GET["location"] == "framework") {
			return tpl()->parse($_GET["object"]."/framework_warning");
		}
		if (empty($_GET["theme"]) || empty($new_theme_name)) {
			return "Theme name required!";
		}
		if ($_GET["theme"] != $new_theme_name) {
			rename($this->_dir_array[$_GET["location"]]. $_GET["theme"], $this->_dir_array[$_GET["location"]]. $new_theme_name);
		}
		return js_redirect("./?object=".$_GET["object"]."&action=show");
	}

	/**
	* Delete Theme
	*/
	function delete_theme () {
		$theme_name	= $_GET["theme"];
		if ($_GET["location"] == "framework") {
			return tpl()->parse($_GET["object"]."/framework_warning");
		}
		if (empty($theme_name)) {
			return "Theme name required!";
		}
		_class("dir")->delete_dir($this->_dir_array[$_GET["location"]]. $theme_name, 1);
		return js_redirect("./?object=".$_GET["object"]."&action=show");
	}

	/**
	* List of STPL items inside selected theme
	*/
	function show_stpls_in_theme () {
		$this->theme_name = $_GET["theme"];
		// Generate path to the current processing theme
		$this->_cur_theme_path = $this->_dir_array[$_GET["location"]]. $this->theme_name;
		// Get multidimensional array of files inside theme
		$files_array = _class("dir")->scan_dir($this->_cur_theme_path, false);
		// Show formatted list of given files
		$items_array = $this->_show_stpls_list($files_array);
		// Connect pager
		list($items_array, $pages, $total) = common()->divide_pages($items_array, null, null, $PER_PAGE);
		$items = implode("", $items_array);
		// Process template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"theme_name"	=> $this->theme_name,
			"back_url"		=> "./?object=".$_GET["object"]."&action=show",
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_stpl&theme=".$this->theme_name."&location=".$_GET["location"],
			"location"		=> $_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/stpls_list_main", $replace);
	}

	/**
	* External API method
	*/
	function _get_stpls_for_type ($type = "user") {
		$theme_name = $type == "admin" ? "admin" : "user";

		$CACHE_NAME = "stpls_list_for_".$type;
		$TTL = 600;
		if (main()->USE_SYSTEM_CACHE) {
			$items = cache()->get($CACHE_NAME, $TTL);
		}
		if (!empty($items)) {
			return $items;
		}
		$items = array();

		$STPL_EXT = ".stpl";
		$pattern_include = array("", "#\.stpl\$#i");
		$pattern_exclude = "#(svn|git)#i";

		$cur_theme_path = $this->_dir_array["framework"]. $theme_name. "/";
		foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
/*		$cur_theme_path = $this->_dir_array["framework_p2"]. $theme_name. "/";
		foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
*/		$cur_theme_path = $this->_dir_array["project"]. $theme_name. "/";
		foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
/*		$cur_theme_path = $this->_dir_array["project_p2"]. $theme_name. "/";
		foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
*/		// Inherit user templates from framework and project
		if ($type == "admin") {
			$cur_theme_path = $this->_dir_array["framework"]. "user". "/";
			foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
				$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
				$items[$name] = $name;
			}

			$cur_theme_path = $this->_dir_array["project"]. "user". "/";
			foreach ((array)_class("dir")->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
				$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
				$items[$name] = $name;
			}
		}
		if (isset($items[""])) {
			unset($items[""]);
		}
		ksort($items);
		if (main()->USE_SYSTEM_CACHE) {
			$items = cache()->put($CACHE_NAME, $items);
		}
		return $items;
	}

	/**
	* Internal method
	*/
	function _show_stpls_list ($files_array = array(), $level = 0) {
		asort($files_array);
		$body = array();
		foreach ((array)$files_array as $cur_file_path => $file_name) {
			if (false !== strpos($cur_file_path, ".svn")) {
				continue;
			}
			if (false !== strpos($cur_file_path, ".git")) {
				continue;
			}
			if (is_array($file_name)) {
				$body[$cur_file_path."_dir"] = $this->_show_stpls_item($cur_file_path, $level, true);
				$body = array_merge($body, (array)$this->_show_stpls_list($file_name, $level + 1));
			} else {
				if (common()->get_file_ext($file_name) != "stpl") {
					continue;
				}
				$body[$cur_file_path] = $this->_show_stpls_item($cur_file_path, $level);
			}
		}
		return $body;
	}

	/**
	* Internal method
	*/
	function _show_stpls_item ($file_path = "", $level = 0, $is_folder = false) {
		static $i, $j;
		$name = str_replace(array($this->_cur_theme_path."/", ".stpl"), "", $file_path);
		// Skip images folder and its contents
		if (substr($name, 0, 6) == "images") return false;
		$replace = array(
			"name"			=> $is_folder ? "<b>".$name."</b>" : $name,
			"bg_class"		=> !($i++%2) ? "bg1" : "bg2",
			"num"			=> !$is_folder ? ++$j : "",
			"pad"			=> $level * 50/* + ($is_folder ? 20 : 0)*/, // In pixels
			"stpl_size"		=> !$is_folder ? filesize($file_path) : "",
			"edit_stpl_url"	=> "./?object=".$_GET["object"]."&action=".($is_folder ? "edit_dir" : "edit_stpl")."&name=".$name."&theme=".$this->theme_name."&location=".$_GET["location"],
			"del_stpl_url"	=> "./?object=".$_GET["object"]."&action=".($is_folder ? "delete_dir" : "delete_stpl")."&name=".$name."&theme=".$this->theme_name."&location=".$_GET["location"],
			"location"		=> $_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/stpls_list_item", $replace);
	}

	/**
	* Edit template form
	*/
	function edit_stpl () {
		$theme_name	= $_GET["theme"];
		$stpl_name	= $_GET["name"];
		if (empty($theme_name) || empty($stpl_name)) return "Template name and theme required!";
		// Get template file from lib
		$lib_stpl_path		= $this->_dir_array[$_GET["location"]]. $theme_name. "/". $stpl_name. tpl()->_STPL_EXT;
		$text = file_get_contents($lib_stpl_path);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_stpl&name=".$stpl_name."&theme=".$theme_name."&location=".$_GET["location"],
			"theme_name"	=> $theme_name,
			"stpl_name"		=> $stpl_name,
			"stpl_text"		=> _prepare_html($text, 0),
			"back_url"		=> "./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"],
			"location"		=> $_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/edit_main", $replace);
	}

	/**
	* Save STPL contents
	*/
	function save_stpl () {
		$theme_name	= $_GET["theme"];
		$stpl_name	= $_REQUEST["name"];
		if ($_GET["location"] == "framework") return tpl()->parse($_GET["object"]."/framework_warning");
		if (empty($theme_name) || empty($stpl_name)) return "Template name and theme required!";
		$lib_stpl_path	= $this->_dir_array[$_GET["location"]]. $theme_name. "/". $stpl_name. tpl()->_STPL_EXT;
		$text = $_POST["stpl_text"];
		// Create template folder if it doesnt exists
		if (!file_exists($lib_stpl_path)) {
			_mkdir_m(dirname($lib_stpl_path));
		}
		// Save template contents
		file_put_contents($lib_stpl_path, $text);
		// Redirect back
		js_redirect("./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"]);
	}

	/**
	* Delete STPL item
	*/
	function delete_stpl () {
		$theme_name	= $_GET["theme"];
		$stpl_name	= $_GET["name"];
		if ($_GET["location"] == "framework") return tpl()->parse($_GET["object"]."/framework_warning");
		if (empty($theme_name) || empty($stpl_name)) return "Template name and theme required!";
		$lib_stpl_path	= $this->_dir_array[$_GET["location"]]. $theme_name. "/". $stpl_name. tpl()->_STPL_EXT;
		// Delete STPL
		if (file_exists($lib_stpl_path)) unlink($lib_stpl_path);
		// Redirect back
		js_redirect("./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"]);
	}

	/**
	* Edit Dir
	*/
	function edit_dir () {
		$theme_name	= $_GET["theme"];
		$dir_name	= $_GET["name"];
		if (empty($theme_name) || empty($dir_name)) return "Template name and dir required!";
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_dir&name=".$dir_name."&theme=".$theme_name."&location=".$_GET["location"],
			"theme_name"	=> $theme_name,
			"dir_name"		=> $dir_name,
			"back_url"		=> "./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"],
			"location"		=> $_GET["location"],
		);
		return tpl()->parse($_GET["object"]."/edit_dir", $replace);
	}

	/**
	* Save Dir
	*/
	function save_dir () {
		$theme_name		= $_GET["theme"];
		$dir_name		= $_GET["name"];
		$new_dir_name	= $_POST["dir_name"];
		if ($_GET["location"] == "framework") return tpl()->parse($_GET["object"]."/framework_warning");
		if (empty($theme_name) || empty($dir_name) || empty($new_dir_name)) return "Template name and dir required!";
		// Do rename folder with theme
		if ($dir_name != $new_dir_name)	rename($this->_dir_array[$_GET["location"]]. $theme_name. "/". $dir_name, $this->_dir_array[$_GET["location"]]. $theme_name. "/". $new_dir_name);
		// Redirect back
		js_redirect("./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"]);
	}

	/**
	* Delete Dir
	*/
	function delete_dir () {
		$theme_name	= $_GET["theme"];
		$dir_name	= $_GET["name"];
		if ($_GET["location"] == "framework") return tpl()->parse($_GET["object"]."/framework_warning");
		if (empty($theme_name) || empty($dir_name)) return "Template name and dir required!";
		// Remove folder recursively with contents
		_class("dir")->delete_dir($this->_dir_array[$_GET["location"]]. $theme_name. "/". $dir_name);
		// Redirect back
		js_redirect("./?object=".$_GET["object"]."&action=show_stpls_in_theme&theme=".$theme_name."&location=".$_GET["location"]);
	}

	/**
	* Interface method
	*/
	function put_stpls_into_db () {
		return $this->_stpls_into_db ($_GET["theme"], $_GET["location"]);
	}

	/**
	* Interface method
	*/
	function put_stpls_into_files () {
		return $this->_stpls_into_files ($_GET["theme"], $_GET["location"]);
	}

	/**
	* Get STPL templates from files table and put them into db table
	*/
	function _stpls_into_db ($theme_name, $location) {

		if ($location == "framework") $site_id = -1;
		if ($location == "project")	 $site_id = 0;

		if ($location !="framework" && $theme_class !="project"){
			list($site_id) = db()->query_fetch("SELECT id AS `0` FROM ".db('sites')." WHERE name='".$location."'");
		}
		$files = _class("dir")->scan_dir($this->_dir_array[$location].$theme_name);
		if (!is_array($files)) continue;
		// Show execution progress
		$body .= "<div align=\"center\">";
		$body .= "<br><b>PROCESSING THEME \"".$theme_name."\" in \"".$location."\":</b><br><br>\r\n";
		// Process files in the current theme
		foreach ((array)$files as $file_name) {
			// Skip all other files except templates
			if (common()->get_file_ext($file_name) != "stpl") {
				continue;
			}
			$theme_name	= _es($theme_name);
			$stpl_name	= _es(str_replace($this->_dir_array[$location].$theme_name."/", "", substr($file_name, 0, -5)));
			$text		= _es(file_get_contents($file_name));
			// Check if current template exists in the db
			list($record_id) = db()->query_fetch("SELECT id AS `0` FROM ".db('templates')." WHERE theme_name='".$theme_name."' AND name='".$stpl_name."' AND site_id='".$site_id."'");
			// Insert or update record
			if ($record_id) {
				db()->UPDATE("templates", array("text" => _es($text)), "id=".intval($record_id));
			} else {
				db()->query("REPLACE INTO ".db('templates')." (theme_name,name,text, site_id) VALUES ('".$theme_name."','".$stpl_name."','".$text."','".$site_id."')");
			}
			// Show execution progress	
			$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
		}
		$body .= "<br /><br /><a href=\"\" onclick=\"javascript:history.back();\">{t(\"Back\")}</a>\r\n";
		$body .= "</div>";
		return $body;
	}

	/**
	* Get STPL templates from db table and put them into files
	*/

	function _stpls_into_files ($theme_name, $location) {

		if ($location == "framework") {
			$body .= tpl()->parse($_GET["object"]."/framework_warning");
			return $body;
		}

		if ($location == "project")	 $site_id = 0;

		if ($location !="framework" && $theme_class !="project"){
			list($site_id) = db()->query_fetch("SELECT id AS `0` FROM ".db('sites')." WHERE name='".$location."'");
		}

		// Show execution progress
		$body .= "<div align=\"center\">";
		$body .= "<br><b>PROCESSING THEME \"".$theme_name."\":</b><br><br>\r\n";
		if (!file_exists($this->_dir_array[$location]. $theme_name)){
			// Create theme folder
			_mkdir_m($this->_dir_array[$location]. $theme_name);
		}
		// Get templates from the current theme and location
		$Q = db()->query("SELECT * FROM ".db('templates')." WHERE theme_name='".$theme_name."' AND site_id='".intval($site_id)."' AND active='1'");
		// Process files
		while ($A = db()->fetch_assoc($Q)) {
			$stpl_name	= $A["name"];
			$text		= trim($A["text"]);
			// Create subfolder if needed for template
			$sub_dir_name = substr($stpl_name, 0, -strlen(basename($stpl_name)));
			if (strlen($sub_dir_name) && !file_exists($this->_dir_array[$location]. $theme_name. "/".  $sub_dir_name)) {
				_mkdir_m($this->_dir_array[$location]. $theme_name. "/". $sub_dir_name);
				$body .= "<b> /".$sub_dir_name."</b>  <b style='color:blue;'>Folder created</b><br>\r\n";
			}
			// Check control sums of existing file and template from DB
			if (!file_exists($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl")){
				if(file_put_contents($this->_dir_array[$location]. $theme_name. "/". $stpl_name. ".stpl", $text)) {
					// Show execution progress
					$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
				} else {
					// Show execution progress
					$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:red;'>FAILED</b><br>\r\n";
				}
			} else {
				$file_contens = trim(file_get_contents($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl"));
				$checksum_exist = crc32($file_contens);
				$checksum_from_db = crc32($text);
				if ($checksum_exist != $checksum_from_db){
					$encoded_path = base64_encode($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl");
					$replace2 = array(
						"location"		=> $location,
						"theme" 		=> $theme_name,
						"name"			=> $stpl_name,
						"db_src_link"	=> "./?object=".$_GET["object"]."&action=show_db_src&id=".$A["id"],
						"file_src_link"	=> "./?object=".$_GET["object"]."&action=show_file_src&path=".$encoded_path,
						"db_src_size"	=> strlen($text),
						"file_src_size" => strlen($file_contens),
						"id"			=> $A["id"],
						"path_hidden"	=> $encoded_path,
					);
				$items .= tpl()->parse($_GET["object"]."/user_dialog_item", $replace2);
				$body .= "<b>".$stpl_name."</b>  <b style='color:#9400D3;'>Admin confirm needed</b><br>\r\n";
				} else {
					$body .= "<b>".$stpl_name."</b>  <b style='color:grey;'>Not changed</b><br>\r\n";
				}
			}
		}
		if ($items) {
			$replace = array(
				"items"			=> $items,
				"form_action"	=> "./?object=".$_GET["object"]."&action=replace_stpls",
			);					
			$body .= tpl()->parse($_GET["object"]."/user_dialog_main", $replace);				
		}
		$body .= "<br /><br /><a href=\"\" onclick=\"javascript:history.back();\">{t(\"Back\")}</a>\r\n";
		$body .= "</div>";
		return $body;
	}

	/**
	* Replaces STPL templates content in existing file
	*/

	function replace_stpls () {
	
		$ids_to_select = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_select[$_cur_id] = $_cur_id;
		}
		// Do select templates from DB
		if (!empty($ids_to_select)) {
			$A = db()->query_fetch("SELECT * FROM ".db('templates')." WHERE id IN(".implode(",",$ids_to_select).")");
			file_put_contents(base64_decode($_POST["path_hidden"][$A["id"]]), $A["text"]);
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Show STPL template's content stored in database
	*/

	function show_db_src () {
		list($stpl_text) = db()->query_fetch("SELECT text AS `0` FROM ".db('templates')." WHERE id='".$_GET["id"]."'");
		$replace = array(
			"stpl_text"	=> trim($stpl_text),
			"location"	=> "database",
		);
		return tpl()->parse($_GET["object"]."/view_content", $replace);
	}

	/**
	* Show STPL template's content stored in file
	*/
	function show_file_src () {
		$path = base64_decode($_GET["path"]);
		$stpl_text = file_get_contents($path);

		$replace = array(
			"stpl_text"	=> trim($stpl_text),
			"location"	=> $path,
		);
		return tpl()->parse($_GET["object"]."/view_content", $replace);
	}

// TODO check and finish
	/**
	* Get (ALL!) STPL templates from files table and put them into db table
	*/
	function _all_stpls_into_db () {
		$themes = $this->_get_themes();
		// Process themes
		foreach ((array)$themes as $theme_class => $theme_attr) {
			if ($theme_class == "framework") $site_id = -1;
			if ($theme_class == "project")	 $site_id = 0;
			if ($theme_class !="framework" && $theme_class !="project"){
				list($site_id) = db()->query_fetch("SELECT id AS `0` FROM ".db('sites')." WHERE name='".$theme_class."'");
			}
			foreach ((array)$theme_attr as $theme_name) {
				$files = _class("dir")->scan_dir($this->_dir_array[$theme_class].$theme_name);
				if (!is_array($files)) continue;
				// Show execution progress
				$body .= "<br><b>PROCESSING THEME \"".$theme_name."\":</b><br><br>\r\n";
				// Process files in the current theme
				foreach ((array)$files as $file_name) {
					// Skip all other files except templates
					if (common()->get_file_ext($file_name) != "stpl") continue;
					$theme_name	= _es($theme_name);
					$stpl_name	= _es(str_replace($this->_dir_array[$theme_class].$theme_name."/", "", substr($file_name, 0, -5)));
					$text		= _es(file_get_contents($file_name));
					// Check if current template exists in the db
					list($record_id) = db()->query_fetch("SELECT id AS `0` FROM ".db('templates')." WHERE theme_name='".$theme_name."' AND name='".$stpl_name."'");
					// Insert or update record
					if ($record_id) db()->query("UPDATE ".db('templates')." SET text='".$text."' WHERE id=".intval($record_id));
					else db()->query("REPLACE INTO ".db('templates')." (theme_name,name,text, site_id) VALUES ('".$theme_name."','".$stpl_name."','".$text."','".$site_id."')");
					// Show execution progress	
					$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
				}
			}
		}
		return $body;
	}

	/**
	* Get STPL templates from db table and put them into files
	*/
// TODO check and finish
// TODO replace old function _mkdir_m_with_indexhtm() with  _class("dir")->mkdir_m() 
	function _all_stpls_into_files () {
		$themes = $this->_get_unique_themes_from_db();
		// Process themes
		foreach ((array)$themes as $theme_name) {
			// Show execution progress
			$body .= "<br><b>PROCESSING THEME \"".$theme_name."\":</b><br><br>\r\n";
			// Create theme folder
			_mkdir_m($this->_dir_array[$location]. $theme_name);
			// Get templates from the current theme
			$Q = db()->query("SELECT * FROM ".db('templates')." WHERE theme_name='".$theme_name."' AND active='1'");
			// Process files
			while ($A = db()->fetch_assoc($Q)) {
				$stpl_name	= $A["name"];
				$text		= $A["text"];
				// Create subfolder if needed for template
				$sub_dir_name = substr($stpl_name, 0, -strlen(basename($stpl_name)));
				if (strlen($sub_dir_name)) $this->_mkdir_m_with_indexhtm(TPLS_LIB_PATH. $theme_name. "/". $sub_dir_name);
				// Put template file contents
				file_put_contents(TPLS_LIB_PATH. $theme_name. "/". $stpl_name. ".stpl", $text);
				// Show execution progress
				$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
			}
		}
		return $body;
	}

	/**
	* Get themes full paths and names
	*/
	function _get_themes () {
		$themes = array();
		foreach ((array)$this->_dir_array as $k => $d){
			$dh = opendir($d);
			while (false !== ($f = readdir($dh))) {
				$dirName = $d.$f;
				if (false !== strpos($dirName, ".svn")) continue;
				if (false !== strpos($dirName, ".git")) continue;
				if (is_dir($dirName) && $f != "." && $f != "..") $themes[$k][$d. $f. "/"] = $f;
			}
		}
//		ksort($themes);
		return $themes;
	}

	/**
	* Get themes only names
	*/
	function _get_themes_names () {
		$names = array();
		foreach ((array)$this->_get_themes() as $where => $themes) {
			foreach ((array)$themes as $_path => $_name) {
				$names[$where][$_name] = $_name;
			}
		}
		return $names;
	}

	/**
	* Get unique themes names from database
	*/
	function _get_unique_themes_from_db () {
		$Q = db()->query("SELECT DISTINCT(theme_name, site_id) INTO theme, site_id FROM ".db('templates')." WHERE active='1'");
		while ($A = db()->fetch_assoc($Q)) if (strlen($A["theme"])) $themes[$A["site_id"]][$A["theme"]] = $A["theme"];
		return $themes;
	}

	/**
	*  Get Stpls In Theme
	*/
	function _get_stpls_in_theme($theme_name, $theme_path) {
		$files = _class("dir")->scan_dir($theme_path. $theme_name);
		// Process files in the current theme
		foreach ((array)$files as $file_name) {
			if (false !== strpos($file_name, ".svn")) continue;
			if (false !== strpos($file_name, ".git")) continue;
			// Skip all other files except templates
			if (common()->get_file_ext($file_name) != "stpl") continue;
			else $stpls[$file_name] = $file_name;
		}
		return $stpls;
	}

	/**
	*  Export STPLs from file system into file as PHP-array
	*/
 	function export() {
		return $this->_export_theme_stpls ($_GET["theme"], $_GET["location"]);
	}

	/**
	*  Import STPLs from file to the filesystem
	*/
	function import() {
		if ($_POST){
			// Check if file is selected
			if (!empty($_FILES["import_file"]["name"])) {
				$new_file_name = $_FILES["import_file"]["name"];
				$new_file_path = INCLUDE_PATH.SITE_UPLOADS_DIR.$new_file_name;
				move_uploaded_file($_FILES["import_file"]["tmp_name"], $new_file_path);
				$file_contens = trim(file_get_contents($new_file_path));				
				$result = @eval("?>".$file_contens."<?p"."hp return 1;");
			}
			// Get data from export file needed to create template files
			$location = array_keys($GLOBALS['_exported_stpls']);
			foreach ((array)$location as $k1 => $v1){
				$theme = array_keys($GLOBALS['_exported_stpls'][$v1]);
				foreach ((array)$theme as $theme_name) {
					foreach ((array)$GLOBALS['_exported_stpls'][$v1] as $k2 => $v2){
						foreach ((array)$v2 as $stpl_name => $stpl_content) {
							// Do create template files
							$body .= $this->_import_theme_stpls ($v1, $theme_name, $stpl_name, $stpl_content);
						}
					}
				}
			}
		}
		if (file_exists($new_file_path)) unlink($new_file_path);
		return $body;
	}

	/**
	*  Export STPLs from file system into file as PHP-array
	*/
	function _export_theme_stpls($theme_name, $location) {

		// Export file header
		$ef_header = "<?php\r\n\$GLOBALS['_exported_stpls'] = array(";
		// Export file footer
		$ef_footer = ");\r\n?>";
		// Generate path to the current processing theme
		$this->_cur_theme_path = $this->_dir_array[$location]. $theme_name;
		// Get multidimensional array of files inside theme
		$files_array = _class("dir")->scan_dir($this->_cur_theme_path, true);
		// Begin to create export file
		$export_file_content .= $ef_header."\r\n";			  
		$export_file_content .= "\t\"".$location."\" => \r\n \t\tarray(\r\n\t\t\"".$theme_name."\" => \r\n \t\t\tarray(\r\n";
		foreach ((array)$files_array as $file_name) {
			// Skip all other files except templates
			if (common()->get_file_ext($file_name) != "stpl") {
				continue;
			}
			$stpl_name	= str_replace($this->_dir_array[$location].$theme_name."/", "", substr($file_name, 0, -5));
			$text		= str_replace("'", "\'", trim(file_get_contents($file_name)));
			$export_file_content .= "\"".$stpl_name."\" => \r\n '".$text."'\r\n,\r\n";
		}
		$export_file_content .= "\t\t)\r\n\t)\r\n";
		$export_file_content .= $ef_footer."\r\n";
		//Generating filename
		$export_file_name = "export_".$location."_".$theme_name.".txt";

		// Put file into a stream
		main()->NO_GRAPHICS = true;
		// Throw headers
		header("Content-Type: application/force-download; name=\"".$export_file_name."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($export_file_content));
		header("Content-Disposition: attachment; filename=\"".$export_file_name."\"");
		// Throw content
		echo $export_file_content;
		exit();

		return true;
	}

	/**
	*  Import STPLs from file to the filesystem
	*/
	function _import_theme_stpls($location, $theme_name, $stpl_name, $text) {
		// Show execution progress
		$body .= "<br><b>PROCESSING THEME \"".$theme_name."\" in \"".$location."\":</b><br><br>\r\n";
		if (!file_exists($this->_dir_array[$location]. $theme_name)){
			// Create theme folder
			_mkdir_m($this->_dir_array[$location]. $theme_name);
		}

		// Process files
		// Create subfolder if needed for template
		$sub_dir_name = substr($stpl_name, 0, -strlen(basename($stpl_name)));
		if (strlen($sub_dir_name) && !file_exists($this->_dir_array[$location]. $theme_name. "/".  $sub_dir_name)) {
			_mkdir_m($this->_dir_array[$location]. $theme_name. "/". $sub_dir_name);
			$body .= "<b> /".$sub_dir_name."</b>  <b style='color:blue;'>Folder created</b><br>\r\n";
		}
		// Check control sums of existing file and template from export file
		if (!file_exists($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl")){
			if(file_put_contents($this->_dir_array[$location]. $theme_name. "/". $stpl_name. ".stpl", $text)) {
				// Show execution progress
				$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:green;'>OK</b><br>\r\n";
			} else {
				// Show execution progress
				$body .= "<b>".$stpl_name."</b> (".strlen($text)." bytes) - <b style='color:red;'>FAILED</b><br>\r\n";
			}
		} else {
			$file_contens = trim(file_get_contents($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl"));
			$checksum_exist = crc32($file_contens);
			$checksum_from_db = crc32($text);
			if ($checksum_exist != $checksum_from_db){
				$encoded_path = base64_encode($this->_dir_array[$location]. $theme_name. "/".  $stpl_name. ".stpl");
				$replace2 = array(
					"location"		=> $location,
					"theme" 		=> $theme_name,
					"name"			=> $stpl_name,
					"db_src_link"	=> "./?object=".$_GET["object"]."&action=show_db_src&id=".$A["id"],
					"file_src_link"	=> "./?object=".$_GET["object"]."&action=show_file_src&path=".$encoded_path,
					"db_src_size"	=> strlen($text),
					"file_src_size" => strlen($file_contens),
					"id"			=> $A["id"],
					"path_hidden"	=> $encoded_path,
				);
				$items .= tpl()->parse($_GET["object"]."/user_dialog_item", $replace2);
				$body .= "<b>".$stpl_name."</b>  <b style='color:#9400D3;'>Admin confirm needed</b><br>\r\n";
			} else {
				$body .= "<b>".$stpl_name."</b>  <b style='color:grey;'>Not changed</b><br>\r\n";
			}
		}
		if ($items) {
			$replace = array(
				"items"			=> $items,
				"form_action"	=> "./?object=".$_GET["object"]."&action=replace_stpls",
			);					
			$body .= tpl()->parse($_GET["object"]."/user_dialog_main", $replace);				
		}
		return $body;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Template editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "Themes list",
			"add_theme_form"		=> "",
			"show_stpls_in_theme" 	=> "",
			"edit_stpl"				=> "",
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
