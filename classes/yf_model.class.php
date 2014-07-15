<?php

// TODO: extend it

/**
*/
class yf_model {

	/**
	* Catch missing method call
	*/
	public function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	public function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	*/
	public function _get_table_name($name = '') {
		if (!$name) {
			$name = $this->_table;
		}
		$db = &$this->_db;
		if (isset($db->_found_tables)) {
			return $db->_found_tables[$name];
		}
		$tables = $db->utils()->list_tables();
		if ($db->DB_PREFIX) {
			$p_len = strlen($db->DB_PREFIX);
			$tmp = array();
			foreach ($tables as $real) {
				$short = $real;
				if (substr($real, 0, $p_len) == $db->DB_PREFIX) {
					$short = substr($real, $p_len);
				}
				$tmp[$short] = $real;
			}
			$tables = $tmp;
		}
		$db->_found_tables = $tables;
		return $db->_found_tables[$name];
	}

	/**
	*/
	public function count() {
#		$cache_name = __FUNCTION__.$this->__changed;
#		if (isset($this->__cache[$cache_name])) {
#			return $this->__cache[$cache_name];
#		}
		$db = &$this->_db;
		$result = $db->get_one('SELECT COUNT(*) FROM '.$this->_get_table_name());
#		$this->__cache[$cache_name] = $result;
		return $result;
	}

	/**
	*/
	public function find() {
		$db = &$this->_db;
		return $db->from($this->_get_table_name())->where(array('__args__' => func_get_args()))->get_all(array('as_object' => true));
	}

	/**
	*/
	public function first() {
		$db = &$this->_db;
		return (object) $db->from($this->_get_table_name())->where(array('__args__' => func_get_args()))->get();
	}

	/**
	* Determine if the model or a given attribute has been modified.
	*/
	public function is_dirty($attr = null) {
// TODO
	}

	/**
	* Get the attributes that have been changed since last sync.
	*/
	public function get_dirty($attr = null) {
// TODO
	}
}
