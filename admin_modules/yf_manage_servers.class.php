<?php

/**
* Core servers management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_servers {

	/**
	* Show admin users
	*/
	function show() {
		return table("SELECT * FROM ".db('core_servers'))
			->text("name")
			->text("comment")
			->text("hostname")
			->text("ip")
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_link("Add", "./?object=".$_GET["object"]."&action=add");
	}

	/**
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		if (!$_GET["id"]) {
			return "No id!";
		}
		$info = db()->query_fetch("SELECT * FROM ".db('core_servers')." WHERE id=".intval($_GET["id"]));
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Name required", "name");
			}
			if (!common()->_error_exists()) {
				$sql = array(
					"name"		=> $_POST["name"],
					"comment"	=> $_POST["comment"],
					"hostname"	=> $_POST["hostname"],
					"ip"		=> $_POST["ip"],
					"active"	=> intval($_POST["active"]),
				);
				db()->UPDATE('core_servers', db()->es($sql), "id=".intval($_GET["id"]));
				common()->admin_wall_add(array('server updated: '.$info['name'], $info['id']));
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$DATA = $info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> $DATA["name"],
			"comment"		=> $DATA["comment"],
			"hostname"		=> $DATA["hostname"],
			"ip"			=> $DATA["ip"],
			"active"		=> $DATA["active"],
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return form($replace)
			->text("name")
			->textarea("comment")
			->text("hostname")
			->text("ip")
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Name required", "name");
			}
			if (!common()->_error_exists()) {
				$sql = array(
					"name"		=> $_POST["name"],
					"comment"	=> $_POST["comment"],
					"hostname"	=> $_POST["hostname"],
					"ip"		=> $_POST["ip"],
					"active"	=> intval($_POST["active"]),
				);
				db()->INSERT('core_servers', db()->es($sql));
				$NEW_ID = db()->INSERT_ID();
				common()->admin_wall_add(array('server added: '.$_POST['name'], $NEW_ID));
				return js_redirect("./?object=".$_GET["object"].($NEW_ID ? "&action=edit&id=".$NEW_ID : ""));
			}
		}
		if (!isset($_POST["active"])) {
			$_POST["active"] = 1;
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> $DATA["name"],
			"comment"		=> $DATA["comment"],
			"hostname"		=> $DATA["hostname"],
			"ip"			=> $DATA["ip"],
			"active"		=> $DATA["active"],
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return form($replace)
			->text("name")
			->textarea("comment")
			->text("hostname")
			->text("ip")
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query("DELETE FROM ".db('core_servers')." WHERE id=".intval($_GET['id']));
			common()->admin_wall_add(array('server deleted '.$_GET['id'], $info['id']));
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
			$info = db()->query_fetch("SELECT * FROM ".db('core_servers')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($info["id"])) {
			db()->UPDATE('core_servers', array("active" => (int)!$info["active"]), "id=".intval($_GET["id"]));
			common()->admin_wall_add(array('server '.$info['name'].' '.($info['active'] ? 'inactivated' : 'activated'), $info['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	function _hook_widget__servers_list ($params = array()) {
// TODO
	}
}
