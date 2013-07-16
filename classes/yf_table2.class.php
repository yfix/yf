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
			->btn("log_auth")
			->footer_link("Failed auth log", "./?object=log_admin_auth_fails_viewer")
			->footer_link("Add", "./?object=".$_GET["object"]."&action=add")
			->render();
	*/

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Render result table html, gathered by row functions
	*/
	function render($params = array()) {
		$sql = $this->_sql;
		$db = is_object($params['db']) ? $params['db'] : db();
		$pager_path = $params['pager_path'] ? $params['pager_path'] : "";
		$pager_type = $params['pager_type'] ? $params['pager_type'] : "blocks";
		$pager_records_on_page = $params['pager_records_on_page'] ? $params['pager_records_on_page'] : 0;
		$pager_num_records = $params['pager_num_records'] ? $params['pager_num_records'] : 0;
		$pager_stpl_path = $params['pager_stpl_path'] ? $params['pager_stpl_path'] : "";
		$pager_add_get_vars = $params['pager_add_get_vars'] ? $params['pager_add_get_vars'] : 1;

		list($add_sql, $pages, $total) = common()->divide_pages($sql, $pager_path, $pager_type, $pager_records_on_page, $pager_num_records, $pager_stpl_path, $pager_add_get_vars);

		$items = array();
		$q = $db->query($sql. $add_sql);
		while ($a = $db->fetch_assoc($q)) {
			$data[] = $a;
		}
		if ($data) {
			$body = '<table class="table table-bordered table-striped table-hover">'.PHP_EOL;
			$body .= '<thead>'.PHP_EOL;
			foreach ((array)$this->_fields as $info) {
				$name = $info['name'];
				$body .= '<th>'.t($info['desc']).'</th>'.PHP_EOL;
			}
			if ($this->_buttons) {
				$body .= '<th>'.t('Actions').'</th>'.PHP_EOL;
			}
			$body .= '</thead>'.PHP_EOL;
			foreach ((array)$data as $row) {
				$body .= '<tr>'.PHP_EOL;
				foreach ((array)$this->_fields as $info) {
					$name = $info['name'];
					if (!isset($row[$name])) {
						continue;
					}
					$func = $info['func'];
					$body .= '<td>'.$func($row[$name], $info, $row).'</td>'.PHP_EOL;
				}
				if ($this->_buttons) {
					$body .= '<td nowrap>';
					foreach ((array)$this->_buttons as $info) {
						$name = $info['name'];
						$func = $info['func'];
						$body .= $func($row, $info).PHP_EOL;
					}
					$body .= '</td>'.PHP_EOL;
				}
				$body .= '</tr>'.PHP_EOL;
			}
#			$body .= '<caption>'.t('Total records:').':'.$total.'</caption>'.PHP_EOL;
			$body .= '</table>'.PHP_EOL;
		} else {
			$body .= '<div class="alert alert-info">'.t('No records').'</div>'.PHP_EOL;
		}
		foreach ((array)$this->_footer_links as $info) {
			$name = $info['name'];
			$func = $info['func'];
			$body .= $func($info).PHP_EOL;
		}
		$body .= $pages.PHP_EOL;
		return $body;
		return implode("\n", $this->_body);
	}

	/**
	* Wrapper for chained mode call from common()->table2()
	*/
	function chained_wrapper($sql = "") {
		$this->_chained_mode = true;
		$this->_sql = $sql;
// TODO: need to change API to create new class instance on every chained request
		return $this;
	}

	/**
	* Wrapper for template engine
	*/
	function tpl_row($type = "input", $name, $desc = "", $extra = array()) {
// TODO: integrate with tpl engine
// TODO: integrate with named errors
#		$errors = array();
		return $this->$type($name, $desc, $extra);
	}

	/**
	*/
	function text($name, $desc = "", $extra = array()) {
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> function($field, $params) {
				return $field;
			}
		);
		return $this;
	}

	/**
	*/
	function link($name, $link = "", $data = "", $extra = array()) {
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"link"	=> $link,
			"data"	=> $data,
			"func"	=> function($field, $params) {
				if (is_string($params['data'])) {
					$text = $params['data'];
				} else {
					$text = (isset($params['data']) ? $params['data'][$field] : $field);
				}
				return '<a href="'.str_replace('%d', $field, $params['link']).'" class="btn btn-mini">'.str_replace(" ", "&nbsp;", $text).'</a>';
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
		$this->_fields[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> function($field, $params) {
				return str_replace(' ', '&nbsp;', _format_date($field, $params['desc']));
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
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="btn btn-mini"><i class="icon-tasks"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_edit($name = "", $link = "", $extra = array()) {
		if (!$name) {
			$name = "Edit";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=edit&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="btn btn-mini ajax_edit"><i class="icon-edit"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_delete($name = "", $link = "", $extra = array()) {
		if (!$name) {
			$name = "Delete";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=delete&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="btn btn-mini ajax_delete"><i class="icon-trash"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_clone($name = "", $link = "", $extra = array()) {
		if (!$name) {
			$name = "Clone";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=clone&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="btn btn-mini ajax_clone"><i class="icon-plus"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_active($name = "", $link = "", $extra = array()) {
		if (!$name) {
			$name = "Active";
		}
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=active&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="change_active">'
						.($row['active'] ? '<span class="label label-success">'.t('Active').'</span>' : '<span class="label label-warning">'.t('Disabled').'</span>')
					.'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function footer_link($name, $link, $extra = array()) {
		$this->_footer_links[$name] = array(
			"type"	=> __FUNCTION__,
			"name"	=> $name,
			"extra"	=> $extra,
			"link"	=> $link,
			"func"	=> function($params) {
				$id = isset($params['extra']['id']) ? $params['extra']['id'] : 'id';
				return '<a href="'.str_replace('%d', $row[$id], $params['link']).'" class="btn btn-mini"><i class="icon-tasks"></i> '.t($params['name']).'</a> ';
			}
		);
		return $this;
	}

	/**
	*/
	function image($path, $link = "", $extra = array()) {
		$name = 'image';
		$this->_fields[$name] = array(
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
}
