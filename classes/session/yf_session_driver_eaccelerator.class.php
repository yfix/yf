<?php 

load('session_driver', 'framework', 'classes/session/');
class yf_session_driver_eaccelerator extends yf_session_driver {

	public $CUR_SESSION_NAME	= 'PHPSESSID';
	public $ITEMS_PREFIX		= 'sess_';

	function open($path, $name) {
		$this->CUR_SESSION_NAME = $name;
		return true;
	}

	function close() {
		return true;
	} 

	function read($ses_id) {
		$sess_data = eaccelerator_get($this->ITEMS_PREFIX. $ses_id);
		if (!$sess_data) {
			return (''); // Must return '' here.
		} else {
			return $sess_data;
		}
	} 

	function write($ses_id, $data) {
		return eaccelerator_put($this->ITEMS_PREFIX. $ses_id, $data, main()->SESSION_LIFE_TIME);
	}

	function destroy($ses_id) {
		return eaccelerator_rm($this->ITEMS_PREFIX. $ses_id);
	}

	function gc($life_time) {
// TODO: check if eaccelerator will do it by itself
		return true;
	}
}
