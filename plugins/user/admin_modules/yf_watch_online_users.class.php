<?php

class yf_watch_online_users{
	var $USER_TYPES = [
		'user_id' => 'user',
		'admin_id' => 'admin',
		'user_id_tmp' => 'guest online',
	];
    
	function _init(){
	}

    
	function show(){
        if (!main()->TRACK_ONLINE_STATUS) {
			return t('online users tracking is disabled');
		}
		jquery('
            setTimeout(function(){
                window.location.reload(1);
            }, 60000);
		');
		$filter_name = $_GET['object'];
		if (!$_SESSION[$filter_name]['user_type']) {
            $_SESSION[$filter_name]['user_type'] = 'user_id';
		}
        if (main()->TRACK_ONLINE_DETAILS) {
            return table('SELECT *,`user_id` AS `id` FROM '.db('users_online_details'), [
                    'filter' => $_SESSION[$filter_name],
                ])
                ->text('user_id')
                ->text('url')
                ->text('ip')
                ->text('session_id')
                ->text('user_agent')
                ->date('time', ['format' => 'full', 'nowrap' => 1])
                ->btn('send notification', './?object=manage_notifications&action=add&receiver_id=%d&receiver_type='.$_SESSION[$filter_name]['user_type'])
            ;
        } else {
            return table('SELECT *,`user_id` AS `id`  FROM '.db('users_online'), [
                    'filter' => $_SESSION[$filter_name],
                ])
                ->text('user_id')
                ->date('time', ['format' => 'full', 'nowrap' => 1])
                ->btn('send notification', './?object=manage_notifications&action=add&receiver_id=%d&receiver_type='.$_SESSION[$filter_name]['user_type'])
            ;             
        }
    }
    
	/**
	*/
	function _show_filter() {
		if (!main()->TRACK_ONLINE_STATUS) {
			return false;
		}        
		$filter_name = $_GET['object'];
		$r = [
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		];
		return form($r, [
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			])
			->select_box('user_type', $this->USER_TYPES)
			->save();
		;
        
    }
    
	function filter_save() {
		$filter_name = $_GET['object'];
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = [];
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		$redirect_url = "./?object=".$_GET['object'];
		return js_redirect($redirect_url);
	}
    
}