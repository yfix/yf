<?php

/**
* Countries management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_countries {

	/**
	*/
	private $params = array(
		'table' => 'countries',
		'id'	=> 'code',
	);

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__show';
		return table('SELECT * FROM '.db('countries'), array(
				'id' => 'code',
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array('name' => 'like', 'native' => 'like'),
			))
			->text('code')
			->text('name')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		$a = db()->query_fetch('SELECT * FROM '.db('countries').' WHERE code="'._es($_GET['id']).'"');
		if (!$a) {
			return _e('Wrong record!');
		}
		$a['id'] = $a['code'];
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_update_if_ok('countries', array('name','active'), 'code="'._es($a['code']).'"', array('on_after_update' => function() {
				cache()->refresh(array('countries'));
				common()->admin_wall_add(array('country updated: '.$_POST['name'].'', $a['code']));
			}))
			->info('code')
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_insert_if_ok('countries', array('name','code','active'), array(), array('on_after_update' => function() {
				cache()->refresh(array('countries'));
				common()->admin_wall_add(array('country added: '.$_POST['name'].'', db()->insert_id()));
			}))
			->text('code')
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete($this->params);
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->params);
	}

	/**
	*/
	function filter_save() {
		$filter_name = $_GET['object'].'__show';
		if ($_GET['sub'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='. str_replace ($_GET['object'].'__', '', $filter_name));
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__show';
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&sub=clear&id='.$filter_name,
		);
		$order_fields = array(
			'code' => 'code',
			'name' => 'name',
			'active' => 'active',
		);
		$per_page = array('' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
#				'class'		=> 'form-inline',
			))
			->text('name')
			->select_box('per_page', $per_page, array('class' => 'input-small'))
			->select_box('order_by', $order_fields, array('show_text' => 1, 'class' => 'input-medium'))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__countries_list ($params = array()) {
// TODO
	}
}
