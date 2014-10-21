<?php

/**
* ORM model
*/
class yf_model {

	/**
	* YF framework constructor
	*/
#	public function _init() {
#	}

	/**
	* We cleanup object properties when cloning
	*/
	public function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Catch missing method call
	*/
	public function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Catch missing method call
	*/
#	public static function __callStatic($name, $args) {
// TODO
#	}

	/**
	*/
	function __get($name) {
// TODO
#		if (!$this->_preload_complete) {
#			$this->_preload_data();
#		}
		return $this->$name;
	}

	/**
	*/
	function __set($name, $value) {
// TODO
#		if (!$this->_preload_complete) {
#			$this->_preload_data();
#		}
		$this->$name = $value;
		return $this->$name;
	}
/*
	function __isset($name) {
// TODO
	}

	function __unset($name) {
// TODO
	}

	function __toString() {
// TODO
	}

	function __invoke() {
// TODO
	}

	function __sleep() {
// TODO
	}

	function __wakeup() {
// TODO
	}
*/

	/**
	* Query builder connector
	*/
	public function _query_builder($params = array()) {
		$table = $params['table'] ?: $this->_get_table_name();
		if (!$table) {
			throw new Exception('MODEL: '.__CLASS__.': requires table name to make queries');
		}
		$qb = $this->_db->query_builder();
		$qb->from($table);
		foreach (array('select','where','where_or','order_by','having','group_by') as $func) {
			if ($params[$func]) {
				call_user_func_array(array($qb, $func), $params[$func]);
			}
		}
		if ($params['join']) {
			foreach((array)$params['join'] as $join) {
				$qb->join($join['table'], $join['on'], $join['type']);
			}
		}
		// limit => [10,30] or limit => 5
		if ($params['limit']) {
			$count = is_numeric($params['limit']) ? $params['limit'] : $params['limit'][0];
			$offset = is_numeric($params['limit']) ? null : $params['limit'][1];
			$qb->limit($count, $offset);
		}
		return $qb;
	}

	/**
	* Linking with the table builder
	*/
	public function table($params = array()) {
		$sql = $this->_query_builder((array)$params['query_builder'])->sql();
		$filter_name = $params['filter_name'] ?: ($this->_params['filter_name'] ?: $_GET['object'].'__'.$_GET['action']);
		$params['filter'] = $params['filter'] ?: ($this->_params['filter'] ?: $_SESSION[$filter_name]);
		return table($sql, $params);
	}

	/**
	* Linking with the form builder
	*/
	public function form($whereid, $data = array(), $params = array()) {
		$a = (array)$this->_query_builder((array)$params['query_builder'])->whereid($whereid)->get();
		return form($a + (array)$data, $params);
	}

	/**
	* Linking with the form builder
	*/
	public function filter_form($data = array(), $params = array()) {
		$filter_name = $params['filter_name'] ?: $_GET['object'].'__'.$_GET['action'];
		$a = array(
			'form_action'	=> url_admin('/@object/filter_save/'.$filter_name),
			'clear_url'		=> url_admin('/@object/filter_save/'.$filter_name.'/clear'),
		);
		$params['selected'] = $params['selected'] ?: $_SESSION[$filter_name];
		return form($a + (array)$data, $params);
	}

	/**
	* Model validation will be here
	*/
	public function validate($rules = array(), $params = array()) {
// TODO
	}

	/**
	*/
	public function _get_table_name($name = '') {
		if (!$name) {
			$name = $this->_table;
		}
		if (!$name) {
			$name = substr(__CLASS__, 0, -strlen('_model'));
			$yf_plen = strlen(YF_PREFIX);
			if ($yf_plen && substr($name, 0, $yf_plen) === YF_PREFIX) {
				$name = substr($name, $yf_plen);
			}
		}
		return $this->_db->_fix_table_name($name);
/*
		$db = &$this->_db;
		if (isset($db->_found_tables)) {
			return $db->_found_tables[$name];
		}
		$tables = $db->utils()->list_tables();
		if ($db->DB_PREFIX) {
			$p_len = strlen($db->DB_PREFIX);
			$tmp = array();
			foreach ($tables as $real) {
				$short = $real;
				if (substr($real, 0, $p_len) == $db->DB_PREFIX) {
					$short = substr($real, $p_len);
				}
				$tmp[$short] = $real;
			}
			$tables = $tmp;
		}
		$db->_found_tables = $tables;
		return $db->_found_tables[$name];
*/
	}

	/**
	*/
	public function count() {
		return $this->_query_builder(array('where' => func_get_args()))->count();
	}

	/**
	* Search for model data, according to args array
	*/
	public function find() {
		return $this->_query_builder(array('where' => func_get_args()))->get_all(array('as_object' => true));
	}

	/**
	* Get all matching rows
	*/
	public function get_all() {
		return call_user_func_array(array($this, 'find'), func_get_args());
	}

	/**
	* Alias for get_all()
	*/
	public function all() {
		return call_user_func_array(array($this, 'find'), func_get_args());
	}

	/**
	* Direct access to the model's query builder where() statement
	*/
	public function where() {
		return $this->_query_builder(array('where' => func_get_args()));
	}

	/**
	* Return first matching row
	*/
	public function first() {
		return (object) $this->_query_builder(array('where' => func_get_args()))->get();
	}

	/**
	* Alias for first
	*/
	public function get() {
		return call_user_func_array(array($this, 'first'), func_get_args());
	}

	/**
	* Return first matched row or create such one, if not existed
	*/
	public function first_or_create() {
// TODO
		return $this;
	}

	/**
	* Create new model record inside database
	*/
	public function create() {
// TODO
		return $this;
	}

	/**
	* Delete matching record(s) from database
	*/
	public function delete() {
		return $this->_query_builder(array('where' => func_get_args()))->delete();
	}

	/**
	* Determine if the model or a given attribute has been modified.
	*/
	public function is_dirty($attr = null) {
// TODO
		return $this;
	}

	/**
	* Get the attributes that have been changed since last sync.
	*/
	public function get_dirty($attr = null) {
// TODO
		return $this;
	}

	/**
	* Save model back into database
	*/
	public function save($params = array()) {
// TODO
		return $this;
	}

	/**
	* Save data related to model back into database
	*/
	public function update($data = array()) {
// TODO
		return $this;
	}

	/**
	* Update only model's timestamps
	*/
	public function touch() {
// TODO
		return $this;
	}

	/**
	* Soft-deleting method (non-empty field deleted_at)
	*/
	public function soft_delete() {
// TODO
		return $this;
	}

	/**
	* Soft-deleted records really delete
	*/
	public function force_delete() {
// TODO
		return $this;
	}

	/**
	* Soft-delete restore method
	*/
	public function restore() {
// TODO
		return $this;
	}

	/**
	* Soft-deleted records matching method
	*/
	public function with_trashed() {
// TODO
		return $this;
	}

	/**
	* Detecmine if current model instance has been soft deleted
	*/
	public function trashed() {
// TODO
		return $this;
	}

	/**
	* Needed for scope call
	*/
	public function of_type($scope) {
// TODO
		return $this;
	}

	/**
	* Relation one-to-one
	*/
	public function has_one($model, $foreign_key = '', $local_key = '') {
// TODO
		return $this;
	}

	/**
	* Relation one-to-one inversed
	*/
	public function belongs_to($model, $local_key = '', $foreign_key = '') {
// TODO
		return $this;
	}

	/**
	* Relation one-to-many
	*/
	public function has_many($model, $foreign_key = '', $local_key = '') {
// TODO
		return $this;
	}

	/**
	* Relation many-to-many
	*/
	public function belongs_to_many($model, $pivot_table = '', $local_key = '', $foreign_key = '') {
// TODO
		return $this;
	}

	/**
	* Relation distant through other model
	*/
	public function has_many_through($model, $through_model, $local_key = '', $foreign_key = '') {
// TODO
		return $this;
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_to() {
// TODO
		return $this;
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_many($model, $method) {
// TODO
		return $this;
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morph_to_many($model, $method) {
// TODO
		return $this;
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morphed_by_many($model, $method) {
// TODO
		return $this;
	}

	/**
	* Associate here means to auotmatically create foreign key on child model
	*/
	public function associate($model_instance) {
// TODO
		return $this;
	}

	/**
	* Relation querying method $posts = model('post')->has('comments', '>=', 3)->get();
	*/
	public function has($relation, $where = array()) {
// TODO
		return $this;
	}

	/**
	* Eager loading with relations. Examples:
	*
	* model('post')->with('comments')->whereid($id)->first();
	*
	* foreach (model('book')->with('author')->get_all() as $book) {  // select * from authors where id in (1, 2, 3, 4, 5, ...)
	*	  echo $book->author->name;
	* }
	*/
	public function with($model) {
// TODO
		return $this;
	}
}
