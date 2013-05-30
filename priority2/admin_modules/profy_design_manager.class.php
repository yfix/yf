<?php

/**
* Design manager
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_design_manager {

	/** @var string @conf_skip User themes dir */
	var $USER_THEMES_DIR	= "user_themes/";
	/** @var string @conf_skip Color schemes dir */
	var $COLOR_SCHEMES_DIR	= "color_schemes/";
	/** @var string @conf_skip Graphic schemes dir */
	var $GRAPH_SCHEMES_DIR	= "graphic_schemes/";
	/** @var string @conf_skip Main template name */
	var $MAIN_TEMPLATE_NAME	= "main.stpl";
	/** @var string @conf_skip Main css filename */
	var $MAIN_CSS_NAME		= "style.css";
	/** @var string @conf_skip Designs store folder */
	var $DESIGNS_DIR		= "designs";
	/** @var int Maximum number of tags */
	var $TAGS_LIMIT			= 5;
	/** @var int Large image limits (width and height in pixels)*/
	var $LARGE_IMG_LIMITS	= 500;
	/** @var string @conf_skip
	*	Allow here only these below \x7F == 127 (ASCII) :
	*		\x0A == 13 (CR), 
	*		\x20 == 32 (Space), 
	*		\x30-\x39 (0-9), 
	*		\x41-\x5A (A-Z),
	*		\x61-\x7A (a-z)
	*/
	var $REGEXP_ALLOWED		= '/[\x00-\x09\x0B-\x1F\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F]/ims';
	/** @var int */
	var $MIN_KEYWORD_LENGTH	= 3;
	/** @var int */
	var $MAX_KEYWORD_LENGTH	= 30;
	/** @var bool */
	var $UTF8_MODE			= 0;
	/** @var bool */
	var $STORE_TO_DB		= 1;
	/** @var string Web path to servise which returns preview image of remote page */
	var $SERVICE_WEB_PATH 	= "http://www.test.com/test/"; //user_login + user_domain for user part
	/** @var bool Make thumbs and preview image automatically or not*/
	var $AUTO_GENERATE_PREVIEW	= 1;
	/** @var bool If true browser do not cache images */
	var $BREAK_BROWSER_CACHE = 1;

	/**
	* Constructor
	*/
	function _init () {
		define("DESIGN_MGR_CLASS_NAME", "design_manager");

		$this->USER_THEMES_WEB_DIR 		= WEB_PATH.$this->USER_THEMES_DIR;
		$this->USER_THEMES_DIR 			= realpath(INCLUDE_PATH)."/".$this->USER_THEMES_DIR;

		$this->COLOR_SCHEMES_WEB_DIR 	= WEB_PATH.SITE_UPLOADS_DIR.$this->COLOR_SCHEMES_DIR;
		$this->COLOR_SCHEMES_DIR 		= realpath(INCLUDE_PATH)."/".SITE_UPLOADS_DIR.$this->COLOR_SCHEMES_DIR;

		$this->GRAPH_SCHEMES_WEB_DIR 	= WEB_PATH.SITE_UPLOADS_DIR.$this->GRAPH_SCHEMES_DIR;
		$this->GRAPH_SCHEMES_DIR 		= realpath(INCLUDE_PATH)."/".SITE_UPLOADS_DIR.$this->GRAPH_SCHEMES_DIR;

		// Init dir and test classes
		$this->DIR_OBJ 	= main()->init_class("dir", "classes/");
		$this->TEST_OBJ = main()->init_class("test", "modules/");
		// Array of theme names
		$Q = db()->query("SELECT `id`,`name` FROM `".db('user_themes')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_existed_themes[$A["id"]] = $A["name"];
		}
		if (!file_exists($this->USER_THEMES_DIR)) {	
			_mkdir_m($this->USER_THEMES_DIR, 0777, 1);
		}
		if (!file_exists($this->COLOR_SCHEMES_DIR)) {	
			_mkdir_m($this->COLOR_SCHEMES_DIR, 0777, 1);
		}
		if (!file_exists($this->GRAPH_SCHEMES_DIR)) {	
			_mkdir_m($this->GRAPH_SCHEMES_DIR, 0777, 1);
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Get number of blocks rules
		$Q = db()->query("SELECT `themes` FROM `".db('block_rules')."` WHERE `themes` != ''");
		while ($A = db()->fetch_assoc($Q)) {
			foreach (explode(",",trim($A["themes"],",")) as $v) {
				$blocks_rules[$v]++;
			}
		}
		// Get number of designs by themes
		$Q = db()->query("SELECT `theme_id`, COUNT(*) AS `num` FROM `".db('designs')."` WHERE 1 GROUP BY `theme_id`");
		while ($A = db()->fetch_assoc($Q)) {
			$num_designs[$A["theme_id"]] = $A["num"];
		}
		// Get 	user themes from db
		$sql = "SELECT * FROM `".db('user_themes')."` ORDER BY `name`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		foreach ((array)db()->query_fetch_all($sql.$add_sql) as $v) {
			$img_path = $this->USER_THEMES_DIR. $v["name"]."/".$v["name"]."_preview.jpg";
			$large_path = $this->USER_THEMES_DIR. $v["name"]."/".$v["name"]."_large.jpg";

			$img_path = $this->_check_image($img_path);
			$large_path = $this->_check_image($large_path);
			//Prepare tags for show	
			if ($v["tags"]) {
				$_tags = substr($v["tags"], 1, strlen($v["tags"])-2);
				$_tags = explode(";", $_tags);
				// Prepare for edit
				$tags_to_show = implode(", ", (array)$_tags);
			} else {
				$tags_to_show = "";
			}
			$replace2 = array(
				"id"			=> $v["id"],
				"theme_name"	=> _prepare_html($v["name"]),
				"descr"			=> _prepare_html($v["descr"]),
				"active"		=> intval($v["active"]),
				"active_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=activate_theme&id=".$v["id"],
				"clone_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=clone_theme&id=".$v["id"],
				"delete_url"	=> (main()->DEFAULT_THEME_ID != $v["id"]) ? "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_theme&id=".urlencode($v["name"]) : "",
				"edit_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_theme&id=".intval($v["id"]),
				"designs_url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=show_designs_in_theme&id=".intval($v["id"]),
				"img_path"		=> $img_path,
				"photo_m_src"	=> $large_path,
				"tags"			=> $tags_to_show,
				"blocks_rules"	=> intval($blocks_rules[$v["id"]]),
				"num_designs"	=> intval($num_designs[$v["id"]]),
			);
			$items .= tpl()->parse(DESIGN_MGR_CLASS_NAME."/item", $replace2);
		}
		$replace = array(
			"items"			=> $items,
			"total"			=> $total,
			"pages"			=> $pages,
			"add_link" 		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=add_theme_form",
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/main", $replace);
	}

	/**
	* Shows designs in theme
	*/
	function show_designs_in_theme () {
		$theme_info = db()->query_fetch("SELECT `id`, `name`, `default_design` FROM `".db('user_themes')."` WHERE `id`=".intval($_GET["id"]));
		// Get 	designs for the theme from db
		$data = db()->query_fetch_all("SELECT * FROM `".db('designs')."` WHERE `theme_id`=".intval($_GET["id"]));
		// Get owners ids
		$owners_ids = array();
		foreach ((array)$data as $A) {
			if ($A["owner_id"]) {
				$owners_ids[$A["owner_id"]] = $A["owner_id"];
			}
		}
		// Get owners details
		if (!empty($owners_ids)) {
			$users_infos = user($owners_ids);
		}
		// Process designs
		foreach ((array)$data as $A) {
			$img_path = "";
			$large_path = "";
			$img_path = $this->_check_image($this->USER_THEMES_DIR. $theme_info["name"]."/designs/".$A["id"]."_preview.jpg");
			$large_path = $this->_check_image($this->USER_THEMES_DIR. $theme_info["name"]."/designs/".$A["id"]."_large.jpg");

			$num_images = count($this->_get_images_from_dir($this->USER_THEMES_DIR. $theme_info["name"]."/designs/".$A["id"]."/"));
			//Prepare tags for show	
			if ($A["tags"]) {
				$_tags = substr($A["tags"], 1, strlen($A["tags"])-2);
				$_tags = explode(";", $_tags);
				// Prepare for edit
				$tags_to_show = implode(", ", (array)$_tags);
			} else {
				$tags_to_show = "";
			}
			$replace2 = array(
				"id"			=> $A["id"],
				"name"			=> $A["name"],
				"active"		=> intval($A["active"]),
				"active_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=activate_design&id=".$A["id"],
				"clone_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=clone_design&id=".$A["id"],
				"delete_url"	=> $theme_info["default_design"] != $A["id"] ? "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_design&id=".intval($A["id"]) : "",
				"edit_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_design&id=".intval($A["id"]),
				"img_path"		=> $img_path,
				"photo_m_src"	=> $large_path,
				"tags"			=> $tags_to_show,
				"owner_id"		=> intval($A["owner_id"]),
				"owner_nick"	=> $A["owner_id"] ? _prepare_html(_display_name($users_infos[$A["owner_id"]])) : "",
				"owner_profile_link"=> $A["owner_id"] ? _profile_link($A["owner_id"]) : "",
				"num_images"	=> intval($num_images),
			);
			$items .= tpl()->parse(DESIGN_MGR_CLASS_NAME."/designs_item", $replace2);
		}
		$replace = array(
			"theme_name"		=> _prepare_html($theme_info["name"]),			
			"items"				=> $items,
			"add_design_link" 	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=add_design_form&id=".urlencode($theme_info["name"]),
			"themes_list_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME,
			"edit_theme_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_theme&id=".$theme_info["id"],
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/designs_main", $replace);
	}

	/**
	* Shows add theme form
	*/
	function add_theme_form () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_add_theme_form() : "";
	}

	/**
	* Add user theme
	*/
	function add_theme () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_add_theme() : "";
	}

	/**
	* Edit user theme
	*/
	function edit_theme () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_edit_theme() : "";
	}

	/**
	* CLone user theme
	*/
	function clone_theme () {
		$OBJ = $this->_load_sub_module("design_manager_clone");
		return is_object($OBJ) ? $OBJ->_clone_theme() : "";
	}

	/**
	* Save theme content
	*/
	function save_theme () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_save_theme() : "";
	}

	/**
	* Delete theme
	*/
	function delete_theme () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_delete_theme() : "";
	}

	/**
	* Activate\deactivate themes
	*/
	function activate_theme () {
		$OBJ = $this->_load_sub_module("design_manager_themes");
		return is_object($OBJ) ? $OBJ->_activate_theme() : "";
	}

	/**
	* Delete preview image for theme
	*/
	function theme_delete_preview_img () {
		$theme_name = urldecode($_GET["id"]);
		$_path = $this->USER_THEMES_DIR. $theme_name."/".$theme_name;

		$this->_delete_preview_imgs($_path);

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Shows add design form
	*/
	function add_design_form () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_add_design_form() : "";
	}

	/**
	* Add design method
	*/
	function add_design () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_add_design() : "";
	}

	/**
	* Delete design
	*/
	function delete_design () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_delete_design() : "";
	}

	/**
	* Edit design method
	*/
	function edit_design () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_edit_design() : "";
	}

	/**
	* CLone design
	*/
	function clone_design () {
		$OBJ = $this->_load_sub_module("design_manager_clone");
		return is_object($OBJ) ? $OBJ->_clone_design() : "";
	}

	/**
	* Save design method
	*/
	function save_design () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_save_design() : "";
	}

	/**
	* Activate\deactivate designs in themes
	*/
	function activate_design () {
		$OBJ = $this->_load_sub_module("design_manager_designs");
		return is_object($OBJ) ? $OBJ->_activate_design() : "";
	}

	/**
	* Delete preview image for design
	*/
	function design_delete_preview_img () {
		$design_id = intval($_GET["id"]);
		$theme_name = urldecode($_GET["page"]);

		$_path = $this->USER_THEMES_DIR. $theme_name."/designs/".$design_id;

		$this->_delete_preview_imgs($_path);

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Manage color schemes
	*/
	function color_schemes () {
		// Get color schemes from db
		$sql = "SELECT * FROM `".db('color_schemes')."` ORDER BY `name` ASC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$schemes_info = db()->query_fetch_all($sql);
		foreach ((array)$schemes_info as $scheme) {
			$img_path = "";
			$large_path = "";
			$img_path 	= $this->_check_image($this->COLOR_SCHEMES_DIR. $scheme["id"]. "_preview.jpg");
			$large_path = $this->_check_image($this->COLOR_SCHEMES_DIR. $scheme["id"]. "_large.jpg");
			$num_images = count($this->_get_images_from_dir($this->COLOR_SCHEMES_DIR. $scheme["id"]."/"));
			$replace2 = array(
				"img_path"		=> $img_path,
				"photo_m_src"	=> $large_path,
				"scheme_name" 	=> $scheme["name"],
				"descr"			=> $scheme["description"],
				"active"		=> $scheme["active"],
				"edit_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_scheme&id=".$scheme["id"],
				"clone_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=clone_color_scheme&id=".$scheme["id"],
				"delete_url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_color_scheme&id=".$scheme["id"],
				"num_images"	=> intval($num_images),
			);
			$items .= tpl()->parse(DESIGN_MGR_CLASS_NAME."/col_schemes_item", $replace2);
		}
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"add_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_scheme"
		);		
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/col_schemes_main", $replace);
	} 

	/**
	* Edit/Add color schemes
	*/
	function edit_scheme () {

		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_POST) && empty($_POST["css_content"])) {
			return redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=color_schemes", 0, "CSS is needed!");
		}

		if (!empty($_POST["css_content"])) {
			$designs = ";".implode(";", (array)$_POST["designs"]).";";
			if (empty($_POST["designs"]) || in_array("", (array)$_POST["designs"])) {
				$designs = "";
			}

			$sql_array = array(
				"name"			=> _es($_POST["col_scheme_name"]),
				"description"	=> _es($_POST["descr"]),
				"css"			=> _es($_POST["css_content"]),
				"designs"		=> $designs,
			);			

			if ($_POST["record_id"]) {
				// Update record
				db()->UPDATE("color_schemes", $sql_array, "`id`=".intval($_POST["record_id"]));
			} else {
				// Insert record
				db()->INSERT("color_schemes", $sql_array);
				$insert_id = db()->INSERT_ID();
			}

			$scheme_id = $_POST["record_id"] ? $_POST["record_id"] : $insert_id;
			// Upload preview image
			$name_in_form = "preview_img";	
			if (!empty($_FILES[$name_in_form]["tmp_name"])) {
				$this->_resize_preview_img ($name_in_form, $scheme_id, $this->COLOR_SCHEMES_DIR);
			} 
			// Or generate it automatically
			if (empty($_FILES[$name_in_form]["tmp_name"]) && $this->AUTO_GENERATE_PREVIEW && !file_exists($this->COLOR_SCHEMES_DIR. $scheme_id. "_preview.jpg")) {
				$COL_SCHEME_ID = $_POST["record_id"] ? $_POST["record_id"] : $insert_id;
				$url = $this->SERVICE_WEB_PATH. "color_scheme/".$COL_SCHEME_ID;
				$tmp_image_path = $this->TEST_OBJ->_remote_thumb_client($url);
				$this->_resize_preview_img ("", $COL_SCHEME_ID, $this->COLOR_SCHEMES_DIR, $tmp_image_path);
			}

			$this->_refresh_cache();
			return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=color_schemes");
		}

		$col_scheme_info = db()->query_fetch("SELECT * FROM `".db('color_schemes')."` WHERE `id`=".$_GET["id"]);
		foreach (explode(";", $col_scheme_info["designs"]) as $v) {
			if (!empty($v)) {
				$selected[$v] = $v;
			}
		}
		$all_designs = my_array_merge(array("" => "All"), (array)$this->_get_designs());
		$designs_box = common()->multi_select("designs", $all_designs, $selected);

		$img_path 	= $this->_check_image($this->COLOR_SCHEMES_DIR. $col_scheme_info["id"]. "_preview.jpg");
		if ($img_path) {
			$del_image_link = "./?object=".DESIGN_MGR_CLASS_NAME."&action=col_scheme_delete_preview_img&id=".$col_scheme_info["id"];
		}
		$large_path = $this->_check_image($this->COLOR_SCHEMES_DIR. $col_scheme_info["id"]. "_large.jpg");
		if ($this->AUTO_GENERATE_PREVIEW) {
			$generate_url = "./?object=".DESIGN_MGR_CLASS_NAME."&action=generate_preview_img&id=color-".$col_scheme_info["id"];
		}

		$replace = array(
			"form_action"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=".$_GET["action"],
			"designs_box"		=> $designs_box,
			"col_scheme_name" 	=> $col_scheme_info["name"],
			"descr"				=> $col_scheme_info["description"],
			"css_content"		=> $col_scheme_info["css"],
			"record_id"			=> $col_scheme_info["id"],
			"back_url"			=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=color_schemes",
			"del_image_link"	=> $del_image_link,
			"img_path"			=> $img_path,
			"generate_url"		=> $generate_url ? $generate_url : "",
			"photo_m_src"		=> $large_path,
			"images_block"		=> $this->_show_scheme_images_block("color", $col_scheme_info),
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_col_scheme_form", $replace);
	}

	/**
	* CLone color scheme
	*/
	function clone_color_scheme () {
		$OBJ = $this->_load_sub_module("design_manager_clone");
		return is_object($OBJ) ? $OBJ->_clone_color_scheme() : "";
	}

	/**
	* Delete color schemes
	*/
	function delete_color_scheme () {
		$_GET["id"] = intval($_GET["id"]);		
		
		db()->query("DELETE FROM `".db('color_schemes')."` WHERE `id`=".$_GET["id"]);

		$_path = $this->COLOR_SCHEMES_DIR. $_GET["id"];

		$this->_delete_preview_imgs($_path);

		$this->DIR_OBJ->delete_dir($_path, 1);

		$this->_refresh_cache();

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Manage graphic schemes
	*/
	function graphic_schemes () {
		// Get color schemes from db
		$sql = "SELECT * FROM `".db('graphic_schemes')."` ORDER BY `name` ASC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$schemes_info = db()->query_fetch_all($sql);
		foreach ((array)$schemes_info as $scheme) {
			$img_path = "";
			$large_path = "";
			$img_path = $this->_check_image($this->GRAPH_SCHEMES_DIR. $scheme["id"]. "_preview.jpg");
			$large_path = $this->_check_image($this->GRAPH_SCHEMES_DIR. $scheme["id"]. "_large.jpg");
			$num_images = count($this->_get_images_from_dir($this->GRAPH_SCHEMES_DIR. $scheme["name"]."/"));
			$replace2 = array(
				"img_path"		=> $img_path,
				"photo_m_src"	=> $large_path,
				"scheme_name" 	=> $scheme["name"],
				"descr"			=> $scheme["description"],
				"active"		=> $scheme["active"],
				"edit_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_graph_scheme&id=".$scheme["id"],
				"clone_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=clone_graph_scheme&id=".$scheme["id"],
				"delete_url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_graph_scheme&id=".$scheme["id"],
				"num_images"	=> intval($num_images),
			);
			$items .= tpl()->parse(DESIGN_MGR_CLASS_NAME."/graph_schemes_item", $replace2);
		}
		$replace = array(
			"items"		=> $items,
			"total"		=> $total,
			"add_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=edit_graph_scheme"
		);		
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/graph_schemes_main", $replace);
	} 


	/**
	* Edit/Add graphic schemes
	*/
	function edit_graph_scheme () {

		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_POST) && empty($_POST["css_content"])) {
			return redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=graphic_schemes", 0, "CSS is needed!");
		}

		if (!empty($_POST["css_content"])) {

			$designs = ";".implode(";", (array)$_POST["designs"]).";";
			if (empty($_POST["designs"]) || in_array("", (array)$_POST["designs"])) {
				$designs = "";
			}
			$col_schemes = ";".implode(";", (array)$_POST["col_schemes"]).";";
			if (empty($_POST["col_schemes"]) || in_array("", (array)$_POST["col_schemes"])) {
				$col_schemes = "";
			}

			$sql_array = array(
				"name"			=> _es($_POST["graph_scheme_name"]),
				"description"	=> _es($_POST["descr"]),
				"css"			=> _es($_POST["css_content"]),
				"designs"		=> $designs,
				"col_schemes"	=> $col_schemes,
			);			

			if ($_POST["record_id"]) {
				// Update record
				db()->UPDATE("graphic_schemes", $sql_array, "`id`=".intval($_POST["record_id"]));
			} else {
				// Insert record
				db()->INSERT("graphic_schemes", $sql_array);
				$insert_id = db()->INSERT_ID();
			}

			$scheme_id = $_POST["record_id"] ? $_POST["record_id"] : $insert_id;
			// Upload preview image
			$name_in_form = "preview_img";	
			if (!empty($_FILES[$name_in_form]["tmp_name"])) {
				$this->_resize_preview_img ($name_in_form, $scheme_id, $this->GRAPH_SCHEMES_DIR);
			} 
			if (empty($_FILES[$name_in_form]["tmp_name"]) && $this->AUTO_GENERATE_PREVIEW && !file_exists($this->GRAPH_SCHEMES_DIR. $scheme_id. "_preview.jpg")) {
				$GRAPH_SCHEME_ID = $_POST["record_id"] ? $_POST["record_id"] : $insert_id;
				// Or generate it automatically
				$url = $this->SERVICE_WEB_PATH. "graphic_scheme/".$GRAPH_SCHEME_ID;
				$tmp_image_path = $this->TEST_OBJ->_remote_thumb_client($url);
				$this->_resize_preview_img ("", $GRAPH_SCHEME_ID, $this->GRAPH_SCHEMES_DIR, $tmp_image_path);
			}

			$this->_refresh_cache();
			return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=graphic_schemes");
		}

		$graph_scheme_info = db()->query_fetch("SELECT * FROM `".db('graphic_schemes')."` WHERE `id`=".$_GET["id"]);
		foreach (explode(";", $graph_scheme_info["designs"]) as $v) {
			if (!empty($v)) {
				$selected_designs[$v] = $v;
			}
		}
		foreach (explode(";", $graph_scheme_info["col_schemes"]) as $v) {
			if (!empty($v)) {
				$selected_col_schemes[$v] = $v;
			}
		}
		$all_designs = my_array_merge(array("" => "All"), (array)$this->_get_designs());

   		$A = db()->query_fetch_all("SELECT * FROM `".db('color_schemes')."` ORDER BY `name`");
		foreach ((array)$A as $v) {
			$all_col_schemes[$v["id"]] = $v["name"];
		}

		$all_col_schemes = my_array_merge(array("" => "All"), (array)$all_col_schemes);

		$designs_box 		= common()->multi_select("designs", $all_designs, $selected_designs);
		$col_schemes_box 	= common()->multi_select("col_schemes", $all_col_schemes, $selected_col_schemes);

		$img_path 	= $this->_check_image($this->GRAPH_SCHEMES_DIR. $graph_scheme_info["id"]. "_preview.jpg");
		if ($img_path) {
			$del_image_link = "./?object=".DESIGN_MGR_CLASS_NAME."&action=graph_scheme_delete_preview_img&id=".$graph_scheme_info["id"];
		}
		$large_path = $this->_check_image($this->GRAPH_SCHEMES_DIR. $graph_scheme_info["id"]. "_large.jpg");

		if ($this->AUTO_GENERATE_PREVIEW) {
			$generate_url = "./?object=".DESIGN_MGR_CLASS_NAME."&action=generate_preview_img&id=graph-".$graph_scheme_info["id"];
		}

		$replace = array(
			"form_action"		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=".$_GET["action"],
			"graph_scheme_name"	=> $graph_scheme_info["name"],
			"descr"				=> $graph_scheme_info["description"],
			"designs_box"		=> $designs_box,
			"col_schemes_box"	=> $col_schemes_box,
			"css_content"		=> $graph_scheme_info["css"],
			"record_id"			=> $graph_scheme_info["id"],
			"back_url"			=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=color_schemes",
			"del_image_link"	=> $del_image_link,
			"img_path"			=> $img_path,
			"generate_url"		=> $generate_url ? $generate_url : "",
			"photo_m_src"		=> $large_path,
			"images_block"		=> $this->_show_scheme_images_block("graph", $graph_scheme_info),
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_graph_scheme_form", $replace);
	}

	/**
	* CLone color scheme
	*/
	function clone_graph_scheme () {
		$OBJ = $this->_load_sub_module("design_manager_clone");
		return is_object($OBJ) ? $OBJ->_clone_graph_scheme() : "";
	}

	/**
	* Delete color schemes
	*/
	function delete_graph_scheme () {
		$_GET["id"] = intval($_GET["id"]);		
		
		db()->query("DELETE FROM `".db('graphic_schemes')."` WHERE `id`=".$_GET["id"]);
		$_path = $this->GRAPH_SCHEMES_DIR. $_GET["id"];

		$this->_delete_preview_imgs($_path);

		$this->DIR_OBJ->delete_dir($_path, 1);

		$this->_refresh_cache();

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Delete preview image for color scheme
	*/
	function col_scheme_delete_preview_img () {
		$col_scheme_id = intval($_GET["id"]);

		$_path = $this->COLOR_SCHEMES_DIR. $col_scheme_id;

		$this->_delete_preview_imgs($_path);

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Delete preview image for graphic scheme
	*/
	function graph_scheme_delete_preview_img () {
		$graph_scheme_id = intval($_GET["id"]);

		$_path = $this->GRAPH_SCHEMES_DIR. $graph_scheme_id;

		$this->_delete_preview_imgs($_path);

		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Prepare tags from form divided by '\n' and put them into array
	*/
	function _prepare_tags ($source = "") {
		if (!$source) {
			return false;
		}
		$keywords_array = array();
		// Process submitted tags
		$source = str_replace(array("\r", "\t"), array("", " "), $source);
		$source = trim(preg_replace("/[ ]{2,}/ims", " ", $source));
		$source = trim(preg_replace("/[\n]{2,}/ims", "\n", $source));
		$source = trim(preg_replace($this->REGEXP_ALLOWED, "", $source));
		// Split by lines
		$lines	= explode("\n", $source);
		// Last cleanup
		foreach ((array)$lines as $cur_word) {
			if (empty($cur_word) || (strlen($cur_word) * ($this->UTF8_MODE ? 2 : 1)) < $this->MIN_KEYWORD_LENGTH) {
				continue;
			}
			// Check max number of keywords
			if (++$i > $this->TAGS_LIMIT) {
				break;
			}
			// Cut long keywords
			if ($this->MAX_KEYWORD_LENGTH && strlen($cur_word) > $this->MAX_KEYWORD_LENGTH * ($this->UTF8_MODE ? 2 : 1)) {
				$cur_word = substr($cur_word, 0, $this->MAX_KEYWORD_LENGTH);
			}
			if (!isset($keywords_array[$cur_word])) {
				$keywords_array[$cur_word] = $cur_word;
			}
		}
		return $keywords_array;
	}

	/**
	* Get all themes as array [theme_id] => [theme_name]
	*/
	function _get_themes () {
		// Get 	user themes from db
		$Q = db()->query("SELECT * FROM `".db('user_themes')."` ORDER BY `name`");	
		while ($A = db()->fetch_assoc($Q)) {
			$_themes_array[$A["id"]] = $A["name"];
		}
		return $_themes_array;
	}	

	/**
	* Get all designs in theme as array [design_id] => [design_name]
	*/
	function _get_designs ($theme_id = 0) {
		if (!$theme_id) {
			$data = db()->query_fetch_all("SELECT * FROM `".db('designs')."` ORDER BY `name`");	
		} else {
			$data = db()->query_fetch_all("SELECT * FROM `".db('designs')."` WHERE `theme_id`=".intval($theme_id));	
		}
		if (empty($data)) {
			return false;
		}
		foreach ((array)$data as $design_info) {
			$this->_designs_in_theme[$design_info["id"]] = $design_info["name"];
		}
		return $this->_designs_in_theme;
	}

	/**
	* Resize image - make thumb and large detailed image
	*/
	function _resize_preview_img ($name_in_form = "", $prefix = "", $path="", $tmp_path = "") {

		$new_file_name = $prefix. "_preview.jpg";
		$large_file_name = $prefix. "_large.jpg";
		$img_path = $path.$new_file_name;
		$large_img_path = $path.$large_file_name;

		if (!$tmp_path) {
			// Do upload image
			$upload_result = common()->upload_image($img_path, $name_in_form);
			// Resize image
			common()->make_thumb($img_path, $large_img_path, $this->LARGE_IMG_LIMITS, $this->LARGE_IMG_LIMITS);
			// Make thumb
			common()->make_thumb($img_path, $img_path, 100, 100);
		} else {
			// Resize image
			common()->make_thumb($tmp_path, $large_img_path, $this->LARGE_IMG_LIMITS, $this->LARGE_IMG_LIMITS);
			// Make thumb
			common()->make_thumb($tmp_path, $img_path, 100, 100);
			unlink($tmp_path);
		}
	}

	/**
	* Try to load forum sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ =& main()->init_class($module_name, ADMIN_MODULES_DIR. DESIGN_MGR_CLASS_NAME."/");
		if (!is_object($OBJ)) {
			trigger_error(DESIGN_MGR_CLASS_NAME. ": Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}

	/**
	* Prepare content for edit
	*/
	function _prepare_for_edit ($body = "") {
		// DO NOT REMOVE!!! Needed to correct display template tags
		$body = str_replace(array("&", "{", "}"), array("&amp;", "&#123;", "&#125;"), $body);
		return _prepare_html($body);
	}

	/**
	* Make deleting preview images (large and small)
	*/
	function _delete_preview_imgs ($path_w_name = "") {
		if (!$path_w_name) {
			return false;
		}
		if (file_exists($path_w_name."_preview.jpg")){
			unlink($path_w_name."_preview.jpg");
		}
		if (file_exists($path_w_name."_large.jpg")){
			unlink($path_w_name."_large.jpg");
		}
		return true;
	}

	/**
	* Get number of images for the given folder
	*/
	function _get_images_from_dir ($start_dir = "") {
		$include_pattern = "/\.jpg\$/i";
		$exclude_pattern = "/(_preview|_large)\.jpg\$/i";

		if (!file_exists($start_dir)) {
			return array();
		}
		$dh		= opendir($start_dir);
		while (false !== ($f = readdir($dh))) {
			if ($f == "." || $f == "..") {
				continue;
			}
			$item_name = $start_dir."/".$f;
			// "Flat" mode (all filenames are stored as 1-dimension array, else - multi-dimension array)
			if (is_dir($item_name)) {
				continue;
			}
			$tmp_file = $f;
			// Include files only if they match the mask
			if (!empty($include_pattern)) {
				if (!preg_match($include_pattern."ims", $tmp_file)) {
					continue;
				}
			}
			// Exclude files from list by mask
			if (!empty($exclude_pattern)) {
				if (preg_match($exclude_pattern."ims", $tmp_file)) {
					continue;
				}
			}
			// Add item to the result array
			$files[filemtime($item_name)] = str_replace("//", "/", $item_name);
		}
		closedir($dh);
		if ($files) {
			ksort($files);
		}
		return $files;
	}

	/**
	* Display images editable block for form
	*/
	function _show_scheme_images_block ($type = "design", $info = array()) {
		if (!$info) {
			return false;
		}
		$images_dir = "";
		// Get images dir
		if ($type == "design") {
			$theme_name = $this->_existed_themes[$info["theme_id"]];
			$images_dir = $this->USER_THEMES_DIR. $theme_name. "/designs/". $info["id"]. "/";
		} elseif ($type == "color") {
			$images_dir = $this->COLOR_SCHEMES_DIR. $info["id"]. "/";
		} elseif ($type == "graph") {
			$images_dir = $this->GRAPH_SCHEMES_DIR. $info["id"]. "/";
		}
		if (!$images_dir) {
			return false;
		}
		if (!file_exists($images_dir)) {
			_mkdir_m($images_dir);
		}
		foreach ((array)$this->_get_images_from_dir($images_dir) as $_img_src) {
			$_tmp = substr($_img_src, strlen(INCLUDE_PATH));
			list($w, $h) = getimagesize($_img_src);
			$images[$_img_src] = array(
				"src"			=> WEB_PATH. $_tmp,
				"src_small"		=> "/".$_tmp,
				"filesize"		=> filesize($_img_src) / 1000,
				"filemtime"		=> _format_date(filemtime($_img_src), "long"),
				"image_dims"	=> $w."x".$h." px",
				"delete_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_scheme_image&id=".$type."-".$info["id"]."&page=".urlencode(basename(strtolower($_tmp))),
			);
		}
		$replace = array(
			"form_action"	=> process_url("./?object=".DESIGN_MGR_CLASS_NAME."&action=load_scheme_image&id=".$type."-".$info["id"]),
			"images"		=> $images,
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/images_block", $replace);
	}

	/**
	* Do load custom image into scheme
	*/
	function load_scheme_image ($type = "", $id = 0) {
		if (!$type) {
			main()->NO_GRAPHICS = true;
			// Parse where to laod image
			list($type, $id) = explode("-", $_GET["id"]);
		} else {
			$silent_mode = true;
		}
		// Default response
		$response = array(
			"error"		=> "",
			"msg"		=> "",
			"img_params"=> "",
		);
		// These params are required
		if ($type && $id) {
			$new_name = preg_replace("/[^a-z0-9\_\-\.]/i", "", strtolower(trim($_FILES["scheme_image"]["name"])));
			$new_file_path = "";
			// Get details by known type
			if ($type == "design") {
				$info = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($id));
				if ($info) {
					$theme_name = $this->_existed_themes[$info["theme_id"]];
					$new_file_path = $this->USER_THEMES_DIR. $theme_name. "/designs/". $info["id"]. "/";
				}
			} elseif ($type == "color") {
				$info = db()->query_fetch("SELECT * FROM `".db('color_schemes')."` WHERE `id`=".intval($id));
				if ($info) {
					$new_file_path = $this->COLOR_SCHEMES_DIR. $info["id"]. "/";
				}
			} elseif ($type == "graph") {
				$info = db()->query_fetch("SELECT * FROM `".db('graphic_schemes')."` WHERE `id`=".intval($id));
				if ($info) {
					$new_file_path = $this->GRAPH_SCHEMES_DIR. $info["id"]. "/";
				}
			}
		} else {
			$error = "Missing required params";
		}
		// Go with upload
		if ($new_file_path) {
			// Check empty name
			if (!$new_name || in_array($new_name, array(".jpg", ".jpeg", ".gif", ".png"))) {
				$new_name = substr(md5(microtime(true).$_SERVER["HTTP_HOST"]."_salt_here"), 0, 8).".jpg";
			}
			$new_file_path = $new_file_path. $new_name;
			common()->upload_image($new_file_path, "scheme_image");
			if (file_exists($new_file_path)) {
				list($w, $h) = getimagesize($new_file_path);
				$_tmp = substr($new_file_path, strlen(INCLUDE_PATH));
				$img_params = array(
					"src"			=> WEB_PATH. $_tmp,
					"src_small"		=> "/".$_tmp,
					"filesize"		=> filesize($new_file_path) / 1000,
					"filemtime"		=> _format_date(filemtime($new_file_path), "long"),
					"w"				=> $w,
					"h"				=> $h,
					"delete_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=delete_scheme_image&id=".$type."-".$id."&page=".urlencode(basename(strtolower($new_file_path))),
				);
			}
		} else {
			$error = "Scheme not found";
		}
		if ($error) {
			$response["error"] = $error;
		}
		if ($img_params) {
			$response["img_params"] = $img_params;
		}
		// We nneed to response in JSON format
		if (!$silent_mode) {
			echo common()->json_encode($response);
		}
	}

	/**
	* Do delete custom image from scheme
	*/
	function delete_scheme_image ($type = "", $id = 0, $_name_to_delete = "") {
		if (!$type) {
			main()->NO_GRAPHICS = true;
			// Parse where to laod image
			list($type, $id) = explode("-", $_GET["id"]);
			$_name_to_delete = urldecode($_GET["page"]);
		} else {
			$silent_mode = true;
		}
		// These params are required
		if ($type && $id && $_name_to_delete) {
			$new_file_path = "";
			// Get details by known type
			if ($type == "design") {
				$design_info = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($id));
				if ($design_info) {
					$theme_name = $this->_existed_themes[$design_info["theme_id"]];
					$new_file_path = $this->USER_THEMES_DIR. $theme_name. "/designs/". $design_info["id"]. "/";
				}
			} elseif ($type == "color") {
				$colot_info = db()->query_fetch("SELECT * FROM `".db('color_schemes')."` WHERE `id`=".intval($id));
				if ($color_info) {
					$new_file_path = $this->COLOR_SCHEMES_DIR. $color_info["id"]. "/";
				}
			} elseif ($type == "graph") {
				$graph_info = db()->query_fetch("SELECT * FROM `".db('graphic_schemes')."` WHERE `id`=".intval($id));
				if ($graph_info) {
					$new_file_path = $this->GRAPH_SCHEMES_DIR. $graph_info["id"]. "/";
				}
			}
		}
		// Go with upload
		if ($new_file_path) {
			unlink($new_file_path. $_name_to_delete);
		}
		if (!$silent_mode) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Do refresh system caches related to designs
	*/
	function _refresh_cache () {
		if (!main()->USE_SYSTEM_CACHE) {
			return false;
		}
		cache()->refresh("user_themes");
		cache()->refresh("user_designs");
		cache()->refresh("user_designs_css");
		cache()->refresh("color_schemes");
		cache()->refresh("color_schemes_css");
		cache()->refresh("graph_schemes");
		cache()->refresh("graph_schemes_css");
	}


	/**
	* Generates preview image (for using over web use $_GET["id"] as <type>-<id>)
	*/
	function generate_preview_img ($type = "", $id = 0) {
		if (!$this->AUTO_GENERATE_PREVIEW) {
			return !$type ? js_redirect($_SERVER["HTTP_REFERER"]) : "";
		}
		if (!$type) {
			main()->NO_GRAPHICS = true;
			// Parse where to laod image
			list($type, $id) = explode("-", $_GET["id"]);
		} else {
			$silent_mode = true;
		}
		// These params are required
		if ($type && $id) {
			$new_file_path = "";
			// Get details by known type
			if ($type == "design") {
				$info = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($id));
				if ($info) {
					$theme_name = $this->_existed_themes[$info["theme_id"]];
					$new_file_path = $this->USER_THEMES_DIR. $theme_name. "/designs/";
					$screenshot_url = $this->SERVICE_WEB_PATH."user_design/".$info["id"];
				}
			} elseif ($type == "color") {
				$info = db()->query_fetch("SELECT * FROM `".db('color_schemes')."` WHERE `id`=".intval($id));
				if ($info) {
					$new_file_path = $this->COLOR_SCHEMES_DIR;
					$screenshot_url = $this->SERVICE_WEB_PATH."color_scheme/".$info["id"];
				}
			} elseif ($type == "graph") {
				$info = db()->query_fetch("SELECT * FROM `".db('graphic_schemes')."` WHERE `id`=".intval($id));
				if ($info) {
					$new_file_path = $this->GRAPH_SCHEMES_DIR;
					$screenshot_url = $this->SERVICE_WEB_PATH."graphic_scheme/".$info["id"];
				}
			}
		}
		// Go with upload
		if ($new_file_path && $screenshot_url) {
			// Generate preview and load it to folder
			$tmp_image_path = $this->TEST_OBJ->_remote_thumb_client($screenshot_url);
			$this->_resize_preview_img ("", $info["id"], $new_file_path, $tmp_image_path);
		}
		if (!$silent_mode) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Generates missing preview images 
	*/
	function generate_all_preview_img () {
		if (!$this->AUTO_GENERATE_PREVIEW) {
			return false;
		}	
		// Processing designs
		$designs_info = db()->query_fetch_all("SELECT * FROM `".db('designs')."`");
		foreach ((array)$designs_info as $design) {
			$this->generate_preview_img ("design", $design["id"]);
		}
		// Processing color schemes
		$schemes_info = db()->query_fetch_all("SELECT * FROM `".db('color_schemes')."`");
		foreach ((array)$schemes_info as $scheme) {
			$this->generate_preview_img ("color", $scheme["id"]);
		}
		// Processing graphic schemes
		$graph_schemes_info = db()->query_fetch_all("SELECT * FROM `".db('graphic_schemes')."`");
		foreach ((array)$graph_schemes_info as $scheme) {
			$this->generate_preview_img ("graph", $scheme["id"]);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Check whether image exists. returns web path with postfix to avoid browser caching
	*/
	function _check_image ($img_realpath = "") {
		if (file_exists($img_realpath)) {
			$img_web_path = substr($img_realpath, strlen(realpath(INCLUDE_PATH)) + 1);
			$img_web_path = WEB_PATH. $img_web_path;
			if ($this->BREAK_BROWSER_CACHE) {
				$img_web_path .= "?".time();
			}
		} else {
			$img_web_path  = "";
		}
		return $img_web_path;
	}
	
	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst(DESIGN_MGR_CLASS_NAME)." main",
				"url"	=> "./?object=".DESIGN_MGR_CLASS_NAME,
			),
			array(
				"name"	=> "Add theme",
				"url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=add_theme_form",
			),
			array(
				"name"	=> "Color schemes",
				"url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=color_schemes",
			),
			array(
				"name"	=> "Graphic schemes",
				"url"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=graphic_schemes",
			),
			array(
				"name"	=> $this->AUTO_GENERATE_PREVIEW ? "Re-generate all preview images" : "",
				"url"	=> $this->AUTO_GENERATE_PREVIEW ? "./?object=".DESIGN_MGR_CLASS_NAME."&action=generate_all_preview_img" : "",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".DESIGN_MGR_CLASS_NAME,
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Design Manager");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "Themes",
			"add_theme_form"	=> "Add theme",
			"edit_scheme"		=> $_GET["id"] ? "Edit color scheme" : "Add color scheme",
			"edit_graph_scheme"	=> $_GET["id"] ? "Edit graphic scheme" : "Add graphic scheme",
			"show_designs_in_theme" => "",
			"edit_design"		=> "",
			"add_design_form"	=> "",

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
