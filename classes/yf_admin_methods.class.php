<?php

/**
* Common admin methods hidden by simple api
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_methods {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Need to avoid calling render() without params
	*/
//	function __toString() {
//		return $this->render();
//	}

	/**
	*/
	function add($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		if (!empty($_POST)) {
			if (!common()->_error_exists()) {
				$sql = array();
				foreach ((array)$fields as $f) {
					$sql[$f] = $_POST[$f];
				}
				db()->insert($table, db()->es($sql));
				$NEW_ID = db()->insert_id();
				return js_redirect("./?object=".$_GET["object"]. ($NEW_ID ? "&action=edit&id=".$NEW_ID : ""));
			}
		}
		$DATA = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"back_link"		=> "./?object=".$_GET["object"],
		);
		foreach ((array)$fields as $f) {
			$replace[$f] = $DATA[$f];
		}
		return $replace;
	}

	/**
	*/
	function edit($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';

		$a = db()->get("SELECT * FROM ".$table." WHERE '".db()->es($primary_field)."'='".db()->es($_GET['id']).'"');
		if (!empty($_POST)) {
			if (!common()->_error_exists()) {
				$sql = array();
				foreach ((array)$fields as $f) {
					$sql[$f] = $_POST[$f];
				}
				db()->update($table, db()->es($sql), "`".$primary_field."`='".db()->es($_GET["id"])."'");
				return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".urlencode($_GET["id"]));
			}
		}
		$DATA = $a;
		foreach((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $_POST[$k];
			}
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".urlencode($_GET["id"]),
			"back_link"		=> "./?object=".$_GET["object"],
		);
		foreach ((array)$fields as $f) {
			$replace[$f] = $DATA[$f];
		}
		return $replace;
	}

	/**
	*/
	function delete($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
/*
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->query("DELETE FROM ".db('admin')." WHERE id=".intval($_GET['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
*/
	}

	/**
	*/
	function active ($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
/*
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($admin_info["id"]) && $_GET["id"] != 1 && $_GET["id"] != $_SESSION["admin_id"]) {
			db()->UPDATE("admin", array("active" => (int)!$admin_info["active"]), "id=".intval($_GET["id"]));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($admin_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
*/
	}

	/**
	*/
	function clone_item ($params = array()) {
// TODO
/*
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		$block_info = db()->query_fetch("SELECT * FROM ".db('blocks')." WHERE id=".intval($_GET["id"]));
		$sql = $block_info;
		unset($sql["id"]);
		$sql["name"] = $sql["name"]."_clone";

		db()->INSERT("blocks", $sql);
		$NEW_BLOCK_ID = db()->INSERT_ID();

		$Q = db()->query("SELECT * FROM ".db('block_rules')." WHERE block_id=".intval($_GET["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			unset($_info["id"]);
			$_info["block_id"] = $NEW_BLOCK_ID;

			db()->INSERT("block_rules", $_info);

			$NEW_ITEM_ID = db()->INSERT_ID();
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		return js_redirect("./?object=".$_GET["object"]);
*/
	}
}
