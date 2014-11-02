<?php

class yf_model_result {
	protected $_model = null;
	public function __construct($data, $model) {
		$this->_set_data($data);
		$this->_model = $model;
	}
	public function __call($name, $args) {
		$this->_sync_model_data();
		return call_user_func_array(array($this->_model, $name), $args);
	}
	public function _set_data($data) {
		foreach ((array)$data as $k => $v) {
			$first = substr($k, 0, 1);
			if (ctype_alpha($first)) {
				$this->$k = $v;
			}
		}
	}
	public function _sync_model_data() {
		foreach (get_object_vars($this) as $var => $value) {
			if (substr($var, 0, 1) === '_') {
				continue;
			}
			$this->_model->$var = $value;
		}
	}
	public function _get_model() {
		return $this->_model;
	}
}
