<?php

/**
* Admin users manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin {

	/**
	*/
	function _init () {
		$this->_admin_groups = array();
		$Q = db()->query("SELECT id,name FROM ".db('admin_groups')." WHERE active='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_admin_groups[$A['id']] = $A['name'];
		}
		$this->_boxes = array(
			"group"		=> 'select_box("group",	$this->_admin_groups,	$selected, false, 2, "", false)',
			"active"	=> 'radio_box("active",	$this->_statuses,		$selected, false, 2, "", false)',
		);
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
	}

	/**
	* Show admin users
	*/
	function show() {
		return common()->table2("SELECT * FROM ".db('admin'))
			->text("login")
			->text("first_name")
			->text("last_name")
			->link("group", "./?object=admin_groups&action=edit&id=%d", $this->_admin_groups)
			->date("add_date")
			->text("go_after_login")
			->btn_active()
			->btn_edit()
			->btn_delete()
			->btn("log_auth", "./?object=log_admin_auth_view&action=show_for_admin&id=%d")
			->footer_link("Failed auth log", "./?object=log_admin_auth_fails_viewer")
			->footer_add();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (!$_GET["id"]) {
			return "No id!";
		}
		$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_GET["id"]));
		if ($_POST) {
			$_POST["login"] = preg_replace("/[^a-z0-9\_\-\.]/ims", "", $_POST["login"]);
			if (!$_POST["login"]) {
				_re("Login required!", "login");
			}
			if (!_ee()) {
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
				db()->update("admin", $sql, "id=".intval($_GET["id"]));
				common()->admin_wall_add(array('admin account edited: '.$_POST['login'].'', $_GET['id']));
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
			"for_edit"		=> 1,
			"login"			=> $DATA["login"],
			"password"		=> "",
			"first_name"	=> $DATA["first_name"],
			"last_name"		=> $DATA["last_name"],
			"go_after_login"=> $DATA["go_after_login"],
			"group_box"		=> $this->_box("group", $DATA["group"]),
			"active"		=> $DATA["active"],
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
			"groups_link"	=> "./?object=admin_groups",
			"add_date"		=> _format_date($admin_info["add_date"], "full"),
		);
		return common()->form2($replace)
			->text("login")
			->text("password")
			->text("first_name")
			->text("last_name")
			->text("go_after_login","Url after login")
			->box_with_link("group_box","Group","groups_link")
			->active_box()
			->info("add_date","Added")
			->save_and_back();
	}

	/**
	*/
	function add() {
		if ($_POST) {
			$_POST["login"] = preg_replace("/[^a-z0-9\_\-\.]/ims", "", $_POST["login"]);
			if (!$_POST["login"]) {
				_re("Login required!", "login");
			}
			if (!strlen($_POST["password"])) {
				_re("Password required!", "password");
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
				db()->insert("admin", $sql);
				common()->admin_wall_add(array('admin account added: '.$_POST['login'].'', db()->insert_id()));
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		if (!isset($_POST["active"])) {
			$_POST["active"] = 1;
		}
		$_POST = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"for_edit"		=> 0,
			"login"			=> $_POST["login"],
			"password"		=> $_POST["password"],
			"first_name"	=> $_POST["first_name"],
			"last_name"		=> $_POST["last_name"],
			"go_after_login"=> $_POST["go_after_login"],
			"group_box"		=> $this->_box("group", $_POST["group"]),
			"active"		=> $DATA["active"],
			"active_box"	=> $this->_box("active", $_POST["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
			"groups_link"	=> "./?object=admin_groups",
		);
		return common()->form2($replace)
			->text("login")
			->text("password")
			->text("first_name")
			->text("last_name")
			->text("go_after_login","Url after login")
			->box_with_link("group_box","Group","groups_link")
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->query("DELETE FROM ".db('admin')." WHERE id=".intval($_GET['id']));
			common()->admin_wall_add(array('admin account deleted', $_GET['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	*/
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($admin_info["id"]) && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->UPDATE("admin", array("active" => (int)!$admin_info["active"]), "id=".intval($_GET["id"]));
			common()->admin_wall_add(array('admin account '.($admin_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($admin_info["active"] ? 0 : 1);
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

	function _hook_widget__admin_accounts ($params = array()) {
// TODO
	}
}
