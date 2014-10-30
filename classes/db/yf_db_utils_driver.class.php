<?php

/**
* YF db utils driver abstract class
*/
abstract class yf_db_utils_driver {

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
	public function _get_supported_field_types() {
		return array('int','float','double','decimal','numeric','varchar','char','text','datetime','date');
	}

	/**
	*/
	public function _get_unsigned_field_types() {
		return array('int');
	}

	/**
	*/
	public function _get_supported_table_options() {
		return array();
	}

	/**
	*/
	public function list_databases($extra = array()) {
		$sql = 'SHOW DATABASES';
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
		if (is_array($db_name)) {
			$extra = (array)$extra + $db_name;
			$db_name = '';
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$info = $this->db->get('SELECT * FROM information_schema.SCHEMATA WHERE schema_name = '.$this->_escape_val($db_name));
		if (!$info) {
			$error = 'db_name not exists';
			return false;
		}
		return array(
			'name'		=> $db_name,
			'charset'	=> $info['DEFAULT_CHARACTER_SET_NAME'],
			'collate'	=> $info['DEFAULT_COLLATION_NAME'],
		);
	}

	/**
	*/
	public function create_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		if (!isset($extra['if_not_exists'])) {
			$extra['if_not_exists'] = true;
		}
		$sql = 'CREATE DATABASE '.($extra['if_not_exists'] ? 'IF NOT EXISTS ' : ''). $this->_escape_database_name($db_name);
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
			'charset'	=> 'CHARACTER SET',
			'collate'	=> 'COLLATE',
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
		if (!$extra['sql'] && !$this->database_exists($db_name)) {
			$error = 'db_name not exists';
			return false;
		}
		if (!$extra['sql'] && $this->database_exists($new_name)) {
			$error = 'new database already exists';
			return false;
		}
		$sql[] = $this->create_database($new_name, $extra);
		foreach ((array)$this->list_tables($db_name) as $t) {
			$t = trim($t);
			if (!strlen($t)) {
				continue;
			}
			$sql[] = $this->rename_table($db_name.'.'.$t, $new_name.'.'.$t, $extra);
		}
		$sql[] = $this->drop_database($db_name, $extra);
		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
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
			$sql[] = $this->drop_table($db_name.'.'.$table, $extra);
		}
		foreach ((array)$this->list_views($db_name) as $name => $tmp) {
			$sql[] = $this->drop_view($db_name.'.'.$name, $extra);
		}
		foreach ((array)$this->list_triggers($db_name) as $name => $tmp) {
			$sql[] = $this->drop_trigger($db_name.'.'.$name, $extra);
		}
		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
	}

	/**
	*/
	public function list_tables($db_name = '', $extra = array(), &$error = false) {
		if (is_array($db_name)) {
			$extra = (array)$extra + $db_name;
			$db_name = '';
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$no_views = isset($extra['no_views']) ? (bool)$extra['no_views'] : true;
		$tables = array();
		$sql = 'SHOW FULL TABLES'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name) : '');
		foreach ((array)$this->db->get_all($sql) as $a) {
			list($table, $type) = array_values($a);
			if ($no_views && $type === 'VIEW') {
				continue;
			}
			$tables[$table] = $table;
		}
		return $tables;
	}

	/**
	*/
	public function list_tables_details($db_name = '', $extra = array(), &$error = false) {
		if (is_array($db_name)) {
			$extra = (array)$extra + $db_name;
			$db_name = '';
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$tables = array();
		$q = $this->db->query('SHOW TABLE STATUS'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name) : ''));
		while ($a = $this->db->fetch_assoc($q)) {
			$table = $a['Name'];
			$tables[$table] = array(
				'name'		=> $table,
				'engine'	=> $a['Engine'],
				'rows'		=> $a['Rows'],
				'data_size'	=> $a['Data_length'],
				'collate'	=> $a['Collation'],
			);
		}
		return $tables;
	}

	/**
	*/
	public function table_exists($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		$db_prefix = $this->db->DB_PREFIX;
		return (bool)in_array($table, (array)$this->list_tables($db_name)) || (strlen($db_prefix) && in_array($db_prefix. $table, (array)$this->list_tables($db_name)));
	}

	/**
	*/
	public function table_get_columns($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'table_name is empty';
			return false;
		}
		$cols = array();
		$q = $this->db->query('SHOW FULL COLUMNS FROM '.$this->_escape_table_name($table));
		while ($a = $this->db->fetch_assoc($q)) {
			$name = $a['Field'];
			list($type, $length, $unsigned, $decimals, $values) = array_values($this->_parse_column_type($a['Type']));
			$nullable = ($a['Null'] == 'YES');
			$default = null;
			if (!is_null($a['Default'])) {
				$default = trim($a['Default']);
			}
			$cols[$name] = array(
				'name'		=> $name,
				'type'		=> $type,
				'length'	=> $length ? intval($length) : null,
				'decimals'	=> $decimals ?: null,
				'unsigned'	=> $unsigned ?: null,
				'nullable'	=> (bool)$nullable,
				'default'	=> $default,
// TODO: detect charset for column
				'charset'	=> null,
				'collate'	=> $a['Collation'] != 'NULL' ? $a['Collation'] : null,
				'auto_inc'	=> false !== strpos(strtolower($a['Extra']), 'auto_increment') ? true : false,
				'primary'	=> $a['Key'] == 'PRI',
				'unique'	=> $a['Key'] == 'UNI',
				'values'	=> $values ?: null,
			);
			if (false !== strpos(strtolower($a['Extra']), 'on update') && in_array($type, array('timestamp','datetime'))) {
				$cols[$name]['on_update'] = strtoupper($a['Extra']);
			}
			$cols[$name]['type_raw'] = $a['Type'];
		}
		// Optionally fill "unique" field from indexes info
		$indexes = $this->list_indexes($table, $extra, $error);
		if ($indexes) {
			foreach ((array)$indexes as $name => $idx) {
				if ($idx['type'] !== 'unique') {
					continue;
				}
				foreach ($idx['columns'] as $fname) {
					if (!isset($cols[$fname])) {
						continue;
					}
					$cols[$fname]['unique'] = true;
				}
			}
		}
		return $cols;
	}

/*
For Schemas:

SELECT default_character_set_name FROM information_schema.SCHEMATA S
WHERE schema_name = "schemaname";
For Tables:

SELECT CCSA.character_set_name FROM information_schema.`TABLES` T,
       information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
WHERE CCSA.collation_name = T.table_collation
  AND T.table_schema = "schemaname"
  AND T.table_name = "tablename";
For Columns:

SELECT character_set_name FROM information_schema.`COLUMNS` C
WHERE table_schema = "schemaname"
  AND table_name = "tablename"
  AND column_name = "columnname";
*/

	/**
	*/
	public function table_get_charset($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		$info = $this->db->get(
			'SELECT CCSA.character_set_name
			FROM information_schema.`TABLES` T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
			WHERE CCSA.collation_name = T.table_collation
				AND T.table_schema = "'.$db_name.'"
				AND T.table_name = "'.$this->db->_fix_table_name($table).'"'
		);
		if (!$info) {
			$error = 'table_name not exists';
			return false;
		}
		return $info['character_set_name'];
	}

	/**
	*/
	public function table_info($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		$info = $this->db->get('SHOW TABLE STATUS'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name).' LIKE "'.$this->db->_fix_table_name($table).'"' : ''));
		if (!$info) {
			$error = 'table_name not exists';
			return false;
		}
		return array(
			'name'			=> $table,
			'db_name'		=> $db_name,
			'columns'		=> !$extra['just_info'] ? $this->table_get_columns($orig_table) : null,
			'row_format'	=> $info['Row_format'],
			'charset'		=> !$extra['just_info'] ? $this->table_get_charset($orig_table) : null,
			'collate'		=> $info['Collation'],
			'engine'		=> $info['Engine'],
			'rows'			=> $info['Rows'],
			'data_size'		=> $info['Data_length'],
			'auto_inc'		=> $info['Auto_increment'],
			'comment'		=> $info['Comment'],
			'create_time'	=> $info['Create_time'],
			'update_time'	=> $info['Update_time'],
		);
	}

	/**
	*/
	public function table_simple_info($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$extra['just_info'] = true;
		return $this->table_info($table, $extra, $error);
	}

	/**
	*/
	public function table_options($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$info = $this->table_simple_info($table, $extra, $error);
		return array(
			'engine'	=> $info['engine'],
			'charset'	=> !$extra['just_info'] ? $this->table_get_charset($table) : null,
			'collate'	=> $info['collate'],
			'comment'	=> $info['comment'],
		);
	}

	/**
	*/
	public function create_table($table, $extra = array(), &$error = false) {
		// Example callable: create_table($name, function($t) { $t->int('id', 10); });
		if (is_callable($extra)) {
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
			$obj = clone _class('db_utils_helper_create_table', 'classes/db/');
			$extra($obj->_setup(array(
				'utils'			=> $this,
				'db_name'		=> $db_name,
				'table_name'	=> $table,
			)));
			return $obj->render();
		}
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$table = $extra['name'] ?: $table;
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
		if (empty($extra['fields'])) {
			$data = $this->_get_table_structure_from_db_installer($table, $error);
			if ($data['fields']) {
				foreach (array('fields','indexes','foreign_keys','options') as $k) {
					$extra[$k] = $data[$k];
				}
			}
		}
		if (empty($extra['fields'])) {
			$error = 'table fields empty';
			return false;
		}
		// Default table options
		$table_options = array(
			'engine'	=> 'InnoDB',
			'charset'	=> 'utf8',
		);
		foreach ((array)$this->_get_supported_table_options() as $name => $real_name) {
			if (isset($extra['options'][$name]) && strlen($extra['options'][$name])) {
				$table_options[$name] = $extra['options'][$name];
			}
		}
		$plen = strlen($this->db->DB_PREFIX);
		$need_fix = $db_name == $this->db->DB_NAME;
		if ($need_fix && $extra['foreign_keys'] && $plen) {
			foreach ((array)$extra['foreign_keys'] as $fname => $finfo) {
				$extra['foreign_keys'][$fname]['ref_table'] = $this->db->_fix_table_name($finfo['ref_table']);
			}
		}
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$sql = $parser->create(array(
			'name'			=> $db_name.'.'.($need_fix ? $this->db->_fix_table_name($table) : $table),
			'fields'		=> $extra['fields'],
			'indexes'		=> $extra['indexes'],
			'foreign_keys'	=> $extra['foreign_keys'],
			'options'		=> $table_options,
		));
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function drop_table($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!$table || !$new_name) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'RENAME TABLE '.$this->_escape_table_name($table).' TO '.$this->_escape_table_name($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function truncate_table($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		return $this->table_get_columns($table, $extra, $error);
	}

	/**
	*/
	public function column_exists($table, $col_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]);
	}

	/**
	*/
	public function column_info($table, $col_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]) ? $columns[$col_name] : false;
	}

	/**
	*/
	public function column_info_item($table, $col_name, $item_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name][$item_name]) ? $columns[$col_name][$item_name] : false;
	}

	/**
	*/
	public function drop_column($table, $col_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ADD COLUMN '.$parser->create_column_line($data);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function rename_column($table, $col_name, $new_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$new_data = $this->column_info($table, $col_name);
		$new_data['name'] = $new_name;
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' CHANGE COLUMN '.$this->_escape_key($col_name).' '.$parser->create_column_line($new_data);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function alter_column($table, $col_name, $data, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (isset($data['first'])) {
			$position_change = ' FIRST';
		} elseif ($data['after']) {
			$position_change = ' AFTER '.$this->_escape_key($data['after']);
		}
		$parser = _class('db_ddl_parser_mysql', 'classes/db/');
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' MODIFY COLUMN '.$parser->create_column_line($col_info). $position_change;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function list_indexes($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
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
		// Possible alternative query: SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'test3' AND TABLE_NAME = 't_user' AND COLUMN_KEY = 'PRI';
		$indexes = array();
		foreach ((array)$this->db->get_all('SHOW INDEX FROM ' . $this->_escape_table_name($table)) as $row) {
			$type = 'index';
			if ($row['Key_name'] === 'PRIMARY') {
				$type = 'primary';
			} elseif (!$row['Non_unique']) {
				$type = 'unique';
			} elseif ($row['Index_type'] == 'FULLTEXT') {
				$type = 'fulltext';
			} elseif ($row['Index_type'] == 'SPATIAL') {
				$type = 'spatial';
			}
			if (!isset($indexes[$row['Key_name']])) {
				$indexes[$row['Key_name']] = array(
					'name'		=> $row['Key_name'],
					'type'		=> $type,
				);
			}
			$indexes[$row['Key_name']]['columns'][$row['Column_name']] = $row['Column_name'];
		}
		return $indexes;
	}

	/**
	*/
	public function index_info($table, $index_name, &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (is_array($index_name)) {
			$extra = (array)$extra + $index_name;
			$index_name = '';
		}
		$table = $extra['table'] ?: $table;
		$index_name = $extra['name'] ?: $index_name;
		$fields = $extra['columns'] ?: $fields;
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (empty($fields)) {
			$error = 'fields are empty';
			return false;
		}
		// gemerate index name from columns names
		$index_name = $index_name ?: implode('_', $fields);
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$index_type = strtolower($extra['type'] ?: 'index');
		$supported_types = array(
			'index'		=> 'index',
			'primary' 	=> 'primary key',
			'unique' 	=> 'unique key',
			'fulltext' 	=> 'fulltext key',
		);
		if (!isset($supported_types[$index_type])) {
			$error = 'index type is not supported';
			return false;
		}
		if ($index_name == 'PRIMARY' || $index_type == 'primary') {
			$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ADD PRIMARY KEY ('.implode(',', $this->_escape_fields($fields)).')';
		} else {
			$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ADD '.strtoupper($supported_types[$index_type]).' '.$this->_escape_val($index_name).' ('.implode(',', $this->_escape_fields($fields)).')';
		}
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function drop_index($table, $index_name, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$index_name = $extra['name'] ?: $index_name;
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		if ($index_name == 'PRIMARY') {
			$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP PRIMARY KEY';
		} else {
			$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP INDEX '.$this->_escape_key($index_name);
		}
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Alias
	*/
	public function alter_index($table, $index_name = '', $fields = array(), $extra = array(), &$error = false) {
		return $this->update_index($table, $index_name, $fields, $extra, $error);
	}

	/**
	*/
	public function update_index($table, $index_name = '', $fields = array(), $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (is_array($index_name)) {
			$extra = (array)$extra + $index_name;
			$index_name = '';
		}
		$table = $extra['table'] ?: $table;
		$index_name = $extra['name'] ?: $index_name;
		if ($this->drop_index($table, $index_name, $extra, $error)) {
			return $this->add_index($table, $index_name, $fields, $extra, $error);
		}
		return false;
	}

	/**
	*/
	public function list_foreign_keys($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		$keys = array();
		$sql = 
			'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = '.$this->_escape_val($db_name).' 
				AND REFERENCED_TABLE_NAME IS NOT NULL 
				AND TABLE_NAME = '. $this->_escape_val($this->db->_fix_table_name($table));
		foreach ((array)$this->db->get_all($sql) as $a) {
			$name = $a['CONSTRAINT_NAME'];
			$keys[$name]['name'] = $name;
			$keys[$name]['columns'][$a['COLUMN_NAME']] = $a['COLUMN_NAME'];
			$keys[$name]['ref_table'] = $a['REFERENCED_TABLE_NAME'];
			$keys[$name]['ref_columns'][$a['REFERENCED_COLUMN_NAME']] = $a['REFERENCED_COLUMN_NAME'];
		}
		$sql = 
			'SELECT CONSTRAINT_NAME, UPDATE_RULE, DELETE_RULE
			FROM information_schema.REFERENTIAL_CONSTRAINTS
			WHERE CONSTRAINT_SCHEMA = '.$this->_escape_val($db_name).'
				AND TABLE_NAME = '.$this->_escape_val($this->db->_fix_table_name($table));
		foreach ((array)$this->db->get_all($sql) as $a) {
			$name = $a['CONSTRAINT_NAME'];
			if (isset($keys[$name])) {
				$keys[$name]['on_update'] = $a['UPDATE_RULE'];
				$keys[$name]['on_delete'] = $a['DELETE_RULE'];
			}
		}
		return $keys;
	}

	/**
	*/
	public function foreign_key_info($table, $index_name, &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		$table = $extra['table'] ?: $table;
		$index_name = $extra['name'] ?: $index_name;
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP FOREIGN KEY '.$this->_escape_key($index_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function add_foreign_key($table, $index_name = '', $fields = array(), $ref_table = '', $ref_fields = array(), $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (is_array($index_name)) {
			$extra = (array)$extra + $index_name;
			$index_name = '';
		}
		$table = $extra['table'] ?: $table;
		$index_name = $extra['name'] ?: $index_name;
		$fields = $extra['columns'] ?: $fields;
		$ref_table = $extra['ref_table'] ?: $ref_table;
		$ref_fields = $extra['ref_columns'] ?: $ref_fields;
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		if (empty($fields) || !is_array($fields)) {
			$error = 'fields are empty';
			return false;
		}
		if (!strlen($ref_table)) {
			$error = 'referenced table name is empty';
			return false;
		}
		if (empty($ref_fields) || !is_array($ref_fields)) {
			$error = 'referenced fields are empty';
			return false;
		}
		if (empty($index_name)) {
			$index_name = $ref_table.'_'.implode('_', $ref_fields);
		}
		$on_delete = isset($extra['on_delete']) ? $extra['on_delete'] : '';
		$on_update = isset($extra['on_update']) ? $extra['on_update'] : '';

		$sql = 'ALTER TABLE '.$this->_escape_table_name($this->db->_fix_table_name($table)).PHP_EOL
			. ' ADD CONSTRAINT '.$this->_escape_key($index_name).PHP_EOL
			. ' FOREIGN KEY ('.implode(',', $this->_escape_fields($fields)).')'.PHP_EOL
			. ' REFERENCES '.$this->_escape_key($this->db->_fix_table_name($ref_table)).' ('.implode(',', $this->_escape_fields($ref_fields)).')'.PHP_EOL
			. ($on_delete ? ' ON DELETE '.strtoupper(str_replace('_', ' ', $on_delete)) : '').PHP_EOL
			. ($on_update ? ' ON UPDATE '.strtoupper(str_replace('_', ' ', $on_update)) : '')
		;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Alias
	*/
	public function alter_foreign_key($table, $index_name = '', $fields = array(), $ref_table = '', $ref_fields = array(), $extra = array(), &$error = false) {
		return $this->update_foreign_key($table, $index_name, $fields, $ref_table, $ref_fields, $extra, $error);
	}

	/**
	*/
	public function update_foreign_key($table, $index_name = '', $fields = array(), $ref_table = '', $ref_fields = array(), $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (is_array($index_name)) {
			$extra = (array)$extra + $index_name;
			$index_name = '';
		}
		$table = $extra['table'] ?: $table;
		$index_name = $extra['name'] ?: $index_name;
		if ($this->drop_foreign_key($table, $index_name, $extra, $error)) {
			return $this->add_foreign_key($table, $index_name, $fields, $ref_table, $ref_fields, $extra, $error);
		}
		return false;
	}

	/**
	*/
	public function list_views($db_name = '', $extra = array(), &$error = false) {
		if (is_array($db_name)) {
			$extra = (array)$extra + $db_name;
			$db_name = '';
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema = '.$this->_escape_val($db_name). ' AND table_type = "VIEW"';
		$views = array();
		foreach ((array)$this->db->get_all($sql) as $a) {
			$name = $a['table_name'];
			$create_view = '';
			if (!$extra['no_details']) {
				$create_view = $this->db->get('SHOW CREATE VIEW '.$this->_escape_table_name($db_name.'.'.$name));
				if (is_array($create_view)) {
					$create_view = $create_view['Create View'];
				}
			}
			$views[$name] = $create_view;
		}
		return $views;
	}

	/**
	*/
	public function view_exists($table, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
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
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'view name is empty';
			return false;
		}
		$sql = 'DROP VIEW IF EXISTS '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* See https://dev.mysql.com/doc/refman/5.6/en/create-view.html
	*/
	public function create_view($table, $sql_as, $extra = array(), &$error = false) {
		if (is_array($table)) {
			$extra = (array)$extra + $table;
			$table = '';
		}
		if (!strlen($table)) {
			$error = 'table is empty';
			return false;
		}
		$sql = 'CREATE VIEW '.$this->_escape_table_name($table).' AS '.$sql_as;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function list_triggers($db_name = '', $extra = array(), &$error = false) {
		if (is_array($db_name)) {
			$extra = (array)$extra + $db_name;
			$db_name = '';
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$triggers = array();
		foreach ((array)$this->db->get_all('SHOW TRIGGERS FROM '.$this->_escape_database_name($db_name)) as $a) {
			$name = $a['Trigger'];
			$triggers[$name] = array(
				'name'		=> $name,
				'table'		=> $a['Table'],
				'event'		=> $a['Event'],
				'timing'	=> $a['Timing'],
				'statement'	=> $a['Statement'],
				'definer'	=> $a['definer'],
			);
		}
		return $triggers;
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
		$sql = 'DROP TRIGGER IF EXISTS '.$this->_escape_table_name($name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* See http://dev.mysql.com/doc/refman/5.6/en/create-trigger.html
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
		$sql = 'CREATE TRIGGER '.$this->_escape_table_name($db_name.'.'.$name). PHP_EOL
			. ' '.strtoupper($trigger_time). ' '.strtoupper($trigger_event). PHP_EOL
			. ' ON '.$this->_escape_table_name($db_name.'.'.$table).' FOR EACH ROW '
			. $trigger_body;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Use db installer repository to get table structure
	*/
	public function _get_table_structure_from_db_installer($table, &$error = false) {
// TODO: move this code into db installer class
		if (strlen($this->db->DB_PREFIX) && substr($table, 0, strlen($this->db->DB_PREFIX)) == $this->db->DB_PREFIX) {
			$search_table = substr($table, strlen($this->db->DB_PREFIX));
		} else {
			$search_table = $table;
		}
		$ext = '.sql_php.php';
		$dir = 'share/db/sql_php/';
		$globs = array(
			PROJECT_PATH. 'plugins/*/'. $dir. $search_table. $ext,
			PROJECT_PATH. $dir. $search_table. $ext,
			CONFIG_PATH. $dir. $search_table. $ext,
			YF_PATH. 'plugins/*/'. $dir. $search_table. $ext,
			YF_PATH. $dir. $search_table. $ext,
		);
		$path = '';
		foreach ($globs as $glob) {
			foreach (glob($glob) as $f) {
				$path = $f;
				break 2;
			}
		}
		if (!file_exists($path)) {
			$error = 'file not exists: '.$path;
			return false;
		}
		$data = include $path;
		return $data;
	}

	/**
	* See http://dev.mysql.com/doc/refman/5.6/en/create-table.html
	*/
	public function _parse_column_type($str, &$error = false) {
// TODO: use db ddl parser if available for the given db family (mysql currently supported)
		$str = trim($str);
		$type = $length = $decimals = $values = null;
		if (preg_match('~^(?P<type>[a-z]+)[\s\t]*\((?P<length>[^\)]+)\)~i', $str, $m)) {
			$type = $m['type'];
			$length = $m['length'];
		} elseif (preg_match('~^(?P<type>[a-z]+)~i', $str, $m)) {
			$type = $m['type'];
		}
		$types = $this->_get_supported_field_types();
		if ($types) {
			$types = array_combine($types, $types);
		}
		if ($type) {
			$type = strtolower($type);
			foreach ((array)$types as $_type) {
				if (false !== strpos($type, $_type)) {
					$type = $_type;
					break;
				}
			}
		}
		if ($length && !is_numeric($length) && false !== strpos($length, ',')) {
			if (in_array($type, array('real','double','float','decimal','numeric'))) {
				list($length, $decimals) = explode(',',$length);
				$length = (int)trim($length);
				$decimals = (int)trim($decimals);
			} elseif (in_array($type, array('enum','set'))) {
				$values = array();
				foreach(explode(',', $length) as $v) {
					$v = trim(trim(trim($v),'\'"'));
					if (strlen($v)) {
						$values[$v] = $v;
					}
				}
				$length = '';
			}
		}
		return array(
			'type'		=> $type,
			'length'	=> $length,
			'unsigned'	=> false !== strpos(strtolower($str), 'unsigned') && in_array($type, $this->_get_unsigned_field_types()) ? true : false,
			'decimals'	=> $decimals,
			'values'	=> $values,
		);
	}

	/**
	* Create part of SQL for "CREATE TABLE" from array of params
	*/
/*
	public function _compile_create_table($data, $extra = array(), &$error = false) {
// TODO: use db ddl parser if available for the given db family (mysql currently supported)
		if (!is_array($data) || !count($data)) {
			return false;
		}
		// 1-dimensional array detected, convert it into 2-dimensional
		if (isset($data['name']) && is_string($data['name'])) {
			$data = array($data);
		}
		$items = array();
		foreach ((array)$data as $v) {
			$name = $v['name'];
			if (!$v['key'] && !$name && !$extra['no_name']) {
				continue;
			}
			$type = strtolower($v['type']);
			if (!isset($v['key']) && !in_array($type, $this->_get_supported_field_types())) {
				continue;
			}
			$unsigned = $v['unsigned'];
			if (!isset($v['key']) && $unsigned && !in_array($type, $this->_get_unsigned_field_types())) {
				$unsigned = false;
			}
			$length = $v['length'];
			$default = $v['default'];
			$null = null;
			if (isset($v['nullable'])) {
				$null = (bool)$v['nullable'];
			} elseif (isset($v['null'])) {
				$null = (bool)$v['null'];
			} elseif (isset($v['not_null'])) {
				$null = (bool)(!$v['not_null']);
			}
			$auto_inc = $v['auto_inc'] || $v['auto_increment'];
			if ($auto_inc && $type != 'int') {
				$auto_inc = false;
			}
			if ($auto_inc) {
				$null = false;
				$unsigned = true;
			}
			$comment = $v['comment'];
			if (isset($v['key'])) {
				$items[] = strtoupper($v['key']).' KEY '.($name ? $this->_escape_key($name).' ' : '').'('.(is_array($v['key_cols']) ? implode(',', $v['key_cols']) : $v['key_cols']).')';
			} else {
				$items[$name] = (!$extra['no_name'] ? $this->_escape_key($name).' ' : '')
					.strtoupper($type)
					. ($length ? '('.$length.')' : '')
					. (isset($unsigned) ? ' UNSIGNED' : '')
					. (isset($null) ? ' '.($null ? 'NULL' : 'NOT NULL') : '')
					. (isset($default) ? ' DEFAULT \''.addslashes($default).'\'' : '')
					. ($auto_inc ? ' AUTO_INCREMENT' : '')
					. (strlen($comment) ? ' COMMENT \''.addslashes($comment).'\'' : '')
				;
			}
		}
		return implode(','.PHP_EOL, $items);
	}
*/

	/**
	* Smart split long SQL into single queries. Usually to be able to execute them with php_mysql API functions
	*/
	public function split_sql($sql) {
		$out = array();
		// do not trim
		$sql = rtrim($sql, "\n\r");
		$sql_len = strlen($sql);
		$char = '';
		$string_start = '';
		$in_string = FALSE;
		$nothing = TRUE;
		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];
			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$out[] = $sql;
						break 2;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					else if ($string_start == '`' || $sql[$i-1] != "\\") {
						$string_start = '';
						$in_string = FALSE;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j = 2;
						$escaped_backslash = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == "\\") {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start = '';
							$in_string = FALSE;
							break;
						}
						// ... else loop
						else {
							$i++;
						}
					}
				}
			}
			// lets skip comments (/*, -- and #)
			else if (
				($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ')
				 || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')
			) {
				$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
				// didn't we hit end of string?
				if ($i === FALSE) {
					break;
				}
				if ($char == '/') {
					$i++;
				}
			}
			// We are not in a string, first check for delimiter...
			else if ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$out[] = substr($sql, 0, $i);
				$nothing = TRUE;
				$sql = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len = strlen($sql);
				if ($sql_len) {
					$i = -1;
				} else {
					// The submited statement(s) end(s) here
					break;
				}
			}
			// ... then check for start of a string,...
			else if (($char == '"') || ($char == '\'') || ($char == '`')) {
				$in_string = TRUE;
				$nothing = FALSE;
				$string_start = $char;
			} elseif ($nothing) {
				$nothing = FALSE;
			}
		}
		// add any rest to the returned array
		if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$out[] = $sql;
		}
		foreach ((array)$out as $k => $v) {
			if (!strlen($v)) {
				unset($out[$k]);
			}
		}
		return array_values($out); // array_values needed here to make array indexes flat and properly incremented again
	}

	/**
	*/
	public function _escape_database_name($name = '') {
		$name = str_replace(array('\'', '"', '`'), '', trim($name));
		if (!strlen($name)) {
			return false;
		}
		return is_object($this->db) ? $this->db->escape_key($name) : '`'.addslashes($name).'`';
	}

	/**
	*/
	public function _escape_table_name($name = '') {
		$name = str_replace(array('\'', '"', '`'), '', trim($name));
		if (!strlen($name)) {
			return false;
		}
		$db = '';
		$table = '';
		if (strpos($name, '.') !== false) {
			list($db, $table) = explode('.', $name);
			$db = trim($db);
			$table = trim($table);
		} else {
			$table = $name;
		}
		if (!strlen($table)) {
			return false;
		}
		$table = $this->db->_fix_table_name($table);
		return (strlen($db) ? $this->_escape_database_name($db).'.' : ''). (is_object($this->db) ? $this->db->escape_key($table) : '`'.addslashes($table).'`');
	}

	/**
	*/
	public function _escape_key($key = '') {
		$key = trim(trim($key), '`');
		if (!strlen($key)) {
			return '';
		}
		$out = '';
		if ($key != '*' && false === strpos($key, '.') && false === strpos($key, '(')) {
			$out = is_object($this->db) ? $this->db->escape_key($key) : '`'.addslashes($key).'`';
		} else {
			// split by "." and escape each value
			if (false !== strpos($key, '.') && false === strpos($key, '(') && false === strpos($key, ' ')) {
				$tmp = array();
				foreach (explode('.', $key) as $v) {
					$tmp[] = is_object($this->db) ? $this->db->escape_key($v) : '`'.addslashes($v).'`';
				}
				$out = implode('.', $tmp);
			} else {
				$out = $key;
			}
		}
		return $out;
	}

	/**
	*/
	public function _escape_val($val = '') {
		if (is_null($val) || $val === 'NULL') {
			return 'NULL';
		}
		$val = trim(trim($val), '\'');
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
		if (is_null($val) || $val === 'NULL') {
			return 'NULL';
		}
		$val = trim($val);
		if (!strlen($val)) {
			return '';
		}
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) && method_exists($this->db, '_es') ? $this->db->_es($val) : addslashes($val);
	}

	/**
	* db()->utils()->database('geonames')->create();
	* db()->utils()->database('geonames')->drop();
	* db()->utils()->database('geonames')->alter($params);
	* db()->utils()->database('geonames')->rename($new_name);
	*/
	public function database($db_name) {
		$obj = clone _class('db_utils_helper_database', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->create();
	* db()->utils()->database('geonames')->table('geo_city')->drop();
	* db()->utils()->database('geonames')->table('geo_city')->alter($params);
	* db()->utils()->database('geonames')->table('geo_city')->rename($new_name);
	* db()->utils()->table('geonames', 'geo_city')->rename($new_name);
	*/
	public function table($db_name, $table) {
		$obj = clone _class('db_utils_helper_table', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
			'table'		=> $table,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->view('geo_city_view')->create('SQL HERE');
	* db()->utils()->view('geonames', 'geo_city_view')->create('SQL HERE');
	*/
	public function view($db_name, $view) {
		$obj = clone _class('db_utils_helper_view', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
			'view'		=> $view,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->add();
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->drop();
	* db()->utils()->table('geonames', 'geo_city')->column('name')->drop();
	* db()->utils()->column('geonames', 'geo_city', 'name')->drop();
	*/
	public function column($db_name, $table, $col) {
		$obj = clone _class('db_utils_helper_column', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'col'		=> $col,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->index('name', ('id', 'name'))->add();
	* db()->utils()->database('geonames')->table('geo_city')->index('name')->drop();
	* db()->utils()->index('geonames', 'geo_city', 'name')->drop();
	*/
	public function index($db_name, $table, $index) {
		$obj = clone _class('db_utils_helper_index', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'index'		=> $index,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->foreign_key('geo_city_fk')->create($params);
	* db()->utils()->foreign_key('geonames', 'geo_city', 'geo_city_fk')->create($params);
	*/
	public function foreign_key($db_name, $table, $fk_name) {
		$obj = clone _class('db_utils_helper_foreign_key', 'classes/db/');
		$obj->_setup(array(
			'utils'		=> $this,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'foreign_key'=> $fk_name,
		));
		return $obj;
	}

	/**
	*/
	function meta_columns($table) {
		return $this->list_columns($table);
	}

	/**
	*/
	function meta_tables($db_prefix = '') {
		$tables = $this->list_tables();
		// Skip tables without prefix of current connection
		if (strlen($db_prefix)) {
			$plen = strlen($db_prefix);
			foreach ($tables as $table) {
				if (substr($table, 0, $plen) !== $db_prefix) {
					unset($tables[$table]);
				}
			}
		}
		return $tables;
	}
}
