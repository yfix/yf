<?php

/**
* Relations handler for model
*/
class yf_model_relation {

	protected $_model = null;
	protected $_relation = [];
	protected $_parent = []; // parent model
	protected $_related = []; // related model

	/**
	*/
	public function __construct($relation, $model) {
		$this->_relation = $relation;
		$this->_model = $model;
	}

	/**
	*/
	public function _get_model() {
		return $this->_model;
	}

	/**
	*/
	public function _get_relation() {
		return $this->_relation;
	}

	/**
	* Attach related data to current model
	*/
	public function attach($id, $params = []) {
		$relation = $this->_relation;
		$model = $this->_model;
		$db = $model->_db;
		$pivot_table = $relation['pivot_table'];
		$type = $relation['type'];

		if ($type === 'belongs_to_many') {

			return $db->replace($pivot_table, [
				$relation['other_key']		=> $id,
				$relation['foreign_key']	=> $model->get_key(),
			]);

		}
		return false;
	}

	/**
	* Detach related data to current model
	*/
	public function detach($id, $params = []) {
		$relation = $this->_relation;
		$model = $this->_model;
		$db = $model->_db;
		$pivot_table = $relation['pivot_table'];
		$type = $relation['type'];

		if ($type === 'belongs_to_many') {

			return $db->from($pivot_table)
				->where($relation['other_key'], $id)
				->where($relation['foreign_key'], $model->get_key())
				->limit(1)
				->delete();

		}
		return false;
	}

	/**
	*/
	public function get_data() {
		$relation = $this->_relation;
		$model = $this->_model;
		$db = $model->_db;
		$table = $model->get_table();
		$table_alias = 't0';
		$id = $model->get_key();
		$query = $relation['query'];
		$rel_model = $query->_model;
		$rel_table = $rel_model->get_table();
		$type = $relation['type'];

		if ($type === 'has_one') {

			return $query
				->whereid($id)
				->get();

		} elseif ($type === 'has_many') {

			$foreign_key = $relation['foreign_key'];
			$local_key = $relation['local_key'];

			$cols = [];
			foreach ($db->utils()->columns_names($rel_table) as $col) {
				$col = $table_alias.'.'.$col;
				$cols[$col] = $col;
			}
			$join_table = $table;
			$join_alias = 't1';
			return $query
				->whereid($id)
				->select(implode(', ', $cols))
				->inner_join($join_table.' AS '.$join_alias, [
					$table_alias.'.'.$foreign_key => $join_alias.'.'.$local_key,
				])->get_all();

		} elseif ($type === 'belongs_to') {

			$foreign_key = $relation['foreign_key'];
			$other_key = $relation['other_key'];

			$join_table = $table;
			$join_alias = 't1';

			$cols = [];
			foreach ($db->utils()->columns_names($rel_table) as $col) {
				$col = $table_alias.'.'.$col;
				$cols[$col] = $col;
			}
			return $query
				->where($join_alias.'.'.$model->get_key_name(), $id)
				->select(implode(', ', $cols))
				->inner_join($join_table.' AS '.$join_alias, [
					$table_alias.'.'.$other_key => $join_alias.'.'.$foreign_key,
				])->get();

		} elseif ($type === 'belongs_to_many') {

			$foreign_key = $relation['foreign_key'];
			$other_key = $relation['other_key'];

			$pivot_table = $db->_fix_table_name($relation['pivot_table']);
			$pivot_alias = 't1';

			$join_table = $table;
			$join_alias = 't2';

			$cols = [];
			foreach ($db->utils()->columns_names($rel_table) as $col) {
				$col = $table_alias.'.'.$col;
				$cols[$col] = $col;
			}
			return $query->whereid($id)
				->select(implode(', ', $cols))
				->inner_join($pivot_table.' AS '.$pivot_alias, [
					$table_alias.'.'.$rel_model->get_key_name() => $pivot_alias.'.'.$other_key,
				])
				->inner_join($join_table.' AS '.$join_alias, [
					$pivot_alias.'.'.$foreign_key => $join_alias.'.'.$model->get_key_name(),
				])
				->get_all();

		} elseif ($type === 'has_many_through') {
// TODO
		} elseif ($type === 'morph_one') {
// TODO
		} elseif ($type === 'morph_to') {
// TODO
		} elseif ($type === 'morph_many') {
// TODO
		} elseif ($type === 'morph_to_many') {
// TODO
		} elseif ($type === 'morphed_by_many') {
// TODO
		}
	}

	/**
	*/
	public function _create_pivot_table($pivot, $relation) {
		$r = $relation;
		$utils->create_table($pivot, function($t) use ($r) {
			$t->int($r['local_key'], ['unsigned' => true, 'nullable' => false]);
			$t->int($r['foreign_key'], ['unsigned' => true, 'nullable' => false]);
			$t->primary([$r['local_key'] => $r['local_key'], $r['foreign_key'] => $r['foreign_key']]);
		});
	}
}

