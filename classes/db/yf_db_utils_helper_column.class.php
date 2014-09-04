<?php

/**
*/
class yf_db_utils_helper_column {

	protected $db = null;
	protected $utils = null;
	protected $db_name = '';
	protected $table = '';
	protected $col = '';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	*/
	function _setup($params) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	/**
	*/
	function exists($extra = array(), &$error = false) {
		return $this->utils->column_exists($this->db_name.'.'.$this->table, $this->col, $extra, $error);
	}

	/**
	*/
	function info($extra = array(), &$error = false) {
		return $this->utils->column_info($this->db_name.'.'.$this->table, $this->col, $extra, $error);
	}

	/**
	*/
	function drop($extra = array(), &$error = false) {
		return $this->utils->drop_column($this->db_name.'.'.$this->table, $this->col, $extra, $error);
	}

	/**
	*/
	function add(array $data, $extra = array(), &$error = false) {
		return $this->utils->add_column($this->db_name.'.'.$this->table, $this->col, $data, $extra, $error);
	}

	/**
	*/
	function alter(array $data, $extra = array(), &$error = false) {
		return $this->utils->alter_column($this->db_name.'.'.$this->table, $this->col, $data, $extra, $error);
	}

	/**
	*/
	function rename($new_name, $extra = array(), &$error = false) {
		return $this->utils->rename_column($this->db_name.'.'.$this->table, $this->col, $new_name, $extra, $error);
	}
}
