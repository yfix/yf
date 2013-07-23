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
					if (isset($_POST[$f])) {
						$sql[$f] = $_POST[$f];
					}
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

		$a = db()->get("SELECT * FROM ".$table." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."'");
		if (!$a) {
			return _e('Wrong id');
		}
		if (!empty($_POST)) {
			if (!common()->_error_exists()) {
				$sql = array();
				foreach ((array)$fields as $f) {
					if (isset($_POST[$f])) {
						$sql[$f] = $_POST[$f];
					}
				}
				db()->update($table, db()->es($sql), "`".db()->es($primary_field)."`='".db()->es($_GET["id"])."'");
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
		foreach ((array)$a as $k => $v) {
			if (!isset($replace[$k])) {
				$replace[$k] = $DATA[$k];
			}
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

		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."' LIMIT 1");
		}
		if (conf('IS_AJAX')) {
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	*/
	function active($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';

		if (!empty($_GET["id"])) {
			$info = db()->query_fetch("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."' LIMIT 1");
		}
		if ($info) {
			db()->update($table, array(
				"active" => (int)!$info["active"],
			), db()->es($primary_field)."='".db()->es($_GET['id'])."'");
		}
		if (conf('IS_AJAX')) {
			echo ($info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	*/
	function clone_item($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';

		if (!empty($_GET["id"])) {
			$info = db()->query_fetch("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."' LIMIT 1");
		}
		if ($info) {
			$sql = $info;
			unset($sql[$primary_field]);

			db()->insert($table, db()->es($sql));
			$new_id = db()->insert_id();
		}
		if (conf('IS_AJAX')) {
			echo ($new_id ? 1 : 0);
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	*/
	function sortable($params = array()) {
		if (!is_array($params)) {
			return false;
		}
		$table	= $params['table'];
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		if ($_POST['first'] && $_POST['second']) {
			$first	= db()->query_fetch("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_POST['first'])."' LIMIT 1");
			$second	= db()->query_fetch("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_POST['second'])."' LIMIT 1");
		}
		if (!$first || !$second) {
			return _e('Wrong first or second id to swap');
		}
// TODO
//		db()->update($table, array("order" => $new_order_first), db()->es($primary_field)."='".db()->es($first[$primary_field])."'");
//		db()->update($table, array("order" => $new_order_second), db()->es($primary_field)."='".db()->es($second[$primary_field])."'");

		return js_redirect("./?object=".$_GET["object"]. _add_get());
	}
}
