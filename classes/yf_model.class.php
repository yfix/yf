<?php

load('yf_model_result', '', 'classes/model/');
load('yf_model_relation', '', 'classes/model/');

/**
* ORM model
*/
class yf_model {

	protected $_db = null;
	protected $_table = null;
	protected $_fillable = array();
	protected $_guarded = array();
	protected $_primary_key = null;
	protected $_primary_id = null;
	protected $_dirty_attrs = null;
	protected $_is_trashed = null;
	protected $_relations = null;
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';

	/**
	*/
	public function __construct($args = array(), $params = array()) {
		$this->set_db_object($params['db']);
		$this->set_data($args);
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
		return call_user_func_array(array(new static, $method), $args);
	}

	/**
	*/
	public function __get($name) {
		if (method_exists($this, $name)) {
			return $this->$name()->get_data();
		}
		if (isset($this->$name)) {
			return $this->$name;
		}
		return null;
	}

	/**
	*/
	function __toString() {
		return json_encode($this->get_data());
	}

	/**
	*/
	public function set_db_object($db = null) {
		$this->_db = $db ?: db();
		return $this;
	}

	/**
	*/
	public function get_table() {
		$name = $this->_table;
		if (!$name) {
			$name = strtolower(class_basename($this, '', '_model'));
			if ($name === 'model') {
				return false;
			}
			$this->set_table($name);
		}
		return $this->_db->_fix_table_name($name);
	}

	/**
	*/
	public function set_table($name) {
		$this->_table = $name;
		return $this->_table;
	}

	/**
	* Primary key value
	*/
	public function get_key() {
		return $this->_primary_id;
	}

	/**
	* Primary key value set
	*/
	public function set_key($id) {
		$this->_primary_id = $id;
		return $this->_primary_id;
	}

	/**
	* Primary key name
	*/
	public function get_key_name() {
		if (isset($this->_primary_key)) {
			return $this->_primary_key;
		}
		$table = $this->get_table();
		$utils = $this->_db->utils();
		if ($table && $utils->table_exists($table)) {
			$primary_index = $utils->index_info($table, 'PRIMARY');
		}
		if (!isset($primary_index['columns'])) {
			return null;
		}
		$name = current($primary_index['columns']);
		return $this->set_key_name($name);
	}

	/**
	* Primary key name set
	*/
	public function set_key_name($name) {
		$this->_primary_key = $name;
		return $this->_primary_key;
	}

	/**
	* Return current model data
	*/
	public function get_data() {
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
	* Set current model data
	*/
	public function set_data($data = array()) {
		if ($data instanceof yf_model_result) {
			$data = $data->get_data();
		}
		foreach ((array)$data as $k => $v) {
			if (substr($k, 0, 1) === '_') {
				continue;
			}
			$this->$k = $v;
		}
		$pk = $this->get_key_name();
		if (isset($data[$pk])) {
			$this->set_key($data[$pk]);
		}
	}

	/**
	* Default model foreign key
	*/
	public function get_foreign_key() {
		return strtolower(class_basename($this, '', '_model')).'_id';
	}

	/**
	* Return new instance of the given model
	*/
	public function new_instance($args = array()) {
		$model = new static($args);
		return $model;
	}

	/**
	* Return new instance of model result
	*/
	public function new_result($result = array()) {
		return new yf_model_result($result, $this);
	}

	/**
	* Return new instance of model relation
	*/
	public function new_relation($relation) {
		return new yf_model_relation($relation, $this);
	}

	/**
	* Return new query builder instance
	*/
	public function new_query($params = array()) {
		if (is_null($params['where'])) {
			unset($params['where']);
		}
		$table = $params['table'] ?: $this->get_table();
		if (!$table) {
			throw new Exception('MODEL: '.get_called_class().': requires table name to make queries');
		}
		$builder = $this->_db->query_builder();
		$builder->_model = $this;
		$builder->_with = $this->_with;
		$builder->_result_wrapper = array($this, 'new_result');
		$builder->_remove_as_from_delete = true;
		$builder->from($table.' AS t0');
		// whereid shortcut, example: find(1)  == 1 is PK
		if (is_array($params['where']) && count($params['where']) === 1 && is_numeric($params['where'][0]) && !isset($params['whereid'])) {
			$params['whereid'] = $params['where'];
			$pk = $this->get_key_name($table);
			if ($pk) {
				$params['whereid'][1] = $pk;
			}
			unset($params['where']);
		}
		foreach (array('select','where','where_or','whereid','order_by','having','group_by') as $part) {
			if ($params[$part]) {
				call_user_func_array(array($builder, $part), is_array($params[$part]) ? $params[$part] : array($params[$part]));
			}
		}
		// limit => [10,30] or limit => 5
		if ($params['limit']) {
			$count = is_numeric($params['limit']) ? $params['limit'] : $params['limit'][0];
			$offset = is_numeric($params['limit']) ? null : $params['limit'][1];
			$builder->limit($count, $offset);
		}
		foreach (array('join','left_join','inner_join','right_join') as $func) {
			if ($params[$func]) {
				$join = $params[$func];
				$builder->$func($join['table'], $join['on']);
			}
		}
		return $builder;
	}

	/**
	* Query builder custom constructor
	*/
	public static function query() {
		$obj = isset($this) ? $this : new static();
		return $obj->new_query(func_get_args());
	}

	/**
	* Query builder custom constructor
	*/
	public static function select() {
		$obj = isset($this) ? $this : new static();
		return $obj->new_query(array(__FUNCTION__ => func_get_args()));
	}

	/**
	* Query builder custom constructor
	*/
	public static function where() {
		$obj = isset($this) ? $this : new static();
		return $obj->new_query(array(__FUNCTION__ => func_get_args()));
	}

	/**
	* Search for model data, according to args array, returning first record
	* Usuallly get by primary key, but possible to use complex conditions.
	*/
	public static function find() {
		$obj = isset($this) ? $this : new static();
		$pk = $obj->get_key_name();
		$result = $obj->new_query(array('where' => func_get_args()))->get();
		if (!$result || !$result->$pk) {
			return null;
		}
		$obj->set_key($result->$pk);
		$obj->set_data($result);
		return $result;
	}

	/**
	* Get all matching rows
	*/
	public static function all() {
		$obj = isset($this) ? $this : new static();
		return $obj->new_query(array('where' => func_get_args()))->get_all();
	}

	/**
	* Count number of matching records, according to condition
	*/
	public static function count() {
		$obj = isset($this) ? $this : new static();
		return (int)$obj->new_query(array('where' => func_get_args()))->count();
	}

	/**
	* Create new model record inside database
	*/
	public static function create(array $data) {
		$obj = isset($this) ? $this : new static();
		$insert_id = $obj->new_query()->insert($data);
		if (!$insert_id) {
			return null;
		}
		$obj->set_key($insert_id);
		return $obj->find($insert_id);
	}

	/**
	* Return first matched row or create such one, if not existed
	*/
	public static function first_or_create() {
		$obj = isset($this) ? $this : new static();
		$args = func_get_args();
		$first = $obj->new_query(array(
			'where' => $args,
			'order_by' => $obj->get_key_name().' asc',
			'limit' => 1,
		))->get();
		if (is_object($first)) {
			$obj->set_data($first);
			return $first;
		}
		return call_user_func_array(array($obj, 'create'), $args);
	}

	/**
	* Return first matched row or create empty model object
	*/
	public static function first_or_new() {
		$obj = isset($this) ? $this : new static();
		$args = func_get_args();
		$first = $obj->new_query(array(
			'where' => $args,
			'order_by' => $obj->get_key_name().' asc',
			'limit' => 1,
		))->get();
		if (is_object($first)) {
			$obj->set_data($first);
			return $first;
		}
		return call_user_func_array(array($obj, 'new_instance'), $args);
	}

	/**
	* Save model back into database
	*/
	public function save() {
		$data = $this->get_data();
		$pk = $this->get_key_name();
		if (!$data[$pk]) {
			$insert_id = $this->new_query()->insert($data);
			if (!$insert_id) {
				return null;
			}
			$data[$pk] = $insert_id;
			$this->set_data($data);
			$this->set_key($insert_id);
			return $insert_id;
		}
		$this->set_key($data[$pk]);
		if (isset($data[self::UPDATED_AT])) {
			$data[self::UPDATED_AT] = date('Y-m-d H:i:s');
		}
		return $this->new_query(array('whereid' => $this->get_key()))->update($data);
	}

	/**
	* Get the joining table name for a many-to-many relation.
	*/
	public function joining_table($related) {
		$base = class_basename($this, '', '_model');
		$related = class_basename($related, '', '_model');
		$models = array($related, $base);
		// Now that we have the model names in an array we can just sort them and
		// use the implode function to join them together with an underscores,
		// which is typically used by convention within the database system.
		sort($models);
		$table = strtolower(implode('_', $models));
		return $this->_db->_fix_table_name($table);
	}

	/**
	* Relation one-to-one
	*/
	public function has_one($related, $foreign_key = null, $local_key = null, $relation = null) {
		if (is_null($relation)) {
			list(, $caller) = debug_backtrace(false);
			$relation = $caller['function'];
		}
		$instance = $this->_db->model($related);
		return $this->new_relation(array(
			'type'			=> __FUNCTION__,
			'related'		=> $related,
			'relation'		=> $relation,
			'foreign_key'	=> $foreign_key ?: $this->get_foreign_key(),
			'local_key'		=> $local_key ?: $instance->get_key_name(),
			'query'			=> $instance->new_query(),
		));
	}

	/**
	* Relation one-to-one inversed
	*/
	public function belongs_to($related, $foreign_key = null, $other_key = null, $relation = null) {
		if (is_null($relation)) {
			list(, $caller) = debug_backtrace(false);
			$relation = $caller['function'];
		}
		$instance = $this->_db->model($related);
		return $this->new_relation(array(
			'type'			=> __FUNCTION__,
			'related'		=> $related,
			'relation'		=> $relation,
			'foreign_key'	=> $foreign_key ?: strtolower($relation).'_id',
			'other_key'		=> $other_key ?: $instance->get_key_name(),
			'query'			=> $instance->new_query(),
		));
	}

	/**
	* Relation one-to-many
	*/
	public function has_many($related, $foreign_key = null, $local_key = null, $relation = null) {
		if (is_null($relation)) {
			list(, $caller) = debug_backtrace(false);
			$relation = $caller['function'];
		}
		$instance = $this->_db->model($related);
		return $this->new_relation(array(
			'type'			=> __FUNCTION__,
			'related'		=> $related,
			'relation'		=> $relation,
			'foreign_key'	=> $foreign_key ?: $this->get_foreign_key(),
			'local_key'		=> $local_key ?: $instance->get_key_name(),
			'query'			=> $instance->new_query(),
		));
	}

	/**
	* Relation many-to-many
	*/
	public function belongs_to_many($related, $pivot_table = null, $foreign_key = null, $other_key = null, $relation = null) {
		if (is_null($relation)) {
			list(, $caller) = debug_backtrace(false);
			$relation = $caller['function'];
		}
		$instance = $this->_db->model($related);
		return $this->new_relation(array(
			'type'			=> __FUNCTION__,
			'related'		=> $related,
			'relation'		=> $relation,
			'pivot_table'	=> $pivot_table ?: $this->joining_table($related),
			'foreign_key'	=> $foreign_key ?: $this->get_foreign_key(),
			'other_key'		=> $other_key ?: $instance->get_foreign_key(),
			'query'			=> $instance->new_query(),
		));
	}

	/**
	* Relation distant through other model
	*/
	public function has_many_through($related, $through_model, $foreign_key = null, $local_key = null, $relation = null) {
		if (is_null($relation)) {
			list(, $caller) = debug_backtrace(false);
			$relation = $caller['function'];
		}
		$instance = $this->_db->model($related);
		return $this->new_relation(array(
			'type'			=> __FUNCTION__,
			'related'		=> $related,
			'relation'		=> $relation,
			'through_model'	=> $through_model,
			'foreign_key'	=> $instance->get_table().'.'.($foreign_key ?: $this->get_foreign_key()),
			'local_key'		=> $local_key ?: $instance->get_key_name(),
			'query'			=> $instance->new_query(),
		));
	}

	/**
	* Relation polymorphic one-to-one
	*/
	public function morph_one() {
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
	public function morph_many() {
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morph_to_many() {
// TODO
	}

	/**
	* Relation polymorphic many-to-many
	*/
	public function morphed_by_many() {
// TODO
	}

	/**
	* Associate here means to auotmatically create foreign key on child model
	*/
	public function associate($model_instance) {
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
		$args = array('where' => func_get_args());
		if (!$args['where']) {
			$args = array('whereid' => (int)$this->get_key());
		}
		return $this->new_query($args)->limit(1)->delete();
	}

	/**
	* Delete matching record(s) from database, quicker method than delete()
	*/
	public static function destroy() {
		$obj = isset($this) ? $this : new static();
		$args = array('whereid' => array(func_get_args()));
		if (!$args['whereid']) {
			$args = array('whereid' => (int)$obj->get_key());
		}
		return $obj->new_query($args)->delete();
	}

	/**
	* Update only model's timestamps
	*/
	public function touch() {
		return $this->new_query(array('where' => func_get_args()))->update(array(self::UPDATED_AT => date('Y-m-d H:i:s')));
	}

	/**
	* Linking with the table builder
	*/
	public function table($params = array()) {
		$sql = $this->new_query((array)$params['query_builder'])->sql();
		$filter_name = $params['filter_name'] ?: ($this->_params['filter_name'] ?: $_GET['object'].'__'.$_GET['action']);
		$params['filter'] = $params['filter'] ?: ($this->_params['filter'] ?: $_SESSION[$filter_name]);
		return table($sql, $params);
	}

	/**
	* Linking with the form builder
	*/
	public function form($whereid, $data = array(), $params = array()) {
		$a = (array)$this->new_query((array)$params['query_builder'])->whereid($whereid)->get();
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
