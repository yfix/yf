<?php

class yf_form2_auto {

	/**
	*/
	function _field_type($type = '') {
		$type = trim(strtolower($type));
		if (strpos($type, 'int') !== false) {
			$type = 'int';
		} elseif (strpos($type, 'datetime') !== false) {
			$type = 'datetime';
		} elseif (strpos($type, 'date') !== false) {
			$type = 'date';
		} elseif (strpos($type, 'time') !== false) {
			$type = 'time';
		} elseif (strpos($type, 'float') !== false) {
			$type = 'float';
		} elseif (strpos($type, 'double') !== false) {
			$type = 'double';
		} elseif (strpos($type, 'decimal') !== false) {
			$type = 'decimal';
		} elseif (strpos($type, 'text') !== false) {
			$type = 'text';
		} elseif (strpos($type, 'char') !== false) {
			$type = 'char';
		} elseif (strpos($type, 'blob') !== false) {
			$type = 'blob';
		} elseif (strpos($type, 'enum') !== false) {
			$type = 'enum';
		} elseif (strpos($type, 'set') !== false) {
			$type = 'set';
		} else {
			$type = '';
		}
		return $type;
	}

	/**
	* Enable automatic fields parsing mode
	*/
	function auto($table = '', $id = '', $params = array(), $__this) {
		if ($params['links_add']) {
			$__this->_params['links_add'] = $params['links_add'];
		}
		if ($table && $id) {
// TODO: use db_installer/fields/  info
			$columns = db()->meta_columns($table);
			$info = db()->get('SELECT * FROM '.db()->es($table).' WHERE id='.intval($id));
			if (!is_array($__this->_replace)) {
				$__this->_replace = array();
			}
			foreach ((array)$info as $k => $v) {
				$__this->_replace[$k] = $v;
			}
			foreach((array)$columns as $name => $details) {
#var_dump($details);
				$type = $this->_field_type($details['type']);
				$length = intval($details['max_length']);
// TODO: detect numeric like time: 1234567890
// TODO: detect YYYY-mm-dd as date, YYYY-mm-dd HH:ii:ss as datetime, HH:ii:ss as time
// TODO: detect enum/set as radio_box
// TODO: detect field length and apply it as atribute
// TODO: detect signed/unsigned field for int/float/double length and apply it as atribute
// TODO: detect foreign keys as select box by constraint
				if ($name == 'id') {
					$func = 'info';
				} elseif ($name == 'login') {
					$func = 'login';
				} elseif ($name == 'email') {
					$func = 'email';
				} elseif ($name == 'phone') {
					$func = 'phone';
				} elseif ($name == 'active') {
					$func = 'active_box';
#				} elseif (strpos($type, 'enum') !== false) {
#					$func = 'radio_box';
#				} elseif (strpos($type, 'set') !== false) {
#					$func = 'radio_box';
				} elseif ($type == 'datetime') {
					$func = 'datetime_select';
				} elseif ($type == 'date') {
					$func = 'datetime_select';
				} elseif ($type == 'time') {
					$func = 'datetime_select';
				} elseif ($type == 'int') {
					if ((strpos($name, 'date') !== false || strpos($name, 'time') !== false) && in_array($length, array(10,11))) {
						$func = 'datetime_select';
					} else {
						$func = 'number';
					}
				} elseif ($type == 'float') {
					$func = 'float';
				} elseif ($type == 'double') {
					$func = 'float';
				} elseif ($type == 'decimal') {
					$func = 'float';
				} elseif ($type == 'text') {
					$func = 'textarea';
				} else {
					$func = 'text';
				}
				$__this->$func($name);
			}
		} elseif ($__this->_sql && $__this->_replace) {
			foreach((array)$__this->_replace as $name => $v) {
				$__this->container($v, $name);
			}
		}
		$__this->save_and_back();
		return $__this;
	}
}