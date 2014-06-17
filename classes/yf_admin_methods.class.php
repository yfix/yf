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
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
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
		$replace = array(
			'form_action'	=> $params['form_action'] ?: url_admin('/@object/@action/'. $params['links_add']),
			'back_link'		=> $params['back_link'] ?: url_admin('/@object/'. $params['links_add']),
		);
		$db = is_object($params['db']) ? $params['db'] : db();
		$table = $db->_fix_table_name($params['table']);
		if (!$table) {
			$error = 'Wrong table name';
			if ($params['return_form']) {
				return _e($error);
			} else {
				_re($error);
				return $replace;
			}
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		if (!$fields) {
			$columns = $db->meta_columns($table);
			if (isset($columns[$primary_field])) {
				unset($columns[$primary_field]);
			}
			$fields = array_keys($columns);
		}
		if (main()->is_post()) {
			if (!common()->_error_exists()) {
				$sql = array();
				foreach ((array)$fields as $f) {
					if (isset($_POST[$f])) {
						$sql[$f] = $_POST[$f];
					}
				}
				if (is_callable($params['on_before_update'])) {
					$params['on_before_update']($sql);
				}

				$db->insert($table, $db->es($sql));
				$NEW_ID = $db->insert_id();
				common()->admin_wall_add(array($_GET['object'].': added record into table '.$table, $NEW_ID));

				if (is_callable($params['on_after_update'])) {
					$params['on_after_update']($sql, $NEW_ID);
				}
				$form_action = $params['form_action'] ?: url_admin('/@object/@action/'. $params['links_add']);
				if ($NEW_ID) {
					$form_action .= '&id=' . $NEW_ID;
				}
				$form_action = str_replace( array( 'add', '_add' ), array( 'edit', '_edit' ), $form_action );
				return js_redirect( $form_action );
			} else {
				if (is_callable($params['on_error'])) {
					$params['on_error']();
				}
			}
		}
		if (is_callable($params['on_before_show'])) {
			$params['on_before_show']($_POST);
		}
		$DATA = $_POST;
		if (!$params['no_escape']) {
			$DATA = _prepare_html($DATA);
		}
		foreach ((array)$fields as $f) {
			$replace[$f] = $DATA[$f];
		}
		if ($params['return_form']) {
			return form($replace)->auto($table, $id, array(
				'links_add' => $params['links_add'],
				'db'		=> $db,
			));
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
		$replace = array(
			'form_action'	=> $params['form_action'] ?: url_admin('/@object/@action/'.urlencode($_GET['id']). '/'. $params['links_add']),
			'back_link'		=> $params['back_link'] ?: url_admin('/@object/'. $params['links_add']),
		);
		$db = is_object($params['db']) ? $params['db'] : db();
		$table = $db->_fix_table_name($params['table']);
		if (!$table) {
			$error = 'Wrong table name';
			if ($params['return_form']) {
				return _e($error);
			} else {
				_re($error);
				return $replace;
			}
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		$id = isset($params['input_'.$primary_field]) ? $params['input_'.$primary_field] : $_GET['id'];
		if (!$fields) {
			$columns = $db->meta_columns($table);
			if (isset($columns[$primary_field])) {
				unset($columns[$primary_field]);
			}
			$fields = array_keys($columns);
		}
		$a = $db->get('SELECT * FROM '.$db->es($table).' WHERE `'.$db->es($primary_field).'`="'.$db->es($id).'"');
		if (!$a) {
			$error = 'Wrong id';
			if ($params['return_form']) {
				return _e($error);
			} else {
				_re($error);
				return $replace;
			}
		}
		if (main()->is_post()) {
			if (!common()->_error_exists()) {
				$sql = array();
				foreach ((array)$fields as $f) {
					if (isset($_POST[$f])) {
						$sql[$f] = $_POST[$f];
					}
				}
				if (is_callable($params['on_before_update'])) {
					$params['on_before_update']($sql);
				}

				$db->update($table, $db->es($sql), '`'.$db->es($primary_field).'`="'.$db->es($id).'"');
				common()->admin_wall_add(array($_GET['object'].': updated record in table '.$table, $id));

				if (is_callable($params['on_after_update'])) {
					$params['on_after_update']($sql);
				}
				return js_redirect( $replace['form_action'] );
			} else {
				if (is_callable($params['on_error'])) {
					$params['on_error']();
				}
			}
		}
		$DATA = $a;
		if (is_callable($params['on_before_show'])) {
			$params['on_before_show']($DATA);
		}
		foreach ((array)$a as $k => $v) {
			if (!isset($replace[$k])) {
				$replace[$k] = $DATA[$k];
			}
		}
		if ($params['return_form']) {
			return form($replace)->auto($table, $id, array(
				'links_add' => $params['links_add'],
				'db'		=> $db,
			));
		} else {
			return $replace;
		}
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
		$db = is_object($params['db']) ? $params['db'] : db();
		$table = $db->_fix_table_name($params['table']);
		if (!$table) {
			_re('Wrong table name');
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		$id = isset($params['input_'.$primary_field]) ? $params['input_'.$primary_field] : $_GET['id'];

		if (!empty($id)) {
			if (is_callable($params['on_before_update'])) {
				$params['on_before_update']($fields);
			}

			$db->query('DELETE FROM '.$db->es($table).' WHERE `'.$db->es($primary_field).'`="'.$db->es($id).'" LIMIT 1');
			common()->admin_wall_add(array($_GET['object'].': deleted record from table '.$table, $id));

			if (is_callable($params['on_after_update'])) {
				$params['on_after_update']($fields);
			}
		}
		if (conf('IS_AJAX')) {
			echo $_GET['id'];
		} else {
			return js_redirect(url_admin('/@object/'. _add_get(). $params['links_add']));
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
		$db = is_object($params['db']) ? $params['db'] : db();
		$table = $db->_fix_table_name($params['table']);
		if (!$table) {
			_re('Wrong table name');
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		$id = isset($params['input_'.$primary_field]) ? $params['input_'.$primary_field] : $_GET['id'];

		if (!empty($id)) {
			$info = $db->query_fetch('SELECT * FROM '.$db->es($table).' WHERE `'.$db->es($primary_field).'`="'.$db->es($id).'" LIMIT 1');
		}
		if ($info) {
			if (is_callable($params['on_before_update'])) {
				$params['on_before_update']($info);
			}
			$db->update($table, array('active' => (int)!$info['active']), $db->es($primary_field).'="'.$db->es($id).'"');
			common()->admin_wall_add(array($_GET['object'].': item in table '.$table.' '.($info['active'] ? 'inactivated' : 'activated'), $id));

			if (is_callable($params['on_after_update'])) {
				$params['on_after_update']($info);
			}
		}
		if (conf('IS_AJAX')) {
			echo ($info['active'] ? 0 : 1);
		} else {
			return js_redirect(url_admin('/@object/'. _add_get(). $params['links_add']));
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
		$db = is_object($params['db']) ? $params['db'] : db();
		$table = $db->_fix_table_name($params['table']);
		if (!$table) {
			return false;
		}
		$fields	= $params['fields'];
		$primary_field = $params['id'] ? $params['id'] : 'id';
		$id = isset($params['input_'.$primary_field]) ? $params['input_'.$primary_field] : $_GET['id'];

		if (!empty($id)) {
			$info = $db->query_fetch('SELECT * FROM '.$db->es($table).' WHERE `'.$db->es($primary_field).'`="'.$db->es($id).'" LIMIT 1');
		}
		if ($info) {
			$sql = $info;
			unset($sql[$primary_field]);

			if (is_callable($params['on_before_update'])) {
				$params['on_before_update']($sql);
			}

			$db->insert($table, $db->es($sql));
			$new_id = $db->insert_id();

			common()->admin_wall_add(array($_GET['object'].': item cloned in table '.$table, $new_id));

			if (is_callable($params['on_after_update'])) {
				$params['on_after_update']($sql, $new_id);
			}
		}
		if (conf('IS_AJAX')) {
			echo ($new_id ? 1 : 0);
		} else {
			return js_redirect(url_admin('/@object/'. _add_get(). $params['links_add']));
		}
	}

	/**
	*/
	function filter_save($params = array()) {
		$filter_name = $params['filter_name'] ?: $this->_params['filter_name'];
		if (!$filter_name) {
			$filter_name = $_GET['object'].'__show';
		}
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
			// Example: &filter=admin_id:1,ip:127.0.0.1
			if (isset($_GET['filter'])) {
				foreach (explode(',', $_GET['filter']) as $item) {
					list($k,$v) = explode(':', $item);
					if ($k && isset($v)) {
						$_SESSION[$filter_name][$k] = $v;
					}
				}
			}
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		$redrect_url = $params['redirect_url'] ?: url_admin('/@object/'. str_replace($_GET['object'].'__', '', $filter_name) );
		return js_redirect($redrect_url);
	}

	/**
	*/
	function _show_filter($params = array()) {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> url_admin('/@object/filter_save/'.$filter_name),
			'clear_url'		=> url_admin('/@object/filter_save/'.$filter_name.'/clear'),
		);
		$order_fields = array();
		foreach (explode('|', 'admin_id|login|group|date|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
				'db'		=> $params['db'],
			))
			->number('admin_id')
			->text('ip')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}
}
