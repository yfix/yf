<?php

class yf_online_users {

	public $_CACHE_UPDATE_TTL = 30;
	public $_CACHE_CLEANUP_TTL = 90;
	public $_ONLINE_TTL = 60;
	public $_COOKIE_TTL = 3600;

	public $_type = array(
		'user_id_tmp',
		'user_id',
		'admin_id',
	);

	function _init() {
		$this->_type = array_combine( $this->_type, $this->_type );
	}

	function process() {
        list($this->online_user_id, $this->online_user_type) = $this->_set_user_id();
        if (main()->TRACK_ONLINE_STATUS) {
            $this->_update();
            $this->_cleanup();
        }
	}

	function _set_user_id() {
		if (intval($_SESSION['admin_id']) != 0) {
			return array($_SESSION['admin_id'], 'admin_id');
		}
		if (intval(main()->USER_ID) != 0) {
			return array(main()->USER_ID, 'user_id');
		}
		if (intval($_COOKIE['user_id_tmp']) != 0) {
			return array($_COOKIE['user_id_tmp'], 'user_id_tmp');
		}

		// todo: more 'smart' algorythm for user id generation
		setcookie('user_id_tmp', rand(), $_SERVER['REQUEST_TIME'] + $this->_COOKIE_TTL);

		return array($_COOKIE['user_id_tmp'], 'user_id_tmp');
	}

	function _is_online( $user_id, $user_type = null ) {
		$user_id = (int)$user_id;
		if( $user_id < 1 ) { return( null ); }
		if( empty( $this->_type[ $user_type ] ) ) {
			$user_type = 'user_id';
		}
		$time = db()->select( 'time' )->table( 'users_online' )->where( array( 'user_id' => $user_id, 'user_type' => $user_type ) )->get_one();
		$result = ( time() - $this->_ONLINE_TTL ) < $time;
		return( $result );
	}

	function _update() {
		$cache_name = __CLASS__.'|'.__FUNCTION__.'|'.$this->online_user_id.'|'.$this->online_user_type;
		if (cache()->get($cache_name) != 'OK' && intval($this->online_user_id) != 0) {
			db()->replace_safe('users_online', array(
				'user_id'	=> $this->online_user_id,
				'user_type'	=> $this->online_user_type,
				'time'		=> $_SERVER['REQUEST_TIME'],
			));
			cache()->set($cache_name, 'OK', $this->_CACHE_UPDATE_TTL);
        }
        // details not cached for current url to be shown
        if (main()->TRACK_ONLINE_DETAILS && !(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || !empty($_GET['ajax_mode'])) && intval($this->online_user_id) != 0) {
			$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ]
				?: $_SERVER[ 'HTTP_CLIENT_IP' ]
				?: $_SERVER[ 'REMOTE_ADDR' ]
			;
			db()->replace_safe('users_online_details', array(
				'user_id'    => $this->online_user_id,
				'user_type'  => $this->online_user_type,
				'time'       => $_SERVER['REQUEST_TIME'],
				'session_id' => session_id(),
				'url'        => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'ip'         => $ip,
			));
		}
	}

	function _cleanup() {
		// todo: queued
		$cache_name = __CLASS__.'|'.__FUNCTION__;
		if (cache()->get($cache_name) != 'OK') {
			$time = $_SERVER['REQUEST_TIME'] - $this->_ONLINE_TTL;
			db()->query('DELETE FROM '.db('users_online').' WHERE `time`<'.$time);
            if (main()->TRACK_ONLINE_DETAILS) {
				db()->query('DELETE FROM '.db('users_online_details').' WHERE `time`<'.$time);
			}
			cache()->set($cache_name,'OK',$this->_CACHE_CLEANUP_TTL);
		}
	}

}
