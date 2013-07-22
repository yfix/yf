<?php

/**
* Admin groups handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_groups {

	/**
	*/
	function _init () {
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_statuses,		$selected, false, 2, "", false)',
		);
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>", 
			"1" => "<span class='positive'>YES</span>",
		);
	}

	/**
	* Display items
	*/
	function show () {
		$blocks = main()->get_data("blocks_names");
		foreach ((array)$blocks as $_id => $_info) {
			if ($_info["type"] == "admin" && $_info["name"] == "center_area") {
				$admin_center_id = $_id;
				break;
			}
		}
		$Q = db()->query("SELECT * FROM ".db('menus')." WHERE type='admin' AND active='1' LIMIT 1");
		while ($A = db()->fetch_assoc($Q)) {
			$menu_id = $A["id"];
		}
		return common()->table2("SELECT * FROM ".db('admin_groups')." ORDER BY id ASC")
			->text("name")
			->text("go_after_login")
			->btn_edit()
			->btn_delete()
			->btn_active()
			->footer_link("Add", "./?object=".$_GET["object"]."&action=add")
			->footer_link("Blocks", "./?object=blocks&action=show_rules&id=".$admin_center_id)
			->footer_link("Menu", "./?object=menus_editor&action=show_items&id=".$menu_id)
			->render();
	}

	/**
	*/
	function add() {
		if (!empty($_POST)) {
			if (empty($_POST["name"])) {
				_re(t("Name is empty"));
			}
			if (!common()->_error_exists()) {
				db()->INSERT("admin_groups", array(
					"name"			=> _es($_POST["name"]),
					"active"		=> intval((bool)$_POST["active"]),
					"go_after_login"=> _es($_POST["go_after_login"]),
				));
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("admin_groups");
					cache()->refresh("admin_groups_details");
				}
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"name"				=> _prepare_html($group_info["name"]),
			"go_after_login"	=> _prepare_html($group_info["go_after_login"]),
			"active"			=> $group_info["active"],
			"active_box"		=> $this->_box("active", $group_info["active"]),
			"back_link"			=> "./?object=".$_GET["object"],
			"error_message"		=> _e(),
			"for_edit"			=> 0,
		);
		return common()->form2($replace)
			->text("name","Group name")
			->text("go_after_login","Url after login")
			->active_box()
			->save_and_back()
			->render();
	}

	/**
	* Edit groups
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e(t("No id"));
		}
		// Get group info
		$group_info = db()->query_fetch("SELECT * FROM ".db('admin_groups')." WHERE id=".intval($_GET["id"]));
		if (empty($group_info)) {
			return _e(t("No such group"));
		}
		// Do save data
		if (!empty($_POST)) {
			// Name could not be empty
			if (empty($_POST["name"])) {
				_re(t("Name is empty"));
			}
			if (!$_POST["active"] && $_GET["id"] == 1) {
				_re(t("You can not disable root admin group"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("admin_groups", array(
					"name" 			=> _es($_POST["name"]),
					"active"		=> intval((bool)$_POST["active"]),
					"go_after_login"=> _es($_POST["go_after_login"]),
				), "id=".intval($_GET['id']));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("admin_groups");
					cache()->refresh("admin_groups_details");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"				=> _prepare_html($group_info["name"]),
			"go_after_login"	=> _prepare_html($group_info["go_after_login"]),
			"group_id"			=> intval($group_info["id"]),
			"active"			=> $group_info["active"],
			"active_box"		=> $this->_box("active", $group_info["active"]),
			"back_link"			=> "./?object=".$_GET["object"],
			"error_message"		=> _e(),
			"for_edit"			=> 1,
		);
		return common()->form2($replace)
			->info("group_id")
			->text("name","Group name")
			->text("go_after_login","Url after login")
			->active_box()
			->save_and_back()
			->render();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET["id"] == 1) {
			$_GET["id"] = 0;
		}
		if (!empty($_GET['id'])) {
			db()->query("DELETE FROM ".db('admin_groups')." WHERE id=".intval($_GET['id'])." LIMIT 1");
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("admin_groups");
			cache()->refresh("admin_groups_details");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function activity() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$group_info = db()->query_fetch("SELECT * FROM ".db('admin_groups')." WHERE id=".intval($_GET["id"]));
		}
		if ($_GET["id"] == 1) {
			$group_info = array();
		}
		if (!empty($group_info)) {
			db()->UPDATE("admin_groups", array(
				"active"	=> intval(!$group_info["active"]),
			), "id=".intval($_GET['id']));
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("admin_groups");
			cache()->refresh("admin_groups_details");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($group_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) {
			return false;
		} else {
			return eval("return common()->".$this->_boxes[$name].";");
		}
	}
}
