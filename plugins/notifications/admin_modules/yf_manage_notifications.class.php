<?php

class yf_manage_notifications {
	
	var $RECEIVER_TYPES = [
		'user_id' => 'user',
		'admin_id' => 'admin',
		'user_id_tmp' => 'guest online',
	];
	
	var $_online_statuses = [
		'0' => 'no',
		'1' => 'yes',
	];
	
	/**
	*/
	function show () {
		return table('SELECT * FROM '.db('notifications'), [
				'filter' => $_SESSION[$_GET['object']],
				'filter_params' => [
					'id'                 => 'like',		
                    'title'              => 'like',
					'content'       	 => 'like',
					'add_date'           => 'dt_between',
                    'receiver_type'      => 'eq',
                    'is_common_template' => 'eq',
                    'template_alias'     => 'like',
				],
			])
			->text('id')
			->text('title')
			->text('content')
			->link('is_common_template', '', $this->_online_statuses)
			->text('template_alias')                
			->link('receiver_type', '', $this->RECEIVER_TYPES)
			->date('add_date', ['format' => 'full', 'nowrap' => 1])				
			->btn('manage receivers', './?object='.$_GET['object'].'&action=view&id=%d')
			->btn_delete()
			->footer_add('add', "./?object=".$_GET['object']."&action=add", ['no_ajax' => 1])				
		;
	}	

	/**
	*/
	function add() {
		$a = $_POST;
        if(intval($_GET['receiver_id']) != 0) $a['receiver_id'] = $_GET['receiver_id'];
        $receiver_type_options = [];
        if(in_array($_GET['receiver_type'],array_keys($this->RECEIVER_TYPES))) {
            $a['receiver_type'] = $_GET['receiver_type'];
        }
		$a['back_link'] = './?object='.$_GET['object'];
		$form = form($a, ['autocomplete' => 'off'])
			->validate([
				'title' => 'trim|required',
				'content' => 'trim|required',
			])
			->db_insert_if_ok('notifications', ['title', 'content', 'receiver_type','is_common_template','template_alias'], ['add_date' => time()])
			->on_after_update(function() {
                if (intval($_POST['receiver_id']) !=0) {
                    db()->insert(db('notifications_receivers'), [
                        'notification_id' => db()->insert_id(),
                        'receiver_id' => intval($_POST['receiver_id']),
                        'receiver_type' => _es($_POST['receiver_type']),
                        'is_read' => 0,
                    ]);
                }
			})
			->text('title')
			->textarea('content')
            ->select_box('is_common_template', $this->_online_statuses)
			->text('template_alias')
        ;
            
        if(in_array($_GET['receiver_type'],array_keys($this->RECEIVER_TYPES))) {
            $form = $form->hidden('receiver_type');
        } else {
            $form = $form->select_box('receiver_type', $this->RECEIVER_TYPES);                
        }

        $form = $form->hidden('receiver_id')
            ->save_and_back();
        return $form;
	}
	
	function view() {
		$A = $this->_get_notification($_GET['id']);
		$info = form($A)
			->info('title')
			->info('content')
			->info('receiver_type')
			->info('is_common_template')
			->info('template_alias')                   
			->info_date('add_date', ['format' => 'full']);

		
		if ($A['receiver_type'] == 'admin_id') {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
				->footer_add('add_receivers', "./?object=".$_GET['object']."&action=add_receivers&id=".intval($_GET['id']), ['no_ajax' => 1])				
			;
		} elseif ($A['receiver_type'] == 'user_id') {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
					
				->date('add_date', ['format' => 'full', 'nowrap' => 1])				
				->footer_add('add_receivers', "./?object=".$_GET['object']."&action=add_receivers&id=".intval($_GET['id']), ['no_ajax' => 1])				
			;
		} else {
			$table = table('SELECT * FROM '.db('notifications_receivers').' WHERE `notification_id`='.intval($_GET['id']))
				->text('receiver_id')
				->text('is_read')
				->date('add_date', ['format' => 'full', 'nowrap' => 1])				
				->footer_add('add_receivers', "./?object=".$_GET['object']."&action=add_receivers&id=".intval($_GET['id']), ['no_ajax' => 1])				
			;			
		}
		$r = [
			'info' => $info,
			'table' => $table,
		];
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
		if (!method_exists($this,$method_name) || !method_exists($this,$method_name."_process")) js_redirect("./?object=".$_GET['object']);		
		if(main()->is_post()) {
			$method_name_process = $method_name."_process";
			$sql = $this->$method_name_process($_GET['id']);
			$receivers = db()->get_2d($sql);
			$sql_arr = [];
			foreach ((array)$receivers as $v) {
				if ($_POST['is_all'] == 1 || $_POST['id'][$v] == 1) {
					$sql_arr[] = "({$_GET['id']}, '{$A['receiver_type']}', {$v}, 0)";
				}
			}
			if (count((array)$sql_arr)>0) {
				db()->query("REPLACE INTO `".db('notifications_receivers')."` (`notification_id`,`receiver_type`,`receiver_id`,`is_read`) VALUES ".implode(",",$sql_arr));
			}
			js_redirect("./?object=".$_GET['object']."&action=view&id=".$_GET['id']);
		}

		$replace = [
			'table' => $this->$method_name($_GET['id']),
			'show_add_selected' => $A['receiver_type'] != 'user_id_tmp' ? 1 : 0,
		];
		return tpl()->parse($_GET['object']."/".__FUNCTION__,$replace);
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
		list($filter_sql,) = _class('table2')->_filter_sql_prepare($filter, [
			'login'	=> 'like',
			'email'	=> 'like',
			'name'	=> 'like',
		],$sql);
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
		list($filter_sql,) = _class('table2')->_filter_sql_prepare($filter, [
			'login'	=> 'like',
			'email'	=> 'like',
		],$sql);
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
		return table($sql, [
				'filter' => $filter,		
				'filter_params' => [
					'login'	=> 'like',
					'email'	=> 'like',
					'name'	=> 'like',
				],
			])
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
		return table($sql, [
				'filter' => $filter,
				'filter_params' => [
					'login'	=> 'like',
					'email'	=> 'like',
				],
			])
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
        if (in_array($_GET['action'],['show',''])) {
            $filter_name = $_GET['object'];
            $r = [
                'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
                'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
            ];
            $order_fields = [];
            foreach (explode('|', 'id|add_date|receiver_type|title|content') as $f) {
                $order_fields[$f] = $f;
            }
            return form($r, [
                    'selected'	=> $_SESSION[$filter_name],
                ])
                ->number('id', ['class' => 'span1', 'min' => 0])
                ->text('title', ['class' => 'input-medium'])
                ->text('content', ['class' => 'input-medium'])
				->datetime_select('add_date',      null, [ 'with_time' => 1 ] )
				->datetime_select('add_date__and', null, [ 'with_time' => 1 ] )                    
                ->select_box('receiver_type', $this->RECEIVER_TYPES, ['show_text' => 1])
                ->select_box('is_common_template', $this->_online_statuses, ['show_text' => 1])                    
                ->text('template_alias', ['class' => 'input-medium'])                    
                ->select_box('order_by', $order_fields, ['show_text' => 1])
                ->radio_box('order_direction', ['asc'=>'Ascending','desc'=>'Descending'])
                ->save_and_clear();
            ;
        }
		if (!in_array($_GET['action'], ['add_receivers'])) {
			return false;
		}
		$A = $this->_get_notification($_GET['id']);
		$receiver_type = $A['receiver_type'];
		
		$method_name = "_show_filter_".$A['receiver_type'];
		if (!method_exists($this,$method_name)) js_redirect("./?object=".$_GET['object']);
		return $this->$method_name();
		
	}
	
	function _show_filter_admin_id() {		
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		$r = [
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		];
		$order_fields = [];
		foreach (explode('|', 'login|email|group|first_name|last_name|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		$r['notification_id'] = $_GET['id'];
		return form($r, [
				'selected'	=> $_SESSION[$filter_name],
			])
			->hidden('notification_id')				
			->login('login', ['class' => 'input-medium'])
			->email('email', ['class' => 'input-medium'])
			->select_box('group', main()->get_data('admin_groups'))
			->select_box('order_by', $order_fields, ['show_text' => 1])
			->select_box('online', $this->_online_statuses, ['show_text' => 1])
			->radio_box('order_direction', ['asc'=>'Ascending','desc'=>'Descending'])
			->save_and_clear();
		;
	}

	function _show_filter_user_id() {
		$filter_name = $_GET['object'].'__'.$_GET['action'].'__admin_id';
		$r = [
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		];
		$order_fields = [];
		foreach (explode('|', 'name,login,email|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		$r['notification_id'] = $_GET['id'];
		return form($r, [
				'selected'	=> $_SESSION[$filter_name],
			])
			->hidden('notification_id')
			->number('id')
			->text('name')
			->login('login')
			->email('email')
			->select_box('group', main()->get_data('user_groups'), ['show_text' => 1])
			->select_box('order_by', $order_fields, ['show_text' => 1])
			->select_box('online', $this->_online_statuses, ['show_text' => 1])				
			->radio_box('order_direction', ['asc'=>'Ascending','desc'=>'Descending'])
			->save_and_clear();
		;		
	}

	function _show_filter_user_id_tmp() {
		return '';
	}
	
	function filter_save() {
		$A = $this->_get_notification($_POST['notification_id']);
		
		$filter_name = $_GET['id'] == 'manage_notifications' ? 'manage_notifications' : ($_GET['object'].'__add_receivers__'.$A['receiver_type']);
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = [];
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit|notification_id') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
        if ($_GET['id'] == 'manage_notifications') {
            $redirect_url = "./?object=".$_GET['object'];
        } else {
            $redirect_url = "./?object=".$_GET['object']."&action=add_receivers&id=".$_POST['notification_id'];
        }
		return js_redirect($redirect_url);
	}
	
	
	function _get_notification($id) {
		if (!empty($this->notifications[$id])) return $this->notifications[$id];
		$A = db()->query_fetch("SELECT * FROM `".db('notifications')."` WHERE `id`=".intval($id));
		if (empty($A)) js_redirect("./?object=".$_GET['object']);
		$this->notifications[$id] = $A;
		return $A;
	}
}
