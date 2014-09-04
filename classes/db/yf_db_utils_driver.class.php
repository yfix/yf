<?php

// TODO: implement migrations like in ROR, based on these methods

/**
* YF db utils driver abstract class
*/
abstract class yf_db_utils_driver {

	/**
	*/
	function _get_supported_field_types() {
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
	function _get_unsigned_field_types() {
		return array(
			'bit','int','real','double','float','decimal','numeric'
		);
	}

	/**
	*/
	function _get_supported_table_options() {
		return array(
			'engine'	=> 'ENGINE',
			'charset'	=> 'DEFAULT CHARSET',
		);
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	*/
	function list_collations($extra = array()) {
		return $this->db->get_all('SHOW COLLATION');
	}

	/**
	*/
	function list_charsets($extra = array()) {
		return $this->db->get_all('SHOW CHARACTER SET');
	}

	/**
	*/
	function list_databases($extra = array()) {
		$sql = 'SHOW DATABASES';
		return $extra['sql'] ? $sql : $this->db->get_2d($sql);
	}

	/**
	*/
	function database_exists($db_name, $extra = array(), &$error = false) {
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		return (bool)in_array($db_name, (array)$this->list_databases());
	}

	/**
	*/
	function database_info($db_name = '', $extra = array(), &$error = false) {
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
			'collation'	=> $info['DEFAULT_COLLATION_NAME'],
		);
	}

	/**
	*/
	function create_database($db_name, $extra = array(), &$error = false) {
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
	function drop_database($db_name, $extra = array(), &$error = false) {
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
	function alter_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$allowed = array(
			'charset'	=> 'CHARACTER SET',
			'collation'	=> 'COLLATE',
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
	function rename_database($db_name, $new_name, $extra = array(), &$error = false) {
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
	function truncate_database($db_name, $extra = array(), &$error = false) {
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
	function list_tables($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
		$tables = $this->db->get_2d('SHOW TABLES'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name) : ''));
		return $tables ? array_combine($tables, $tables) : array();
	}

	/**
	*/
	function list_tables_details($db_name = '', $extra = array(), &$error = false) {
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
				'collation'	=> $a['Collation'],
			);
		}
		return $tables;
	}

	/**
	*/
	function table_exists($table, $extra = array(), &$error = false) {
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
	function table_get_columns($table, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$extra['sql'] && !$this->table_exists($table)) {
			$error = 'table_name not exists';
			return false;
		}
		$cols = array();
		$q = $this->db->query('SHOW FULL COLUMNS FROM '.$this->_escape_table_name($table));
		while ($a = $this->db->fetch_assoc($q)) {
			$name = $a['Field'];
			list($type, $length, $unsigned) = array_values($this->_parse_column_type($a['Type']));
			$cols[$name] = array(
				'name'		=> $name,
				'type'		=> $type,
				'length'	=> $length,
				'unsigned'	=> $unsigned,
				'collation'	=> $a['Collation'] != 'NULL' ? $a['Collation'] : null,
				'null'		=> $a['Null'] == 'NO' ? false : true,
				'default'	=> $a['Default'] != 'NULL' ? $a['Default'] : null,
				'auto_inc'	=> false !== strpos($a['Extra'], 'auto_increment') ? true : false,
				'is_primary'=> $a['Key'] == 'PRI',
				'is_unique'	=> $a['Key'] == 'UNI',
				'type_raw'	=> $a['Type'],
			);
		}
		return $cols;
	}

	/**
	*/
	function table_get_charset($table, $extra = array(), &$error = false) {
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
	function table_info($table, $extra = array(), &$error = false) {
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
			'columns'		=> $this->table_get_columns($orig_table),
			'row_format'	=> $info['Row_format'],
			'charset'		=> $this->table_get_charset($orig_table),
			'collation'		=> $info['Collation'],
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
	function create_table($table, $data = array(), $extra = array(), &$error = false) {
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
		// Default table options
		$extra['engine'] = $extra['engine'] ?: 'InnoDB';
		$extra['charset'] = $extra['charset'] ?: 'utf8';

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
	function drop_table($table, $extra = array(), &$error = false) {
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
	function alter_table($table, $extra = array(), &$error = false) {
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
	function rename_table($table, $new_name, $extra = array(), &$error = false) {
		if (!$table || !$new_name) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'RENAME TABLE '.$this->_escape_table_name($table).' TO '.$this->_escape_table_name($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function truncate_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'TRUNCATE TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function check_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'CHECK TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function optimize_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'OPTIMIZE TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function repair_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'REPAIR TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function list_columns($table, $extra = array(), &$error = false) {
		return $this->table_get_columns($table, $extra, $error);
	}

	/**
	*/
	function column_exists($table, $col_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]);
	}

	/**
	*/
	function column_info($table, $col_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name]) ? $columns[$col_name] : false;
	}

	/**
	*/
	function column_info_item($table, $col_name, $item_name, $extra = array(), &$error = false) {
		$columns = $this->table_get_columns($table, $extra, $error);
		return isset($columns[$col_name][$item_name]) ? $columns[$col_name][$item_name] : false;
	}

	/**
	*/
	function drop_column($table, $col_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP COLUMN '.$this->db->escape_key($col_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function add_column($table, $data, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' ADD COLUMN '.$this->_compile_create_table($data);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function rename_column($table, $col_name, $new_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$col_info_str = $this->_compile_create_table($this->column_info($table, $col_name), array('no_name' => true));
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' CHANGE COLUMN '.$this->_escape_key($col_name).' '.$this->_escape_key($new_name).' '.$col_info_str;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function alter_column($table, $col_name, $data, $extra = array(), &$error = false) {
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
		$col_info_str = $this->_compile_create_table($col_info, array('no_name' => true));
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' MODIFY COLUMN '.$this->_escape_key($col_name).' '.$col_info_str. $position_change;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Slow method, but returning all info about indexes for selected database at once.
	* Useful for analytics and getting overall picture.
	*/
	function list_all_database_indexes($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = 
			'SELECT a.table_schema,
				a.table_name,
				a.constraint_name, 
				a.constraint_type,
				CONVERT(GROUP_CONCAT(DISTINCT b.column_name ORDER BY b.ordinal_position SEPARATOR ", "), char) as column_list,
				b.referenced_table_name,
				b.referenced_column_name
			FROM information_schema.table_constraints a
			INNER JOIN information_schema.key_column_usage b ON a.constraint_name = b.constraint_name AND a.table_schema = b.table_schema AND a.table_name = b.table_name
			WHERE a.table_schema = '.$this->_escape_val($db_name).'
			GROUP BY a.table_schema, a.table_name, a.constraint_name, 
				a.constraint_type, b.referenced_table_name, 
				b.referenced_column_name
			UNION
			SELECT table_schema,
				table_name,
				index_name as constraint_name,
				if(index_type="FULLTEXT", "FULLTEXT", "NON UNIQUE") as constraint_type,
				CONVERT(GROUP_CONCAT(column_name ORDER BY seq_in_index separator ", "), char) as column_list,
				null as referenced_table_name,
				null as referenced_column_name
			FROM information_schema.statistics
			WHERE non_unique = 1 AND table_schema = '.$this->_escape_val($db_name).'
			GROUP BY table_schema, table_name, constraint_name, constraint_type, referenced_table_name, referenced_column_name
			ORDER BY table_schema, table_name, constraint_name'
		;
		$indexes = array();
		foreach ((array)$this->db->get_all($sql) as $a) {
			$table = $a['table_name'];
			$name = $a['constraint_name'];
			$type = 'key';
			if ($a['constraint_type'] === 'PRIMARY KEY') {
				$type = 'primary';
			} elseif ($a['constraint_type'] === 'UNIQUE') {
				$type = 'unique';
			} elseif ($a['constraint_type'] == 'FULLTEXT') {
				$type = 'fulltext';
			}
			$indexes[$table][$name] = array(
				'name'		=> $name,
				'type'		=> $type,
				'columns'	=> explode(', ', $a['column_list']),
			);
		}
		return $indexes;
	}

	/**
	*/
	function list_indexes($table, $extra = array(), &$error = false) {
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
			$type = 'key';
			if ($row['Key_name'] === 'PRIMARY') {
				$type = 'primary';
			} elseif (!$row['Non_unique']) {
				$type = 'unique';
			} elseif ($row['Index_type'] == 'FULLTEXT') {
				$type = 'fulltext';
			}
			$indexes[$row['Key_name']] = array(
				'name'		=> $row['Key_name'],
				'type'		=> $type,
			);
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		return $indexes;
	}

	/**
	*/
	function index_info($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$indexes = $this->list_indexes($table);
		return isset($indexes[$index_name]) ? $indexes[$index_name] : false;
	}

	/**
	*/
	function index_exists($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$indexes = $this->list_indexes($table);
		return isset($indexes[$index_name]);
	}

	/**
	*/
	function add_index($table, $index_name = '', $fields = array(), $extra = array(), &$error = false) {
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
	function drop_index($table, $index_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
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
	*/
	function update_index($table, $index_name, $fields = array(), $extra = array(), &$error = false) {
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
	function list_foreign_keys($table, $extra = array(), &$error = false) {
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
		$sql = 'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = '.$this->_escape_val($db_name).' 
				AND REFERENCED_TABLE_NAME IS NOT NULL 
				AND TABLE_NAME = '. $this->_escape_val($this->db->_fix_table_name($table));
		foreach ((array)$this->db->get_all($sql) as $id => $row) {
			$keys[$row['CONSTRAINT_NAME']] = array(
				'name'		=> $row['CONSTRAINT_NAME'], // foreign key name
				'local'		=> $row['COLUMN_NAME'], // local columns
				'table'		=> $row['REFERENCED_TABLE_NAME'], // referenced table
				'foreign' 	=> $row['REFERENCED_COLUMN_NAME'], // referenced columns
			);
		}
		return $keys;
	}

	/**
	*/
	function foreign_key_info($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$keys = $this->list_foreign_keys($table);
		return isset($keys[$index_name]) ? $keys[$index_name] : false;
	}

	/**
	*/
	function foreign_key_exists($table, $index_name, &$error = false) {
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$keys = $this->list_foreign_keys($table);
		return isset($keys[$index_name]);
	}

	/**
	*/
	function drop_foreign_key($table, $index_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP FOREIGN KEY '.$this->_escape_key($index_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function add_foreign_key($table, $index_name = '', array $fields, $ref_table, array $ref_fields, $extra = array(), &$error = false) {
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

		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).PHP_EOL
			. ' ADD CONSTRAINT '.$this->_escape_key($index_name).PHP_EOL
			. ' FOREIGN KEY ('.implode(',', $this->_escape_fields($fields)).')'.PHP_EOL
			. ' REFERENCES '.$this->_escape_key($ref_table).' ('.implode(',', $this->_escape_fields($ref_fields)).')'.PHP_EOL
			. ($on_delete ? ' ON DELETE '.$on_delete : '').PHP_EOL
			. ($on_update ? ' ON UPDATE '.$on_update : '')
		;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function update_foreign_key($table, $index_name, array $fields, $ref_table, array $ref_fields, $extra = array(), &$error = false) {
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
	function list_views($db_name = '', $extra = array(), &$error = false) {
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
	function view_exists($table, $extra = array(), &$error = false) {
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
	function view_info($table, $extra = array(), &$error = false) {
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
	function drop_view($table, $extra = array(), &$error = false) {
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
	function create_view($table, $sql_as, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table is empty';
			return false;
		}
		$sql = 'CREATE VIEW '.$this->_escape_table_name($table).' AS '.$sql_as;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Note: The 'SHOW PROCEDURE|FUNCTION CODE' feature is disabled; you need MySQL built with '--with-debug' to have it working (code:1289)
	*/
	function list_procedures($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$data = array();
		foreach ((array)$this->db->get_all('SHOW PROCEDURE STATUS') as $a) {
			$_a = array();
			foreach ((array)$a as $k => $v) {
				$_a[strtolower($k)] = $v;
			}
			$a = $_a;
			$name = $a['name'];
			$data[$name] = $a;
		}
		return $data;
	}

	/**
	*/
	function procedure_exists($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'procedure name is empty';
			return false;
		}
		$procedures = $this->list_procedures($extra, $error);
		return (bool)isset($procedures[$name]);
	}

	/**
	*/
	function procedure_info($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'procedure name is empty';
			return false;
		}
		$procedures = $this->list_procedures($extra, $error);
		return isset($procedures[$name]) ? $procedures[$name] : false;
	}

	/**
	*/
	function drop_procedure($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP PROCEDURE IF EXISTS '.$this->_escape_key($name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* See https://dev.mysql.com/doc/refman/5.6/en/create-procedure.html
	*/
	function create_procedure($name, $sql_body, $sql_params = '', $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'CREATE PROCEDURE '.$this->_escape_key($name).' ('.$sql_params.')'. PHP_EOL
			. 'BEGIN'. PHP_EOL. $sql_body. PHP_EOL. 'END'
		;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Note: // The 'SHOW PROCEDURE|FUNCTION CODE' feature is disabled; you need MySQL built with '--with-debug' to have it working (code:1289)
	*/
	function list_functions($extra = array(), &$error = false) {
		$data = array();
		foreach ((array)$this->db->get_all('SHOW FUNCTION STATUS') as $a) {
			$_a = array();
			foreach ((array)$a as $k => $v) {
				$_a[strtolower($k)] = $v;
			}
			$a = $_a;
			$name = $a['name'];
			$data[$name] = $a;
		}
		return $data;
	}

	/**
	*/
	function function_exists($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'function name is empty';
			return false;
		}
		$funcs = $this->list_functions();
		return (bool)isset($funcs[$name]);
	}

	/**
	*/
	function function_info($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'function name is empty';
			return false;
		}
		$funcs = $this->list_functions();
		return isset($funcs[$name]) ? $funcs[$name] : false;
	}

	/**
	*/
	function drop_function($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = 'DROP FUNCTION IF EXISTS '.$this->_escape_table_name($db_name.'.'.$name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* See https://dev.mysql.com/doc/refman/5.6/en/create-function.html
	*/
	function create_function($name, $sql_body, $sql_returns_type, $sql_params = '', $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$sql = ' CREATE FUNCTION '.$this->_escape_table_name($db_name.'.'.$name).' ('.$sql_params.')'. PHP_EOL
			. 'RETURNS '.$sql_returns_type.' DETERMINISTIC'. PHP_EOL
			. 'RETURN '.$sql_body;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function list_triggers($db_name = '', $extra = array(), &$error = false) {
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
	function trigger_exists($name, $extra = array(), &$error = false) {
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
	function trigger_info($name, $extra = array(), &$error = false) {
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
	function drop_trigger($name, $extra = array(), &$error = false) {
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
	function create_trigger($name, $table, $trigger_time, $trigger_event, $trigger_body, $extra = array(), &$error = false) {
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
	*/
	function list_events($db_name = '', $extra = array(), &$error = false) {
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$events = array();
		foreach ((array)$this->db->get_all('SHOW EVENTS FROM '.$this->_escape_database_name($db_name)) as $a) {
			$name = $a['Name'];
			$events[$name] = array(
				'name'			=> $name,
				'db'			=> $a['Db'],
				'definer'		=> $a['definer'],
				'timezone'		=> $a['Time_zone'],
				'type'			=> $a['Type'],
				'execute_at'	=> $a['Execute_at'],
				'interval_value'=> $a['Interval_value'],
				'interval_field'=> $a['Interval_field'],
				'starts'		=> $a['Starts'],
				'ends'			=> $a['Ends'],
				'status'		=> $a['Status'],
				'originator'	=> $a['Originator'],
			);
		}
		return $events;
	}

	/**
	*/
	function event_exists($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'event name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$events = $this->list_events($db_name, $extra, $error);
		return (bool)isset($events[$name]);
	}

	/**
	*/
	function event_info($name, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!$name) {
			$error = 'event name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		$events = $this->list_events($db_name, $extra, $error);
		return isset($events[$name]) ? $events[$name] : false;
	}

	/**
	*/
	function drop_event($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'event name is empty';
			return false;
		}
		$sql = 'DROP EVENT IF EXISTS '.$this->_escape_table_name($name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* See: https://dev.mysql.com/doc/refman/5.6/en/create-event.html
	* Example: CREATE EVENT e_totals  ON SCHEDULE AT '2006-02-10 23:59:00'  DO INSERT INTO test.totals VALUES (NOW());
	*/
	function create_event($name, $event_shedule, $event_body, $extra = array(), &$error = false) {
		if (strpos($name, '.') !== false) {
			list($db_name, $name) = explode('.', trim($name));
		}
		if (!strlen($name)) {
			$error = 'event name is empty';
			return false;
		}
		if (!$db_name) {
			$db_name = $this->db->DB_NAME;
		}
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		if (!strlen($event_shedule)) {
			$error = 'event shedule is empty';
			return false;
		}
		if (!strlen($event_body)) {
			$error = 'event body is empty';
			return false;
		}
		$supported_event_intervals = array(
			'YEAR', 'QUARTER', 'MONTH', 'DAY', 'HOUR', 'MINUTE', 
			'WEEK', 'SECOND', 'YEAR_MONTH', 'DAY_HOUR', 'DAY_MINUTE', 'DAY_SECOND', 
			'HOUR_MINUTE', 'HOUR_SECOND', 'MINUTE_SECOND',
		);
// TODO: implement strict shedule contents checks
		$sql = 'CREATE EVENT IF NOT EXISTS '.$this->_escape_table_name($db_name.'.'.$name).' '. PHP_EOL
			. 'ON SCHEDULE '.$event_shedule. PHP_EOL
			. 'DO '.$event_body;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function list_users($extra = array(), &$error = false) {
		$users = array();
		foreach ((array)$this->db->get_all('SELECT * FROM mysql.user') as $a) {
			$user = array();
			foreach ((array)$a as $k => $v) {
				$user[strtolower($k)] = $v;
			}
			$name = $user['user'].'@'.$user['host'];
			$users[$name] = $user;
		}
		return $users;
	}

	/**
	*/
	function user_exists($name, $extra = array(), &$error = false) {
		$users = $this->list_users($extra, $error);
		return (bool)isset($users[$name]);
	}

	/**
	*/
	function user_info($name, $extra = array(), &$error = false) {
		$users = $this->list_users($extra, $error);
		return isset($users[$name]) ? $users[$name] : false;
	}

	/**
	*/
	function delete_user($name, $extra = array(), &$error = false) {
		list($host, $user) = explode('@', $name);
		$sql = 'DELETE FROM mysql.user WHERE host='.$this->_escape_val($host).' AND user='.$this->_escape_val($user);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function add_user($name, array $data, $extra = array(), &$error = false) {
		list($host, $user) = explode('@', $name);
// TODO: allow add only password in addition to host and user
#		return $this->db->insert('mysql.user WHERE user='.$this->_escape_val($name));
		if (!strlen($host) || !strlen($name) || !strlen($data['pswd'])) {
			$error = 'Missing required params';
			return false;
		}
		$sql = '';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function update_user($name, array $data, $extra = array(), &$error = false) {
		list($host, $user) = explode('@', $name);
// TODO: allow update only password
#		return $this->db->update('mysql.user WHERE user='.$this->_escape_val($name));
		if (!strlen($host) || !strlen($name) || !strlen($data['pswd'])) {
			$error = 'Missing required params';
			return false;
		}
		$sql = '';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	* Use db installer repository to get table structure
	*/
	function _get_table_structure_from_db_installer($table, &$error = false) {
		if (strlen($this->db->DB_PREFIX) && substr($table, 0, strlen($this->db->DB_PREFIX)) == $this->db->DB_PREFIX) {
			$search_table = substr($table, strlen($this->db->DB_PREFIX));
		} else {
			$search_table = $table;
		}
		$globs = array(
			PROJECT_PATH. 'plugins/*/share/db_installer/sql/'.$search_table.'.sql.php',
			PROJECT_PATH. 'share/db_installer/sql/'.$search_table.'.sql.php',
			CONFIG_PATH. 'share/db_installer/sql/'.$search_table.'.sql.php',
			YF_PATH. 'plugins/*/share/db_installer/sql/'.$search_table.'.sql.php',
			YF_PATH. 'share/db_installer/sql/'.$search_table.'.sql.php',
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
		include $path;
		return $data;
	}

	/**
	* See http://dev.mysql.com/doc/refman/5.6/en/create-table.html
	*/
	function _parse_column_type($str, &$error = false) {
		$str = trim($str);
		$type = $length = $decimals = $values = null;
		if (preg_match('~^(?P<type>[a-z]+)[\s\t]*\((?P<length>[^\)]+)\)~i', $str, $m)) {
			$type = $m['type'];
			$length = $m['length'];
		} elseif (preg_match('~^(?P<type>[a-z]+)~i', $str, $m)) {
			$type = $m['type'];
		}
		$types = $this->_get_supported_field_types();
		$types = array_combine($types, $types);
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
	function _compile_create_table($data, $extra = array(), &$error = false) {
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
			if (isset($v['null'])) {
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

	/**
	* Smart split long SQL into single queries. Usually to be able to execute them with php_mysql API functions
	*/
	function split_sql(&$ret, $sql) {
		// do not trim
		$sql			= rtrim($sql, "\n\r");
		$sql_len		= strlen($sql);
		$char			= '';
		$string_start	= '';
		$in_string		= FALSE;
		$nothing	 	= TRUE;
		$time0			= time();
		$is_headers_sent = headers_sent();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];
			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i		 = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$ret[] = array('query' => $sql, 'empty' => $nothing);
						return TRUE;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					else if ($string_start == '`' || $sql[$i-1] != "\\") {
						$string_start	  = '';
						$in_string		 = FALSE;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j					 = 2;
						$escaped_backslash	 = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == "\\") {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string	 = FALSE;
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
			else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
				$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
				// didn't we hit end of string?
				if ($i === FALSE) {
					break;
				}
				if ($char == '/') $i++;
			}
			// We are not in a string, first check for delimiter...
			else if ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$ret[]	  = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
				$nothing	= TRUE;
				$sql		= ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len	= strlen($sql);
				if ($sql_len) {
					$i	  = -1;
				} else {
					// The submited statement(s) end(s) here
					return TRUE;
				}
			}
			// ... then check for start of a string,...
			else if (($char == '"') || ($char == '\'') || ($char == '`')) {
				$in_string	= TRUE;
				$nothing	  = FALSE;
				$string_start = $char;
			} elseif ($nothing) {
				$nothing = FALSE;
			}
			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1	 = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
				if (!$is_headers_sent) {
					header('X-YFPing: Pong');
				}
			}
		}
		// add any rest to the returned array
		if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$ret[] = array('query' => $sql, 'empty' => $nothing);
		}
		return TRUE;
	}

	/**
	*/
	function _escape_database_name($name = '') {
		$name = trim($name);
		if (!strlen($name)) {
			return false;
		}
		return is_object($this->db) ? $this->db->escape_key($name) : '`'.addslashes($name).'`';
	}

	/**
	*/
	function _escape_table_name($name = '') {
		$name = trim($name);
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
	function _escape_key($key = '') {
		$key = trim($key);
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
	function _escape_val($val = '') {
		$val = trim($val);
		if (!strlen($val)) {
			return '';
		}
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) ? $this->db->escape_val($val) : '\''.addslashes($val).'\'';
	}

	/**
	*/
	function _escape_fields(array $fields) {
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
	function _es($val = '') {
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
	function database($name) {
		$obj = clone _class('db_utils_helper_database', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->create();
	* db()->utils()->database('geonames')->table('geo_city')->drop();
	* db()->utils()->database('geonames')->table('geo_city')->alter($params);
	* db()->utils()->database('geonames')->table('geo_city')->rename($new_name);
	*/
	function table($db_name, $table) {
		$obj = clone _class('db_utils_helper_table', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
			'table'		=> $table,
		));
		return $obj;
	}

	/**
	*/
	function view($db_name, $view) {
		$obj = clone _class('db_utils_helper_view', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
			'view'		=> $view,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->add();
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->drop();
	*/
	function column($db_name, $table, $col) {
		$obj = clone _class('db_utils_helper_column', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'col'		=> $col,
		));
		return $obj;
	}

	/**
	* db()->utils()->database('geonames')->table('geo_city')->index('name', ('id', 'name'))->add();
	* db()->utils()->database('geonames')->table('geo_city')->index('name')->drop();
	*/
	function index($db_name, $table, $index) {
		$obj = clone _class('db_utils_helper_index', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'index'		=> $index,
		));
		return $obj;
	}

	/**
	*/
	function foreign_key($db_name, $table, $fk_name) {
		$obj = clone _class('db_utils_helper_foreign_key', 'classes/db/');
		$obj->_setup(array(
			'db'		=> $this->db,
			'utils'		=> $this->utils,
			'db_name'	=> $db_name,
			'table'		=> $table,
			'foreign_key'=> $fk_name,
		));
		return $obj;
	}
}
