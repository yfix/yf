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
	public function __toString() {
		return $this->render();
	}

	/**
	*/
	public function render() {
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		return $parser->create(array(
			'name'			=> $this->db_name.'.'.$this->utils->db->_fix_table_name($this->table_name),
			'fields'		=> $this->fields,
			'indexes'		=> $this->indexes,
			'foreign_keys'	=> $this->foreign_keys,
			'options'		=> $this->table_options,
		));
	}

	/**
	*/
	public function increments($column, $params = array())	{
		$this->fields[$column] = (array)$params + array(
			'type'		=> 'int',
			'length'	=> 10,
			'auto_inc'	=> true,
			'nullable'	=> false,
			'unsigned'	=> true,
		);
		return $this;
	}

	/**
	*/
	public function big_increments($column, $params = array()) {
		return $this->increments($column, array('type' => 'bigint', 'length' => 20) + (array)$params);
	}

	/**
	*/
	public function char($column, $length = 255, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'		=> 'char',
			'length'	=> $length,
		);
		return $this;
	}

	/**
	*/
	public function string($column, $length = 255, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'		=> 'varchar',
			'length'	=> $length,
		);
		return $this;
	}

	/**
	*/
	public function text($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'		=> 'text',
		);
		return $this;
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
		return $this->int($column, $params);
	}

	/**
	*/
	public function int($column, $params = array()) {
		if (is_numeric($params)) {
			$params = array(
				'length' => $params,
			);
		}
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'int',
		);
		return $this;
	}

	/**
	*/
	public function big_int($column, $params = array()) {
		return $this->int($column, array('type' => 'bigint') + (array)$params);
	}

	/**
	*/
	public function medium_int($column, $params = array()) {
		return $this->int($column, array('type' => 'mediumint') + (array)$params);
	}

	/**
	*/
	public function tiny_int($column, $params = array()) {
		return $this->int($column, array('type' => 'tinyint') + (array)$params);
	}

	/**
	*/
	public function small_int($column, $params = array()) {
		return $this->int($column, array('type' => 'smallint') + (array)$params);
	}

	/**
	*/
	public function unsigned_int($column, $params = array()) {
		return $this->int($column, array('unsigned' => true) + (array)$params);
	}

	/**
	*/
	public function unsigned_big_int($column, $params = array()) {
		return $this->big_int($column, array('unsigned' => true) + (array)$params);
	}

	/**
	*/
	public function float($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'float',
		);
		return $this;
	}

	/**
	*/
	public function double($column, $params = array()) {
		return $this->float($column, array('type' => 'double') + (array)$params);
	}

	/**
	*/
	public function decimal($column, $length = 8, $decimals = 2, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'		=> 'decimal',
			'length'	=> $length,
			'decimals'	=> $decimals,
		);
		return $this;
	}

	/**
	*/
	public function boolean($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'tinyint',
			'length'=> 1,
		);
		return $this;
	}

	/**
	*/
	public function binary($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'binary',
		);
		return $this;
	}

	/**
	*/
	public function enum($column, array $allowed, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'enum',
			'values'=> $allowed,
		);
		return $this;
	}

	/**
	*/
	public function date($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'date',
		);
		return $this;
	}

	/**
	*/
	public function date_time($column, $params = array()) {
		return $this->int($column, array('type' => 'datetime') + (array)$params);
	}

	/**
	*/
	public function time($column, $params = array()) {
		return $this->int($column, array('type' => 'time') + (array)$params);
	}

	/**
	*/
	public function timestamp($column, $params = array()) {
		$this->fields[$column] = (array)$params + array(
			'type'	=> 'timestamp',
		);
		return $this;
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

#	public function morphs($name, $indexName = null) {
#		$this->unsignedInteger("{$name}_id");
#		$this->string("{$name}_type");
#		$this->index(array("{$name}_id", "{$name}_type"), $indexName);
#	}

#	public function rememberToken() {
#		return $this->string('remember_token', 100)->nullable();
#	}

	public function dropColumn($columns) {
		$columns = is_array($columns) ? $columns : (array) func_get_args();
		return $this->addCommand('dropColumn', compact('columns'));
	}

	public function renameColumn($from, $to) {
	}

	public function dropPrimary($index = null) {
	}

	public function dropUnique($index) {
	}

	public function dropIndex($index) {
	}

	public function dropForeign($index) {
	}

	public function dropTimestamps() {
	}

	public function dropSoftDeletes() {
	}

	public function rename($to)	{
	}

	public function primary($columns, $name = null)	{
	}

	public function unique($columns, $name = null) {
	}

	public function index($columns, $name = null) {
	}

	public function foreign($columns, $name = null) {
	}
}
