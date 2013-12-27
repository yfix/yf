<?php
class yf_manage_shop_users{

	/**
	*/
	function users () {
		if (empty($_SESSION[$_GET['object'].'__users'])) {
			$_SESSION[$_GET['object'].'__users'] = array(
				'order_by' => 'add_date',
				'order_direction' => 'desc'
			);
		}
		return table('SELECT * FROM '.db('user'), array(
				'filter' => $_SESSION[$_GET['object'].'__users'],
				'filter_params' => array(
					'id'		=> 'like',					
					'name'		=> 'like',
					'email'		=> 'like',
					'phone'		=> 'like',
					'address'	=> 'like',					
				),
			))
			->text('id')
			->text('name')
			->text('email')
			->text('phone')
			->text('address')
			->date('add_date',array('format' => 'Y-m-d H:i:s','nowrap' => '1'))
			->btn_edit('', './?object=manage_shop&action=user_edit&id=%d')
			->btn('Login', './?object=manage_users&action=login_as&id=%d')
			->btn_delete('', './?object=manage_shop&action=user_delete&id=%d')
			->btn_active('', './?object=manage_shop&action=user_activate&id=%d')
		;
	}

	/**
	*/
	function user_delete () {
		$_GET['id'] = intval($_GET['id']);
		$field_info = db()->query_fetch('SELECT * FROM '.db('user').' WHERE id = '.intval($_GET['id']));
		if (empty($field_info)) {
			return _e(t('no field'));
		}
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('user').' WHERE id='.$_GET['id']);
			common()->admin_wall_add(array('user deleted: '.$_GET['id'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
	}

	/**
	*/
	function user_activate () {
		if ($_GET['id']){
			$A = db()->query_fetch('SELECT * FROM '.db('user').' WHERE id='.intval($_GET['id']));
			if ($A['active'] == 1) {
				$active = 0;
			} elseif ($A['active'] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('user'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object=manage_shop&action=users');
		}
	}
	
	/**
	*/
	function user_edit() {
		if ($_GET['id']){
			$A = db()->query_fetch('SELECT * FROM '.db('user').' WHERE id='.intval($_GET['id']));
			if (empty($A)) {
				return js_redirect('./?object=manage_shop&action=users');
			}
		}
		$validate_rules = array(
			'name'		=> array( 'trim|required' ),
			'email'		=> array( 'trim|required|valid_email' ),
			'phone'		=> array( 'trim|required' ),
			'address'	=> array( 'trim|required' ),
		);
		$A['redirect_link'] = './?object='.$_GET['object'].'&action=users';
		
		return form($A)
			->validate($validate_rules)
			->db_update_if_ok('user', array('name','phone','address'), $_GET['id'])
			->text('name')
			->email('email')
			->text('phone')
			->text('address')
			->save();
	}

}