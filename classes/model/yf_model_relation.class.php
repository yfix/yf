<?php

class yf_model_relation {
	protected $_model = null;
	protected $_relation = array();
	protected $_parent = array(); // parent model
	protected $_related = array(); // related model
	public function __construct($relation, $model) {
		$this->_relation = $relation;
		$this->_model = $model;
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
			return $db->replace($pivot, array(
				$r['local_key']		=> $model->get_key(),
				$r['foreign_key']	=> $id,
			));
		}
		return false;
	}
	public function get_data() {
		$relation = $this->_relation;
		$model = $this->_model;
		$table = $model->get_table();
		$table_alias = 't0';
		$id = $model->get_key();
		$query = $relation['query'];
		$rel_model = $query->_model;
		$type = $relation['type'];

		if ($type === 'has_one') {

			return $query->whereid($id)->get();

		} elseif ($type === 'has_many') {

			$rel_table = $rel_model->get_table();
			$foreign_key = $relation['foreign_key'];
			$local_key = $relation['local_key'];

			return $query->whereid($id)->inner_join($rel_table, array(
				$foreign_key => $table_alias.'.'.$local_key,
			))->get_all();

		} elseif ($type === 'belongs_to') {
// TODO
		} elseif ($type === 'belongs_to_many') {
// TODO
		}
	}
/*
	public function __call($name, $args) {
		if (method_exists($this, $name)) {
			return $this->$name;
		}
	}
	public function _create_pivot_table($pivot, $relation) {
		$r = $relation;
		$utils->create_table($pivot, function($t) use ($r) {
			$t->int($r['local_key'], array('unsigned' => true, 'nullable' => false));
			$t->int($r['foreign_key'], array('unsigned' => true, 'nullable' => false));
			$t->primary(array($r['local_key'] => $r['local_key'], $r['foreign_key'] => $r['foreign_key']));
		});
	}
*/
}
