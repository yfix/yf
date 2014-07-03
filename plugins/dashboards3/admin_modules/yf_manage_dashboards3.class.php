<?php

/**
* Dashboards3 management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_dashboards3 {

	/**
	 */
	function _init () {
		conf('css_framework', 'bs3');
	}

	/**
	 */
	function show() {
		return table('SELECT * FROM '.db('dashboards3'))
			->text('name')
			->text('type')
			->func("lock", function($value, $extra, $row_info){return $value == 1 ? '<span class="label label-success">YES<span>' : '<span class="label label-danger">NO<span>';})
			->btn_view()
			->btn_edit()
			->btn_clone()
			->btn_delete()
			->btn_active()
			->footer_add()

			;
	}

	/**
	*/
	function save() {
		main()->NO_GRAPHICS = true;
		print_r(json_encode($_POST['structure']));
		db()->update('dashboards3', db()->es(array(
					'data'	=> json_encode($_POST['structure']),
				)), 'id='.intval($_GET['id']));
		exit;
		
	}

	/**
	*/
	function delete () {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards3').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		if (!empty($ds_info['id'])) {
			db()->query('DELETE FROM '.db('dashboards3').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('dashboard deleted: '.$ds_info['name'].'', $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function clone_item() {
		$id = $_GET['id'];
		$ds_info = db()->get('SELECT * FROM '.db('dashboards3').' WHERE name="'.db()->es($id).'" OR id='.intval($id));
		if (!$ds_info['id']) {
			return _e('No such record');
		}
		$_GET['id'] = $ds_info['id'];
		$sql = $ds_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';
		db()->insert('dashboards3', $sql);
		common()->admin_wall_add( array('dashboard cloned: '.$ds_info['name'], db()->insert_id() ));
		return js_redirect('./?object='.$_GET['object']);
	}

	/**
	*/
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$ds_info = db()->get('SELECT * FROM '.db('dashboards3').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($ds_info['id'])) {
			db()->update('dashboards3', array('active' => (int)!$ds_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('dashboard '.$ds_info['name'].' '.($ds_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($ds_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}	

	/**
	*/
	function lock () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$ds_info = db()->get('SELECT * FROM '.db('dashboards3').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($ds_info['id'])) {
			db()->update('dashboards3', array('lock' => (int)!$ds_info['lock']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('dashboard '.$ds_info['name'].' '.($ds_info['lock'] ? 'unlocked' : 'locked'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($ds_info['lock'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function add () {
		if (main()->is_post()) {
			if (!_ee()) {
				db()->insert('dashboards3', db()->es(array(
					'name'		=> $_POST['name'],
					'type'		=> $_POST['type'],
					'active'	=> $_POST['active'],
				)));
				$new_id = db()->insert_id();
				common()->admin_wall_add(array('dashboard added: '.$_POST['name'], $new_id));
				return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$new_id);
			}
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'back_link'		=> './?object='.$_GET['object'],
		);
		return form2($replace)
			->text('name')
			->radio_box('type', array('admin' => 'admin', 'user' => 'user'))
			->radio_box('lock', array('1' => 'YES', '0' => 'NO'))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('dashboards3').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required',
			))
			->db_update_if_ok('dashboards3', array('name','type','active'), 'id='.$id, array('on_after_update' => function() {
				common()->admin_wall_add(array('dashboards3 updated: '.$a['name'], $id));
			}))
			->text('name')
			->radio_box('type', array('admin' => 'admin', 'user' => 'user'))
			->radio_box('lock', array('1' => 'YES', '0' => 'NO'))
			->active_box()
			->save_and_back();
	}


	/**
	*/
	function view () {
			$replace = array();
		return tpl()->parse(__CLASS__.'/view', $replace);
	}

	

}
