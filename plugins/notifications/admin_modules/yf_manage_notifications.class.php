<?php

class yf_manage_notifications {
	
	var $RECEIVER_TYPES = array(
		'user_id' => 'user',
		'admin_id' => 'admin',
		'user_id_tmp' => 'guest online',
	);
	
	var $_online_statuses = array(
		'0' => 'no',
		'1' => 'yes',
	);
	
	/**
	*/
	function show () {
		return table('SELECT * FROM '.db('notifications'))
			->text('id')
			->text('title')
			->text('content')
			->link('receiver_type', '', $this->RECEIVER_TYPES)
			->date('add_date', array('format' => 'full', 'nowrap' => 1))				
			->btn('manage receivers', './?object='.$_GET['object'].'&action=view&id=%d')
			->btn_delete()
			->footer_add('add', "./?object=".__CLASS__."&action=add", array('no_ajax' => 1))				
		;
	}	

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'title' => 'trim|required',
				'content' => 'trim|required',
			))
			->db_insert_if_ok('notifications', array('title', 'content', 'receiver_type'), array('add_date' => time()))
			->on_after_update(function() {
				// todo: receivers
			})
			->text('title')
			->textarea('content')
			->select_box('receiver_type', $this->RECEIVER_TYPES)
			->save_and_back();
	}
	
	function view() {
		$A = $this->_get_notification($_GET['id']);
		$info = form($A)
			->info('title')
			->info('content')
			->info('receiver_type')				
			->info_date('add_date', array('format' => 'full'));

		
		if ($A['receiver_type'] == 'admin_id') {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
				->footer_add('add_receivers', "./?object=".__CLASS__."&action=add_receivers&id=".intval($_GET['id']), array('no_ajax' => 1))				
			;
		} elseif ($A['receiver_type'] == 'user_id') {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
					
				->date('add_date', array('format' => 'full', 'nowrap' => 1))				
				->footer_add('add_receivers', "./?object=".__CLASS__."&action=add_receivers&id=".intval($_GET['id']), array('no_ajax' => 1))				
			;
		} else {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
				->date('add_date', array('format' => 'full', 'nowrap' => 1))				
				->footer_add('add_receivers', "./?object=".__CLASS__."&action=add_receivers&id=".intval($_GET['id']), array('no_ajax' => 1))				
			;			
		}
		$r = array(
			'info' => $info,
			'table' => $table,
		);
		return tpl()->parse("manage_notifications/view",$r);
	}
	
	function delete () {
		$A = $this->_get_notification($_GET['id']);
		if ($A['id']) {
			db()->query('DELETE FROM '.db('notifications').' WHERE id='.$_GET['id']);
			db()->query('DELETE FROM '.db('notifications_receivers').' WHERE notification_id='.$_GET['id']);
		}
		return js_redirect('./?object='.main()->_get('object').'action=products');
	}	
	
	function add_receivers() {
		$A = $this->_get_notification($_GET['id']);
		$method_name = "_add_receivers_".$A['receiver_type'];		
		if (!method_exists($this,$method_name) || !method_exists($this,$method_name."_process")) js_redirect("./?object=".__CLASS__);		
		if(main()->is_post()) {
			$method_name_process = $method_name."_process";
			$sql = $this->$method_name_process($_GET['id']);
			$receivers = db()->get_2d($sql);
			$sql_arr = array();
			foreach ((array)$receivers as $v) {
				if ($_POST['is_all'] == 1 || $_POST['id'][$v] == 1) {
					$sql_arr[] = "({$_GET['id']}, '{$A['receiver_type']}', {$v}, 0)";
				}
			}
			if (count($sql_arr)>0) {
				db()->query("REPLACE INTO `".db('notifications_receivers')."` (`notification_id`,`receiver_type`,`receiver_id`,`is_read`) VALUES ".implode(",",$sql_arr));
			}
			js_redirect("./?object=".__CLASS__."&action=view&id=".$_GET['id']);
		}

		$replace = array(
			'table' => $this->$method_name($_GET['id']),
			'show_add_selected' => $A['receiver_type'] != 'user_id_tmp' ? 1 : 0,
		);
		return tpl()->parse(__CLASS__."/".__FUNCTION__,$replace);
	}

	function _add_receivers_user_id_process() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__user_id';
		if ($_SESSION[$filter_name]['online'] != '') {
			$sql = "SELECT `id` FROM ".db('user')." WHERE `id` ".($_SESSION[$filter_name]['online'] != 1 ? "NOT" : "")." IN (SELECT `user_id` FROM ".db('users_online')." WHERE `user_type`='user_id') /*FILTER*/";
		} else {
			$sql = "SELECT `id` FROM ".db('user')." WHERE 1 /*FILTER*/";
		}
		$filter = $_SESSION[$filter_name];
		unset($filter['online']);
		list($filter_sql,) = _class('table2')->_filter_sql_prepare($filter, array(
			'login'	=> 'like',
			'email'	=> 'like',
			'name'	=> 'like',
		),$sql);
		$sql = str_replace('/*FILTER*/', ' '.$filter_sql.' ', $sql);
		return $sql;
	}
	
	function _add_receivers_admin_id_process() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		if ($_SESSION[$filter_name]['online'] != '') {
			$sql = "SELECT `id` FROM ".db('admin')." WHERE `id` ".($_SESSION[$filter_name]['online'] != 1 ? "NOT" : "")." IN (SELECT `user_id` FROM ".db('users_online')." WHERE `user_type`='admin_id') /*FILTER*/ /*ORDER*/";
		} else {
			$sql = "SELECT `id` FROM ".db('admin')." WHERE 1 /*FILTER*/ /*ORDER*/";
		}
		$filter = $_SESSION[$filter_name];
		unset($filter['online']);
		list($filter_sql,) = _class('table2')->_filter_sql_prepare($filter, array(
			'login'	=> 'like',
			'email'	=> 'like',
		),$sql);
		$sql = str_replace('/*FILTER*/', ' '.$filter_sql.' ', $sql);
		return $sql;
	}
	
	function _add_receivers_user_id_tmp_process() {
		$sql = "SELECT `user_id` AS `id` FROM `".db('users_online')."` WHERE `user_type`='user_id_tmp'";
		return $sql;
	}	
	
	function _add_receivers_user_id() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__user_id';
		if ($_SESSION[$filter_name]['online'] != '') {
			$sql = "SELECT * FROM ".db('user')." WHERE `id` ".($_SESSION[$filter_name]['online'] != 1 ? "NOT" : "")." IN (SELECT `user_id` FROM ".db('users_online')." WHERE `user_type`='user_id') /*FILTER*/ /*ORDER*/";
		} else {
			$sql = "SELECT * FROM ".db('user')." WHERE 1 /*FILTER*/ /*ORDER*/";
		}
		$filter = $_SESSION[$filter_name];
		unset($filter['online']);
		return table($sql, array(
				'filter' => $filter,		
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
					'name'	=> 'like',
				),
			))
			->check_box('id')				
			->text('id')
			->text('login')
			->text('email')
			->text('name')
		;
	}
	
	function _add_receivers_admin_id() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		if ($_SESSION[$filter_name]['online'] != '') {
			$sql = "SELECT * FROM ".db('admin')." WHERE `id` ".($_SESSION[$filter_name]['online'] != 1 ? "NOT" : "")." IN (SELECT `user_id` FROM ".db('users_online')." WHERE `user_type`='admin_id') /*FILTER*/ /*ORDER*/";
		} else {
			$sql = "SELECT * FROM ".db('admin')." WHERE 1 /*FILTER*/ /*ORDER*/";
		}
		$filter = $_SESSION[$filter_name];
		unset($filter['online']);
		return table($sql, array(
				'filter' => $filter,
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
				),
			))
			->check_box('id')
			->text('id')				
			->text('login')
			->text('email')
			->link('group', '', main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->date('add_date');	
	}	
	
	function _add_receivers_user_id_tmp() {
		$A = db()->get("SELECT COUNT(`user_id`) AS `cnt` FROM `".db('users_online')."` WHERE `user_type`='user_id_tmp'");
		return $A['cnt']." ".t('guests are online now')."<br />";
	}
	
	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('add_receivers'))) {
			return false;
		}
		$A = $this->_get_notification($_GET['id']);
		$receiver_type = $A['receiver_type'];
		
		$method_name = "_show_filter_".$A['receiver_type'];
		if (!method_exists($this,$method_name)) js_redirect("./?object=".__CLASS__);
		return $this->$method_name();
		
	}
	
	function _show_filter_admin_id() {		
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'login|email|group|first_name|last_name|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		$r['notification_id'] = $_GET['id'];
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->hidden('notification_id')				
			->login('login', array('class' => 'input-medium'))
			->email('email', array('class' => 'input-medium'))
			->select_box('group', main()->get_data('admin_groups'))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->select_box('online', $this->_online_statuses, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	function _show_filter_user_id() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'name,login,email|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		$r['notification_id'] = $_GET['id'];
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->hidden('notification_id')
			->number('id')
			->text('name')
			->login('login')
			->email('email')
			->select_box('group', main()->get_data('user_groups'), array('show_text' => 1))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->select_box('online', $this->_online_statuses, array('show_text' => 1))				
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;		
	}

	function _show_filter_user_id_tmp() {
		return '';
	}
	
	function filter_save() {
		$A = $this->_get_notification($_POST['notification_id']);
		
		$filter_name = $_GET['object'].'__add_receivers__'.$A['receiver_type'];
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit|notification_id') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		$redirect_url = "./?object=".__CLASS__."&action=add_receivers&id=".$_POST['notification_id'];
		return js_redirect($redirect_url);
	}
	
	
	function _get_notification($id) {
		if (!empty($this->notifications[$id])) return $this->notifications[$id];
		$A = db()->query_fetch("SELECT * FROM `".db('notifications')."` WHERE `id`=".intval($id));
		if (empty($A)) js_redirect("./?object=".__CLASS__);
		$this->notifications[$id] = $A;
		return $A;
	}
}
