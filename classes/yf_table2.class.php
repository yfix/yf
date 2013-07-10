<?php

/**
* Table2, using bootstrap html/css framework
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_table2 {

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
	function render() {
		$sql = $this->_sql;
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$items = array();
		$q = db()->query($sql. $add_sql);
		while ($a = db()->fetch_assoc($q)) {
			$data[] = $a;
		}
		$body = '<table class="table table-bordered table-striped table-hover">';
		$body .= '<thead>';
		foreach ($this->_fields as $name => $info) {
			$body .= '<th>'.t($info['desc']).'</th>';
		}
		$body .= '<th>'.t('Actions').'</th>';
		$body .= '</thead>';
		foreach ($data as $row) {
			$body .= '<tr>';
			foreach ($this->_fields as $name => $field_info) {
				if (!isset($row[$name])) {
					continue;
				}
				$func = $field_info['func'];
				$body .= '<td>'.$func($row[$name], $field_info).'</td>';
			}
			$body .= '<td>';
			foreach ($this->_buttons as $name => $btn_info) {
				$func = $btn_info['func'];
				$body .= $func($row, $btn_info);
			}
			$body .= '</td>';
			$body .= '</tr>';
		}
		$body .= '</table>';
		foreach ($this->_footer as $name => $info) {
		}
		return $body;
/*
		return common()->table2("SELECT * FROM ".db('admin'))
			->text("login")
			->text("first_name")
			->text("last_name")
			->link("group", "./?object=admin_groups&action=edit&id=%d", $this->_admin_groups)
			->date("add_date")
			->link("go_after_login")
			->btn_active()
			->btn_edit()
			->btn_delete()
			->btn("log_auth")
			->footer_link("Failed auth log", "./?object=log_admin_auth_fails_viewer")
			->footer_link("Add", "./?object=".$_GET["object"]."action=add")
			->render();
*/
		return implode("\n", $this->_body);
	}

	/**
	* Wrapper for chained mode call from common()->table2()
	*/
	function chained_wrapper($sql = "") {
		$this->_chained_mode = true;
		$this->_sql = $sql;
// TODO: need to change API to create new class instance on every chained request
// TODO: test how this will work with several forms
		return $this;
	}

	/**
	* Wrapper for template engine
	*/
	function tpl_row($type = "input", $replace = array(), $name, $desc = "", $extra = array()) {
// TODO: integrate with named errors
#		$errors = array();
		return $this->$type($name, $desc, $extra, $replace);
	}

	/**
	*/
	function text($name, $desc = "", $extra = array()) {
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_fields[$name] = array(
			"type"	=> __FUNCTION__,
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
			"extra"	=> $extra,
			"desc"	=> $desc,
			"link"	=> $link,
			"data"	=> $data,
			"func"	=> function($field, $params) {
				return '<a href="'.str_replace('%d', $field, $params['link']).'">'.(isset($params['data']) ? $params['data'][$field] : $field).'</a>';
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
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> function($field, $params) {
				return _format_date($field);
			}
		);
		return $this;
	}

	/**
	*/
	function btn($name, $link, $extra = array()) {
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"extra"	=> $extra,
			"name"	=> $name,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				return '<a href="'.str_replace('%d', $row['id'], $params['link']).'" class="btn btn-mini"><i class="icon-tasks"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_edit($name = "Edit", $link = "", $extra = array()) {
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=edit&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"extra"	=> $extra,
			"name"	=> $name,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				return '<a href="'.str_replace('%d', $row['id'], $params['link']).'" class="btn btn-mini"><i class="icon-edit"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_delete($name = "Delete", $link = "", $extra = array()) {
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=delete&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"extra"	=> $extra,
			"name"	=> $name,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				return '<a href="'.str_replace('%d', $row['id'], $params['link']).'" class="btn btn-mini" onclick="return confirm(\''.t('Are you sure').'?\');"><i class="icon-trash"></i> '.t($params['name']).'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function btn_active($name = "Active", $desc = "", $extra = array()) {
		if (!$link) {
			$link = "./?object=".$_GET["object"]."&action=active&id=%d";
		}
		$this->_buttons[$name] = array(
			"type"	=> __FUNCTION__,
			"extra"	=> $extra,
			"name"	=> $name,
			"link"	=> $link,
			"func"	=> function($row, $params) {
				return '<a href="'.str_replace('%d', $row['id'], $params['link']).'" class="change_active">'
						.($row['active'] ? '<span class="label label-success">'.t('ACTIVE').'</span>' : '<span class="label label-warning">'.t('INACTIVE').'</span>')
					.'</a> ';
			},
		);
		return $this;
	}

	/**
	*/
	function footer_link($name, $desc = "", $extra = array()) {
		if (!$desc) {
			$desc = ucfirst(str_replace("_", " ", $name));
		}
		$this->_footer[$name] = array(
			"type"	=> __FUNCTION__,
			"extra"	=> $extra,
			"desc"	=> $desc,
			"func"	=> function($params) {
// TODO
				return '__TODO__';
			}
		);
		return $this;
	}
}
