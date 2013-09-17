<?php

/**
* Core sites management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_sites {

	/**
	* Show admin users
	*/
	function show() {
		return common()->table2("SELECT * FROM ".db('sites'))
			->text("name")
			->text("web_path")
			->text("real_path")
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
		$info = db()->query_fetch("SELECT * FROM ".db('sites')." WHERE id=".intval($_GET["id"]));
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Name required", "name");
			}
			if (!common()->_error_exists()) {
				$sql = array(
					"name"			=> $_POST["name"],
					"web_path"		=> $_POST["web_path"],
					"real_path"		=> $_POST["real_path"],
					"active"		=> intval($_POST["active"]),
				);
				db()->UPDATE('sites', db()->es($sql), "id=".intval($_GET["id"]));
				common()->admin_wall_add(array('site updated: '.$info['name'].'', $info['id']));
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$DATA = $info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
#		$DATA = _prepare_html($DATA);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> $DATA["name"],
			"web_path"		=> $DATA["web_path"],
			"real_path"		=> $DATA["real_path"],
			"active"		=> $DATA["active"],
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return common()->form2($replace)
			->text("name")
			->text("web_path")
			->text("real_path")
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
					"name"			=> $_POST["name"],
					"web_path"		=> $_POST["web_path"],
					"real_path"		=> $_POST["real_path"],
					"active"		=> intval($_POST["active"]),
				);
				db()->INSERT('sites', db()->es($sql));
				$NEW_ID = db()->INSERT_ID();
				common()->admin_wall_add(array('site added: '.$_POST['name'], $NEW_ID));
				return js_redirect("./?object=".$_GET["object"].($NEW_ID ? "&action=edit&id=".$NEW_ID : ""));
			}
		}
		if (!isset($_POST["active"])) {
			$_POST["active"] = 1;
		}
#		$_POST = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> $DATA["name"],
			"web_path"		=> $DATA["web_path"],
			"real_path"		=> $DATA["real_path"],
			"active"		=> $DATA["active"],
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return common()->form2($replace)
			->text("name")
			->text("web_path")
			->text("real_path")
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query("DELETE FROM ".db('sites')." WHERE id=".intval($_GET['id']));
			common()->admin_wall_add(array('site deleted: '.$_GET['id'].'', $_GET['id']));
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
			$info = db()->query_fetch("SELECT * FROM ".db('sites')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($info["id"])) {
			db()->UPDATE('sites', array("active" => (int)!$info["active"]), "id=".intval($_GET["id"]));
			common()->admin_wall_add(array('site '.$info['name'].' '.($info['active'] ? 'inactivated' : 'activated'), $info['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	function _hook_widget__sites_list ($params = array()) {
// TODO
	}
}
