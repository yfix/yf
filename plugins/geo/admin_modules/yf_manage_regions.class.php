<?php

/**
* Regions management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_regions {

// TODO

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__show';
		return table('SELECT * FROM '.db('regions'), array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array('name' => 'like'),
			))
			->text('name')
			->text('country')
			->text('code')
			->text('code3')
			->text('num')
			->text('cont')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db('regions').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_update_if_ok('regions', array('name','active'), 'id='.$a['id'])
			->on_after_update(function() {
				cache_del(array('regions'));
				common()->admin_wall_add(array('region updated: '.$_POST['name'].'', $a['id']));
			})
			->text('name')
			->text('country')
			->info('code')
			->info('code3')
			->info('num')
			->info('cont')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_insert_if_ok('regions', array('name','active'), array())
			->on_after_update(function() {
				cache_del(array('regions'));
				common()->admin_wall_add(array('region added: '.$_POST['name'].'', db()->insert_id()));
			})
			->text('name')
			->text('country')
			->info('code')
			->info('code3')
			->info('num')
			->info('cont')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => 'regions'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'regions'));
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
			'code' => 'code',
			'name' => 'name',
		);
		$per_page = array('' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			))
			->text('name')
			->select_box('per_page', $per_page, array('class' => 'input-small'))
			->select_box('order_by', $order_fields, array('show_text' => 1, 'class' => 'input-medium'))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'), array('horizontal' => 1, 'translate' => 1))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__regions_list ($params = array()) {
// TODO
	}
}
