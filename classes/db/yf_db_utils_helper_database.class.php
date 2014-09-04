<?php

/**
*/
class yf_db_utils_helper_database {

	protected $db = null;
	protected $utils = null;
	protected $db_name = '';

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
	public function info($extra = array(), &$error = false) {
		return $this->utils->database_info($this->db_name, $extra, $error);
	}

	/**
	*/
	public function exists($extra = array(), &$error = false) {
		return $this->utils->database_exists($this->db_name, $extra, $error);
	}

	/**
	*/
	public function drop($extra = array(), &$error = false) {
		return $this->utils->drop_database($this->db_name, $extra, $error);
	}

	/**
	*/
	public function create(array $data, $extra = array(), &$error = false) {
		return $this->utils->create_database($this->db_name, $data, $extra, $error);
	}

	/**
	*/
	public function alter(array $data, $extra = array(), &$error = false) {
		return $this->utils->alter_database($this->db_name, $data, $extra, $error);
	}

	/**
	*/
	public function rename($new_name, $extra = array(), &$error = false) {
		return $this->utils->rename_database($this->db_name, $new_name, $extra, $error);
	}

	/**
	*/
	public function truncate($extra = array(), &$error = false) {
		return $this->utils->truncate_database($this->db_name, $extra, $error);
	}

	/**
	*/
	public function tables($extra = array(), &$error = false) {
		return $this->utils->list_tables($this->db_name, $extra, $error);
	}

	/**
	*/
	public function table($name, $extra = array(), &$error = false) {
		return $this->utils->table($this->db_name.'.'.$name, $extra, $error);
	}

	/**
	*/
	public function views($extra = array(), &$error = false) {
		return $this->utils->list_views($this->db_name, $extra, $error);
	}

	/**
	*/
	public function view($name, $extra = array(), &$error = false) {
		return $this->utils->view($this->db_name.'.'.$name, $extra, $error);
	}

	/**
	*/
	public function triggers($extra = array(), &$error = false) {
		return $this->utils->list_triggers($this->db_name, $extra, $error);
	}

	/**
	*/
	public function trigger($name, $extra = array(), &$error = false) {
		return $this->utils->trigger($this->db_name.'.'.$name, $extra, $error);
	}

	/**
	*/
	public function events($extra = array(), &$error = false) {
		return $this->utils->list_events($this->db_name, $extra, $error);
	}

	/**
	*/
	public function event($name, $extra = array(), &$error = false) {
		return $this->utils->event($this->db_name.'.'.$name, $extra, $error);
	}
}
