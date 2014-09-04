<?php

/**
*/
class yf_db_utils_helper_view {

	protected $db = null;
	protected $utils = null;
	protected $db_name = '';
	protected $view = '';

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
		return $this->utils->view_exists($this->db_name.'.'.$this->view, $extra, $error);
	}

	/**
	*/
	public function info($extra = array(), &$error = false) {
		return $this->utils->view_info($this->db_name.'.'.$this->view, $extra, $error);
	}

	/**
	*/
	public function drop($extra = array(), &$error = false) {
		return $this->utils->drop_view($this->db_name.'.'.$this->view, $extra, $error);
	}

	/**
	*/
	public function create($sql_as, $extra = array(), &$error = false) {
		return $this->utils->create_view($this->db_name.'.'.$this->view, $sql_as, $extra, $error);
	}
}
