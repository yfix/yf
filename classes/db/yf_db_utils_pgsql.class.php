<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_pgsql extends yf_db_utils_driver {

	/**
	*/
	public function _get_supported_field_types() {
// TODO
		return array(
			'bit','int','real','float','double','decimal','numeric',
			'varchar','char','tinytext','mediumtext','longtext','text',
			'tinyblob','mediumblob','longblob','blob','varbinary','binary',
			'timestamp','datetime','time','date','year',
			'enum','set',
		);
	}

	/**
	*/
	public function _get_unsigned_field_types() {
// TODO
		return array(
			'bit','int','real','double','float','decimal','numeric'
		);
	}

	/**
	*/
	public function _get_supported_table_options() {
// TODO
		return array(
			'conn_limit' => 'CONNECTION LIMIT',
		);
	}

	/**
	*/
	public function list_databases($extra = array()) {
		$sql = 'SELECT datname,datname FROM pg_database WHERE datistemplate = false';
		return $extra['sql'] ? $sql : $this->db->get_2d($sql);
	}

	/**
	*/
	public function database_exists($db_name, $extra = array(), &$error = false) {
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		return (bool)in_array($db_name, (array)$this->list_databases());
	}

	/**
	*/
	public function database_info($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$info = $this->db->get('SELECT * FROM pg_database WHERE datname = '.$this->_escape_val($db_name));
		if (!$info) {
			$error = 'db_name not exists';
			return false;
		}
		return array(
			'name'	=> $db_name,
		);
	}

	/**
	*/
	public function create_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = 'CREATE DATABASE '. $this->_escape_database_name($db_name);
		return $extra['sql'] ? $sql : (bool)$this->db->query($sql);
	}

	/**
	*/
	public function drop_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		if (!isset($extra['if_exists'])) {
			$extra['if_exists'] = true;
		}
		if (!$extra['sql'] && !$this->database_exists($db_name)) {
			return true;
		}
		foreach ((array)$this->list_tables($db_name) as $table) {
			$table = trim($table);
			if (!strlen($table)) {
				continue;
			}
			$sql[] = $this->drop_table($db_name.'.'.$table, $extra);
		}
		$_sql = 'DROP DATABASE '.($extra['if_exists'] ? 'IF EXISTS ' : ''). $this->_escape_database_name($db_name);
		$sql[] = $extra['sql'] ? $_sql : $this->db->query($_sql);
		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
	}

	/**
	*/
	public function alter_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$allowed = array(
			'conn_limit' => 'CONNECTION LIMIT',
		);
		foreach ((array)$extra as $k => $v) {
			$v = preg_replace('~[^a-z0-9_]+~i', '', $v);
			if (isset($allowed[$k])) {
				$params[$k] = $allowed[$k].' = '.$v;
			} elseif (in_array($k, $allowed)) {
				$params[$k] = $k.' = '.$v;
			}
		}
		$sql = '';
		if ($params) {
			$sql = 'ALTER DATABASE '.$this->_escape_database_name($db_name).' '.implode(' ', $params);
		}
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function rename_database($db_name, $new_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		if (!strlen($new_name)) {
			$error = 'new_name is empty';
			return false;
		}
		$sql = 'ALTER DATABASE '.$this->_escape_database_name($db_name).' RENAME TO '.$this->_escape_database_name($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function truncate_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		if (!isset($extra['if_exists'])) {
			$extra['if_exists'] = true;
		}
		if (!$extra['sql'] && !$this->database_exists($db_name)) {
			return true;
		}
		foreach ((array)$this->list_tables($db_name) as $table) {
			$table = trim($table);
			if (!strlen($table)) {
				continue;
			}
			$sql[] = $this->drop_table($db_name.'.'.$table, $extra);
		}
		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
	}

	/**
	*/
	public function list_tables($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = 'SELECT table_name
			FROM "information_schema"."tables"
			WHERE "table_catalog" = '.$this->_escape_val($db_name).'
				AND "table_schema" = \'public\'
			ORDER BY table_schema,table_name';
		$tables = $this->db->get_2d($sql);
		return $tables ? array_combine($tables, $tables) : array();
	}

	/**
	*/
	public function list_tables_details($db_name = '', $extra = array(), &$error = false) {
		foreach((array)$this->list_tables($db_name, $extra, $error) as $table) {
			$tables[$table] = array(
				'name'		=> $table,
				'engine'	=> null,
				'rows'		=> null,
				'data_size'	=> null,
				'collate'	=> null,
			);
		}
		return $tables;
	}

	/**
	*/
	public function table_exists($table, $extra = array(), &$error = false) {
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		return (bool)in_array($table, (array)$this->list_tables($db_name));
	}

	/**
	*/
	public function table_get_columns($table, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$extra['sql'] && !$this->table_exists($table)) {
			$error = 'table_name not exists';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function table_info($table, $extra = array(), &$error = false) {
		$orig_table = $table;
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!strlen($table)) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function create_table($table, $data = array(), $extra = array(), &$error = false) {
		$orig_table = $table;
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		if (!$extra['sql'] && $this->table_exists($table, $db_name)) {
			$error = 'table_name already exists';
			return false;
		}
		$table_options = array();
		foreach ((array)$this->_get_supported_table_options() as $name => $real_name) {
			if (isset($extra[$name]) && strlen($extra[$name])) {
				$table_options[$name] = $real_name.'='.$extra[$name];
			}
		}
		$table_options = implode(' ', $table_options);

		$data = ($extra['sql'] ?: $extra['data']) ?: $data;
		if (is_array($data)) {
			$data = $this->_compile_create_table($data, $extra, $error);
		}
		if (!$data) {
			$data = $this->_get_table_structure_from_db_installer($table, $error);
		}
		if (!$data) {
			$error = 'data is empty';
			return false;
		}
		$sql = 'CREATE TABLE IF NOT EXISTS '.$this->_escape_table_name($db_name.'.'.$table).' ('
			. PHP_EOL. $data. PHP_EOL
			. ')'.($table_options ? ' '.$table_options : '')
			. ';'. PHP_EOL;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function drop_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'DROP TABLE IF EXISTS '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Here we support only small subset of alter table options, mostly related to basic things like engine or charset
	*/
	public function alter_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$table_options = array();
		foreach ((array)$this->_get_supported_table_options() as $name => $real_name) {
			if (isset($extra[$name]) && strlen($extra[$name])) {
				$table_options[$name] = $real_name.'='.$extra[$name];
			}
		}
		if (empty($table_options)) {
			$error = 'no supported table options provided';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table). PHP_EOL. implode(' ', $table_options);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function rename_table($table, $new_name, $extra = array(), &$error = false) {
		if (!$table || !$new_name) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table). ' RENAME '.$this->_escape_table_name($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function truncate_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'TRUNCATE TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function list_columns($table, $extra = array(), &$error = false) {
		return $this->table_get_columns($table, $extra, $error);
	}

	/**
	*/
	public function column_exists($table, $col_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]);
	}

	/**
	*/
	public function column_info($table, $col_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]) ? $columns[$col_name] : false;
	}

	/**
	*/
	public function column_info_item($table, $col_name, $item_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name][$item_name]) ? $columns[$col_name][$item_name] : false;
	}

	/**
	*/
	public function drop_column($table, $col_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP COLUMN '.$this->db->escape_key($col_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function add_column($table, $data, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ADD COLUMN '.$this->_compile_create_table($data);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function rename_column($table, $col_name, $new_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' RENAME COLUMN '.$this->_escape_key($col_name).' TO '.$this->_escape_key($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function alter_column($table, $col_name, $data, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$col_info = $this->column_info($table, $col_name);
		if (!$col_info) {
			$error = 'column not exists';
			return false;
		}
		foreach ((array)$data as $k => $v) {
			if (isset($col_info[$k])) {
				$col_info[$k] = $v;
			}
		}
		$col_info_str = $this->_compile_create_table($col_info, array('no_name' => true));
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ALTER COLUMN '.$this->_escape_key($col_name).' '.$col_info_str;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function list_indexes($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function index_info($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$indexes = $this->list_indexes($table);
		return isset($indexes[$index_name]) ? $indexes[$index_name] : false;
	}

	/**
	*/
	public function index_exists($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$indexes = $this->list_indexes($table);
		return isset($indexes[$index_name]);
	}

	/**
	*/
	public function add_index($table, $index_name = '', $fields = array(), $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (empty($fields)) {
			$error = 'fields are empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function drop_index($table, $index_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function update_index($table, $index_name, $fields = array(), $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$this->drop_index($table, $index_name, $extra, $error);
		return $this->add_index($table, $index_name, $fields, $extra, $error);
	}

	/**
	*/
	public function list_foreign_keys($table, $extra = array(), &$error = false) {
		$orig_table = $table;
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function foreign_key_info($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$keys = $this->list_foreign_keys($table);
		return isset($keys[$index_name]) ? $keys[$index_name] : false;
	}

	/**
	*/
	public function foreign_key_exists($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$keys = $this->list_foreign_keys($table);
		return isset($keys[$index_name]);
	}

	/**
	*/
	public function drop_foreign_key($table, $index_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function add_foreign_key($table, $index_name = '', array $fields, $ref_table, array $ref_fields, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (empty($fields)) {
			$error = 'fields are empty';
			return false;
		}
		if (!strlen($ref_table)) {
			$error = 'referenced table name is empty';
			return false;
		}
		if (empty($ref_fields)) {
			$error = 'referenced fields are empty';
			return false;
		}
		if (empty($index_name)) {
			$index_name = $ref_table.'_'.implode('_', $ref_fields);
		}
		$supported_ref_options = array(
			'restrict'	=> 'RESTRICT',
			'cascade'	=> 'CASCADE',
			'set_null'	=> 'SET NULL',
			'no_action'	=> 'NO ACTION',
		);
		$on_delete = isset($extra['on_delete']) && isset($supported_ref_options[$extra['on_delete']]) ? $supported_ref_options[$extra['on_delete']] : '';
		$on_update = isset($extra['on_update']) && isset($supported_ref_options[$extra['on_update']]) ? $supported_ref_options[$extra['on_update']] : '';

// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function update_foreign_key($table, $index_name, array $fields, $ref_table, array $ref_fields, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$this->drop_foreign_key($table, $index_name, $extra, $error);
		return $this->add_foreign_key($table, $index_name, $fields, $ref_table, $ref_fields, $extra, $error);
	}

	/**
	*/
	public function list_views($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}

// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function view_exists($table, $extra = array(), &$error = false) {
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$views = $this->list_views($db_name);
		return (bool)isset($views[$table]);
	}

	/**
	*/
	public function view_info($table, $extra = array(), &$error = false) {
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$views = $this->list_views($db_name);
		return isset($views[$table]) ? $views[$table] : false;
	}

	/**
	*/
	public function drop_view($table, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'view name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function create_view($table, $sql_as, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function list_triggers($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function trigger_exists($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'trigger name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$triggers = $this->list_triggers($db_name, $extra, $error);
		return (bool)isset($triggers[$name]);
	}

	/**
	*/
	public function trigger_info($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'trigger name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$triggers = $this->list_triggers($db_name, $extra, $error);
		return isset($triggers[$name]) ? $triggers[$name] : false;
	}

	/**
	*/
	public function drop_trigger($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function create_trigger($name, $table, $trigger_time, $trigger_event, $trigger_body, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'trigger name is empty';
			return false;
		}
		if (strpos($table, '.') !== false) {
			list($db_name, $table) = explode('.', trim($table));
		}
		if (!$table) {
			$error = 'trigger table is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$supported_trigger_times = array(
			'before',
			'after'
		);
		if (!strlen($trigger_time) || !in_array(strtolower($trigger_time), $supported_trigger_times)) {
			$error = 'trigger time is wrong';
			return false;
		}
		$supported_trigger_events = array(
			'insert',
			'update',
			'delete'
		);
		if (!strlen($trigger_event) || !in_array(strtolower($trigger_event), $supported_trigger_events)) {
			$error = 'trigger event is wrong';
			return false;
		}
		if (!strlen($trigger_body)) {
			$error = 'trigger body is empty';
			return false;
		}
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function _parse_column_type($str, &$error = false) {
// TODO: use code from mysql and adapt it
	}

	/**
	* Create part of SQL for "CREATE TABLE" from array of params
	*/
	public function _compile_create_table($data, $extra = array(), &$error = false) {
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function _escape_database_name($name = '') {
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function _escape_table_name($name = '') {
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function _escape_key($key = '') {
// TODO: use code from mysql and adapt it
	}

	/**
	*/
	public function _escape_val($val = '') {
		$val = trim($val);
		if (!strlen($val)) {
			return '';
		}
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) ? $this->db->escape_val($val) : '\''.addslashes($val).'\'';
	}

	/**
	*/
	public function _escape_fields(array $fields) {
		if (empty($fields)) {
			return $fields;
		}
		$self = __FUNCTION__;
		foreach ((array)$fields as $k => $v) {
			if (is_array($v)) {
				$fields[$k] = $this->$self($v);
			} else {
				$fields[$k] = $this->_escape_key($v);
			}
		}
		return $fields;
	}

	/**
	*/
	public function _es($val = '') {
		$val = trim($val);
		if (!strlen($val)) {
			return '';
		}
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) && method_exists($this->db, '_es') ? $this->db->_es($val) : addslashes($val);
	}
}
