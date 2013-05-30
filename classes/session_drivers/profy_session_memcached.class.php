<?php 

/**
* Session storage in memcached handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_session_memcached {

	/** @var string */
	var $CUR_SESSION_NAME	= "PHPSESSID";
	/** @var string Prefix */
	var $ITEMS_PREFIX		= "sess:";

	/**
	* Constructor (PHP 4.x)
	*/
	function profy_session_memcached () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		if (function_exists("cache_memcached_connect")) {
			$this->MC_OBJ = cache_memcached_connect();
		}
	}

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
		$sess_data = $this->MC_OBJ->get($this->ITEMS_PREFIX. $ses_id);
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
	
		$result = $this->MC_OBJ->replace($this->ITEMS_PREFIX. $ses_id, $data, MEMCACHE_COMPRESSED, main()->SESSION_LIFE_TIME);
	
		if (!$result) {
			return $this->MC_OBJ->set($this->ITEMS_PREFIX. $ses_id, $data, MEMCACHE_COMPRESSED, main()->SESSION_LIFE_TIME);
		}
		//return ($this->MC_OBJ->put($this->ITEMS_PREFIX. $ses_id, $data, main()->SESSION_LIFE_TIME));
	}

	/*
	* Destroy session
	*/
	function _destroy($ses_id) {
		return ($this->MC_OBJ->delete($this->ITEMS_PREFIX. $ses_id));
	}

	/*
	* Garbage collection, deletes old sessions
	*/
	function _gc($life_time) {
		// Memcached will do it by itself
		return true;
	}
}
