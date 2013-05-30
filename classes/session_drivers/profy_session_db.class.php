<?php 

/**
* Session storage in db handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_session_db {

	/*
	* Open session
	*
	* @access	public
	* @return	bool
	*/ 
	function _open($path, $name) {
		return true;
	}

	/*
	* Close session
	*
	* @access	public
	* @return	bool
	*/ 
	function _close() {
		// This is used for a manual call of the session gc function
		$this->_gc(0);
		return true;
	} 

	/*
	* Read session data from database
	*
	* @access	public
	* @return	bool
	*/
	function _read($ses_id) {
		$SESSION_DATA = db()->query_fetch("SELECT * FROM `".db('sessions')."` WHERE `id` = '"._es($ses_id)."'");
		return is_array($SESSION_DATA) && !empty($SESSION_DATA) ? $SESSION_DATA["data"] : "";
	} 

	/*
	* Write new data to database
	*
	* @access	public
	* @return	bool
	*/
	function _write($ses_id, $data) {
		$SESSION_DATA = db()->query_fetch("SELECT * FROM `".db('sessions')."` WHERE `id` = '"._es($ses_id)."'");
		if (is_array($SESSION_DATA) && !empty($SESSION_DATA)) {
			db()->UPDATE("sessions", array(
				"user_id"	=> intval($SESSION_DATA["user_id"]),
				"user_group"=> intval($SESSION_DATA["user_group"]),
				"host_name"	=> _es(common()->get_ip()),
				"data"		=> _es($data),
				"type"		=> _es(MAIN_TYPE),
				"last_time"	=> time(),
			), "`id`='"._es($ses_id)."'");
		} elseif ($data || count($_COOKIE)) {
			// Only save session data when when the browser sends a cookie.	This keeps
			// crawlers out of session table. This improves speed up queries, reduces
			// memory, and gives more useful statistics.
			db()->INSERT("sessions", array(
				"id"		=> _es($ses_id),
				"user_id"	=> 0,
				"user_group"=> 0,
				"start_time"=> time(),
				"last_time"	=> time(),
				"host_name"	=> _es(common()->get_ip()),
				"data"		=> _es($data),
				"type"		=> _es(MAIN_TYPE),
			));
		}
		return true;
	}

	/*
	* Destroy session record in database
	*
	* @access	public
	* @return	bool
	*/
	function _destroy($ses_id) {
		return db()->query("DELETE FROM `".db('sessions')."` WHERE `id` = '"._es($ses_id)."'");
	}

	/*
	* Garbage collection, deletes old sessions
	*
	* @access	public
	* @return	bool
	*/
	function _gc($life_time) {
		// Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
		// value.	For example, if you want user sessions to stay in your database
		// for three weeks before deleting them, you need to set gc_maxlifetime
		// to '1814400'.	At that value, only after a user doesn't log in after
		// three weeks (1814400 seconds) will his/her session be removed.
		$ses_life = strtotime("-5 minutes"); 
// FIXME: need to implement max life time here instead of "-5 minutes"
		db()->query("DELETE FROM `".db('sessions')."` WHERE `last_time` < ".intval(time() - $ses_life));
		return true;
	}
}
