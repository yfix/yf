<?php 

/**
* Session storage in EAccelerator (http://bart.eaccelerator.net/doc/phpdoc/)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_session_eaccelerator {

	/** @var string */
	public $CUR_SESSION_NAME	= "PHPSESSID";
	/** @var string Prefix */
	public $ITEMS_PREFIX		= "sess_";

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
		$sess_data = eaccelerator_get($this->ITEMS_PREFIX. $ses_id);
		if (!$sess_data) {
			return (""); // Must return "" here.
		} else {
			return $sess_data;
		}
	} 

	/*
	* Write new data
	*/
	function _write($ses_id, $data) {
		return eaccelerator_put($this->ITEMS_PREFIX. $ses_id, $data, main()->SESSION_LIFE_TIME);
	}

	/*
	* Destroy session
	*/
	function _destroy($ses_id) {
		return eaccelerator_rm($this->ITEMS_PREFIX. $ses_id);
	}

	/*
	* Garbage collection, deletes old sessions
	*/
	function _gc($life_time) {
// TODO: check if eaccelerator will do it by itself
		return true;
	}
}
