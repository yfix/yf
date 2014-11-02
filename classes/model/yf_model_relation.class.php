<?php

class yf_model_relation {
	protected $_model = null;
	protected $_relation = array();
	public function __construct($model, $relation) {
		$this->_model = $model;
		$this->_relation = $relation;
		if ($relation['type'] === 'has_one') {
#			$model_name = get_class($model);
#			$column = $model_name.'_id';
			return model($relation['model'])->find($model->id);
		}
	}
	public function _get_model() {
		return $this->_model;
	}
	public function _get_relation() {
		return $this->_relation;
	}
	public function attach($id, $params = array()) {
		$r = &$this->_relation;
		$model = $this->_model;
		$db = $model->_db;
		$utils = $db->utils();
		if ($r['type'] === 'belongs_to_many') {
			$pivot = $r['pivot_table'];
/*
			$utils->create_table($pivot, function($t) use ($r) {
				$t->int($r['local_key'], array('unsigned' => true, 'nullable' => false));
				$t->int($r['foreign_key'], array('unsigned' => true, 'nullable' => false));
				$t->primary(array($r['local_key'] => $r['local_key'], $r['foreign_key'] => $r['foreign_key']));
			});
*/
			return $db->replace($pivot, array(
				$r['local_key']		=> $model->id,
				$r['foreign_key']	=> $id,
			));
		}
		return false;
	}
}
