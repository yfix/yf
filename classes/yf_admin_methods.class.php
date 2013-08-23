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
		if (is_string($params)) {
			$params = array(
				'table' => $params,
			);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$table = db($params['table']);
		if (!$table) {
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		if (!$fields) {
			$columns = db()->meta_columns($table);
			if (isset($columns[$primary_field])) {
				unset($columns[$primary_field]);
			}
			$fields = array_keys($columns);
		}
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
				common()->admin_wall_add(array($_GET['object'].': added record into table '.$table, $NEW_ID));
				return js_redirect("./?object=".$_GET["object"]. ($NEW_ID ? "&action=edit&id=".$NEW_ID : ""). $params['links_add']);
			}
		}
		$DATA = _prepare_html($_POST);
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]. $params['links_add'],
			"back_link"		=> "./?object=".$_GET["object"]. $params['links_add'],
		);
		foreach ((array)$fields as $f) {
			$replace[$f] = $DATA[$f];
		}
		return $replace;
	}

	/**
	*/
	function edit($params = array()) {
		if (is_string($params)) {
			$params = array(
				'table' => $params,
			);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$table = db($params['table']);
		if (!$table) {
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		if (!$fields) {
			$columns = db()->meta_columns($table);
			if (isset($columns[$primary_field])) {
				unset($columns[$primary_field]);
			}
			$fields = array_keys($columns);
		}
		$a = db()->get("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."'");
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
				common()->admin_wall_add(array($_GET['object'].': updated record in table '.$table, $_GET['id']));
				return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".urlencode($_GET["id"]). $params['links_add']);
			}
		}
		$DATA = $a;
		foreach((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $_POST[$k];
			}
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".urlencode($_GET["id"]). $params['links_add'],
			"back_link"		=> "./?object=".$_GET["object"]. $params['links_add'],
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
		if (is_string($params)) {
			$params = array(
				'table' => $params,
			);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$table = db($params['table']);
		if (!$table) {
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';

		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."' LIMIT 1");
			common()->admin_wall_add(array($_GET['object'].': deleted record from table '.$table, $_GET['id']));
		}
		if (conf('IS_AJAX')) {
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get(). $params['links_add']);
		}
	}

	/**
	*/
	function active($params = array()) {
		if (is_string($params)) {
			$params = array(
				'table' => $params,
			);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$table = db($params['table']);
		if (!$table) {
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';

		if (!empty($_GET["id"])) {
			$info = db()->query_fetch("SELECT * FROM ".db()->es($table)." WHERE `".db()->es($primary_field)."`='".db()->es($_GET['id'])."' LIMIT 1");
		}
		if ($info) {
			db()->update($table, array(
				"active" => (int)!$info["active"],
			), db()->es($primary_field)."='".db()->es($_GET['id'])."'");
			common()->admin_wall_add(array($_GET['object'].': item in table '.$table.' '.($info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if (conf('IS_AJAX')) {
			echo ($info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get(). $params['links_add']);
		}
	}

	/**
	*/
	function clone_item($params = array()) {
		if (is_string($params)) {
			$params = array(
				'table' => $params,
			);
		}
		if (!is_array($params)) {
			$params = array();
		}
		$table = db($params['table']);
		if (!$table) {
			return false;
		}
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
			common()->admin_wall_add(array($_GET['object'].': item cloned in table '.$table, $new_id));
		}
		if (conf('IS_AJAX')) {
			echo ($new_id ? 1 : 0);
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get(). $params['links_add']);
		}
	}
}
