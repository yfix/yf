<?php 

load('session_driver', 'framework', 'classes/session/');
class yf_session_driver_xcache extends yf_session_driver {

	public $CUR_SESSION_NAME	= 'PHPSESSID';
	public $ITEMS_PREFIX		= 'sess_';

	/*
	* Open session
	*/ 
	function _open($path, $name) {
		$this->CUR_SESSION_NAME = $name;
		return true;
	}

	/*
	* Close session
	*/ 
	function _close() {
		return true;
	} 

	/*
	* Read session data
	*/
	function _read($ses_id) {
		$sess_data = xcache_get($this->ITEMS_PREFIX. $ses_id);
		if (!$sess_data) {
			return (''); // Must return '' here.
		} else {
			return $sess_data;
		}
	} 

	/*
	* Write new data
	*/
	function _write($ses_id, $data) {
		return xcache_put($this->ITEMS_PREFIX. $ses_id, $data, main()->SESSION_LIFE_TIME);
	}

	/*
	* Destroy session
	*/
	function _destroy($ses_id) {
		return xcache_unset($this->ITEMS_PREFIX. $ses_id);
	}

	/*
	* Garbage collection, deletes old sessions
	*/
	function _gc($life_time) {
// TODO: check if eaccelerator will do it by itself
		return true;
	}
}
