<?php

class yf_online_users {
	var $_CACHE_UPDATE_TTL = 30;
	var $_CACHE_CLEANUP_TTL = 90;
	var $_ONLINE_TTL = 60;
	var $_COOKIE_TTL = 3600;
	
	function process() {
        if (main()->TRACK_ONLINE_STATUS) {
            list($this->online_user_id,$this->online_user_type) = $this->_set_user_id();
            $this->_update();
            $this->_cleanup();
        }
	}
	
	function _set_user_id() {
		if (intval($_SESSION['admin_id']) != 0) return array($_SESSION['admin_id'], 'admin_id');
		if (intval(main()->USER_ID) != 0) return array(main()->USER_ID, 'user_id');
		if (intval($_COOKIE['user_id_tmp']) != 0) return array($_COOKIE['user_id_tmp'], 'user_id_tmp');
		
		// todo: more 'smart' algorythm for user id generation
		setcookie('user_id_tmp', rand(), $_SERVER['REQUEST_TIME']+$this->_COOKIE_TTL);
		
		return array($_COOKIE['user_id_tmp'], 'user_id_tmp');
	}
	
	function _update() {
		$cache_name = __CLASS__.'|'.__FUNCTION__."|".$this->online_user_id."|".$this->online_user_type;
		if (cache()->get($cache_name) != 'OK' && intval($this->online_user_id)!=0) {
			db()->replace(db('users_online'),array(
				'user_id' => $this->online_user_id,
				'user_type' => $this->online_user_type,
				'time' => $_SERVER['REQUEST_TIME'],
			));
            if (main()->TRACK_ONLINE_DETAILS) {
                db()->replace(db('users_online_details'),array(
                    'user_id' => $this->online_user_id,
                    'user_type' => $this->online_user_type,
                    'time' => $_SERVER['REQUEST_TIME'],
                    'session_id' => session_id(),
                    'url' => "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                ));
                
            }
            
			cache()->set($cache_name,'OK',$this->_CACHE_UPDATE_TTL);
		}
	}

	function _cleanup() {
		// todo: queued
		$cache_name = __CLASS__.'|'.__FUNCTION__;
		if (cache()->get($cache_name) != 'OK') {
			$time = $_SERVER['REQUEST_TIME'] - $this->_ONLINE_TTL;
			db()->query("DELETE FROM `".db('users_online')."` WHERE `time`<".$time);
            if ($this->STORE_DETAILS) db()->query("DELETE FROM `".db('users_online_details')."` WHERE `time`<".$time);
			cache()->set($cache_name,'OK',$this->_CACHE_CLEANUP_TTL);
		}
	}
		
}
