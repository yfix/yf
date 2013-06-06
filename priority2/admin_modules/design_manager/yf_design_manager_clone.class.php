<?php

/**
* Submodule for cloning objects
*/
class yf_design_manager_clone {

	/**
	* Constructor
	*/
	function _init () {
		$this->PARENT_OBJ	= module(DESIGN_MGR_CLASS_NAME);
	}

	/**
	* Clone user theme
	*/
	function _clone_theme ($FORCE_ID = 0) {
		$THEME_ID = intval($FORCE_ID ? $FORCE_ID : $_GET["id"]);
		// Get details
		$theme_info = db()->query_fetch("SELECT * FROM `".db('user_themes')."` WHERE `id`=".intval($THEME_ID));
		if (!$theme_info) {
			return !$FORCE_ID ? _e("No such theme") : false;
		}

		$new_theme_name = $theme_info["name"]."_clone";
		// Prepare new data
		$sql = $theme_info;
		unset($sql["id"]);
		$sql["name"] = $new_theme_name;
		$sql = _es($sql);
		// Do create new record
		db()->INSERT("user_themes", $sql);
		$NEW_THEME_ID = db()->INSERT_ID();
		// Copy all files inside theme dir
		$old_theme_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/";
		$new_theme_path = $this->PARENT_OBJ->USER_THEMES_DIR. $new_theme_name."/";

		_class("dir")->copy_dir($old_theme_path, $new_theme_path, "", "/(svn|git)/i");

		$new_css	= str_replace(substr($old_theme_path, strlen(INCLUDE_PATH)), substr($new_theme_path, strlen(INCLUDE_PATH)), $theme_info["css"]);
		$new_css_ie = str_replace(substr($old_theme_path, strlen(INCLUDE_PATH)), substr($new_theme_path, strlen(INCLUDE_PATH)), $theme_info["css_ie"]);
		$new_html	= str_replace(substr($old_theme_path, strlen(INCLUDE_PATH)), substr($new_theme_path, strlen(INCLUDE_PATH)), $theme_info["html"]);

		db()->UPDATE("user_themes", array(
			"css"		=> _es($new_css),
			"css_ie"	=> _es($new_css_ie),
			"html"		=> _es($new_html),
		), "`id`=".intval($NEW_THEME_ID));

		$NEW_THEME_INFO = db()->query_fetch("SELECT * FROM `".db('user_themes')."` WHERE `id`=".intval($NEW_THEME_ID));
		// Rename theme preview image
		_rename($new_theme_path. $theme_info["name"]."_preview.jpg", $new_theme_path. $new_theme_name."_preview.jpg");
		_rename($new_theme_path. $theme_info["name"]."_large.jpg", $new_theme_path. $new_theme_name."_large.jpg");

		// Process designs
		$_designs_sql = "SELECT * FROM `".db('designs')."` WHERE `theme_id`=".intval($THEME_ID);
		foreach ((array)db()->query_fetch_all($_designs_sql) as $design_info) {
			$OLD_DESIGN_ID = $design_info["id"];
			$NEW_DESIGN_ID = $this->_clone_design($OLD_DESIGN_ID, $NEW_THEME_INFO);
			$_designs_old_to_new[$OLD_DESIGN_ID] = $NEW_DESIGN_ID;
			// Fix old designs ids subfolders
			$old_design_path = $this->PARENT_OBJ->USER_THEMES_DIR. $NEW_THEME_INFO["name"]."/designs/".$OLD_DESIGN_ID;
			$this->PARENT_OBJ->_delete_preview_imgs($old_design_path);
			$this->PARENT_OBJ->DIR_OBJ->delete_dir($old_design_path, 1);
		}

		if ($theme_info["default_design"]) {
			db()->UPDATE("user_themes", array(
				"default_design" => intval($_designs_old_to_new[$theme_info["default_design"]]),
			), "`id`=".intval($NEW_THEME_ID));
		}

		// Clone blocks rules
		$Q = db()->query("SELECT * FROM `".db('block_rules')."` WHERE `themes` LIKE '%,".$THEME_ID.",%'");
		while ($A = db()->fetch_assoc($Q)) {
			foreach (explode(",",trim($A["themes"],",")) as $_theme_id) {
				if ($_theme_id != $THEME_ID) {
					continue;
				}
				$theme_block_rules[$A["id"]] = $A["themes"];
			}
		}
		foreach ((array)$theme_block_rules as $_rule_id => $_old_themes_field) {
			db()->UPDATE("block_rules", array(
				"themes" => _es($_old_themes_field. $NEW_THEME_ID. ","),
			), "`id`=".intval($_rule_id));
		}

		$this->PARENT_OBJ->_refresh_cache();

		return !$FORCE_ID ? js_redirect("./?object=".$_GET["object"]) : $NEW_THEME_ID;
	}

	/**
	* Clone design
	*/
	function _clone_design ($FORCE_ID = 0, $new_theme_info = array()) {
		$DESIGN_ID = intval($FORCE_ID ? $FORCE_ID : $_GET["id"]);
		// Get details
		$design_info = db()->query_fetch("SELECT * FROM `".db('designs')."` WHERE `id`=".intval($DESIGN_ID));
		if (!$design_info) {
			return !$FORCE_ID ? _e("No such design") : false;
		}
		// Get theme info
		$theme_info = db()->query_fetch("SELECT * FROM `".db('user_themes')."` WHERE `id`=".intval($design_info["theme_id"]));
		if (empty($new_theme_info)) {
			$new_theme_info = $theme_info;
		}

		// Prepare new data
		$sql = $design_info;
		unset($sql["id"]);
		$sql["name"] = $design_info["name"]."_clone";
		$sql["theme_id"] = $new_theme_info["id"];
		$sql = _es($sql);
		// Do create new record
		db()->INSERT("designs", $sql);
		$NEW_DESIGN_ID = db()->INSERT_ID();

		$old_images_path = $this->PARENT_OBJ->USER_THEMES_DIR. $theme_info["name"]."/designs/". $design_info["id"];
		$new_images_path = $this->PARENT_OBJ->USER_THEMES_DIR. $new_theme_info["name"]."/designs/". $NEW_DESIGN_ID;

		_class("dir")->copy_dir($old_images_path, $new_images_path, "", "/(svn|git)/i");

		$new_css = str_replace(substr($old_images_path, strlen(INCLUDE_PATH)), substr($new_images_path, strlen(INCLUDE_PATH)), $design_info["css"]);

		db()->UPDATE("designs", array(
			"css" => _es($new_css),
		), "`id`=".intval($NEW_DESIGN_ID));

		$old_preview_path = $old_images_path;
		$new_preview_path = $new_images_path;
		if (file_exists($old_preview_path. "_preview.jpg")) {
			@copy($old_preview_path. "_preview.jpg", $new_preview_path. "_preview.jpg");
		}
		if (file_exists($old_preview_path. "_large.jpg")) {
			@copy($old_preview_path. "_large.jpg", $new_preview_path. "_large.jpg");
		}

		if (!$FORCE_ID) {
			$this->PARENT_OBJ->_refresh_cache();
			return js_redirect("./?object=".$_GET["object"]."&action=show_designs_in_theme&id=".$theme_info["id"]);
		} else {
			return $NEW_DESIGN_ID;
		}
	}

	/**
	* Clone color scheme
	*/
	function _clone_color_scheme ($FORCE_ID = 0) {
		$COLOR_ID = intval($FORCE_ID ? $FORCE_ID : $_GET["id"]);
		// Get details
		$color_scheme_info = db()->query_fetch("SELECT * FROM `".db('color_schemes')."` WHERE `id`=".intval($COLOR_ID));
		if (!$color_scheme_info) {
			return !$FORCE_ID ? _e("No such color scheme") : false;
		}
		// Prepare new data
		$sql = $color_scheme_info;
		unset($sql["id"]);
		$sql["name"] = $color_scheme_info["name"]."_clone";
		$sql = _es($sql);
		// Do create new record
		db()->INSERT("color_schemes", $sql);
		$NEW_COLOR_ID = db()->INSERT_ID();
		// Update some required info
		$old_images_path = $this->PARENT_OBJ->COLOR_SCHEMES_DIR. $color_scheme_info["id"]. "/";
		$new_images_path = $this->PARENT_OBJ->COLOR_SCHEMES_DIR. $NEW_COLOR_ID. "/";

		_class("dir")->copy_dir($old_images_path, $new_images_path, "", "/(svn|git)/i");

		$new_css = str_replace(substr($old_images_path, strlen(INCLUDE_PATH)), substr($new_images_path, strlen(INCLUDE_PATH)), $color_scheme_info["css"]);

		db()->UPDATE("color_schemes", array(
			"css" => _es($new_css),
		), "`id`=".intval($NEW_COLOR_ID));

		$old_preview_path = $this->PARENT_OBJ->COLOR_SCHEMES_DIR. $color_scheme_info["id"]. "";
		$new_preview_path = $this->PARENT_OBJ->COLOR_SCHEMES_DIR. $NEW_COLOR_ID. "";
		if (file_exists($old_preview_path. "_preview.jpg")) {
			@copy($old_preview_path. "_preview.jpg", $new_preview_path. "_preview.jpg");
		}
		if (file_exists($old_preview_path. "_large.jpg")) {
			@copy($old_preview_path. "_large.jpg", $new_preview_path. "_large.jpg");
		}

		$this->PARENT_OBJ->_refresh_cache();

		return !$FORCE_ID ? js_redirect("./?object=".$_GET["object"]."&action=color_schemes") : $NEW_COLOR_ID;
	}

	/**
	* Clone graph scheme
	*/
	function _clone_graph_scheme ($FORCE_ID = 0) {
		$GRAPH_ID = intval($FORCE_ID ? $FORCE_ID : $_GET["id"]);
		// Get details
		$graph_scheme_info = db()->query_fetch("SELECT * FROM `".db('graphic_schemes')."` WHERE `id`=".intval($GRAPH_ID));
		if (!$graph_scheme_info) {
			return !$FORCE_ID ? _e("No such graph scheme") : false;
		}
		// Prepare new data
		$sql = $graph_scheme_info;
		unset($sql["id"]);
		$sql["name"] = $graph_scheme_info["name"]."_clone";
		$sql = _es($sql);
		// Do create new record
		db()->INSERT("graphic_schemes", $sql);
		$NEW_GRAPH_ID = db()->INSERT_ID();
		// Update some required info
		$old_images_path = $this->PARENT_OBJ->GRAPH_SCHEMES_DIR. $graph_scheme_info["id"]. "/";
		$new_images_path = $this->PARENT_OBJ->GRAPH_SCHEMES_DIR. $NEW_GRAPH_ID. "/";

		_class("dir")->copy_dir($old_images_path, $new_images_path, "", "/(svn|git)/i");

		$new_css = str_replace(substr($old_images_path, strlen(INCLUDE_PATH)), substr($new_images_path, strlen(INCLUDE_PATH)), $graph_scheme_info["css"]);

		db()->UPDATE("graphic_schemes", array(
			"css" => _es($new_css),
		), "`id`=".intval($NEW_GRAPH_ID));

		$old_preview_path = $this->PARENT_OBJ->GRAPH_SCHEMES_DIR. $graph_scheme_info["id"]. "";
		$new_preview_path = $this->PARENT_OBJ->GRAPH_SCHEMES_DIR. $NEW_GRAPH_ID. "";
		if (file_exists($old_preview_path. "_preview.jpg")) {
			@copy($old_preview_path. "_preview.jpg", $new_preview_path. "_preview.jpg");
		}
		if (file_exists($old_preview_path. "_large.jpg")) {
			@copy($old_preview_path. "_large.jpg", $new_preview_path. "_large.jpg");
		}

		$this->PARENT_OBJ->_refresh_cache();

		return !$FORCE_ID ? js_redirect("./?object=".$_GET["object"]."&action=graphic_schemes") : $NEW_GRAPH_ID;
	}
}
