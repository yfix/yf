<?php

/**
*/
class yf_db_utils_helper_create_table {

	protected $utils = null;
	protected $db_name = '';
	protected $table_name = '';
	protected $fields = array();
	protected $indexes = array();
	protected $foreign_keys = array();
	protected $table_options = array();
	protected $for_create = false;

	/**
	* Catch missing method call
	*/
	public function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	public function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	*/
	public function _setup($params) {
		foreach ($params as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	/**
	*/
	public function _get_table_name($new_name = '') {
		return $this->db_name.'.'.$this->utils->db->_fix_table_name($new_name ?: $this->table_name);
	}

	/**
	*/
	public function __toString() {
		return $this->render();
	}

	/**
	*/
	public function render() {
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		return $parser->create(array(
			'name'			=> $this->_get_table_name(),
			'fields'		=> $this->fields,
			'indexes'		=> $this->indexes,
			'foreign_keys'	=> $this->foreign_keys,
			'options'		=> $this->table_options,
		));
	}

	/**
	*/
	public function increments($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'		=> 'int',
			'length'	=> 10,
			'auto_inc'	=> true,
			'nullable'	=> false,
			'unsigned'	=> true,
		))->primary($column);
	}

	/**
	*/
	public function big_increments($column, $params = array()) {
		return $this->increments($column, array('type' => 'bigint', 'length' => 20) + (array)$params);
	}

	/**
	*/
	public function char($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->add_column($column, (array)$params + array(
			'type'		=> 'char',
			'length'	=> $params['length'] ?: 255,
		));
	}

	/**
	* Alias
	*/
	public function string($column, $params = array()) {
		return $this->varchar($column, $params);
	}

	/**
	*/
	public function varchar($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->add_column($column, (array)$params + array(
			'type'		=> 'varchar',
			'length'	=> $params['length'] ?: 255,
		));
	}

	/**
	*/
	public function text($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'text',
		));
	}

	/**
	*/
	public function medium_text($column, $params = array()) {
		return $this->text($column, array('type' => 'mediumtext') + (array)$params);
	}

	/**
	*/
	public function long_text($column, $params = array()) {
		return $this->text($column, array('type' => 'longtext') + (array)$params);
	}

	/**
	* Alias for int
	*/
	public function integer($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, $params);
	}

	/**
	*/
	public function int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'int',
		));
	}

	/**
	*/
	public function big_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, array('type' => 'bigint') + (array)$params);
	}

	/**
	*/
	public function medium_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, array('type' => 'mediumint') + (array)$params);
	}

	/**
	*/
	public function tiny_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, array('type' => 'tinyint') + (array)$params);
	}

	/**
	*/
	public function small_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, array('type' => 'smallint') + (array)$params);
	}

	/**
	*/
	public function unsigned_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->int($column, array('unsigned' => true) + (array)$params);
	}

	/**
	*/
	public function unsigned_big_int($column, $params = array()) {
		if (is_numeric($params)) { $params = array('length' => $params); }
		return $this->big_int($column, array('unsigned' => true) + (array)$params);
	}

	/**
	*/
	public function float($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'float',
		));
	}

	/**
	*/
	public function double($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'double',
		));
	}

	/**
	*/
	public function decimal($column, $length = 8, $decimals = 2, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'		=> 'decimal',
			'length'	=> $length,
			'decimals'	=> $decimals,
		));
	}

	/**
	*/
	public function boolean($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'tinyint',
			'length'=> 1,
		));
	}

	/**
	*/
	public function binary($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'binary',
		));
	}

	/**
	*/
	public function enum($column, array $allowed, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'enum',
			'values'=> $allowed,
		));
	}

	/**
	*/
	public function date($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'date',
		));
	}

	/**
	*/
	public function date_time($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'datetime',
		));
	}

	/**
	*/
	public function time($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'time',
		));
	}

	/**
	*/
	public function timestamp($column, $params = array()) {
		return $this->add_column($column, (array)$params + array(
			'type'	=> 'timestamp',
		));
	}

	/**
	*/
	public function nullable_timestamps($params = array()) {
		$this->timestamp('created_at', array('nullable' => true) + (array)$params);
		$this->timestamp('updated_at', array('nullable' => true) + (array)$params);
		return $this;
	}

	/**
	*/
	public function timestamps($params = array()) {
		$this->timestamp('created_at', $params);
		$this->timestamp('updated_at', $params);
		return $this;
	}

	/**
	*/
	public function soft_deletes($params = array()) {
		return $this->timestamp('deleted_at', array('nullable' => true) + (array)$params);
	}

	/**
	* Rename current table
	*/
	public function rename($to)	{
		$this->utils->rename_table($this->_get_table_name(), $this->_get_table_name($to));
		return $this;
	}

	/**
	*/
	public function add_column($column, array $params) {
		$this->fields[$column] = $params;
		return $this;
	}

	/**
	*/
	public function drop_column($columns) {
		if (!is_array($columns)) {
			$columns = array($columns);
		}
		foreach ($columns as $column) {
			$this->utils->drop_column($this->_get_table_name(), $column);
		}
		return $this;
	}

	/**
	*/
	public function rename_column($from, $to) {
		$this->utils->rename_column($this->_get_table_name(), $from, $to);
		return $this;
	}

	/**
	*/
	public function drop_timestamps() {
		$this->drop_column('created_at');
		$this->drop_column('updated_at');
		return $this;
	}

	/**
	*/
	public function drop_soft_deletes() {
		$this->drop_column('deleted_at');
		return $this;
	}

	/**
	*/
	public function primary($columns, $name = null, $params = array())	{
		if (is_string($columns)) {
			$columns = array($columns => $columns);
		}
		if (!$name) {
			$name = 'PRIMARY';
		}
		if ($this->for_create) {
			$this->indexes[$name] = array(
				'type'		=> 'primary',
				'columns'	=> $columns,
			);
		} else {
			$this->utils->add_index($this->_get_table_name(), $name, $columns, array('type' => 'primary') + (array)$params);
		}
		return $this;
	}

	/**
	*/
	public function unique($columns, $name = null, $params = array()) {
		if (is_string($columns)) {
			$columns = array($columns => $columns);
		}
		if (!$name) {
			$name = 'uniq_'.implode('_', $columns);
		}
		if ($this->for_create) {
			$this->indexes[$name] = array(
				'type'		=> 'unique',
				'columns'	=> $columns,
			);
		} else {
			$this->utils->add_index($this->_get_table_name(), $name, $columns, array('type' => 'unique') + (array)$params);
		}
		return $this;
	}

	/**
	*/
	public function index($columns, $name = null, $params = array()) {
		if (is_string($columns)) {
			$columns = array($columns => $columns);
		}
		if (!$name) {
			$name = implode('_', $columns);
		}
		if ($this->for_create) {
			$this->indexes[$name] = array(
				'type'		=> 'index',
				'columns'	=> $columns,
			);
		} else {
			$this->utils->add_index($this->_get_table_name(), $name, $columns, $params);
		}
		return $this;
	}

	/**
	*/
	public function foreign($columns, $ref_table, $ref_columns, $name = null, $params = array()) {
		if (is_string($columns)) {
			$columns = array($columns => $columns);
		}
		if (is_string($ref_columns)) {
			$ref_columns = array($ref_columns => $ref_columns);
		}
		if (!$name) {
			$name = $ref_table.'_'.implode('_', $ref_columns);
		}
		if ($this->for_create) {
			$this->foreign_keys[$name] = array(
				'columns'		=> $columns,
				'ref_table'		=> $ref_table,
				'ref_columns'	=> $ref_columns,
			) + (array)$params;
		} else {
			$this->utils->add_foreign_key($this->_get_table_name(), $name, $columns, $this->_get_table_name($ref_table), $ref_columns, $params);
		}
		return $this;
	}

	/**
	*/
	public function drop_primary($name = null) {
		if (!$name) {
			$name = 'PRIMARY';
		}
		$this->utils->drop_index($this->_get_table_name(), $name);
		return $this;
	}

	/**
	*/
	public function drop_unique($name) {
		$this->utils->drop_index($this->_get_table_name(), $name);
		return $this;
	}

	/**
	*/
	public function drop_index($name) {
		$this->utils->drop_index($this->_get_table_name(), $name);
		return $this;
	}

	/**
	*/
	public function drop_foreign($name) {
		$this->utils->drop_foreign_key($this->_get_table_name(), $name);
		return $this;
	}

	/**
	*/
	public function option($name, $value) {
		$this->table_options[$name] = $value;
		return $this;
	}
}
