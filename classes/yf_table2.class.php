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
	* Enabling automatic fields parsing mode
	*/
	function auto() {
		$this->_params['auto'] = true;
		return $this;
	}

	/**
	* Render result table html, gathered by row functions
	*/
	function render($params = array()) {
		// Merge params passed to table2() and params passed here, with params here have more priority:
		$tmp = $this->_params;
		foreach ((array)$params as $k => $v) {
			$tmp[$k] = $v;
		}
		$params = $tmp;
		unset($tmp);

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

			if ($params['filter']) {
				$filter_sql = $this->_filter_sql_prepare($params['filter'], $params['filter_params']);
			}
			if ($filter_sql) {
				$sql .= (strpos(strtoupper($sql), 'WHERE') === false ? " WHERE 1 " : "")." ".$filter_sql;
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
		// Automatically get fields from results
		if ($params['auto']) {
			$field_names = array_keys(current($data));
			foreach ((array)$field_names as $f) {
				$this->text($f);
			}
			$this->btn_edit();
			$this->btn_delete();
			$this->footer_add();
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
		if ($data && $ids && $params['custom_fields']) {
			$ids_sql = implode(',', $ids);
			foreach ((array)$params['custom_fields'] as $custom_name => $custom_sql) {
				$this->_data_sql_names[$custom_name] = db()->get_2d(str_replace('%ids', $ids_sql, $custom_sql));
			}
			foreach ((array)$data as $_id => $row) {
				foreach ((array)$this->_data_sql_names as $custom_name => $custom_data) {
					$data[$_id][$custom_name] = strval($custom_data[$_id]);
				}
			}
			// Needed to correctly pass inside $instance_params to each function
			$params['data_sql_names'] = $this->_data_sql_names;
		}
		if ($data) {
			if ($params['form']) {
				$fparams = $params['form'];
				$form = $this->_init_form();
				$body .= $form->form_begin($fparams['name'], $fparams['method'], $fparams, $fparams['replace']);
			}
			if ($params['condensed']) {
				$params['table_class'] .= ' table-condensed';
			}
			$body = '<table class="table table-bordered table-striped table-hover'
				.(isset($params['table_class']) ? ' '.$params['table_class'] : '').'"'
				.(isset($params['table_attr']) ? ' '.$params['table_attr'] : '').'>'.PHP_EOL;
			if (!$params['no_header']) {
				$body .= '<thead>'.PHP_EOL;
				foreach ((array)$this->_fields as $info) {
					$name = $info['name'];
					$th_width = ($info['extra']['width'] ? ' width="'.$info['extra']['width'].'"' : '');
					$th_icon_prepend = ($params['th_icon_prepend'] ? '<i class="icon icon-'.$params['th_icon_prepend'].'"></i> ' : '');
					$th_icon_append = ($params['th_icon_append'] ? ' <i class="icon icon-'.$params['th_icon_append'].'"></i>' : '');

					$body .= '<th'.$th_width.'>'. $th_icon_prepend. t($info['desc']). $th_icon_prepend. '</th>'.PHP_EOL;
				}
				if ($this->_buttons) {
					$body .= '<th>'.t('Actions').'</th>'.PHP_EOL;
				}
				$body .= '</thead>'.PHP_EOL;
			}
			$sortable_url = $params['sortable'];
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
					$td_width = ($info['extra']['width'] ? ' width="'.$info['extra']['width'].'"' : '');

					$body .= '<td'.$td_width.'>'.$func($row[$name], $info, $row, $params).'</td>'.PHP_EOL;
				}
				if ($this->_buttons) {
					$body .= '<td nowrap>';
					foreach ((array)$this->_buttons as $info) {
						$name = $info['name'];
						$func = $info['func'];
						unset($info['func']); // Save resources

						$body .= $func($row, $info, $params).PHP_EOL;
					}
					$body .= '</td>'.PHP_EOL;
				}
				$body .= '</tr>'.PHP_EOL;
			}
			$body .= '</tbody>'.PHP_EOL;
			if ($params['caption']) {
				$body .= '<caption>'.t('Total records:').':'.$total.'</caption>'.PHP_EOL;
			}
			$body .= '</table>'.PHP_EOL;
			if ($params['form']) {
				$body .= '</form>';
			}
		} else {
			$body .= '<div class="alert alert-info">'.t('No records').'</div>'.PHP_EOL;
		}
		foreach ((array)$this->_footer_links as $info) {
			$name = $info['name'];
			$func = $info['func'];
			unset($info['func']); // Save resources
			$body .= $func($info, $params).PHP_EOL;
		}
		$body .= $pages.PHP_EOL;
		return $body;
	}

	/**
	*/
	function _filter_sql_prepare($filter_data = array(), $filter_params = array()) {
		if (!$filter_data) {
			return "";
		}
		$special_fields = array(
			'order_by',
			'order_direction',
		);
		$supported_conds = array(
			"eq"		=> function($a){ return ' = "'._es($a['value']).'"'; }, // "equal"
			"ne"		=> function($a){ return ' != "'._es($a['value']).'"'; }, // "not equal"
			"gt"		=> function($a){ return ' > "'._es($a['value']).'"'; }, // "greater than",
			"gte"		=> function($a){ return ' >= "'._es($a['value']).'"'; }, // "greater or equal than",
			"lt"		=> function($a){ return ' < "'._es($a['value']).'"'; }, // "less than",
			"lte"		=> function($a){ return ' <= "'._es($a['value']).'"'; }, // "lower or equal than"
			"like"		=> function($a){ return ' LIKE "%'._es($a['value']).'%"'; }, // LIKE '%'.$value.'%'
			"rlike"		=> function($a){ return ' RLIKE "'._es($a['value']).'"'; }, // regular expression, RLIKE $value
			"between"	=> function($a){ return ' BETWEEN "'._es($a['value']).'" AND "'._es($a['and']).'"'; }, // BETWEEN $min AND $max
		);
		foreach((array)$filter_data as $k => $v) {
			if (!strlen($k)) {
				continue;
			}
			if (in_array($k, $special_fields)) {
				continue;
			}
			// Special field for BETWEEN second value
			if (substr($k, -strlen('__and')) == '__and') {
				continue;
			}
			$part_on_the_right = "";
			// Here we support complex filtering conditions, examples:
			// 'price' => array('gt', 'value' => '100')
			// 'price' => array('between', 'value' => '1', 'and' => '10')
			// 'name' => array('like', 'value' => 'john')
			if (is_array($v)) {
				$cond = $v[0];
				if (!isset($supported_conds[$cond])) {
					continue;
				}
				if (!isset($v['and'])) {
					$v['and'] == $filter_data[$k.'__and'];
				}
				$part_on_the_right = $supported_conds[$cond]($v);
			} else {
				if (!strlen($v)) {
					continue;
				}
				$cond = 'eq';
				// Here we can override default "eq" condition with custom one by passing additional param to table2. 
				// example: table2($sql, array('filter_params' => array('name' => 'gt', 'price' => 'between'), 'filter' => $_SESSION[__CLASS__]))
				if (isset($filter_params[$k]) && isset($supported_conds[$filter_params[$k]])) {
					$cond = $filter_params[$k];
				}
				// Field with __and on the end of its name is special one for "between" condition
				$part_on_the_right = $supported_conds[$cond](array('value' => $v, 'and' => $filter_data[$k.'__and']));
			}
			$sql[] = '`'.db()->es($k).'`'.$part_on_the_right;
		}
		if ($sql) {
			$filter_sql = " AND ".implode(" AND ", $sql);
		}
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
			"link"	=> $extra['link'],
			"data"	=> t($extra['data']),
			"func"	=> function($field, $params, $row, $instance_params) {
				if (!$params['data'] && $params['extra']['data_name']) {
					$params['data'] = $instance_params['data_sql_names'][$params['extra']['data_name']];
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
				if ($params['link']) {
					$link = str_replace('%d', urlencode($field), $params['link']). $instance_params['links_add'];
					$body = '<a href="'.$link.'" class="btn btn-mini">'.str_replace(" ", "&nbsp;", $text).'</a>';
				} else {
					$body = $text;
				}
				return _class('table2')->_apply_badges($body, $params['extra'], $field);
			}
		);
		return $this;
	}

	/**
	*/
	function link($name, $link = "", $data = "", $extra = array()) {
		$extra['link'] = $link;
		$extra['data'] = $data;
		return $this->text($name, $extra['desc'], $extra);
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
			"func"	=> function($field, $params, $row, $instance_params) {
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
			"func"	=> function($field, $params, $row, $instance_params) {
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
			"func"	=> function($row, $params, $instance_params) {
				$override_id = "";
				if (isset($params['extra']['id'])) {
					$override_id = $params['extra']['id'];
				}
				if (isset($instance_params['id'])) {
					$override_id = $instance_params['id'];
				}
				if ($instance_params['btn_no_text']) {
					$no_text = 1;
				}
				$id = $override_id ? $override_id : 'id';
				$a_class = ($params['extra']['a_class'] ? ' '.$params['extra']['a_class'] : '');
				$icon = ($params['extra']['icon'] ? ' '.$params['extra']['icon'] : 'icon-tasks');
				$link = str_replace('%d', urlencode($row[$id]), $params['link']). $instance_params['links_add'];
				return '<a href="'.$link.'" class="btn btn-mini'.$a_class.'"><i class="'.$icon.'"></i>'.(empty($no_text) ? ' '.t($params['name']) : '').'</a> ';
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
				$link = str_replace('%d', urlencode($row[$id]), $params['link']). $instance_params['links_add'];
				$values = array(
					1 => '<span class="label label-success">'.t('Active').'</span>',
					0 => '<span class="label label-warning">'.t('Disabled').'</span>',
				);
				return '<a href="'.$link.'" class="change_active">'. $values[intval((bool)$row['active'])]. '</a> ';
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
			"func"	=> function($params, $instance_params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				$link = str_replace('%d', urlencode($row[$id]), $params['link']). $instance_params['links_add'];
				$icon = ($params['extra']['icon'] ? ' '.$params['extra']['icon'] : 'icon-tasks');
				$a_class = ($params['extra']['a_class'] ? ' '.$params['extra']['a_class'] : '');
				return '<a href="'.$link.'" class="btn btn-mini'.$a_class.'"><i class="'.$icon.'"></i> '.t($params['name']).'</a> ';
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
