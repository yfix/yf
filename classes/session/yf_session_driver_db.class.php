<?php

load('session_driver', 'framework', 'classes/session/');
class yf_session_driver_db extends yf_session_driver {

	/*
	* Open session
	*/ 
	function _open($path, $name) {
		return true;
	}

	/*
	* Close session
	*/ 
	function _close() {
		// This is used for a manual call of the session gc function
		$this->_gc(0);
		return true;
	} 

	/*
	* Read session data from database
	*/
	function _read($ses_id) {
		$session = db()->get('SELECT * FROM '.db('sessions').' WHERE id = "'._es($ses_id).'"');
		return is_array($session) && !empty($session) ? $session['data'] : '';
	} 

	/*
	* Write new data to database
	*/
	function _write($ses_id, $data) {
		$session = db()->get('SELECT * FROM '.db('sessions').' WHERE id = "'._es($ses_id).'"');
		if (is_array($session) && !empty($session)) {
			db()->update_safe('sessions', array(
				'user_id'	=> (int)$session['user_id'],
				'user_group'=> (int)$session['user_group'],
				'host_name'	=> common()->get_ip(),
				'data'		=> $data,
				'type'		=> MAIN_TYPE,
				'last_time'	=> time(),
			), 'id="'.db()->es($ses_id).'"');
		} elseif ($data || count($_COOKIE)) {
			// Only save session data when when the browser sends a cookie.	This keeps
			// crawlers out of session table. This improves speed up queries, reduces
			// memory, and gives more useful statistics.
			db()->insert_safe('sessions', array(
				'id'		=> $ses_id,
				'user_id'	=> 0,
				'user_group'=> 0,
				'start_time'=> time(),
				'last_time'	=> time(),
				'host_name'	=> common()->get_ip(),
				'data'		=> $data,
				'type'		=> MAIN_TYPE,
			));
		}
		return true;
	}

	/*
	* Destroy session record in database
	*/
	function _destroy($ses_id) {
		return db()->query('DELETE FROM '.db('sessions').' WHERE id = "'._es($ses_id).'"');
	}

	/*
	* Garbage collection, deletes old sessions
	*/
	function _gc($life_time) {
		// Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
		// value.	For example, if you want user sessions to stay in your database
		// for three weeks before deleting them, you need to set gc_maxlifetime
		// to '1814400'.	At that value, only after a user doesn't log in after
		// three weeks (1814400 seconds) will his/her session be removed.
		$ses_life = strtotime('-5 minutes'); 
// FIXME: need to implement max life time here instead of '-5 minutes'
		return db()->query('DELETE FROM '.db('sessions').' WHERE last_time < '.intval(time() - $ses_life));
	}
}
