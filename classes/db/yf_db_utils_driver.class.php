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
	function database_exists($db_name, $extra = array(), &$error = false) {
		if (!$db_name) {
			$error = 'db_name is empty';
			return false;
		}
		return (bool)in_array($db_name, (array)$this->list_databases());
	}

	/**
	*/
	function list_databases($extra = array()) {
		$sql = 'SHOW DATABASES';
		return $extra['sql'] ? $sql : $this->db->get_2d($sql);
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
	function database_info($db_name, $extra = array(), &$error = false) {
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
			foreach ($types as $_type) {
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
		foreach ($data as $v) {
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
		$data = ($extra['sql'] ?: $extra['data']) ?: $data;
		$engine = $extra['engine'] ?: 'InnoDB';
		$charset = $extra['charset'] ?: 'utf8';
// TODO: implement full list of table options like in ALTER TABLE
		$table_options = 'ENGINE='.$engine.' DEFAULT CHARSET='.$charset;
		if (is_array($data)) {
			$data = $this->_compile_create_table($data, $extra, $error);
		}
		if (!$data) {
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
	*/
/*
	function alter_table($table, $params = array(), $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		if (!$extra['sql'] && !$this->table_exists($table)) {
			$error = 'table_name not exists';
			return false;
		}
#		$allowed = array(
#			'charset'	=> 'CHARACTER SET',
#			'collation'	=> 'COLLATE',
#		);
#		$params = array();
// TODO: implement allowed list of params and their shortcuts
// TODO: implement adding columns (with "before" and "after")
// TODO: implement 
#		foreach ((array)$extra as $k => $v) {
#			$params[$k] = $k.' = '.$v;
#		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table). PHP_EOL. implode(' ', $params);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}
*/

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
		foreach ($data as $k => $v) {
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
	*/
	function list_indexes($table, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$indexes = array();
		foreach ((array)$this->db->get_all('SHOW INDEX FROM ' . $this->_escape_table_name($table)) as $row) {
			$indexes[$row['Key_name']] = array(
				'name'		=> $row['Key_name'],
				'unique'	=> !$row['Non_unique'],
				'primary'	=> $row['Key_name'] === 'PRIMARY',
			);
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		return $indexes;
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
	function list_all_indexes($table, $extra = array(), &$error = false) {
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
			WHERE a.table_schema = '.$this->_escape_val($db_name).'.'.$this->_escape_val($this->db->_fix_table_name($table)).'
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
			WHERE non_unique = 1 AND table_schema = '.$this->_escape_val($db_name).'.'.$this->_escape_val($this->db->_fix_table_name($table)).'
			GROUP BY table_schema, table_name, constraint_name, constraint_type, referenced_table_name, referenced_column_name
			ORDER BY table_schema, table_name, constraint_name'
		;
		$indexes = array();
		foreach ((array)$this->db->get_all($sql) as $a) {
			$table = $a['table_name'];
			$name = $a['constraint_name'];
			$indexes[$table][$name] = array(
				'name'		=> $name,
				'unique'	=> $a['constraint_type'] === 'UNIQUE',
				'primary'	=> $a['constraint_type'] === 'PRIMARY KEY',
				'columns'	=> explode(', ', $a['column_list']),
			);
		}
		return $indexes;
	}

	/**
	*/
	function add_index($table, $index_name = '', $fields = array(), $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
// TODO: gemerate index name from columns names
		if (!strlen($index_name)) {
			$error = 'index name is empty';
			return false;
		}
		$index_name = $index_name ?: implode('_', $fields);
		$sql = 'CREATE INDEX '.$index_name.' ON '.$this->_escape_table_name($table).' ('.implode(',', $fields).')';
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
		$sql = 'DROP INDEX '.$index_name.' ON '.$this->_escape_table_name($table);
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
		$this->drop_index($table, $index_name);
		return $this->add_index($table, $index_name, $fields);
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
				AND TABLE_NAME = '. $this->db->_fix_table_name($table);
		foreach ((array)$this->db->get_all($sql) as $id => $row) {
			$keys[$id] = array(
				'name'		=> $row['CONSTRAINT_NAME'], // foreign key name
				'local'		=> $row['COLUMN_NAME'], // local columns
				'table'		=> $row['REFERENCED_TABLE_NAME'], // referenced table
				'foreign' 	=> $row['REFERENCED_COLUMN_NAME'], // referenced columns
			);
		}
		return array_values($keys);
	}

	/**
	*/
	function list_all_foreign_keys($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function add_foreign_key($table, $fields, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
/*
ALTER TABLE tbl_name
	ADD [CONSTRAINT [symbol]] FOREIGN KEY
	[index_name] (index_col_name, ...)
	REFERENCES tbl_name (index_col_name,...)
	[ON DELETE reference_option]
	[ON UPDATE reference_option]
*/
// TODO
	}

	/**
	*/
	function drop_foreign_key($table, $name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->db->_fix_table_name($table).' DROP FOREIGN KEY '.$name;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function foreign_key_exists($table, $name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_views($db_name = '', $extra = array(), &$error = false) {
		/*$this->connection->query("
			SELECT TABLE_NAME as name, TABLE_TYPE = 'VIEW' as view
			FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
		");*/
		$tables = array();
		foreach ((array)$this->db->get_2d('SHOW FULL TABLES') as $name => $type) {
			if ($type != 'VIEW') {
				continue;
			}
			$create_view = !$extra['no_details'] ? $this->db->get('SHOW CREATE VIEW '.$name) : '';
			$tables[$name] = is_array($create_view) ? $create_view['Create View'] : '';
		}
		return $tables;
	}

	/**
	*/
	function create_view($name, $sql_as, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'CREATE VIEW '.$this->db->_fix_table_name($name).' AS '.$sql_as;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function drop_view($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP VIEW '.$this->db->_fix_table_name($name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function view_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_procedures($extra = array(), &$error = false) {
		$data = array();
		foreach ($this->db->get_all('SHOW PROCEDURE STATUS') as $v) {
			$name = $v['Name'];
			$source = $extra['show_code'] ? $this->db->get_all('SHOW PROCEDURE CODE '.$name) : '';
			$data[$name] = $v + array('source' => $source);
		}
		return $data;
	}

	/**
	*/
	function create_procedure($name, $data, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
// https://dev.mysql.com/doc/refman/5.5/en/create-procedure.html
# CREATE PROCEDURE simpleproc (OUT param1 INT)
# BEGIN
#	SELECT COUNT(*) INTO param1 FROM t;
# END//
// TODO
	}

	/**
	*/
	function drop_procedure($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP PROCEDURE '.$name;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function procedure_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_functions($extra = array(), &$error = false) {
		$data = array();
		foreach ($this->db->get_all('SHOW FUNCTION STATUS') as $v) {
			$name = $v['Name'];
			$source = $extra['show_code'] ? $this->db->get_all('SHOW FUNCTION CODE '.$name) : '';
			$data[$name] = $v + array('source' => $source);
		}
		return $data;
	}

	/**
	*/
	function create_function($name, $data, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
# CREATE FUNCTION hello (s CHAR(20))
# RETURNS CHAR(50) DETERMINISTIC
# RETURN CONCAT('Hello, ',s,'!');
// TODO
	}

	/**
	*/
	function drop_function($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP FUNCTION '.$name;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function func_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_triggers($extra = array(), &$error = false) {
		return $this->db->get_all('SHOW TRIGGERS');
	}

	/**
	*/
	function list_all_triggers($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function create_trigger($name, $data, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
// https://dev.mysql.com/doc/refman/5.5/en/create-trigger.html
# CREATE	[DEFINER = { user | CURRENT_USER }]	TRIGGER trigger_name	trigger_time trigger_event	 ON tbl_name FOR EACH ROW	trigger_body
// TODO
	}

	/**
	*/
	function drop_trigger($name, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP TRIGGER '.$name;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function trigger_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_events($extra = array(), &$error = false) {
		// SHOW EVENTS
		// SHOW CREATE EVENT
// TODO
	}

	/**
	*/
	function create_event($name, $data, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
// https://dev.mysql.com/doc/refman/5.5/en/create-event.html
/*
CREATE EVENT e_totals
ON SCHEDULE AT '2006-02-10 23:59:00'
DO INSERT INTO test.totals VALUES (NOW());
*/
// TODO
	}

	/**
	*/
	function drop_event($name, $data, $extra = array(), &$error = false) {
		if (!strlen($name)) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP EVENT '.$name;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function event_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function list_users($extra = array(), &$error = false) {
		// SELECT * FROM mysql.user
// TODO
	}

	/**
	*/
	function user_exists($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
	*/
	function alter_user($name, $extra = array(), &$error = false) {
// TODO
	}

	/**
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
// TODO: unit tests
		return is_object($this->db) ? $this->db->escape_key($name) : '`'.addslashes($name).'`';
	}

	/**
	*/
	function _escape_table_name($name = '') {
// TODO: unit tests
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
		$table = $this->db->_fix_table_name($table);
		return (strlen($db) ? $this->_escape_database_name($db).'.' : ''). (is_object($table) ? $this->db->escape_key($table) : '`'.addslashes($table).'`');
	}

	/**
	*/
	function _escape_key($key = '') {
// TODO: unit tests
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
// TODO: unit tests
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) ? $this->db->escape_val($val) : '\''.addslashes($val).'\'';
	}

	/**
	*/
	function _es($val = '') {
// TODO: unit tests
// TODO: support for binding params (':field' => $val)
		return is_object($this->db) && method_exists($this->db, '_es') ? $this->db->_es($val) : addslashes($val);
	}

	/**
	* Will be like this: 
	* db()->utils()->database('geonames')->create();
	* db()->utils()->database('geonames')->drop();
	* db()->utils()->database('geonames')->alter($params);
	* db()->utils()->database('geonames')->rename($new_name);
	*/
	function database($name) {
// TODO
		return _class('db_utils_database', 'classes/db/');
	}

	/**
	* Will be like this: 
	* db()->utils()->database('geonames')->table('geo_city')->create();
	* db()->utils()->database('geonames')->table('geo_city')->drop();
	* db()->utils()->database('geonames')->table('geo_city')->alter($params);
	* db()->utils()->database('geonames')->table('geo_city')->rename($new_name);
	*/
	function table($name) {
// TODO
		return _class('db_utils_table', 'classes/db/');
	}

	/**
	* Will be like this: 
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->add();
	* db()->utils()->database('geonames')->table('geo_city')->column('name')->drop();
	*/
	function column($name) {
// TODO
		return _class('db_utils_column', 'classes/db/');
	}

	/**
	* db()->utils()->database('geonames')->view('test')->create();
	*/
	function view($name) {
// TODO
		return _class('db_utils_view', 'classes/db/');
	}

	/**
	* db()->utils()->database('geonames')->procedure('test')->create();
	*/
	function procedure($name) {
// TODO
		return _class('db_utils_procedure', 'classes/db/');
	}

	/**
	* db()->utils()->database('geonames')->trigger('test')->create();
	*/
	function trigger($name) {
// TODO
		return _class('db_utils_trigger', 'classes/db/');
	}

	/**
	* db()->utils()->database('geonames')->event('test')->create();
	*/
	function event($name) {
// TODO
		return _class('db_utils_event', 'classes/db/');
	}
}
