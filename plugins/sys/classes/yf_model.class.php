<?php

load('yf_model_result', '', 'classes/model/');
load('yf_model_relation', '', 'classes/model/');

/**
 * ORM model.
 */
class yf_model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $_db = null;
    protected $_table = null;
    protected $_fillable = [];
    protected $_primary_key = null;
    protected $_primary_id = null;

    /**
     * Query builder custom constructor.
     */
    public static function query()
    {
        $obj = isset($this) ? $this : new static();
        return $obj->new_query(func_get_args());
    }

    /**
     * Query builder custom constructor.
     */
    public static function select()
    {
        $obj = isset($this) ? $this : new static();
        return $obj->new_query([__FUNCTION__ => func_get_args()]);
    }

    /**
     * Query builder custom constructor.
     */
    public static function where()
    {
        $obj = isset($this) ? $this : new static();
        return $obj->new_query([__FUNCTION__ => func_get_args()]);
    }

    /**
     * Search for model data, according to args array, returning first record
     * Usuallly get by primary key, but possible to use complex conditions.
     */
    public static function find()
    {
        $obj = isset($this) ? $this : new static();
        $pk = $obj->get_key_name();
        $result = $obj->new_query(['where' => func_get_args()])->get();
        if ( ! $result || ! $result->$pk) {
            return null;
        }
        $obj->set_key($result->$pk);
        $obj->set_data($result);
        return $result;
    }

    /**
     * Get all matching rows.
     */
    public static function all()
    {
        $obj = isset($this) ? $this : new static();
        return $obj->new_query(['where' => func_get_args()])->get_all();
    }

    /**
     * Count number of matching records, according to condition.
     */
    public static function count()
    {
        $obj = isset($this) ? $this : new static();
        return (int) $obj->new_query(['where' => func_get_args()])->count();
    }

    /**
     * Create new model record inside database.
     */
    public static function create(array $data)
    {
        $obj = isset($this) ? $this : new static();
        $insert_id = $obj->new_query()->insert($data);
        if ( ! $insert_id) {
            return null;
        }
        $obj->set_key($insert_id);
        return $obj->find($insert_id);
    }

    /**
     * Return first matched row or create such one, if not existed.
     */
    public static function first_or_create()
    {
        $obj = isset($this) ? $this : new static();
        $args = func_get_args();
        $first = $obj->new_query([
            'where' => $args,
            'order_by' => $obj->get_key_name() . ' asc',
            'limit' => 1,
        ])->get();
        if (is_object($first)) {
            $obj->set_data($first);
            return $first;
        }
        return call_user_func_array([$obj, 'create'], $args);
    }

    /**
     * Return first matched row or create empty model object.
     */
    public static function first_or_new()
    {
        $obj = isset($this) ? $this : new static();
        $args = func_get_args();
        $first = $obj->new_query([
            'where' => $args,
            'order_by' => $obj->get_key_name() . ' asc',
            'limit' => 1,
        ])->get();
        if (is_object($first)) {
            $obj->set_data($first);
            return $first;
        }
        return call_user_func_array([$obj, 'new_instance'], $args);
    }

    /**
     * Delete matching record(s) from database, quicker method than delete().
     */
    public static function destroy()
    {
        $obj = isset($this) ? $this : new static();
        return $obj->new_query(['where' => func_get_args()])->delete();
    }

    /**
     * @param mixed $args
     * @param mixed $params
     */
    public function __construct($args = [], $params = [])
    {
        $this->set_db_object($params['db']);
        $this->set_data($args);
    }

    /**
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        $persist_properties = [
            '_table',
            '_fillable',
        ];
        foreach ((array) get_object_vars($this) as $k => $v) {
            if ( ! in_array($k, $persist_properties)) {
                $this->$k = null;
            }
        }
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        $where_prefix = 'where_';
        $scope_prefix = 'scope_';
        $get_prefix = 'get_attr_';
        $set_prefix = 'set_attr_';
        if (strpos($name, $where_prefix) !== false) {
            $name = substr($name, strlen($where_prefix));
            array_unshift($args, 't0.' . $name);
            return call_user_func_array([$this, 'where'], $args);
        } elseif (strpos($name, $scope_prefix) !== false) {
            if (method_exists($this, $name)) {
                return call_user_func_array([$this, $name], $args);
            }
        } elseif (strpos($name, $get_prefix) !== false) {
            $accessor = $get_prefix . $name;
            if (method_exists($this, $accessor)) {
                return $this->$accessor($args);
            }
        } elseif (strpos($name, $set_prefix) !== false) {
            $mutator = $set_prefix . $name;
            if (method_exists($this, $mutator)) {
                return $this->$mutator($args);
            }
        }
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Catch static calls.
     * @param mixed $method
     * @param mixed $args
     */
    public static function __callStatic($method, $args)
    {
        $where_prefix = 'where_';
        $scope_prefix = 'scope_';
        $obj = new static();
        if (strpos($name, $where_prefix) !== false) {
            $name = substr($name, strlen($where_prefix));
            array_unshift($args, 't0.' . $name);
            return call_user_func_array([$obj, 'where'], $args);
        } elseif (strpos($name, $scope_prefix) !== false) {
            if (method_exists($obj, $name)) {
                return call_user_func_array([$obj, $name], $args);
            }
        }
        return call_user_func_array([$obj, $method], $args);
    }

    /**
     * @param mixed $name
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->$name()->get_data();
        }
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }


    public function __toString()
    {
        return json_encode($this->get_data());
    }

    /**
     * Set model attribute, example: (new bear)->set('name', 'Joe').
     * @param mixed $name
     * @param null|mixed $value
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $func = __FUNCTION__;
            foreach ($name as $k => $v) {
                $this->$func($k, $v);
            }
        } else {
            $mutator = 'set_attr_' . $name;
            if (method_exists($this, $mutator)) {
                $this->$mutator($value);
            } else {
                $this->$name = $value;
            }
        }
        return $this;
    }

    /**
     * @param null|mixed $db
     */
    public function set_db_object($db = null)
    {
        $this->_db = $db ?: db();
        return $this;
    }


    public function get_table()
    {
        $name = $this->_table;
        if ( ! $name) {
            $name = strtolower(class_basename($this, '', '_model'));
            if ($name === 'model') {
                return false;
            }
            $this->set_table($name);
        }
        return $this->_db->_fix_table_name($name);
    }

    /**
     * @param mixed $name
     */
    public function set_table($name)
    {
        $this->_table = $name;
        return $this->_table;
    }

    /**
     * Primary key value.
     */
    public function get_key()
    {
        return $this->_primary_id;
    }

    /**
     * Primary key value set.
     * @param mixed $id
     */
    public function set_key($id)
    {
        $this->_primary_id = $id;
        return $this->_primary_id;
    }

    /**
     * Primary key name.
     */
    public function get_key_name()
    {
        if (isset($this->_primary_key)) {
            return $this->_primary_key;
        }
        $table = $this->get_table();
        $utils = $this->_db->utils();
        if ($table && $utils->table_exists($table)) {
            $primary_index = $utils->index_info($table, 'PRIMARY');
        }
        if ( ! isset($primary_index['columns'])) {
            return null;
        }
        $name = current($primary_index['columns']);
        return $this->set_key_name($name);
    }

    /**
     * Primary key name set.
     * @param mixed $name
     */
    public function set_key_name($name)
    {
        $this->_primary_key = $name;
        return $this->_primary_key;
    }

    /**
     * Return current model data.
     */
    public function get_data()
    {
        $data = [];
        foreach (get_object_vars($this) as $var => $value) {
            if (substr($var, 0, 1) === '_') {
                continue;
            }
            $data[$var] = $value;
        }
        return $data;
    }

    /**
     * Set current model data.
     * @param mixed $data
     */
    public function set_data($data = [])
    {
        if ($data instanceof yf_model_result) {
            $data = $data->get_data();
        }
        foreach ((array) $data as $k => $v) {
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
     * Default model foreign key.
     */
    public function get_foreign_key()
    {
        return strtolower(class_basename($this, '', '_model')) . '_id';
    }

    /**
     * Return new instance of the given model.
     * @param mixed $args
     */
    public function new_instance($args = [])
    {
        $model = new static($args);
        return $model;
    }

    /**
     * Return new instance of model result.
     * @param mixed $result
     */
    public function new_result($result = [])
    {
        return new yf_model_result($result, $this);
    }

    /**
     * Return new instance of model relation.
     * @param mixed $relation
     */
    public function new_relation($relation)
    {
        return new yf_model_relation($relation, $this);
    }

    /**
     * Return new query builder instance.
     * @param mixed $params
     */
    public function new_query($params = [])
    {
        if ($params['where'] === null) {
            unset($params['where']);
        }
        $table = $params['table'] ?: $this->get_table();
        if ( ! $table) {
            throw new Exception('MODEL: ' . get_called_class() . ': requires table name to make queries');
        }
        $builder = $this->_db->query_builder();
        $builder->_model = $this;
        $builder->_with = $this->_with;
        $builder->_result_wrapper = [$this, 'new_result'];
        $builder->_remove_as_from_delete = true;
        $builder->from($table . ' AS t0');
        foreach (['select', 'where', 'where_or', 'whereid', 'order_by', 'having', 'group_by'] as $part) {
            if ($params[$part]) {
                call_user_func_array([$builder, $part], is_array($params[$part]) ? $params[$part] : [$params[$part]]);
            }
        }
        // limit => [10,30] or limit => 5
        if ($params['limit']) {
            $count = is_numeric($params['limit']) ? $params['limit'] : $params['limit'][0];
            $offset = is_numeric($params['limit']) ? null : $params['limit'][1];
            $builder->limit($count, $offset);
        }
        foreach (['join', 'left_join', 'inner_join', 'right_join'] as $func) {
            if ($params[$func]) {
                $join = $params[$func];
                $builder->$func($join['table'], $join['on']);
            }
        }
        return $builder;
    }

    /**
     * Save model back into database.
     */
    public function save()
    {
        $data = $this->get_data();
        $pk = $this->get_key_name();
        if ( ! $data[$pk]) {
            $insert_id = $this->new_query()->insert($data);
            if ( ! $insert_id) {
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
        return $this->new_query(['whereid' => $this->get_key()])->update($data);
    }

    /**
     * Get the joining table name for a many-to-many relation.
     * @param mixed $related
     */
    public function joining_table($related)
    {
        $base = class_basename($this, '', '_model');
        $related = class_basename($related, '', '_model');
        $models = [$related, $base];
        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($models);
        $table = strtolower(implode('_', $models));
        return $this->_db->_fix_table_name($table);
    }

    /**
     * Relation one-to-one.
     * @param mixed $related
     * @param null|mixed $foreign_key
     * @param null|mixed $local_key
     * @param null|mixed $relation
     */
    public function has_one($related, $foreign_key = null, $local_key = null, $relation = null)
    {
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        $instance = $this->_db->model($related);
        return $this->new_relation([
            'type' => __FUNCTION__,
            'related' => $related,
            'relation' => $relation,
            'foreign_key' => $foreign_key ?: $this->get_foreign_key(),
            'local_key' => $local_key ?: $instance->get_key_name(),
            'query' => $instance->new_query(),
        ]);
    }

    /**
     * Relation one-to-one inversed.
     * @param mixed $related
     * @param null|mixed $foreign_key
     * @param null|mixed $other_key
     * @param null|mixed $relation
     */
    public function belongs_to($related, $foreign_key = null, $other_key = null, $relation = null)
    {
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        $instance = $this->_db->model($related);
        return $this->new_relation([
            'type' => __FUNCTION__,
            'related' => $related,
            'relation' => $relation,
            'foreign_key' => $foreign_key ?: strtolower($relation) . '_id',
            'other_key' => $other_key ?: $instance->get_key_name(),
            'query' => $instance->new_query(),
        ]);
    }

    /**
     * Relation one-to-many.
     * @param mixed $related
     * @param null|mixed $foreign_key
     * @param null|mixed $local_key
     * @param null|mixed $relation
     */
    public function has_many($related, $foreign_key = null, $local_key = null, $relation = null)
    {
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        $instance = $this->_db->model($related);
        return $this->new_relation([
            'type' => __FUNCTION__,
            'related' => $related,
            'relation' => $relation,
            'foreign_key' => $foreign_key ?: $this->get_foreign_key(),
            'local_key' => $local_key ?: $instance->get_key_name(),
            'query' => $instance->new_query(),
        ]);
    }

    /**
     * Relation many-to-many.
     * @param mixed $related
     * @param null|mixed $pivot_table
     * @param null|mixed $foreign_key
     * @param null|mixed $other_key
     * @param null|mixed $relation
     */
    public function belongs_to_many($related, $pivot_table = null, $foreign_key = null, $other_key = null, $relation = null)
    {
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        $instance = $this->_db->model($related);
        return $this->new_relation([
            'type' => __FUNCTION__,
            'related' => $related,
            'relation' => $relation,
            'pivot_table' => $pivot_table ?: $this->joining_table($related),
            'foreign_key' => $foreign_key ?: $this->get_foreign_key(),
            'other_key' => $other_key ?: $instance->get_foreign_key(),
            'query' => $instance->new_query(),
        ]);
    }

    /**
     * Relation distant through other model.
     * @param mixed $related
     * @param mixed $through_model
     * @param null|mixed $foreign_key
     * @param null|mixed $local_key
     * @param null|mixed $relation
     */
    public function has_many_through($related, $through_model, $foreign_key = null, $local_key = null, $relation = null)
    {
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);
            $relation = $caller['function'];
        }
        $instance = $this->_db->model($related);
        return $this->new_relation([
            'type' => __FUNCTION__,
            'related' => $related,
            'relation' => $relation,
            'through_model' => $through_model,
            'foreign_key' => $instance->get_table() . '.' . ($foreign_key ?: $this->get_foreign_key()),
            'local_key' => $local_key ?: $instance->get_key_name(),
            'query' => $instance->new_query(),
        ]);
    }

    /**
     * Relation polymorphic one-to-one.
     */
    public function morph_one()
    {
        // TODO
    }

    /**
     * Relation polymorphic one-to-many.
     */
    public function morph_to()
    {
        // TODO
    }

    /**
     * Relation polymorphic one-to-many.
     */
    public function morph_many()
    {
        // TODO
    }

    /**
     * Relation polymorphic many-to-many.
     */
    public function morph_to_many()
    {
        // TODO
    }

    /**
     * Relation polymorphic many-to-many.
     */
    public function morphed_by_many()
    {
        // TODO
    }

    /**
     * Associate here means to auotmatically create foreign key on child model.
     * @param mixed $model_instance
     */
    public function associate($model_instance)
    {
        // TODO
    }

    /**
     * Relation querying method $posts = model('post')->has('comments', '>=', 3)->get();.
     * @param mixed $relation
     * @param mixed $where
     */
    public function has($relation, $where = [])
    {
        // TODO
        return $this;
    }

    /**
     * Eager loading with relations. Examples:.
     *
     * model('post')->with('comments')->whereid($id)->first();
     *
     * foreach (model('book')->with('author')->get_all() as $book) {  // select * from authors where id in (1, 2, 3, 4, 5, ...)
     *	  echo $book->author->name;
     * }
     * @param mixed $model
     */
    public function with($model)
    {
        // TODO
        return $this;
    }

    /**
     * Delete matching record(s) from database.
     */
    public function delete()
    {
        return $this->new_query(['where' => func_get_args()])->limit(1)->delete();
    }

    /**
     * Update only model's timestamps.
     */
    public function touch()
    {
        return $this->new_query(['where' => func_get_args()])->update([self::UPDATED_AT => date('Y-m-d H:i:s')]);
    }

    /**
     * Linking with the table builder.
     * @param mixed $params
     */
    public function table($params = [])
    {
        $sql = $this->new_query((array) $params['query_builder'])->sql();
        $filter_name = $params['filter_name'] ?: ($this->_params['filter_name'] ?: $_GET['object'] . '__' . $_GET['action']);
        $params['filter'] = $params['filter'] ?: ($this->_params['filter'] ?: $_SESSION[$filter_name]);
        return table($sql, $params);
    }

    /**
     * Linking with the form builder.
     * @param mixed $whereid
     * @param mixed $data
     * @param mixed $params
     */
    public function form($whereid, $data = [], $params = [])
    {
        $a = (array) $this->new_query((array) $params['query_builder'])->whereid($whereid)->get();
        return form($a + (array) $data, $params);
    }

    /**
     * Linking with the form builder.
     * @param mixed $data
     * @param mixed $params
     */
    public function filter_form($data = [], $params = [])
    {
        $filter_name = $params['filter_name'] ?: $_GET['object'] . '__' . $_GET['action'];
        $a = [
            'form_action' => url_admin('/@object/filter_save/' . $filter_name),
            'clear_url' => url_admin('/@object/filter_save/' . $filter_name . '/clear'),
        ];
        $params['selected'] = $params['selected'] ?: $_SESSION[$filter_name];
        return form($a + (array) $data, $params);
    }

    /**
     * Model validation will be here.
     * @param mixed $rules
     * @param mixed $params
     */
    public function validate($rules = [], $params = [])
    {
        // TODO
    }

    /**
     * Html widget connetion.
     * @param mixed $name
     * @param mixed $params
     */
    public function html($name, $params = [])
    {
        // TODO
    }
}
