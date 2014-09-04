<?php

/**
*/
class yf_db_utils_helper_table {

	protected $db = null;
	protected $utils = null;
	protected $db_name = '';
	protected $table = '';

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
		return $this->utils->table_exists($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function info($extra = array(), &$error = false) {
		return $this->utils->table_info($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function drop($extra = array(), &$error = false) {
		return $this->utils->drop_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function create(array $data, $extra = array(), &$error = false) {
		return $this->utils->create_table($this->db_name.'.'.$this->table, $data, $extra, $error);
	}

	/**
	*/
	function alter(array $data, $extra = array(), &$error = false) {
		return $this->utils->alter_table($this->db_name.'.'.$this->table, $data, $extra, $error);
	}

	/**
	*/
	function rename($new_name, $extra = array(), &$error = false) {
		return $this->utils->rename_table($this->db_name.'.'.$this->table, $new_name, $extra, $error);
	}

	/**
	*/
	function truncate($extra = array(), &$error = false) {
		return $this->utils->truncate_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function check($extra = array(), &$error = false) {
		return $this->utils->check_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function repair($extra = array(), &$error = false) {
		return $this->utils->repair_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function optimize($extra = array(), &$error = false) {
		return $this->utils->optimize_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function columns($extra = array(), &$error = false) {
		return $this->utils->list_columns($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function column($extra = array(), &$error = false) {
		return $this->utils->column($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function indexes($extra = array(), &$error = false) {
		return $this->utils->list_indexes($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function index($extra = array(), &$error = false) {
		return $this->utils->index($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function foreign_keys($extra = array(), &$error = false) {
		return $this->utils->list_foreign_keys($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	function foreign_key($extra = array(), &$error = false) {
		return $this->utils->foreign_key($this->db_name.'.'.$this->table, $extra, $error);
	}
}
