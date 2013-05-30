<?php

/**
* Submodule for manage themes
*/
class profy_design_manager_themes {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ	= module(DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Shows add theme form
	*/
	function _add_theme_form () {
		// Show insert theme content form
		$replace = array(
			"record_id"		=> "",
			"theme_name"	=> "",
			"descr"			=> "",
			"designs_box"	=> "",
			"theme_content"	=> "",
			"css_content"	=> "", 
			"css_ie"		=> "", 
			"tags"			=> "",
			"back_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME,
			"form_action" 	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=add_theme",
			"img_path"		=> "",
			"del_image_link"=> "",
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_theme_form", $replace);		
	}

	/**
	* Add user theme
	*/
	function _add_theme () {
		if (!empty($_POST) && (!$_POST["theme_content"] || !$_POST["theme_name"])) {
			return redirect($_SERVER["HTTP_REFERER"], 0, "CSS and theme name required!");
		}

		if (in_array($_POST["theme_name"], (array)$this->PARENT_OBJ->_existed_themes)) {
			$error = "Theme exists";
			return redirect("./?object=".DESIGN_MGR_CLASS_NAME, true, $error);
		}

		$_POST["theme_name"] = preg_replace("/[^0-9a-z\_\-\.]/", "", $_POST["theme_name"]);
		//Prepare tags
		if (!empty($_POST["tags"])) {
			$tags_array = $this->PARENT_OBJ->_prepare_tags($_POST["tags"]);
			$tags_string = ";".implode(";", $tags_array).";";
		}
		// Insert to DB
		$sql_array = array(
			"name"		=> _es($_POST["theme_name"]),
			"descr"		=> _es($_POST["descr"]),
			"active"	=> 1,
			"tags"		=> $tags_string,
			"css"		=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["css_content"]) : "",
			"css_ie"	=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["css_ie"]) : "",
			"html"		=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["theme_content"]) : "",
		);
		db()->INSERT("user_themes", $sql_array); 

		// Check if theme folder exists. If not create it
		if (!file_exists($this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"])) {
			// Make folder
			$this->PARENT_OBJ->DIR_OBJ->mkdir_m($this->PARENT_OBJ->USER_THEMES_DIR.$_POST["theme_name"], 0777, 1);
		}

		if (!$this->PARENT_OBJ->STORE_TO_DB) {
			// Create template file and Save template contents
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"]."/".$this->PARENT_OBJ->MAIN_TEMPLATE_NAME, $_POST["theme_content"]);
			// Create css file and Save css contents
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"]."/".$this->PARENT_OBJ->MAIN_CSS_NAME, $_POST["css_content"]);
			// Create css file and Save css IE fixes
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"]."/"."ie_only.css", $_POST["css_ie"]);
		}

		// Upload preview image
		$name_in_form = "preview_img";
		if (!empty($_FILES[$name_in_form]["tmp_name"])) {
			$this->PARENT_OBJ->_resize_preview_img ($name_in_form, $_POST["theme_name"], $this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"]."/");
		}
		// Refresh system cache
		$this->PARENT_OBJ->_refresh_cache();

		// Redirect back
		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Edit theme
	*/
	function _edit_theme () {
		$theme_info = db()->query_fetch("SELECT * FROM `".db('user_themes')."` WHERE `id`=".intval($_GET["id"]));

		$theme_content 	= $theme_info["html"];
		$css_content 	= $theme_info["css"];
		$css_ie			= $theme_info["css_ie"];

		if ((($this->PARENT_OBJ->STORE_TO_DB && empty($theme_content)) || !$this->PARENT_OBJ->STORE_TO_DB) && file_exists($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$this->PARENT_OBJ->MAIN_TEMPLATE_NAME)) {
			$theme_content 	= file_get_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$this->PARENT_OBJ->MAIN_TEMPLATE_NAME);
		}
		if ((($this->PARENT_OBJ->STORE_TO_DB && empty($css_content)) || !$this->PARENT_OBJ->STORE_TO_DB) && file_exists($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$this->PARENT_OBJ->MAIN_CSS_NAME)) {
			$css_content 	= file_get_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$this->PARENT_OBJ->MAIN_CSS_NAME);
		}
		if ((($this->PARENT_OBJ->STORE_TO_DB && empty($css_ie)) || !$this->PARENT_OBJ->STORE_TO_DB) && file_exists($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/"."ie_only.css")) {
			$css_ie 	= file_get_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/"."ie_only.css");
		}

		$img_path = "";
		$large_path = "";
		$img_path 	= $this->PARENT_OBJ->_check_image($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$theme_info["name"]."_preview.jpg");
		$large_path = $this->PARENT_OBJ->_check_image($this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/".$theme_info["name"]."_large.jpg");
		// Prepare tags for show
		if ($theme_info["tags"]) {
			$_tags = substr($theme_info["tags"], 1, strlen($theme_info["tags"])-2);
			$_tags = explode(";", $_tags);
			// Prepare for edit
			$tags_to_edit = implode("\r\n", (array)$_tags);
		} else {
			$tags_to_edit = "";
		}

		// Get blocks
		$blocks_names = main()->get_data("blocks_names");
		// Get blocks rules
		$rules_items = array();
		$Q = db()->query("SELECT * FROM `".db('block_rules')."` WHERE `themes` LIKE '%,".intval($_GET["id"]).",%'");
		while ($A = db()->fetch_assoc($Q)) {
			$rules_items[$A["id"]] = array(
				"rule_id"		=> intval($A["id"]),
				"block_id"		=> intval($A["block_id"]),
				"rule_type"		=> _prepare_html($A["rule_type"]),
				"block_name"	=> _prepare_html($blocks_names[$A["block_id"]]["name"]),
				"edit_link"		=> "./?object=blocks&action=edit_rule&id=".$A["id"],
				"block_link"	=> "./?object=blocks&action=edit&id=".$A["block_id"],
			);
		}
		$designs_in_theme = $this->PARENT_OBJ->_get_designs($theme_info["id"]);
		$replace = array(
			"form_action" 	=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=save_theme&id=".urlencode($theme_info["name"]),
			"record_id"		=> intval($theme_info["id"]),
			"theme_name"	=> _prepare_html($theme_info["name"]),
			"descr"			=> $this->PARENT_OBJ->_prepare_for_edit($theme_info["descr"]),
			"theme_content"	=> $this->PARENT_OBJ->_prepare_for_edit($theme_content),
			"css_content"	=> $this->PARENT_OBJ->_prepare_for_edit($css_content), 
			"css_ie"		=> $this->PARENT_OBJ->_prepare_for_edit($css_ie), 
			"designs_box"	=> common()->select_box("design", $designs_in_theme, $theme_info["default_design"]),
			"tags"			=> $tags_to_edit,
			"back_url"		=> "./?object=".DESIGN_MGR_CLASS_NAME,
			"img_path"		=> $img_path,
			"photo_m_src"	=> $large_path,
			"del_image_link"=> "./?object=".DESIGN_MGR_CLASS_NAME."&action=theme_delete_preview_img&id=".urlencode($theme_info["name"]),
			"blocks_link"	=> "./?object=blocks",
			"rules_items"	=> $rules_items ? $rules_items : "",
		);
		return tpl()->parse(DESIGN_MGR_CLASS_NAME."/add_theme_form", $replace);
	}

	/**
	* Save theme content
	*/
	function _save_theme () {

		if (!empty($_POST) && (!$_POST["theme_content"] || !$_POST["theme_name"])) {
			return redirect($_SERVER["HTTP_REFERER"], 0, "CSS and theme name required!");
		}

		$old_theme_name = urldecode($_GET["id"]);
		$_POST["theme_name"] = preg_replace("/[^0-9a-z\_\-\.]/", "", $_POST["theme_name"]);

		$theme_name = $old_theme_name;
		if ($old_theme_name != $_POST["theme_name"]){
			// rename folder
			if (file_exists($this->PARENT_OBJ->USER_THEMES_DIR. $old_theme_name)){
				$new_folder_name = $this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"];
				rename($this->PARENT_OBJ->USER_THEMES_DIR. $old_theme_name, $new_folder_name);
			}
			// rename preview images (thumb and large)
			if (file_exists($new_folder_name."/".$old_theme_name."_preview.jpg")){
				rename($new_folder_name."/".$old_theme_name."_preview.jpg", $new_folder_name."/".$_POST["theme_name"]."_preview.jpg");
			}
			if (file_exists($new_folder_name."/".$old_theme_name."_large.jpg")){
				rename($new_folder_name."/".$theme_name."_large.jpg", $new_folder_name."/".$_POST["theme_name"]."_large.jpg");
			}
			$theme_name = $_POST["theme_name"];
		}
		if (!$this->PARENT_OBJ->STORE_TO_DB) {
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/".$this->PARENT_OBJ->MAIN_TEMPLATE_NAME, $_POST["theme_content"]);
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/".$this->PARENT_OBJ->MAIN_CSS_NAME, $_POST["css_content"]);
			file_put_contents($this->PARENT_OBJ->USER_THEMES_DIR. $theme_name."/"."ie_only.css", $_POST["css_ie"]);
		}

		//Prepare tags
		if (!empty($_POST["tags"])) {
			$tags_array = $this->PARENT_OBJ->_prepare_tags($_POST["tags"]);
			$tags_string = ";".implode(";", $tags_array).";";
		}
		db()->UPDATE("user_themes", array(
			"name"				=> _es($_POST["theme_name"]),
			"descr"				=> _es($_POST["descr"]),
			"tags"				=> $tags_string,
			"default_design"	=> $_POST["design"],
			"html"				=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["theme_content"]) : "",
			"css"				=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["css_content"]) : "",
			"css_ie"			=> $this->PARENT_OBJ->STORE_TO_DB ? _es($_POST["css_ie"]) : "",
		), "`name`='".$old_theme_name."'");
		// Upload preview image
		$name_in_form = "preview_img";
		if (!empty($_FILES[$name_in_form]["name"])) {
			$this->PARENT_OBJ->_resize_preview_img ($name_in_form, $_POST["theme_name"], $this->PARENT_OBJ->USER_THEMES_DIR. $_POST["theme_name"]."/");
		}
		// Refresh system cache
		$this->PARENT_OBJ->_refresh_cache();

		// Redirect back
		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Delete theme
	*/
	function _delete_theme () {
		$theme_name = urldecode($_GET["id"]);
		$theme_info = db()->query("SELECT `id` FROM `".db('user_themes')."` WHERE `name`='".$_GET["id"]."'");
		if ($theme_info["id"] == main()->DEFAULT_THEME_ID) {
			return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME);
		}

		$this->PARENT_OBJ->DIR_OBJ->delete_dir($this->PARENT_OBJ->USER_THEMES_DIR. $theme_name, 1);

		// Delete record from DB
		db()->query("DELETE FROM `".db('user_themes')."` WHERE `name`='".$theme_name."'");
		// Refresh system cache
		$this->PARENT_OBJ->_refresh_cache();

		return js_redirect("./?object=".DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Activate\deactivate themes
	*/
	function _activate_theme () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM `".db('user_themes')."` WHERE `id`=".intval($_GET["id"]));
			if ($A["active"] == 1){
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE("user_themes", array(
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
