<?php

/**
* Submodule for manage designs
*/
class profy_design_manager_designs {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ	= module(DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Shows add design form
	*/
	function _add_design_form () {
		$theme_info = db()->query_fetch("SELECT `id`, `name` FROM `".db('user_themes')."` WHERE `name`='".$_GET["id"]."'");		
		// Show insert design content form
		$replace = array(
			"record_id"		=> "",
			"theme_name"	=> $_GET["id"] ? $_GET["id"] : "",
			"design_name"	=> "",
			"design_content"=> "", 
			"css_ie"		=> "", 
			"tags"			=> "",
			"back_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME,
			"form_action" 	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=add_design&id=".intval($theme_info["id"]),
			"img_path"		=> "",
			"del_image_link"=> "",
			"owner_id"		=> 0, 	//public
			"images_block"	=> "",
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_design_form", $replace);		
	}


	/**
	* Add design method
	*/
	function _add_design () {

		if (!empty($_POST) && (!$_POST["design_content"] || !$_POST["design_name"])) {
			return redirect($_SERVER["HTTP_REFERER"], 0, "CSS and design name required!");
		}

		// Theme name for path
		$theme_info = db()->query_fetch("SELECT `name` FROM `".db('user_themes')."` WHERE `id`=".intval($_GET["id"]));
		$theme_name = $theme_info["name"];
		// Array of design names
		$A = db()->query_fetch_all("SELECT `id`, `name` FROM `".db('designs')."` WHERE `theme_id`=".intval($_GET["id"]));
		foreach ((array)$A as $V) {
			$exists_designs[] = $V["name"];
		}
		if (in_array($_POST["design_name"], (array)$exists_designs)) {
			$error = "Design exists";
			return redirect($_SERVER["HTTP_REFERER"], true, $error);
		}

		$_POST["design_name"] = preg_replace("/[^0-9a-z\_\-\.]/", "", $_POST["design_name"]);
		//Prepare tags
		if (!empty($_POST["tags"])) {
			$tags_array = $this->PARENT_OBJ->_prepare_tags($_POST["tags"]);
			$tags_string = ";".implode(";", $tags_array).";";
		}
		// Insert to DB
		$sql_array = array(
			"name"		=> _es($_POST["design_name"]),
			"theme_id"	=> intval($_GET["id"]),
			"active"	=> 1,
			"tags"		=> $tags_string,
			"owner_id"	=> intval($_POST["owner_id"]),
			"css"		=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["design_content"]) : "",
		);
		db()->INSERT("designs", $sql_array); 
		$NEW_DESIGN_ID = db()->INSERT_ID();

		$designs_dir = $this->PARENT_OBJ->USER_THEMES_DIR.$theme_name."/designs/";
		// Check if theme folder exists. If not create it
		if (!file_exists($designs_dir)) {
			$this->PARENT_OBJ->DIR_OBJ->mkdir_m($designs_dir, 0777, 1);
		}

		// Make design default for theme if it is first
		if (!$theme_info["default_design"]) {
			db()->UPDATE("user_themes", array(
				"default_design" => $NEW_DESIGN_ID
			), "`id`=".intval($theme_info["id"]));
		}

		// Create css file and Save css contents
		if (!$this->PARENT_OBJ->STORE_TO_DB) {
			file_put_contents($designs_dir. $NEW_DESIGN_ID.".css", $_POST["design_content"]);
		}

		// Upload preview image
		$name_in_form = "preview_img";
		if (!empty($_FILES[$name_in_form]["tmp_name"])) {
			$this->PARENT_OBJ->_resize_preview_img ($name_in_form, $NEW_DESIGN_ID, $this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/designs/");
		}
		if (empty($_FILES[$name_in_form]["tmp_name"]) && $this->PARENT_OBJ->AUTO_GENERATE_PREVIEW) {
			// Make preview images automatically
			$url = $this->PARENT_OBJ->SERVICE_WEB_PATH. "user_design/".$NEW_DESIGN_ID;
			$tmp_image_path = $this->PARENT_OBJ->TEST_OBJ->_remote_thumb_client($url);
			$this->PARENT_OBJ->_resize_preview_img ("", $NEW_DESIGN_ID, $this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/designs/", $tmp_image_path);
		}

		$this->PARENT_OBJ->_refresh_cache();

		// Redirect back
		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=show_designs_in_theme&id=".intval($_GET["id"]));
	}

	/**
	* Delete design
	*/
	function _delete_design () {
		// Get design folder name
		$design_info = db()->query_fetch("SELECT `id`,`name`, `theme_id` FROM `".db('designs')."` WHERE `id`='".intval($_GET["id"])."'");	
		$theme_info = db()->query_fetch("SELECT `name`, `default_design` FROM `".db('user_themes')."` WHERE `id`=".intval($design_info["theme_id"]));

		if ($design_info["id"] == $theme_info["default_design"]) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}

		$css_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/".$design_info["id"].".css";
		if (file_exists($css_path)){
			unlink($css_path);
		}

		// Delete IE fixes
		$css_ie_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/".$design_info["id"]."__ie_only.css";
		if (file_exists($css_ie_path)){
			unlink($css_ie_path);
		}

		$_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/".$design_info["id"];
		$this->PARENT_OBJ->_delete_preview_imgs($_path);

		$this->PARENT_OBJ->DIR_OBJ->delete_dir($this->PARENT_OBJ->USER_THEMES_DIR. $theme_name. "/designs/". $design_info["id"], 1);

		// Delete record from DB
		db()->query("DELETE FROM `".db('designs')."` WHERE `id`='".intval($_GET["id"])."'");
		// Refresh system cache
		$this->PARENT_OBJ->_refresh_cache();

		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=show_designs_in_theme&id=".$design_info["theme_id"]);
	}

	/**
	* Edit design method
	*/
	function _edit_design () {

		$design_info	= db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($_GET["id"]));				
		$theme_info		= db()->query_fetch("SELECT `name` FROM `".db('user_themes')."` WHERE `id`=".intval($design_info["theme_id"]));

		$css_content = $design_info["css"];
		$_css_main_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/".$design_info["id"].".css";
		if ((($this->PARENT_OBJ->STORE_TO_DB && empty($css_content)) || !$this->PARENT_OBJ->STORE_TO_DB) && file_exists($_css_main_path)) {
			$css_content 	= file_get_contents($_css_main_path);
		}

		$img_path = "";
		$large_path = "";
		$_preview_path = $this->PARENT_OBJ->USER_THEMES_DIR.$theme_info["name"]."/designs/".$design_info["id"]."_preview.jpg";
		$_large_path = $this->PARENT_OBJ->USER_THEMES_DIR.$theme_info["name"]."/designs/".$design_info["id"]."_large.jpg";
		$img_path 	= $this->PARENT_OBJ->_check_image($_preview_path);
		$large_path = $this->PARENT_OBJ->_check_image($_large_path);
		//Prepare tags for show	
		if ($design_info["tags"]) {
			$_tags = substr($design_info["tags"], 1, strlen($design_info["tags"])-2);
			$_tags = explode(";", $_tags);
			// Prepare for edit
			$tags_to_edit = implode("\r\n", (array)$_tags);
		} else {
			$tags_to_edit = "";
		}

		if ($design_info["owner_id"]) {
			$user_info = user($design_info["owner_id"], "short");
		}

		if ($this->PARENT_OBJ->AUTO_GENERATE_PREVIEW) {
			$generate_url = "./?object=".DESIGN_MGR_CLASS_NAME."&action=generate_preview_img&id=design-".$design_info["id"];
		}

		$replace = array(
			"record_id"			=> intval($design_info["id"]),
			"design_name"		=> _prepare_html($design_info["name"]),
			"theme_name"		=> _prepare_html($theme_info["name"]),
			"design_content"	=> $css_content ? $this->PARENT_OBJ->_prepare_for_edit($css_content) : $this->PARENT_OBJ->_prepare_for_edit($design_info["css"]),
			"tags"				=> $tags_to_edit,
			"back_url"			=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=show_designs_in_theme&id=".$design_info["theme_id"],
			"form_action" 		=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=save_design&id=".$design_info["id"],
			"img_path"			=> $img_path,
			"photo_m_src"		=> $large_path,
			"generate_url"		=> $generate_url ? $generate_url : "",
			"del_image_link"	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=design_delete_preview_img&id=".$design_info["id"]."&page=".urlencode($theme_info["name"]),
			"owner_id"			=> $design_info["owner_id"],
			"members_url"		=> "./?object=members",
			"user_profile_url"	=> isset($user_info) ? _profile_link($user_info) : "",
			"user_nick"			=> _display_name($user_info),
			"images_block"		=> $this->PARENT_OBJ->_show_scheme_images_block("design", $design_info),
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_design_form", $replace);		
	}

	/**
	* Save design method
	*/
	function _save_design () {

		if (!empty($_POST) && (!$_POST["design_content"] || !$_POST["design_name"])) {
			return redirect($_SERVER["HTTP_REFERER"], 0, "CSS and design name required!");
		}

		if (intval($_POST["owner_id"])) {
			$user_info = user(intval($_POST["owner_id"]), "short");
			if (empty($user_info)) {
				// User is not exists
				return js_redirect($_SERVER["HTTP_REFERER"]);
			}
		}

		$_POST["design_name"] = preg_replace("/[^0-9a-z\_\-\.]/", "", $_POST["design_name"]);

		// Get design folder name
		$design_info = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`='".intval($_GET["id"])."'");	
		$theme_info	= db()->query_fetch("SELECT `name` FROM `".db('user_themes')."` WHERE `id`=".intval($design_info["theme_id"]));
		$theme_name = $theme_info["name"];

		// Save into db
		if ($design_info["owner_id"] || $this->PARENT_OBJ->STORE_TO_DB) {

			db()->UPDATE("designs", array("css" => _es($_POST["design_content"])), "`id`=".intval($design_info["id"]));

		// Common save into files
		} else {

			$designs_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/";

			file_put_contents($designs_path. $design_info["id"].".css",				$_POST["design_content"]);
			file_put_contents($designs_path. $design_info["id"]."__ie_only.css",	$_POST["css_ie"]);
		}
		// Prepare tags
		if (!empty($_POST["tags"])) {
			$tags_array = $this->PARENT_OBJ->_prepare_tags($_POST["tags"]);
			$tags_string = ";".implode(";", $tags_array).";";
		}

		db()->UPDATE("designs", array(
			"name"		=> _es($_POST["design_name"]),
			"tags"		=> $tags_string,
			"owner_id"	=> intval($_POST["owner_id"]),
		),"`id`='".intval($_GET["id"])."'");
		// Upload preview image
		$name_in_form = "preview_img";
		if (!empty($_FILES[$name_in_form]["tmp_name"])) {
			$this->PARENT_OBJ->_resize_preview_img ($name_in_form, $design_info["id"], $this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/designs/");
		} 

		$DESIGN_ID = intval($_GET["id"]);
		if (empty($_FILES[$name_in_form]["tmp_name"]) && $this->PARENT_OBJ->AUTO_GENERATE_PREVIEW && !file_exists($this->PARENT_OBJ->USER_THEMES_DIR.$theme_name."/designs/".$design_info["id"]."_preview.jpg")) {
			// Or generate it automatically
			$url = $this->PARENT_OBJ->SERVICE_WEB_PATH. "user_design/".$DESIGN_ID;
			$tmp_image_path = $this->PARENT_OBJ->TEST_OBJ->_remote_thumb_client($url);
			$this->PARENT_OBJ->_resize_preview_img ("", $DESIGN_ID, $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/", $tmp_image_path);
		}

		$this->PARENT_OBJ->load_scheme_image("design", $_GET["id"]);

		// Refresh system cache
		$this->PARENT_OBJ->_refresh_cache();
		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME."&action=show_designs_in_theme&id=".$design_info["theme_id"]);
	}

	/**
	* Activate\deactivate designs in themes
	*/
	function _activate_design () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($_GET["id"]));
			if ($A["active"] == 1){
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE("designs", array(
				"active"		=> $active,
			),
			"`id`='".intval($_GET["id"])."'" 
			);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("user_designs");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME);
		}
	}
}
