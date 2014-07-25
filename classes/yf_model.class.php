<?php

// TODO: extend it

/**
*/
class yf_model {

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
	public static function __callStatic($name, $args) {
// TODO
	}

	/**
	*/
	function __get($name) {
// TODO
#		if (!$this->_preload_complete) {
#			$this->_preload_data();
#		}
#		return $this->$name;
	}

	/**
	*/
	function __set($name, $value) {
// TODO
#		if (!$this->_preload_complete) {
#			$this->_preload_data();
#		}
#		$this->$name = $value;
#		return $this->$name;
	}

	/**
	*/
	function __isset($name) {
// TODO
	}

	/**
	*/
	function __unset($name) {
// TODO
	}

	/**
	*/
	function __toString() {
// TODO
	}

	/**
	*/
	function __invoke() {
// TODO
	}

	/**
	*/
	function __sleep() {
// TODO
	}

	/**
	*/
	function __wakeup() {
// TODO
	}

	/**
	*/
	public function _get_table_name($name = '') {
		if (!$name) {
			$name = $this->_table;
		}
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
	}

	/**
	*/
	public function count() {
#		$cache_name = __FUNCTION__.$this->__changed;
#		if (isset($this->__cache[$cache_name])) {
#			return $this->__cache[$cache_name];
#		}
		$db = &$this->_db;
		$result = $db->get_one('SELECT COUNT(*) FROM '.$this->_get_table_name());
#		$this->__cache[$cache_name] = $result;
		return $result;
	}

	/**
	* Search for model data, according to args array
	*/
	public function find() {
		$db = &$this->_db;
		return $db->from($this->_get_table_name())->where(array('__args__' => func_get_args()))->get_all(array('as_object' => true));
	}

	/**
	* Get all matching rows
	*/
	public function get_all() {
// TODO
	}

	/**
	* Alias for get_all()
	*/
	public function all() {
// TODO
	}

	/**
	* Direct access to the model's query builder where() statement
	*/
	public function where() {
// TODO
	}

	/**
	* Return first matching row
	*/
	public function first() {
		$db = &$this->_db;
		return (object) $db->from($this->_get_table_name())->where(array('__args__' => func_get_args()))->get();
	}

	/**
	* Alias for first
	*/
	public function get() {
// TODO
	}

	/**
	* Return first matched row or create such one, if not existed
	*/
	public function first_or_create() {
// TODO
	}

	/**
	* Create new model record inside database
	*/
	public function create() {
// TODO
	}

	/**
	* Delete matching record(s) from database
	*/
	public function delete() {
// TODO
	}

	/**
	* Determine if the model or a given attribute has been modified.
	*/
	public function is_dirty($attr = null) {
// TODO
	}

	/**
	* Get the attributes that have been changed since last sync.
	*/
	public function get_dirty($attr = null) {
// TODO
	}

	/**
	* Save model back into database
	*/
	public function save($params = array()) {
// TODO
	}

	/**
	* Save data related to model back into database
	*/
	public function update($data = array()) {
// TODO
	}

	/**
	* Update only model's timestamps
	*/
	public function touch() {
// TODO
	}

	/**
	* Soft-deleting method (non-empty field deleted_at)
	*/
	public function soft_delete() {
// TODO
	}

	/**
	* Soft-deleted records really delete
	*/
	public function force_delete() {
// TODO
	}

	/**
	* Soft-delete restore method
	*/
	public function restore() {
// TODO
	}

	/**
	* Soft-deleted records matching method
	*/
	public function with_trashed() {
// TODO
	}

	/**
	* Detecmine if current model instance has been soft deleted
	*/
	public function trashed() {
// TODO
	}

	/**
	* Needed for scope call
	*/
	public function of_type($scope) {
// TODO
	}

	/**
	* Relation one-to-one
	*/
	public function has_one($model, $foreign_key = '', $local_key = '') {
// TODO
	}

	/**
	* Relation one-to-one inversed
	*/
	public function belongs_to($model, $local_key = '', $foreign_key = '') {
// TODO
	}

	/**
	* Relation one-to-many
	*/
	public function has_many($model, $foreign_key = '', $local_key = '') {
// TODO
	}

	/**
	* Relation many-to-many
	*/
	public function belongs_to_many($model, $pivot_table = '', $local_key = '', $foreign_key = '') {
// TODO
	}

	/**
	* Relation distant through other model
	*/
	public function has_many_through($model, $through_model, $local_key = '', $foreign_key = '') {
// TODO
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_to() {
// TODO
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_many($model, $method) {
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morph_to_many($model, $method) {
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morphed_by_many($model, $method) {
// TODO
	}

	/**
	* Associate here means to auotmatically create foreign key on child model
	*/
	public function associate($model_instance) {
// TODO
	}

	/**
	* Relation querying method $posts = model('post')->has('comments')->get();
	*/
	public function has($relation, $where = array()) {
// TODO
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
	}

	/**
	* Linking with the table builder
	*/
	public function table($params = array()) {
		$db = &$this->_db;
		$sql = $db->from($this->_get_table_name())->sql();
		$filter_name = $params['filter_name'] ?: ($this->_params['filter_name'] ?: $_GET['object'].'__'.$_GET['action']);
		$params['filter'] = $params['filter'] ?: ($this->_params['filter'] ?: $_SESSION[$filter_name]);
		return table($sql, $params);
	}

	/**
	* Linking with the form builder
	*/
	public function form($whereid, $data = array(), $params = array()) {
		$db = &$this->_db;
		$a = (array)$db->from($this->_get_table_name())->whereid($whereid)->get();
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
}
