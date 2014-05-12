<?php

/**
* Currencies management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_currencies {

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__show';
		return table('SELECT * FROM '.db('currencies'), array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array('name' => 'like'),
			))
			->text('id')
			->text('name')
			->text('sign')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		$a = db()->query_fetch('SELECT * FROM '.db('currencies').' WHERE id="'._es($_GET['id']).'"');
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_update_if_ok('currencies', array('name','sign','active'), 'id="'._es($a['id']).'"')
			->on_after_update(function() {
				cache_del(array('currencies'));
				common()->admin_wall_add(array('icon updated: '.$_POST['name'].'', $a['id']));
			})
			->text('name')
			->text('sign')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_insert_if_ok('currencies', array('name','id','sign','active'), array())
			->on_after_update(function() {
				cache_del(array('currencies'));
				common()->admin_wall_add(array('icon added: '.$_POST['name'].'', db()->insert_id()));
			})
			->text('id')
			->text('name')
			->text('sign')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => 'currencies'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'currencies'));
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
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
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array(
			'name' => 'name',
		);
		$per_page = array('' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
#				'class'		=> 'form-inline',
			))
			->text('name', array('class' => 'input-medium'))
			->select_box('per_page', $per_page, array('class' => 'input-small'))
			->select_box('order_by', $order_fields, array('show_text' => 1, 'class' => 'input-medium'))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__currencies_list ($params = array()) {
// TODO
	}
}
