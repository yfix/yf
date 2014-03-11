<?php 

load('session_driver', 'framework', 'classes/session/');
class yf_session_driver_memcache extends yf_session_driver {

	public $CUR_SESSION_NAME	= 'PHPSESSID';
	public $ITEMS_PREFIX		= 'sess:';

	function __construct () {
		if (function_exists('cache_memcached_connect')) {
			$this->MC_OBJ = cache_memcached_connect();
		}
	}

	function open($path, $name) {
		$this->CUR_SESSION_NAME = $name;
		return true;
	}

	function close() {
		return true;
	} 

	function read($ses_id) {
		$sess_data = $this->MC_OBJ->get($this->ITEMS_PREFIX. $ses_id);
		if (!$sess_data) {
			return (''); // Must return '' here.
		} else {
			return $sess_data;
		}
	} 

	function write($ses_id, $data) {
		$result = $this->MC_OBJ->replace($this->ITEMS_PREFIX. $ses_id, $data, MEMCACHE_COMPRESSED, main()->SESSION_LIFE_TIME);
		if (!$result) {
			return $this->MC_OBJ->set($this->ITEMS_PREFIX. $ses_id, $data, MEMCACHE_COMPRESSED, main()->SESSION_LIFE_TIME);
		}
		//return ($this->MC_OBJ->put($this->ITEMS_PREFIX. $ses_id, $data, main()->SESSION_LIFE_TIME));
	}

	function destroy($ses_id) {
		return ($this->MC_OBJ->delete($this->ITEMS_PREFIX. $ses_id));
	}

	function gc($life_time) {
		// Memcached will do it by itself
		return true;
	}
}
