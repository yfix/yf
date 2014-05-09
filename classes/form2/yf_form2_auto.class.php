<?php

class yf_form2_auto {

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
				$type = strtoupper($details['type']);
// TODO: detect numeric like time: 1234567890
// TODO: detect YYYY-mm-dd as date, YYYY-mm-dd HH:ii:ss as datetime, HH:ii:ss as time
// TODO: detect email field
// TODO: detect phone field
// TODO: detect password/pswd field
// TODO: detect decimal as float
// TODO: detect float/double as float
// TODO: detect int as numeric
// TODO: detect text/longtext as textarea
// TODO: detect active as active_box
// TODO: detect enum/set as radio_box
// TODO: detect id as info (not editable)
// TODO: detect field length and apply it as atribute
// TODO: detect signed/unsigned field for int/float/doublelength and apply it as atribute
// TODO: detect foreign keys as select box by constraint
				if (strpos($type, 'TEXT') !== false) {
					$__this->textarea($name);
				} else {
					$__this->text($name);
				}
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