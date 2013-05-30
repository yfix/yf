<?php

/**
* Admin users manager
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_admin {

	/**
	* Constructor
	*/
	function _init () {
		$this->_admin_groups = array();
		// Fill array of admin groups
		$Q = db()->query("SELECT `id`,`name` FROM `".db('admin_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_admin_groups[$A['id']] = $A['name'];
		}
		// Array of select boxes to process
		$this->_boxes = array(
			"group"		=> 'select_box("group",	$this->_admin_groups,	$selected, false, 2, "", false)',
			"active"	=> 'radio_box("active",	$this->_statuses,		$selected, false, 2, "", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
	}

	/**
	* Show admin users
	*/
	function show() {
		$sql = "SELECT * FROM `".db('admin')."`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		$items = array();

		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$A = _prepare_html($A);
			$items[$A["id"]] = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"login"			=> $A["login"],
				"first_name"	=> $A["first_name"],
				"last_name"		=> $A["last_name"],
				"active"		=> intval((bool)$A["active"]),
				"group_id"		=> intval($A["group"]),
				"group_name"	=> _prepare_html($this->_admin_groups[$A["group"]]),
				"go_after_login"=> _prepare_html($A["go_after_login"]),
				"add_date"		=> _format_date($A["add_date"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"admin_auth_link"=> "./?object=log_admin_auth_view&action=show_for_admin&id=".$A["id"],
				"delete_link"	=> $A["id"] != 1 && $A["id"] != $_SESSION["admin_id"] ? "./?object=".$_GET["object"]."&action=delete&id=".$A["id"] : "",
				"active_link"	=> $A["id"] != 1 && $A["id"] != $_SESSION["admin_id"] ? "./?object=".$_GET["object"]."&action=activate&id=".$A["id"] : "",
				"edit_group_link"=> "./?object=admin_groups&action=edit&id=".$A["group"],
			);
		}
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"add_link"			=> "./?object=".$_GET["object"]."&action=add",
			"failed_log_link"	=> "./?object=log_admin_auth_fails_viewer",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Form to edit admin users
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (!$_GET["id"]) {
			return "No id!";
		}
		// Get current record
		$admin_info = db()->query_fetch("SELECT * FROM `".db('admin')."` WHERE `id`=".intval($_GET["id"]));
		// Save posted data
		if (!empty($_POST)) {
			$_POST["login"] = preg_replace("/[^a-z0-9\_\-\.]/ims", "", $_POST["login"]);
			if (!$_POST["login"]) {
				_re("Login required!");
			}
			if (!common()->_error_exists()) {
				$_new_pswd = $_POST["password"];
				$_POST = _es($_POST);
				$sql = array(
					"login"			=> $_POST["login"],
					"first_name"	=> $_POST["first_name"],
					"last_name"		=> $_POST["last_name"],
					"go_after_login"=> $_POST["go_after_login"],
					"group"			=> intval($_POST["group"]),
					"active"		=> intval($_POST["active"]),
				);
				if (strlen($_POST["password"])) {
					$sql["password"] = md5($_new_pswd);
				}
				db()->UPDATE("admin", $sql, "`id`=".intval($_GET["id"]));
				return js_redirect("./?object=".$_GET["object"]);
			}
	 		
		}
		$DATA = $admin_info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$DATA = _prepare_html($DATA);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"error_message"	=> _e(),
			"for_edit"		=> 1,
			"login"			=> $DATA["login"],
			"password"		=> "",
			"first_name"	=> $DATA["first_name"],
			"last_name"		=> $DATA["last_name"],
			"go_after_login"=> $DATA["go_after_login"],
			"group_box"		=> $this->_box("group", $DATA["group"]),
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
			"groups_link"	=> "./?object=admin_groups",
			"add_date"		=> _format_date($admin_info["add_date"], "full"),
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	/**
	* Form to add admin users
	*/
	function add() {
		// Save posted data
		if (!empty($_POST)) {
			$_POST["login"] = preg_replace("/[^a-z0-9\_\-\.]/ims", "", $_POST["login"]);
			if (!$_POST["login"]) {
				_re("Login required!");
			}
			if (!strlen($_POST["password"])) {
				_re("Password required!");
			}
			if (!common()->_error_exists()) {
				$_new_pswd = md5($_POST["password"]);
				$_POST = _es($_POST);
				$sql = array(
					"login"			=> $_POST["login"],
					"password"		=> $_new_pswd,
					"first_name"	=> $_POST["first_name"],
					"last_name"		=> $_POST["last_name"],
					"go_after_login"=> $_POST["go_after_login"],
					"group"			=> intval($_POST["group"]),
					"active"		=> intval($_POST["active"]),
					"add_date"		=> time(),
				);
				db()->INSERT("admin", $sql);
				$NEW_ID = db()->INSERT_ID();
				return js_redirect("./?object=".$_GET["object"].($NEW_ID ? "&action=edit&id=".$NEW_ID : ""));
			}
	 		
		}

		if (!isset($_POST["active"])) {
			$_POST["active"] = 1;
		}
		$_POST = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"error_message"	=> _e(),
			"for_edit"		=> 0,
			"login"			=> $_POST["login"],
			"password"		=> $_POST["password"],
			"first_name"	=> $_POST["first_name"],
			"last_name"		=> $_POST["last_name"],
			"go_after_login"=> $_POST["go_after_login"],
			"group_box"		=> $this->_box("group", $_POST["group"]),
			"active_box"	=> $this->_box("active", $_POST["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
			"groups_link"	=> "./?object=admin_groups",
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	/**
	* Delete administrators
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->query("DELETE FROM `".db('admin')."` WHERE `id`=".intval($_GET['id']));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	* Change activity status
	*/
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$admin_info = db()->query_fetch("SELECT * FROM `".db('admin')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Change activity
		if (!empty($admin_info["id"]) && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->UPDATE("admin", array("active" => (int)!$admin_info["active"]), "`id`=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($admin_info["active"] ? 0 : 1);
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
				"name"	=> "Add admin",
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
		$pheader = _ucfirst(t($_GET["object"]));
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"	=> "",
			"edit"	=> t("Edit admin user"),
			"add"	=> t("Add admin user"),
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> t("Admin users"),
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
