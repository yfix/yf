<?php

/**
*/
class yf_db_utils_helper_table {

	protected $utils = null;
	protected $db_name = '';
	protected $table = '';

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
	public function _setup($params) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	/**
	*/
	public function exists($extra = array(), &$error = false) {
		return $this->utils->table_exists($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function info($extra = array(), &$error = false) {
		return $this->utils->table_info($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function drop($extra = array(), &$error = false) {
		return $this->utils->drop_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function create(array $data, $extra = array(), &$error = false) {
		return $this->utils->create_table($this->db_name.'.'.$this->table, $data, $extra, $error);
	}

	/**
	*/
	public function alter(array $data, $extra = array(), &$error = false) {
		return $this->utils->alter_table($this->db_name.'.'.$this->table, $data, $extra, $error);
	}

	/**
	*/
	public function rename($new_name, $extra = array(), &$error = false) {
		return $this->utils->rename_table($this->db_name.'.'.$this->table, $new_name, $extra, $error);
	}

	/**
	*/
	public function truncate($extra = array(), &$error = false) {
		return $this->utils->truncate_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function check($extra = array(), &$error = false) {
		return $this->utils->check_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function repair($extra = array(), &$error = false) {
		return $this->utils->repair_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function optimize($extra = array(), &$error = false) {
		return $this->utils->optimize_table($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function columns($extra = array(), &$error = false) {
		return $this->utils->list_columns($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function column($name, $extra = array(), &$error = false) {
		return $this->utils->column($this->db_name, $this->table, $name, $extra, $error);
	}

	/**
	*/
	public function indexes($extra = array(), &$error = false) {
		return $this->utils->list_indexes($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function index($name, $extra = array(), &$error = false) {
		return $this->utils->index($this->db_name, $this->table, $name, $extra, $error);
	}

	/**
	*/
	public function foreign_keys($extra = array(), &$error = false) {
		return $this->utils->list_foreign_keys($this->db_name.'.'.$this->table, $extra, $error);
	}

	/**
	*/
	public function foreign_key($name, $extra = array(), &$error = false) {
		return $this->utils->foreign_key($this->db_name, $this->table, $name, $extra, $error);
	}
}
