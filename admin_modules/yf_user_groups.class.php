<?php

/**
* User groups editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_user_groups {

	/**
	* Framework constructor
	*/
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_statuses,		$selected, false, 2, "", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>", 
			"1" => "<span class='positive'>YES</span>",
		);
	}

	/**
	* Show admin groups
	*/
	function show() {
		// Get number of members of each group
		if (defined("db('user')")) {
			$Q = db()->query("SELECT `group`, COUNT(`id`) AS `num_members` FROM `".db('user')."` WHERE 1=1 GROUP BY `group`");
			while ($A = db()->fetch_assoc($Q)) {
				$num_group_members[$A["group"]] = $A["num_members"];
			}
		}
		// Try to get admin "center_area" block id
		$blocks = main()->get_data("blocks_names");
		foreach ((array)$blocks as $_id => $_info) {
			if ($_info["type"] == "user" && $_info["name"] == "center_area") {
				$block_center_id = $_id;
				break;
			}
		}
		$Q = db()->query("SELECT * FROM `".db('menus')."` WHERE `type`='user' AND `active`='1' LIMIT 1");
		while ($A = db()->fetch_assoc($Q)) {
			$menu_id = $A["id"];
		}
		// Connect pager
		$sql = "SELECT * FROM `".db('user_groups')."`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"active"		=> (int)$A["active"],
				"num_members"	=> (int)$num_group_members[$A["id"]],
				"go_after_login"=> _prepare_html($A["go_after_login"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activity&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"add_link"		=> "./?object=".$_GET["object"]."&action=add",
			"blocks_link"	=> $block_center_id ? "./?object=blocks&action=show_rules&id=".$block_center_id : "",
			"menu_link"		=> $menu_id ? "./?object=menus_editor&action=show_items&id=".$menu_id : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Add groups
	*/
	function add() {
		// Do save data
		if (!empty($_POST)) {
			// Name could not be empty
			if (empty($_POST["name"])) {
				common()->_raise_error(t("Name is empty"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("user_groups", array(
					"name"			=> _es($_POST["name"]),
					"active"		=> intval((bool)$_POST["active"]),
					"go_after_login"=> _es($_POST["go_after_login"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("user_groups");
					cache()->refresh("user_groups_details");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"name"				=> _prepare_html($group_info["name"]),
				"go_after_login"	=> _prepare_html($group_info["go_after_login"]),
				"active_box"		=> $this->_box("active", $group_info["active"]),
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
				"for_edit"			=> 0,
			);
			return tpl()->parse($_GET["object"]."/edit_group_form", $replace);
		}
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
		$group_info = db()->query_fetch("SELECT * FROM `".db('user_groups')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($group_info)) {
			return _e(t("No such group"));
		}
		// Do save data
		if (!empty($_POST)) {
			// Name could not be empty
			if (empty($_POST["name"])) {
				common()->_raise_error(t("Name is empty"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("user_groups", array(
					"name" 			=> _es($_POST["name"]),
					"go_after_login"=> _es($_POST["go_after_login"]),
				), "`id`=".intval($_GET['id']));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("user_groups");
					cache()->refresh("user_groups_details");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"name"				=> _prepare_html($group_info["name"]),
				"go_after_login"	=> _prepare_html($group_info["go_after_login"]),
				"group_id"			=> intval($group_info["id"]),
				"active_box"		=> $this->_box("active", $group_info["active"]),
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
				"for_edit"			=> 1,
			);
			return tpl()->parse($_GET["object"]."/edit_group_form", $replace);
		}
	}

	/**
	* Delete
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		// Do delete records
		if (!empty($_GET['id'])) {
			db()->query("DELETE FROM `".db('user_groups')."` WHERE `id`=".intval($_GET['id'])." LIMIT 1");
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("user_groups");
			cache()->refresh("user_groups_details");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Change group activity
	*/
	function activity() {
		$_GET['id'] = intval($_GET['id']);
		// Get group info
		if (!empty($_GET['id'])) {
			$group_info = db()->query_fetch("SELECT * FROM `".db('user_groups')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do update record
		if (!empty($group_info)) {
			db()->UPDATE("user_groups", array(
				"active"	=> intval(!$group_info["active"]),
			), "`id`=".intval($_GET['id']));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("user_groups");
			cache()->refresh("user_groups_details");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($group_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
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
				"name"	=> "Add group",
				"url"	=> "./?object=".$_GET["object"]."&action=add",
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
		$pheader = t("User groups");
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
