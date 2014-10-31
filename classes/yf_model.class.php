<?php

/**
* ORM model
*/
class yf_model {

#	protected $_db = null;
	protected $_table = null;
	protected $_fillable = array();
	protected $_primary_key = null;
	protected $_primary_id = null;
	protected $_dirty_attrs = null;
	protected $_is_trashed = null;
	protected $_preload_complete = null;
	protected $_relations = null;
	protected $_params = null;
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';

	/**
	*/
	public function __construct($params = array()) {
		if (isset($params['_is_static_call'])) {
			$this->_db = db();
		}
	}

	/**
	* YF framework constructor
	*/
	public function _init() {
	}

	/**
	* We cleanup object properties when cloning
	*/
	public function __clone() {
		$persist_properties = array(
			'_table',
			'_fillable',
		);
		foreach ((array)get_object_vars($this) as $k => $v) {
			if (!in_array($k, $persist_properties)) {
				$this->$k = null;
			}
		}
	}

	/**
	* Catch missing method call
	*/
	public function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Catch static calls
	*/
	public static function __callStatic($method, $args) {
		$instance = new static;
		$instance->_db = db();
		return call_user_func_array(array($instance, $method), $args);
	}

	/**
	*/
	function __toString() {
		return json_encode($this->_get_current_data());
	}

	/**
	*/
	public function _get_table_name($name = '') {
		if (!$name) {
			$name = $this->_table;
		}
		if (!$name) {
			$name = get_called_class();
			$postfix = '_model';
			$plen = strlen($postfix);
			if ($plen && substr($name, -$plen) === $postfix) {
				$name = substr($name, 0, -$plen);
			}
			$yf_len = strlen(YF_PREFIX);
			if ($yf_len && substr($name, 0, $yf_len) === YF_PREFIX) {
				$name = substr($name, $yf_len);
			}
			$this->_table = $name;
		}
		return $this->_db->_fix_table_name($name);
	}

	/**
	* Find primary key name
	*/
	public function _get_primary_key_column($table = null) {
		if (!isset($table)) {
			$table = $this->_get_table_name();
			$self = true;
		}
		$container = &$this->_pk_cache;
		if (isset($container[$table])) {
			return $container[$table];
		}
		$primary = $this->_db->utils()->index_info($table, 'PRIMARY');
		if ($primary) {
			$container[$table] = current($primary['columns']);
		} else {
			$container[$table] = false;
		}
		if ($self && !isset($this->_primary_key)) {
			$this->_primary_key = $container[$table];
		}
		return $container[$table];
	}

	/**
	*/
	public function _get_current_data() {
		$data = array();
		foreach (get_object_vars($this) as $var => $value) {
			if (substr($var, 0, 1) === '_') {
				continue;
			}
			$data[$var] = $value;
		}
		return $data;
	}

	/**
	* Query builder connector
	*/
	public function _query_builder($params = array()) {
		$params = (array)$params + (array)$this->_params;
		$table = $params['table'] ?: $this->_get_table_name();
		if (!$table) {
			throw new Exception('MODEL: '.get_called_class().': requires table name to make queries');
		}
		$qb = $this->_db->query_builder();
		$qb->from($table);
		// whereid shortcut, example: find(1)  == 1 is PK
		if (is_array($params['where']) && count($params['where']) === 1 && is_numeric($params['where'][0]) && !isset($params['whereid'])) {
			$params['whereid'] = $params['where'];
			$pk = $this->_get_primary_key_column($table);
			if ($pk) {
				$params['whereid'][1] = $pk;
			}
			unset($params['where']);
		}
		foreach (array('select','where','where_or','whereid','order_by','having','group_by') as $func) {
			if ($params[$func]) {
				call_user_func_array(array($qb, $func), is_array($params[$func]) ? $params[$func] : array($params[$func]));
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
		$qb = $this->_prepare_relations_for_qb($qb);
		return $qb;
	}

	/**
	*/
	public function _prepare_relations_for_qb($qb) {
		foreach ((array)$this->_relations as $key => $info) {
			$type = $info['type'];
			$model = $info['model'];
			if (!$type || !$model) {
				continue;
			}
			$model_obj = model($model);
			$table = $model_obj->_get_table_name();
			$qb->join($table/*, $join['on'], $join['type']*/);
		}
		return $qb;
	}

	/**
	* Params for query builder
	*/
	public function select() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function where() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function where_or() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function whereid() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function group_by() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function order_by() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function having() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Params for query builder
	*/
	public function limit() {
		$this->_params[__FUNCTION__] = func_get_args();
		return $this;
	}

	/**
	* Search for model data, according to args array, returning first record
	* Usuallly get by primary key, but possible to use complex conditions.
	*/
	public function find() {
		$args = func_get_args();
		$this->_where = $args;
		$result = $this->_query_builder($args ? array('where' => $args) : null)->get();
		$this->_primary_id = $result[$pk];
		return new yf_model_internal_result($result, $this);
	}

	/**
	* Return first column from first row from resultset
	*/
	public function one() {
		$data = call_user_func_array(array($this, 'find'), func_get_args());
		return is_array($data) ? current($data) : null;
	}

	/**
	* Get first record ordered by the primary key
	*/
	public function first() {
		$args = func_get_args();
		$pk = $this->_get_primary_key_column();
		$result = $this->_query_builder($args ? array('where' => $args, 'order_by' => $pk.' asc', 'limit' => 1) : null)->get();
		$this->_primary_id = $result[$pk];
		return new yf_model_internal_result($result, $this);
	}

	/**
	* Get last record ordered by the primary key
	*/
	public function last() {
		$args = func_get_args();
		$pk = $this->_get_primary_key_column();
		$result = $this->_query_builder($args ? array('where' => $args, 'order_by' => $pk.' desc', 'limit' => 1) : null)->get();
		$this->_primary_id = $result[$pk];
		return new yf_model_internal_result($result, $this);
	}

	/**
	* Just get one row from resultset
	*/
	public function get() {
		$args = func_get_args();
		$pk = $this->_get_primary_key_column();
		$result = $this->_query_builder($args ? array('where' => $args) : null)->get();
		$this->_primary_id = $result[$pk];
		return new yf_model_internal_result($result, $this);
	}

	/**
	* Get all matching rows
	*/
	public function all() {
		$args = func_get_args();
		$pk = $this->_get_primary_key_column();
		return $this->_query_builder($args ? array('where' => $args) : null)->get_all(/*array('as_objects' => true)*/);
#		return $this->_query_builder($args ? array('where' => $args) : null)->get_all(array('as_objects' => true));
#		$result = $this->_query_builder($args ? array('where' => $args) : null)->get_all(/*array('as_objects' => true)*/);
#		return new yf_model_internal_collection($result, $this);
	}

	/**
	* Alias for all
	*/
	public function get_all() {
		return call_user_func_array(array($this, 'all'), func_get_args());
	}

	/**
	* Count number of matching records, according to condition
	*/
	public function count() {
		$args = func_get_args();
		$pk = $this->_get_primary_key_column();
		return (int)$this->_query_builder($args ? array('where' => $args) : null)->count();
	}

	/**
	* Return first matched row or create such one, if not existed
	*/
	public function first_or_create() {
/*
		$args = func_get_args();
		$data = call_user_func_array(array($this, 'first'), $args);
		if (empty($data)) {
			$insert_ok = $this->_query_builder($args ? array('where' => $args) : null)->insert();
			$insert_id = $insert_ok ? $this->_db->insert_id() : 0;
			if ($insert_id) {
				$data = $this->find($insert_id);
			}
		}
		return new yf_model_internal_result($data, $this);
*/
	}

	/**
	* Create new model record inside database
	*/
	public static function create(array $data) {
		$obj = new static(array('_is_static_call' => true));
#		if (isset($data[self::CREATED_AT])) {
#			$data[self::CREATED_AT] = date('Y-m-d H:i:s');
#		}
		$insert_id = $obj->_query_builder()->insert($data);
		if (!$insert_id) {
			return false;
		}
		$pk = $obj->_get_primary_key_column();
		$data = (array)$obj->find($data[$pk]);
		$obj->_primary_id = $data[$pk];
		return new yf_model_internal_result($data, $obj);
	}

	/**
	* Save model back into database
	*/
	public function save() {
		return call_user_func_array(array($this, 'update'), func_get_args());
	}

	/**
	* Save data related to model back into database
	*/
	public function update() {
		$data = (array)$this->_get_current_data();
		$pk = $this->_get_primary_key_column();
		$this->_primary_id = $data[$pk];
		if (isset($data[self::UPDATED_AT])) {
			$data[self::UPDATED_AT] = date('Y-m-d H:i:s');
		}
		return $this->_query_builder(array('whereid' => $this->_primary_id))->update($data);
	}

	/**
	* Internal method
	*/
	public function _add_relation(array $params) {
		$data = array(
			'model'			=> $params['model'],
			'type'			=> $params['type'],
			'foreign_key'	=> $params['foreign_key'],
			'local_key'		=> $params['local_key'],
			'pivot_table'	=> $params['pivot_table'],
			'through_model'	=> $params['through_model'],
		);
		foreach ($data as $k => $v) {
			if (empty($v)) {
				unset($data[$k]);
			}
		}
		$relation_key = implode(':', array_keys($data));
		$this->_relations[$relation_key] = $data;
		return $this;
	}

	/**
	* Relation one-to-one
	*/
	public function has_one($model, $foreign_key = '', $local_key = '') {
		return $this->_add_relation(array(
			'type'			=> __FUNCTION__,
			'model'			=> $model,
			'foreign_key'	=> $foreign_key,
			'local_key'		=> $local_key,
		));
	}

	/**
	* Relation one-to-one inversed
	*/
	public function belongs_to($model, $local_key = '', $foreign_key = '') {
		return $this->_add_relation(array(
			'type'			=> __FUNCTION__,
			'model'			=> $model,
			'foreign_key'	=> $foreign_key,
			'local_key'		=> $local_key,
		));
	}

	/**
	* Relation one-to-many
	*/
	public function has_many($model, $foreign_key = '', $local_key = '') {
		return $this->_add_relation(array(
			'type'			=> __FUNCTION__,
			'model'			=> $model,
			'foreign_key'	=> $foreign_key,
			'local_key'		=> $local_key,
		));
	}

	/**
	* Relation many-to-many
	*/
	public function belongs_to_many($model, $pivot_table = '', $local_key = '', $foreign_key = '') {
		return $this->_add_relation(array(
			'type'			=> __FUNCTION__,
			'model'			=> $model,
			'foreign_key'	=> $foreign_key,
			'local_key'		=> $local_key,
			'pivot_table'	=> $pivot_table,
		));
	}

	/**
	* Relation distant through other model
	*/
	public function has_many_through($model, $through_model, $local_key = '', $foreign_key = '') {
		return $this->_add_relation(array(
			'type'			=> __FUNCTION__,
			'model'			=> $model,
			'foreign_key'	=> $foreign_key,
			'local_key'		=> $local_key,
			'through_model'	=> $through_model,
		));
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_to() {
		return $this->_add_relation(array(
		));
// TODO
	}

	/**
	* Relation polymorphic one-to-many
	*/
	public function morph_many($model, $method) {
		return $this->_add_relation(array(
		));
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morph_to_many($model, $method) {
		return $this->_add_relation(array(
		));
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morphed_by_many($model, $method) {
		return $this->_add_relation(array(
		));
// TODO
	}

	/**
	* Associate here means to auotmatically create foreign key on child model
	*/
	public function associate($model_instance) {
		return $this->_add_relation(array(
		));
// TODO
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

	/**
	* Delete matching record(s) from database
	*/
	public function delete() {
		$args = func_get_args();
		return $this->_query_builder($args ? array('where' => $args) : null)->delete();
	}

	/**
	* Soft-deleted records really delete
	*/
	public function force_delete() {
		return call_user_func_array(array($this, 'delete'), func_get_args());
	}

	/**
	* Determine if the model or a given attribute has been modified.
	*/
	public function is_dirty($attr = null) {
		return $attr ? isset($this->_dirty_attrs[$attr]) : !empty($this->_dirty_attrs);
	}

	/**
	* Get the attributes that have been changed since last sync.
	*/
	public function get_dirty($attr = null) {
		return $this->_dirty_attrs;
	}

	/**
	* Update only model's timestamps
	*/
	public function touch() {
		$args = func_get_args();
		return $this->_query_builder($args ? array('where' => $args) : null)->update(array('timestamp' => time()));
	}

	/**
	* Soft-deleting method (non-empty field deleted_at)
	*/
	public function soft_delete() {
		$args = func_get_args();
		return $this->_query_builder($args ? array('where' => $args) : null)->update(array('is_deleted' => 1));
	}

	/**
	* Soft-delete restore method
	*/
	public function restore() {
		$args = func_get_args();
		return $this->_query_builder($args ? array('where' => $args) : null)->update(array('is_deleted' => 0));
	}

	/**
	* Soft-deleted records matching method
	*/
	public function with_trashed() {
// TODO
		$args = func_get_args();
		return $this->_query_builder($args ? array('where' => $args) : null)->where('is_deleted = 1');
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
	*/
#	public static function __callStatic($name, $args) {
// TODO
#	}

	/**
	*/
#	function __isset($name) {
// TODO
#	}

	/**
	*/
#	function __unset($name) {
// TODO
#	}

	/**
	*/
#	function __get($name) {
#		if (substr($name, 0, 1) === '_') {
#			return $this->$name;
#		}
#		if (!$this->_preload_complete) {
#			$this->_preload_data();
#		}
#		return $this->$name;
#	}

	/**
	*/
#	function __set($name, $value) {
#		$this->$name = $value;
#		return $this->$name;
#	}

	/**
	*/
#	public function _preload_data() {
#		$this->_preload_complete = true;
#		foreach ((array)$this->find() as $k => $v) {
#			$this->$k = $v;
#		}
#		return true;
#	}

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
	* Html widget connetion
	*/
	public function html($name, $params = array()) {
// TODO
	}
}

if (!class_exists('yf_model_internal_result')) {
	class yf_model_internal_result {
		public function __construct($result, $model) {
			foreach ($result as $k => $v) {
				$this->$k = $v;
			}
			$this->_model($model);
		}
		public function __call($name, $args) {
			$model = $this->_model();
			// Forward current data into model
			foreach (get_object_vars($this) as $var => $value) {
				if (substr($var, 0, 1) === '_') {
					continue;
				}
				$model->$var = $value;
			}
			return call_user_func_array(array($model, $name), $args);
		}
		public function _model($model = null) {
			static $_model;
			if (is_null($model)) {
				return $_model;
			}
			return $_model = $model;
		}
/*
		public function __get($name) {
			if (substr($name, 0, 1) === '_') {
				return $this->$name;
			}
			return $this->$name;
		}
*/
	}
}

/*
if (!class_exists('yf_model_internal_collection')) {
	class yf_model_internal_collection {
// TODO
	}
}
*/
