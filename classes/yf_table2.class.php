<?php

/**
* Table2, using bootstrap html/css framework
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_table2 {

	/* Example:
		return table2("SELECT * FROM ".db('admin'))
			->text("login")
			->text("first_name")
			->text("last_name")
			->link("group", "./?object=admin_groups&action=edit&id=%d", $this->_admin_groups)
			->date("add_date")
			->text("go_after_login")
			->btn_active()
			->btn_edit()
			->btn_delete()
			->btn("log_auth")
			->footer_link("Failed auth log", "./?object=log_admin_auth_fails_viewer")
			->footer_link("Add", "./?object=".$_GET["object"]."&action=add");
	*/

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	* Setup form2 class instance to share its methods for form-related components like checkbox, input, etc
	*/
	function _init_form() {
		if (!isset($this->_form)) {
			$this->_form = clone _class('form2');
			$this->_form->_chained_mode = false;
		}
		return $this->_form;
	}

	/**
	* Render result table html, gathered by row functions
	*/
	function render($params = array()) {
		$sql = $this->_sql;
		$ids = array();
		if (is_array($sql)) {
			$data = $sql;
			$pages = "";
			$total = count($data);
			$ids = array_keys($data);
		} else {
			$db = is_object($params['db']) ? $params['db'] : db();
			$pager_path = $params['pager_path'] ? $params['pager_path'] : "";
			$pager_type = $params['pager_type'] ? $params['pager_type'] : "blocks";
			$pager_records_on_page = $params['pager_records_on_page'] ? $params['pager_records_on_page'] : 0;
			$pager_num_records = $params['pager_num_records'] ? $params['pager_num_records'] : 0;
			$pager_stpl_path = $params['pager_stpl_path'] ? $params['pager_stpl_path'] : "";
			$pager_add_get_vars = $params['pager_add_get_vars'] ? $params['pager_add_get_vars'] : 1;

			if ($this->_params['filter']) {
				$filter_sql = $this->_filter_sql_prepare($this->_params['filter']);
			}
			if ($filter_sql) {
				$sql .= (strpos(strtoupper($sql), 'WHERE') === false ? " WHERE " : "")." ".$filter_sql;
			}
			list($add_sql, $pages, $total) = common()->divide_pages($sql, $pager_path, $pager_type, $pager_records_on_page, $pager_num_records, $pager_stpl_path, $pager_add_get_vars);

			$items = array();
			$q = $db->query($sql. $add_sql);
			while ($a = $db->fetch_assoc($q)) {
				if (isset($a["id"])) {
					$data[$a["id"]] = $a;
					$ids[$a["id"]] = $a["id"];
				} else {
					$data[] = $a;
				}
			}
		}
		/*
		* Fill data array with custom fields, also fitting slots with empty strings where no custom data. Example:
		* 	table2('SELECT * FROM '.db('user'), array('custom_fields'	=> array(
		*		'num_logins' => 'SELECT user_id, COUNT(*) AS num FROM '.db('log_user_auth').' WHERE user_id IN(%ids) GROUP BY user_id'
		*		'num_auth_fails' => 'SELECT user_id, COUNT(*) AS num FROM '.db('log_user_auth_fails').' WHERE user_id IN(%ids) GROUP BY user_id'
		* 	)))
		*	->text('name')
		*	->text('num_logins')
		*	->text('num_auth_fails')
		*/
		if ($data && $ids && $this->_params['custom_fields']) {
			$ids_sql = implode(',', $ids);
			foreach ((array)$this->_params['custom_fields'] as $custom_name => $custom_sql) {
				$this->_data_sql_names[$custom_name] = db()->get_2d(str_replace('%ids', $ids_sql, $custom_sql));
			}
			foreach ((array)$data as $_id => $row) {
				foreach ((array)$this->_data_sql_names as $custom_name => $custom_data) {
					$data[$_id][$custom_name] = strval($custom_data[$_id]);
				}
			}
		}
		if ($data) {
			if ($this->_params['form']) {
				$fparams = $this->_params['form'];
				$form = $this->_init_form();
				$body .= $form->form_begin($fparams['name'], $fparams['method'], $fparams, $fparams['replace']);
			}
			$body = '<table class="table table-bordered table-striped table-hover'.(isset($params['table_class']) ? ' '.$params['table_class'] : '').'"'.(isset($params['table_attr']) ? ' '.$params['table_attr'] : '').'>'.PHP_EOL;
			if (!$this->_params['no_header']) {
				$body .= '<thead>'.PHP_EOL;
				foreach ((array)$this->_fields as $info) {
					$name = $info['name'];
					$body .= '<th>'.t($info['desc']).'</th>'.PHP_EOL;
				}
				if ($this->_buttons) {
					$body .= '<th>'.t('Actions').'</th>'.PHP_EOL;
				}
				$body .= '</thead>'.PHP_EOL;
			}
			$sortable_url = $this->_params['sortable'];
			if ($sortable_url && strlen($sortable_url) <= 5) {
				$sortable_url = './?object='.$_GET['object'].'&action=sortable';
			}
			$body .= '<tbody'.($sortable_url ? ' class="sortable" data-sortable-url="'.htmlspecialchars($sortable_url).'"' : '').'>'.PHP_EOL;
			foreach ((array)$data as $_id => $row) {
				$body .= '<tr'.(isset($params['tr'][$_id]) ? ' '.$params['tr'][$_id] : '').'>'.PHP_EOL;
				foreach ((array)$this->_fields as $info) {
					$name = $info['name'];
					if (!isset($row[$name])) {
						continue;
					}
					$func = $info['func'];
					unset($info['func']); // Save resources
					$body .= '<td>'.$func($row[$name], $info, $row).'</td>'.PHP_EOL;
				}
				if ($this->_buttons) {
					$body .= '<td nowrap>';
					foreach ((array)$this->_buttons as $info) {
						$name = $info['name'];
						$func = $info['func'];
						unset($info['func']); // Save resources
						$body .= $func($row, $info).PHP_EOL;
					}
					$body .= '</td>'.PHP_EOL;
				}
				$body .= '</tr>'.PHP_EOL;
			}
			$body .= '</tbody>'.PHP_EOL;
			if ($this->_params['caption']) {
				$body .= '<caption>'.t('Total records:').':'.$total.'</caption>'.PHP_EOL;
			}
			$body .= '</table>'.PHP_EOL;
			if ($this->_params['form']) {
				$body .= '</form>';
			}
		} else {
			$body .= '<div class="alert alert-info">'.t('No records').'</div>'.PHP_EOL;
		}
		foreach ((array)$this->_footer_links as $info) {
			$name = $info['name'];
			$func = $info['func'];
			unset($info['func']); // Save resources
			$body .= $func($info).PHP_EOL;
		}
		$body .= $pages.PHP_EOL;
		return $body;
	}

	/**
	* Wrapper for chained mode call from common()->table2()
	*/
	function chained_wrapper($sql = "", $params = array()) {
		$this->_chained_mode = true;
		$this->_sql = $sql;
		$this->_params = $params;
		return $this;
	}

	/**
	* Wrapper for template engine
	*/
	function tpl_row($type = "input", $name, $desc = "", $extra = array()) {
		return $this->$type($name, $desc, $extra);
	}

	/**
	*/
	function _filter_sql_prepare($filter_data = array()) {
		if (!$filter_data) {
			return "";
		}
		$special_fields = array(
			'order_by',
			'order_direction',
		);
		foreach((array)$filter_data as $k => $v) {
			if (in_array($k, $special_fields)) {
				continue;
			}
			if (!strlen($k) || !strlen($v)) {
				continue;
			}
			$sql[] = '`'.db()->es($k).'`='.(is_numeric($v) ? intval($v) : '"'.db()->es($v).'"');
		}
		$filter_sql = implode(" AND ", $sql);
		if ($filter_data['order_by']) {
			$filter_sql .= ' ORDER BY `'.db()->es($filter_data['order_by']).'` ';
			if ($filter_data['order_direction']) {
				$direction = strtoupper($filter_data['order_direction']);
			}
			if ($direction && in_array($direction, array('ASC','DESC'))) {
				$filter_sql .= ' '.$direction;
			}
		}
		return $filter_sql;
	}

	/**
	* Supported: success, warning, important, info, inverse
	* Also support array of badges/labels/classes where will try to find match for a field value
	*/
	function _apply_badges($text, $extra = array(), $field = null) {
		if ($extra['badge']) {
			$badge = is_array($extra['badge']) && isset($extra['badge'][$field]) ? $extra['badge'][$field] : $extra['badge'];
			if ($badge) {
				$text = '<span class="badge badge-'.$badge.'">'.$text.'</span>';
			}
		} elseif ($extra['label']) {
			$label = is_array($extra['label']) && isset($extra['label'][$field]) ? $extra['label'][$field] : $extra['label'];
			if ($label) {
				$text = '<span class="label label-'.$label.'">'.$text.'</span>';
			}
		} elseif ($extra['class']) {
			$css_class = is_array($extra['class']) && isset($extra['class'][$field]) ? $extra['class'][$field] : $extra['class'];
			if ($css_class) {
				$text = '<span class="'.$css_class.'">'.$text.'</span>';
			}
		}
		return $text;
	}

	/**
	*/
	function text($name, $desc = "", $extra = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"data"	=> t($extra['data']),
			"func"	=> function($field, $params, $row) {
				if (!$params['data'] && $params['extra']['data_name']) {
					$params['data'] = _class('table2')->_data_sql_names[$params['extra']['data_name']];
				}
				if (!$params['data']) {
					$text = $field;
				} else {
					if (is_string($params['data'])) {
						$text = $params['data'];
					} else {
						$text = (isset($params['data'][$field]) ? $params['data'][$field] : $field);
					}
				}
				return _class('table2')->_apply_badges($text, $params['extra'], $field);
			}
		);
		return $this;
	}

	/**
	*/
	function link($name, $link = "", $data = "", $extra = array()) {
		if (isset($extra['desc'])) {
			$desc = $extra['desc'];
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"link"	=> $link,
			"data"	=> t($data),
			"func"	=> function($field, $params, $row) {
				if (!$params['data']) {
					$text = (isset($row[$field]) ? $row[$field] : $field);
				} else {
					if (is_string($params['data'])) {
						$text = $params['data'];
					} else {
						$text = (isset($params['data'][$field]) ? $params['data'][$field] : $field);
					}
				}
				$body = '<a href="'.str_replace('%d', urlencode($field), $params['link']).'" class="btn btn-mini">'.str_replace(" ", "&nbsp;", $text).'</a>';
				return _class('table2')->_apply_badges($body, $params['extra'], $field);
			}
		);
		return $this;
	}

	/**
	*/
	function date($name, $desc = "", $extra = array()) {
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> function($field, $params, $row) {
				$text = str_replace(' ', '&nbsp;', _format_date($field, $params['desc']));
				return _class('table2')->_apply_badges($text, $params['extra'], $field);
			}
		);
		return $this;
	}

	/**
	*/
	function image($path, $link = "", $extra = array()) {
		$name = 'image';
		$this->_fields[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"path"	=> $path,
			"link"	=> $link,
			"func"	=> function($field, $params, $row) {
				$id = $row['id'];
				// Make 3-level dir path
				$d = sprintf("%09s", $id);
				$replace = array(
					'{subdir1}'	=> substr($d, 0, -6),
					'{subdir2}'	=> substr($d, -6, 3),
					'{subdir3}'	=> substr($d, -3, 3),
					'%d'		=> $id,
				);
				$img_path = str_replace(array_keys($replace), array_values($replace), $params['path']);
				if (!file_exists(PROJECT_PATH. $img_path)) {
					return '';
				}
				$link_url = str_replace(array_keys($replace), array_values($replace), $params['link']);
				return ($link_url ? '<a href="'.$link_url.'">' : '')
					.'<img src="'.WEB_PATH. $img_path.'">'
					.($link_url ? '</a>' : '');
			}
		);
		return $this;
	}

	/**
	*/
	function func($name, $func, $extra = array()) {
		if (!$desc && isset($extra['desc'])) {
			$desc = $extra['desc'];
		}
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> $func,
		);
		return $this;
	}

	/**
	*/
	function btn($name, $link, $extra = array()) {
		$this->_buttons[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$override_id = "";
				if (isset($params['extra']['id'])) {
					$override_id = $params['extra']['id'];
				}
				if (isset(_class('table2')->_params['id'])) {
					$override_id = _class('table2')->_params['id'];
				}
				$id = $override_id ? $override_id : 'id';
				$a_class = ($params['extra']['a_class'] ? ' '.$params['extra']['a_class'] : '');
				$icon = ($params['extra']['icon'] ? ' '.$params['extra']['icon'] : 'icon-tasks');
				return '<a href="'.str_replace('%d', urlencode($row[$id]), $params['link']).'" class="btn btn-mini'.$a_class.'"><i class="'.$icon.'"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_edit($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "Edit";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=edit&id=%d";
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['no_ajax']) {
			$extra['a_class'] .= ' ajax_edit';
		}
		$extra['icon'] .= 'icon-edit';
		return $this->btn($name, $link, $extra);
	}

	/**
	*/
	function btn_delete($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "Delete";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=delete&id=%d";
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['no_ajax']) {
			$extra['a_class'] .= ' ajax_delete';
		}
		$extra['icon'] .= 'icon-trash';
		return $this->btn($name, $link, $extra);
	}

	/**
	*/
	function btn_clone($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "Clone";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=clone_item&id=%d";
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['no_ajax']) {
			$extra['a_class'] .= ' ajax_clone';
		}
		$extra['icon'] .= 'icon-arrow-down';
		return $this->btn($name, $link, $extra);
	}

	/**
	*/
	function btn_view($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "View";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=view&id=%d";
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$extra['no_ajax']) {
			$extra['a_class'] .= ' ajax_view';
		}
		$extra['icon'] .= 'icon-eye-open';
		return $this->btn($name, $link, $extra);
	}

	/**
	*/
	function btn_active($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "Active";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=active&id=%d";
		}
		$this->_buttons[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', urlencode($row[$id]), $params['link']).'" class="change_active">'
						.($row['active'] ? '<span class="label label-success">'.t('Active').'</span>' : '<span class="label label-warning">'.t('Disabled').'</span>')
					.'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function footer_link($name, $link, $extra = array()) {
		$this->_footer_links[] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				$icon = ($params['extra']['icon'] ? ' '.$params['extra']['icon'] : 'icon-tasks');
				$a_class = ($params['extra']['a_class'] ? ' '.$params['extra']['a_class'] : '');
				return '<a href="'.str_replace('%d', urlencode($row[$id]), $params['link']).'" class="btn btn-mini'.$a_class.'"><i class="'.$icon.'"></i> '.t($params['name']).'</a> ';
			}
		);
		return $this;
	}

	/**
	*/
	function footer_add($name = "", $link = "", $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = "add";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=add";
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['a_class'] .= ' ajax_add';
		$extra['icon'] .= 'icon-plus';
		return $this->footer_link($name, $link, $extra);
	}

	/**
	*/
	function check_box($extra = array()) {
// TODO
//		$form = $this->_init_form();
//		return $this->func('id', function($field, $params, $row) { return $obj; } );
	}

	/**
	*/
	function select_box($extra = array()) {
// TODO
//		$form = $this->_init_form();
//		return $this->func('id', function($field, $params, $row) { return $obj; } );
	}

	/**
	*/
	function radio_box($extra = array()) {
// TODO
//		$form = $this->_init_form();
//		return $this->func('id', function($field, $params, $row) { return $obj; } );
	}

	/**
	*/
	function input($extra = array()) {
// TODO
//		$form = $this->_init_form();
//		return $this->func('id', function($field, $params, $row) { return $obj; } );
	}

	/**
	*/
	function _show_tip($value = "", $extra = array()) {
// TODO: connect 2 kind of tips args to all funcs: "tip" - near field value, "header_tip" - for table header, 
// TODO: also add ability to pass tips array into table2() params like "data"
		return _class('graphics')->_show_help_tip(array(
			"tip_id"	=> $value,
//			"replace"	=> $extra[],
		));
	}
}
