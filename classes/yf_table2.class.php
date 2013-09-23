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
		return table2('SELECT * FROM '.db('admin'))
			->text('login')
			->text('first_name')
			->text('last_name')
			->link('group', './?object=admin_groups&action=edit&id=%d', $this->_admin_groups)
			->date('add_date')
			->text('go_after_login')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->btn('log_auth')
			->footer_link('Failed auth log', './?object=log_admin_auth_fails_viewer')
			->footer_link('Add', './?object='.$_GET['object'].'&action=add');
	*/

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
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
	* Wrapper for chained mode call from common()->table2()
	*/
	function chained_wrapper($sql = '', $params = array()) {
		$this->_chained_mode = true;
		$this->_sql = $sql;
		$this->_params = $params;
		return $this;
	}

	/**
	* Wrapper for template engine
	*/
	function tpl_row($type = 'input', $name, $desc = '', $extra = array()) {
		return $this->$type($name, $desc, $extra);
	}

	/**
	* Enabling automatic fields parsing mode
	*/
	function auto($params = array()) {
		$this->_params['auto'] = true;
		foreach ((array)$params as $k => $v) {
			$this->_params[$k] = $v;
		}
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

		$pager_path = $params['pager_path'] ? $params['pager_path'] : '';
		$pager_type = $params['pager_type'] ? $params['pager_type'] : 'blocks';
		$pager_records_on_page = $params['pager_records_on_page'] ? $params['pager_records_on_page'] : (MAIN_TYPE_USER ? conf('user_per_page') : conf('admin_per_page'));
		$pager_num_records = $params['pager_num_records'] ? $params['pager_num_records'] : 0;
		$pager_stpl_path = $params['pager_stpl_path'] ? $params['pager_stpl_path'] : '';
		$pager_add_get_vars = $params['pager_add_get_vars'] ? $params['pager_add_get_vars'] : 1;

		$sql = $this->_sql;
		$ids = array();
		if (is_array($sql)) {
			$data = $sql;
			unset($sql);
			list(,$pages,) = common()->divide_pages(null, null, null, $pager_records_on_page, count($data));
			if (count($data) > $pager_records_on_page) {
				$slice_start = (empty($_GET['page']) ? 0 : intval($_GET['page']) - 1) * $pager_records_on_page;
				$slice_end = $pager_records_on_page;
				$data = array_slice($data, $slice_start, $slice_end, $preserve_keys = true);
			}
			$total = count($data);
			$ids = array_keys($data);
		} elseif (strlen($sql)) {
			$db = is_object($params['db']) ? $params['db'] : db();
			if ($params['filter']) {
				$filter_sql = $this->_filter_sql_prepare($params['filter'], $params['filter_params']);
			}
			if ($filter_sql) {
				$sql .= (strpos(strtoupper($sql), 'WHERE') === false ? ' WHERE 1 ' : '').' '.$filter_sql;
			}
			list($add_sql, $pages, $total) = common()->divide_pages($sql, $pager_path, $pager_type, $pager_records_on_page, $pager_num_records, $pager_stpl_path, $pager_add_get_vars);

			$items = array();
			$q = $db->query($sql. $add_sql);
			while ($a = $db->fetch_assoc($q)) {
				if (isset($a['id'])) {
					$data[$a['id']] = $a;
					$ids[$a['id']] = $a['id'];
				} else {
					$data[] = $a;
				}
			}
		}
		// Automatically get fields from results
		if ($params['auto'] && $data) {
			$field_names = array_keys((array)current((array)$data));
			foreach ((array)$field_names as $f) {
				$this->text($f);
			}
			if (!$params['auto_no_buttons']) {
				$this->btn_edit();
				$this->btn_delete();
				$this->footer_add();
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
		*
		*	table2('SELECT * FROM '.db('shop_orders'), array('custom_fields' => array(
		*		'user' => array('SELECT id, CONCAT(login," ",email) AS name FROM '.db('user').' WHERE id IN(%ids)', 'user_id'),
		*	)))
		*	->text('user')
		*/
		if ($data && $ids && $params['custom_fields']) {
			$ids_sql = implode(',', $ids);
			$custom_foreign_fields = array();
			foreach ((array)$params['custom_fields'] as $custom_name => $custom_sql) {
				// In this case we can override name of the field used in virtual foreign key, used for custom field.
				// good example is 'user_id' instead of 'id'
				if (is_array($custom_sql)) {
					$tmp = $custom_sql;
					$custom_sql = $tmp[0];
					$foreign_field = $tmp[1];
					unset($tmp);
					if ($foreign_field != 'id') {
						$_ids = array();
						foreach((array)$data as $k => $v) {
							$_ids[$v[$foreign_field]] = $v[$foreign_field];
						}
						$_ids_sql = implode(',', $_ids);
					}
					$custom_foreign_fields[$custom_name] = $foreign_field;
					$this->_data_sql_names[$custom_name] = db()->get_2d(str_replace('%ids', $_ids_sql, $custom_sql));
				} else {
					$this->_data_sql_names[$custom_name] = db()->get_2d(str_replace('%ids', $ids_sql, $custom_sql));
				}
			}
			foreach ((array)$data as $_id => $row) {
				foreach ((array)$this->_data_sql_names as $custom_name => $custom_data) {
					if ($custom_foreign_fields[$custom_name]) {
						$_custom_id = $row[$custom_foreign_fields[$custom_name]];
					} else {
						$_custom_id = $_id;
					}
					$data[$_id][$custom_name] = strval($custom_data[$_custom_id]);
				}
			}
			// Needed to correctly pass inside $instance_params to each function
			$params['data_sql_names'] = $this->_data_sql_names;
		}
		$body = '';
		$body .= (!$params['no_pages'] && $params['pages_on_top'] ? $pages : '').PHP_EOL;

		if ($data) {
			if ($this->_form_params) {
				$body .= $this->_init_form()->form_begin($this->_form_params['name'], $this->_form_params['method'], $this->_form_params, $this->_form_params['replace']);
			}
			foreach ((array)$this->_header_links as $info) {
				$name = $info['name'];
				$func = $info['func'];
				unset($info['func']); // Save resources
				$body .= $func($info, $params).PHP_EOL;
			}
			if ($params['condensed']) {
				$params['table_class'] .= ' table-condensed';
			}
			$body .= '<table class="table table-bordered table-striped table-hover'
				.(isset($params['table_class']) ? ' '.$params['table_class'] : '').'"'
				.(isset($params['table_attr']) ? ' '.$params['table_attr'] : '').'>'.PHP_EOL;
			if (!$params['no_header']) {
				$body .= '<thead>'.PHP_EOL;
				foreach ((array)$this->_fields as $info) {
					$name = $info['name'];
					$th_width = ($info['extra']['width'] ? ' width="'.intval($info['extra']['width']).'"' : '');
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
					$td_width = ($info['extra']['width'] ? ' width="'.intval($info['extra']['width']).'"' : '');

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
		} else {
			$body .= ($params['no_records_simple'] ? t('No records') : '<div class="alert alert-info">'.t('No records').'</div>').PHP_EOL;
		}
		foreach ((array)$this->_footer_links as $info) {
			$name = $info['name'];
			$func = $info['func'];
			unset($info['func']); // Save resources
			$body .= $func($info, $params).PHP_EOL;
		}
		if ($data && $this->_form_params) {
			$body .= '</form>';
		}
		if (!isset($params['pages_on_bottom'])) {
			$params['pages_on_bottom'] = true;
		}
		$body .= (!$params['no_pages'] && $params['pages_on_bottom'] ? $pages : '').PHP_EOL;
		return $body;
	}

	/**
	*/
	function _filter_sql_prepare($filter_data = array(), $filter_params = array()) {
		if (!$filter_data) {
			return '';
		}
		$special_fields = array(
			'order_by',
			'order_direction',
		);
		$supported_conds = array(
			'eq'		=> function($a){ return ' = "'._es($a['value']).'"'; }, // "equal"
			'ne'		=> function($a){ return ' != "'._es($a['value']).'"'; }, // "not equal"
			'gt'		=> function($a){ return ' > "'._es($a['value']).'"'; }, // "greater than",
			'gte'		=> function($a){ return ' >= "'._es($a['value']).'"'; }, // "greater or equal than",
			'lt'		=> function($a){ return ' < "'._es($a['value']).'"'; }, // "less than",
			'lte'		=> function($a){ return ' <= "'._es($a['value']).'"'; }, // "lower or equal than"
			'like'		=> function($a){ return ' LIKE "%'._es($a['value']).'%"'; }, // LIKE '%'.$value.'%'
			'rlike'		=> function($a){ return ' RLIKE "'._es($a['value']).'"'; }, // regular expression, RLIKE $value
			'between'	=> function($a){ return ' BETWEEN "'._es($a['value']).'" AND "'._es($a['and']).'"'; }, // BETWEEN $min AND $max
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
			$part_on_the_right = '';
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
				// Here we can override default 'eq' condition with custom one by passing additional param to table2. 
				// example: table2($sql, array('filter_params' => array('name' => 'gt', 'price' => 'between'), 'filter' => $_SESSION[__CLASS__]))
				if (isset($filter_params[$k]) && isset($supported_conds[$filter_params[$k]])) {
					$cond = $filter_params[$k];
				}
				// Field with __and on the end of its name is special one for 'between' condition
				$part_on_the_right = $supported_conds[$cond](array('value' => $v, 'and' => $filter_data[$k.'__and']));
			}
			$sql[] = '`'.db()->es($k).'`'.$part_on_the_right;
		}
		if ($sql) {
			$filter_sql = ' AND '.implode(' AND ', $sql);
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
	* Used to embed hidden data blocks, that can be later displayed.
	*/
	function _hidden_data_container($row, $params, $instance_params) {
		$extra = $params['extra'];
		$hidden_data = $extra['hidden_data'];
		if (empty($hidden_data)) {
			return '';
		}
		if (!is_array($hidden_data)) {
			$hidden_data = array($hidden_data);
		}
		$body = '';
		foreach ((array)$hidden_data as $data) {
			if (!$data) {
				continue;
			}
			// Linking data from row element, example: %explain
			if ($data[0] == '%') {
				$name = substr($data, 1);
				$data = isset($row[$name]) ? $row[$name] : $data;
			} else {
				$name = $params['name'];
			}
			if ($data) {
				$body .= '<div style="display:none;" data-hidden-name="'.$name.'">'.$data.'</div>';
			}
		}
		return $body;
	}

	/**
	*/
	function text($name, $desc = '', $extra = array()) {
		if (!is_array($extra)) {
			$extra = array();
		}
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$this->_fields[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'desc'	=> $desc,
			'link'	=> $extra['link'],
			'data'	=> t($extra['data']),
			'func'	=> function($field, $params, $row, $instance_params) {
				$extra = $params['extra'];
				if (!$params['data'] && $extra['data_name']) {
					$params['data'] = $instance_params['data_sql_names'][$extra['data_name']];
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
				if (!isset($extra['nowrap'])) {
					$extra['nowrap'] = true;
				}
				if ($params['link']) {
					$link_field_name = $extra['link_field_name'];
					$link_id = $link_field_name ? $row[$link_field_name] : $field;
					$link = str_replace('%d', urlencode($link_id), $params['link']). $instance_params['links_add'];
					if ($extra['hidden_toggle']) {
						$attrs .= ' data-hidden-toggle="'.$extra['hidden_toggle'].'"';
					}
					// It is intentionally placed only here, because if apply nowrap by default to simple text too - will produce strange UI bugs
					if ($extra['nowrap']) {
						$text = str_replace(' ', '&nbsp;', $text);
					}
					$body = '<a href="'.$link.'" class="btn btn-mini"'.$a_class. $attrs. '>'.$text.'</a>';
				} else {
					$body = $text;
				}
				$body .= $extra['hidden_data'] ? _class('table2')->_hidden_data_container($row, $params, $instance_params) : '';
				return _class('table2')->_apply_badges($body, $extra, $field);
			}
		);
		return $this;
	}

	/**
	*/
	function link($name, $link = '', $data = '', $extra = array()) {
		$extra['link'] = $link;
		$extra['data'] = $data;
		return $this->text($name, $extra['desc'], $extra);
	}

	/**
	* Currently designed only for admin usage
	*/
	function user($name = '', $link = '', $data = '', $extra = array()) {
		if (!$name) {
			$name = 'user_id';
		}
		$_name = 'user';
		$extra['link'] = $link ? $link : './?object=members&action=edit&id=%d';
		$extra['link_field_name'] = $name;
		$extra['data'] = $data;
		$this->_params['custom_fields'][$_name] = array('SELECT id, CONCAT(login," ",email) AS user_name FROM '.db('user').' WHERE id IN(%ids)', $name);
		return $this->text($_name, $extra['desc'], $extra);
	}

	/**
	*/
	function date($name, $desc = '', $extra = array()) {
		// Shortcut: use second param as $extra
		if (is_array($desc) && empty($extra)) {
			$extra = $desc;
			$desc = '';
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$this->_fields[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'desc'	=> $desc,
			'func'	=> function($field, $params, $row, $instance_params) {
				$extra = $params['extra'];
				$text = str_replace(' ', '&nbsp;', _format_date($field, $params['desc']));
				return _class('table2')->_apply_badges($text, $extra, $field);
			}
		);
		return $this;
	}

	/**
	*/
	function image($path, $link = '', $extra = array()) {
		$name = 'image';
		$this->_fields[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'desc'	=> $extra['desc'] ? $extra['desc'] : 'Image',
			'path'	=> $path,
			'link'	=> $link,
			'func'	=> function($field, $params, $row, $instance_params) {
				$extra = $params['extra'];
				$id = $row['id'];
				// Make 3-level dir path
				$d = sprintf('%09s', $id);
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
					.'<img src="'.WEB_PATH. $img_path.'"'
						.($extra['width'] ? ' width="'.intval($extra['width']).'"' : '')
						.($extra['height'] ? ' height="'.intval($extra['height']).'"' : '')
					.' style="'
						.($extra['width'] ? 'width:'.$extra['width'].';' : '')
						.($extra['height'] ? 'height:'.$extra['height'].';' : '')
					.'">'
					.($link_url ? '</a>' : '');
			}
		);
		return $this;
	}

	/**
	* Callback function will be populated with these params: function($field, $params, $row, $instance_params) {}
	*/
	function func($name, $func, $extra = array()) {
		if (!$desc && isset($extra['desc'])) {
			$desc = $extra['desc'];
		}
		if (!$desc) {
			$desc = ucfirst(str_replace('_', ' ', $name));
		}
		$this->_fields[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'desc'	=> $desc,
			'func'	=> $func,
		);
		return $this;
	}

	/**
	* Currently designed only for admin usage
	* Show multiple selected data items
	*/
	function data($name, $data = array(), $extra = array()) {
		// $this->_params['custom_fields'][$_name] = array('SELECT id, CONCAT(login," ",email) AS user_name FROM '.db('user').' WHERE id IN(%ids)', $name);
		return $this->text($name, $extra['desc'], $extra);
	}

	/**
	*/
	function data_array($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
			$extra = $params['extra'];
			$out = array();
			foreach (explode(',', trim(trim($field,','))) as $k => $v) {
				if (!empty($extra['data'][$v])) {
					$out[$v] = $extra['data'][$v];
				}
			}
			return $out ? implode('<br />', $out) : t('--All--');
		}, $extra);
	}

	/**
	*/
	function btn($name, $link, $extra = array()) {
		$this->_buttons[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'link'	=> $link,
			'func'	=> function($row, $params, $instance_params) {
				$extra = $params['extra'];
				$override_id = '';
				if (isset($extra['id'])) {
					$override_id = $extra['id'];
				}
				if (isset($instance_params['id'])) {
					$override_id = $instance_params['id'];
				}
				if ($instance_params['btn_no_text']) {
					$no_text = 1;
				}
				$id = $override_id ? $override_id : 'id';
				$a_class = ($extra['a_class'] ? ' '.$extra['a_class'] : '');
				if ($extra['hidden_toggle']) {
					$attrs .= ' data-hidden-toggle="'.$extra['hidden_toggle'].'"';
				}
				$icon = ($extra['icon'] ? ' '.$extra['icon'] : 'icon-tasks');
				$link = str_replace('%d', urlencode($row[$id]), $params['link']). $instance_params['links_add'];

				$body = '<a href="'.$link.'" class="btn btn-mini'.$a_class.'"'.$attrs.'><i class="'.$icon.'"></i>'.(empty($no_text) ? ' '.t($params['name']) : '').'</a> ';

				$body .= $extra['hidden_data'] ? _class('table2')->_hidden_data_container($row, $params, $instance_params) : '';
				return $body;
			},
		);
		return $this;
	}

	/**
	*/
	function btn_edit($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'Edit';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=edit&id=%d';
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
	function btn_delete($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'Delete';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=delete&id=%d';
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
	function btn_clone($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'Clone';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=clone_item&id=%d';
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
	function btn_view($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'View';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=view&id=%d';
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
	function btn_active($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'Active';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=active&id=%d';
		}
		$this->_buttons[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'link'	=> $link,
			'func'	=> function($row, $params) {
				$extra = $params['extra'];
				$id = isset($extra['id']) ? $extra['id'] : 'id';
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
	function header_link($name, $link, $extra = array()) {
		$extra['display_in'] = 'header';
		return $this->footer_link($name, $link, $extra);
	}

	/**
	*/
	function footer_link($name, $link, $extra = array()) {
		$item = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'link'	=> $link,
			'func'	=> function($params, $instance_params) {
				$extra = $params['extra'];
				$id = isset($extra['id']) ? $extra['id'] : 'id';
				$link = str_replace('%d', urlencode($row[$id]), $params['link']). $instance_params['links_add'];
				$icon = ($extra['icon'] ? ' '.$extra['icon'] : 'icon-tasks');
				$a_class = ($extra['a_class'] ? ' '.$extra['a_class'] : '');
				return '<a href="'.$link.'" class="btn btn-mini'.$a_class.'"><i class="'.$icon.'"></i> '.t($params['name']).'</a> ';
			}
		);
		if (!$extra['display_in']) {
			$extra['display_in'] = 'footer';
		}
		if ($extra['display_in'] == 'header' || $extra['copy_to_header']) {
			$this->_header_links[] = $item;
		}
		if ($extra['display_in'] == 'footer' || $extra['copy_to_footer']) {
			$this->_footer_links[] = $item;
		}
		return $this;
	}

	/**
	*/
	function header_add($name = '', $link = '', $extra = array()) {
		$extra['display_in'] = 'header';
		return $this->footer_add($name, $link, $extra);
	}

	/**
	*/
	function footer_add($name = '', $link = '', $extra = array()) {
		if (is_array($name)) {
			$extra = $name;
		}
		if (!$name) {
			$name = 'add';
		}
		if (!$link) {
			$link = './?object='.$_GET['object'].'&action=add';
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
	function header_submit($name = '', $extra = array()) {
		$extra['display_in'] = 'header';
		return $this->footer_submit($name, $extra);
	}

	/**
	*/
	function footer_submit($name = '', $extra = array()) {
		$item = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'link'	=> $link,
			'func'	=> function($params, $instance_params) {
				$extra = $params['extra'];
				$value = $params['name'] ? $params['name'] : 'Submit';
				if (is_array($value) && empty($extra)) {
					$extra = $value;
					$value = '';
				}
				$value = $extra['value'] ? $extra['value'] : $value;
				return '<input type="submit" name="'.$value.'" value="'.t($value).'" class="btn btn-mini">';
			}
		);
		if (!$extra['display_in']) {
			$extra['display_in'] = 'footer';
		}
		if ($extra['display_in'] == 'header' || $extra['copy_to_header']) {
			$this->_header_links[] = $item;
		}
		if ($extra['display_in'] == 'footer' || $extra['copy_to_footer']) {
			$this->_footer_links[] = $item;
		}
		return $this;
	}

	/**
	* Simply tells that current table should consist of form inside
	*/
	function form($action = '', $method = '', $extra = array()) {
		if (isset($this->_form_params) && !$extra['force']) {
			return $this;
		}
		if (is_array($action)) {
			$extra = $action;
			$action = '';
		}
		if (is_array($method)) {
			$extra = $method;
			$method = '';
		}
		$this->_form_params = array(
			'action'=> $action ? $action : './?object='.$_GET['object']. ($_GET['action'] != 'show' ? '&action='.$_GET['action'] : ''). ($_GET['id'] ? '&id='.$_GET['id'] : ''),
			'method'=> $method ? $method : 'POST',
			'extra'	=> (array)$extra,
		);
		return $this;
	}

	/**
	*/
	function input($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
			$extra = $params['extra'];
			if ($extra['padding'] && $row['level']) {
				$padding = '<span style="padding-left:'.($row['level'] * 20).'px; padding-right:5px;">&#9492;</span>';
			}
			return $padding. _class('html_controls')->input(array(
				'id'	=> 'input_'.$params['name'].'_'.$row['id'],
				'name'	=> $params['name'].'['.$row['id'].']',
				'desc'	=> $params['name'],
				'value'	=> $field,
			) + (array)$extra);
		}, $extra);
	}

	/**
	*/
	function icon($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
// TODO: finish with icons
/*
			$icon_src = '';
			if ($A['icon']) {
				$_icon_path = $this->ICONS_PATH. $A['icon'];
				if (file_exists(INCLUDE_PATH. $_icon_path)) {
					$icon_src = WEB_PATH. $_icon_path;
				}
			}
			// Icon class from bootstrap icon class names 
			$icon_class = '';
			if ($A['icon'] && (strpos($A['icon'], '.') === false)) {
				$icon_class = $A['icon'];
			}
*/
			return $out;
		}, $extra);
	}

	/**
	*/
	function check_box($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
			$extra = $params['extra'];
			if (!is_array($extra)) {
				$extra = array();
			}
			if (!$extra['name']) {
				$extra['name'] = $params['name'];
			}
			if (false === strpos($extra['name'], '[')) {
				$extra['name'] .= '['.$field.']';
			}
			$extra['id'] = 'checkbox_'.$field;
			return _class('html_controls')->check_box($extra);
		}, $extra);
	}

	/**
	*/
	function radio_box($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
			$extra = $params['extra'];
			if (!is_array($extra)) {
				$extra = array();
			}
			if (!$extra['name']) {
				$extra['name'] = $params['name'];
			}
			if (false === strpos($extra['name'], '[')) {
				$extra['name'] .= '['.$field.']';
			}
			$extra['id'] = 'radiobox_'.$field;
// TODO: test me and maybe upgrade _class('html_controls')->radio_box()
			return _class('html_controls')->radio_box($extra);
		}, $extra);
	}

	/**
	*/
	function select_box($name, $extra = array()) {
		$this->form();
		return $this->func($name, function($field, $params, $row) {
			$extra = $params['extra'];
			if (!is_array($extra)) {
				$extra = array();
			}
			if (!$extra['name']) {
				$extra['name'] = $params['name'];
			}
			if (false === strpos($extra['name'], '[')) {
				$extra['name'] .= '['.$field.']';
			}
			$extra['id'] = 'selectbox_'.$field;
// TODO: test me and maybe upgrade _class('html_controls')->select_box()
			return _class('html_controls')->select_box($extra);
		}, $extra);
	}

	/**
	*/
	function _show_tip($value = '', $extra = array()) {
// TODO: connect 2 kind of tips args to all funcs: 'tip' - near field value, 'header_tip' - for table header, 
// TODO: also add ability to pass tips array into table2() params like 'data'
		return _class('graphics')->_show_help_tip(array(
			'tip_id'	=> $value,
//			'replace'	=> $extra[],
		));
	}
}
