<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_sqlite extends yf_db_utils_driver {

	/**
	*/
	function _get_supported_field_types() {
		return array(
			'int','int2','int8','integer','tinyint','smallint','mediumint','bigint','unsigned big int',
			'real','float','double','double precision','decimal','numeric','boolean',
			'varchar','character','text','varying character','nchar','native character','nvarchar','blob','clob',
			'datetime','date',
		);
	}

	/**
	*/
	function _get_unsigned_field_types() {
		return array(
			'unsigned big int',
		);
	}

	/**
	*/
	function _get_supported_table_options() {
		return array();
	}

	/**
	*/
	function list_tables($db_name = '', $extra = array(), &$error = false) {
		$tables = $this->db->get_2d('SELECT name FROM sqlite_master WHERE type = "table" AND name <> "sqlite_sequence"');
		return $tables ? array_combine($tables, $tables) : array();
	}

	/**
	*/
	function list_tables_details($db_name = '', $extra = array(), &$error = false) {
		return $this->list_tables($db_name, $extra, $error);
	}

	/**
	*/
	function table_get_columns($table, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table_name is empty';
			return false;
		}
		$cols = array();
		$q = $this->db->query('PRAGMA table_info('.$this->_escape_table_name($table).')');
		while ($a = $this->db->fetch_assoc($q)) {
			$name = $a['name'];
			$type = strtolower($a['type']);
			$unsigned = (false !== strpos($type, 'unsigned'));
			if (false !== strpos($type, ' ')) {
				list($type, $tmp) = explode(' ', $type);
			}
			$length = '';
			$unsigned = false;
			$cols[$name] = array(
				'name'		=> $name,
				'type'		=> $type,
				'length'	=> $length,
				'decimals'	=> $decimals ?: null,
				'unsigned'	=> $unsigned,
				'nullable'	=> !$a['notnull'],
				'default'	=> $a['dflt_value'],
				'charset'	=> null,
				'collate'	=> null,
				'auto_inc'	=> $a['pk'] == 1,
				'primary'	=> $a['pk'] == 1,
// TODO: detect unique from indexes list
				'unique'	=> $a['pk'] == 1,
				'type_raw'	=> $a['type'],
				'values'	=> null,
			);
			$cols[$name]['type_raw'] = $a['type'];
		}
		return $cols;
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
		return array(
			'name'			=> $table,
			'db_name'		=> null,
			'columns'		=> $this->table_get_columns($orig_table),
			'row_format'	=> null,
			'charset'		=> null,
			'collate'		=> null,
			'engine'		=> null,
			'rows'			=> null,
			'data_size'		=> null,
			'auto_inc'		=> null,
			'comment'		=> null,
			'create_time'	=> null,
			'update_time'	=> null,
		);
	}

	/**
	*/
// Inherited
#	function create_table($table, $extra = array(), &$error = false) {
#	}

	/**
	*/
	function rename_table($table, $new_name, $extra = array(), &$error = false) {
		if (!$table || !$new_name) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' RENAME TO '.$this->_escape_table_name($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function truncate_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'DELETE FROM '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function drop_column($table, $col_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
#		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' DROP COLUMN '.$this->db->escape_key($col_name);
/*
BEGIN TRANSACTION;
CREATE TEMPORARY TABLE t1_backup(a,b);
INSERT INTO t1_backup SELECT a,b FROM t1;
DROP TABLE t1;
CREATE TABLE t1(a,b);
INSERT INTO t1 SELECT a,b FROM t1_backup;
DROP TABLE t1_backup;
COMMIT;
*/
#		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function rename_column($table, $col_name, $new_name, $extra = array(), &$error = false) {
		if (!strlen($table)) {
			$error = 'table name is empty';
			return false;
		}
/*
BEGIN TRANSACTION;
CREATE TEMPORARY TABLE t1_backup(a,b);
INSERT INTO t1_backup SELECT a,b FROM t1;
DROP TABLE t1;
CREATE TABLE t1(a,b);
INSERT INTO t1 SELECT a,b FROM t1_backup;
DROP TABLE t1_backup;
COMMIT;
*/
#		$col_info_str = $this->_compile_create_table($this->column_info($table, $col_name), array('no_name' => true));
#		$sql = 'ALTER TABLE '.$this->_escape_table_name($table).' CHANGE COLUMN '.$this->_escape_key($col_name).' '.$this->_escape_key($new_name).' '.$col_info_str;
#		return $extra['sql'] ? $sql : $this->db->query($sql);
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
#	function update_index(
#	}

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
		$keys = array();
// TODO: port code from mysql
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
#	function add_foreign_key(
#	}

	/**
	*/
#	function update_foreign_key(
#	}

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
// TODO: port code from mysql
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
	*/
	function list_triggers($db_name = '', $extra = array(), &$error = false) {
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
	* See: http://www.sqlite.org/datatype3.html
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
			}
		}
		return array(
			'type'		=> $type,
			'length'	=> $length,
			'unsigned'	=> false !== strpos(strtolower($str), 'unsigned') && in_array($type, $this->_get_unsigned_field_types()) ? true : false,
			'decimals'	=> $decimals,
			'values'	=> null,
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
					. (isset($unsigned) ? ' UNSIGNED' : '')
					. (isset($null) ? ' '.($null ? 'NULL' : 'NOT NULL') : '')
					. (isset($default) ? ' DEFAULT \''.addslashes($default).'\'' : '')
					. ($auto_inc ? ' PRIMARY KEY' : '')
				;
			}
		}
		return implode(','.PHP_EOL, $items);
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
	function _escape_table_name($name = '') {
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
		return is_object($this->db) ? $this->db->escape_key($table) : '`'.addslashes($table).'`';
	}

	/**
	*/
	function _escape_key($key = '') {
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
	function _escape_val($val = '') {
		if (is_null($val)) {
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
}
