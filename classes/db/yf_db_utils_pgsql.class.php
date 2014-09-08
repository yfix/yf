<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_pgsql extends yf_db_utils_driver {

	/**
	*/
	public function list_collations($extra = array()) {
#		return $this->db->get_all('SELECT colname FROM pg_catalog.pg_collation');
	}

	/**
	* @not_implemented
	*/
	public function list_charsets($extra = array()) {
		return false;
	}

	/**
	*/
	public function list_databases($extra = array()) {
		$sql = 'SELECT datname FROM pg_database WHERE datistemplate = false';
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
			'name'		=> $db_name,
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
#		$sql = 'CREATE DATABASE '.($extra['if_not_exists'] ? 'IF NOT EXISTS ' : ''). $this->_escape_database_name($db_name);
#		return $extra['sql'] ? $sql : (bool)$this->db->query($sql);
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
#		$_sql = 'DROP DATABASE '.($extra['if_exists'] ? 'IF EXISTS ' : ''). $this->_escape_database_name($db_name);
#		$sql[] = $extra['sql'] ? $_sql : $this->db->query($_sql);
#		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
	}

	/**
	*/
	public function alter_database($db_name, $extra = array(), &$error = false) {
		if (!strlen($db_name)) {
			$error = 'db_name is empty';
			return false;
		}
/*
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
*/
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
/*
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
*/
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
#		'SELECT table_schema,table_name FROM information_schema.tables ORDER BY table_schema,table_name';
#		$tables = $this->db->get_2d('SHOW TABLES'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name) : ''));
#		$tables = $this->db->get_2d('SHOW TABLES'. (strlen($db_name) ? ' FROM '.$this->_escape_database_name($db_name) : ''));
		return $tables ? array_combine($tables, $tables) : array();
	}

	/**
	* Meta Columns
	*/
	function meta_columns($table) {
		$retarr = array();
/*
		$sql = "SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum 
			FROM pg_class c, pg_attribute a,pg_type t 
			WHERE relkind IN ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) AND a.attname NOT LIKE '....%%'
			AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum";

		$Q = $this->db->query(sprintf($sql, $table));
		while ($A = $this->db->fetch_row($Q)) {
			$fld = array();

			$fld['name']= $A[0];
			$type		= $A[1];

			$fld['scale'] = null;
			if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
				$fld['scale'] = is_numeric($query_array[3]) ? $query_array[3] : -1;
			} elseif (preg_match('/^(.+)\((\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = max(array_map('strlen',explode(',',$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld['max_length'] = ($fld['max_length'] == 0 ? 1 : $fld['max_length']);
			} else {
				$fld['type'] = $type;
				$fld['max_length'] = -1;
			}
			$fld['not_null']		= ($A[2] != 'YES');
			$fld['primary_key']		= ($A[3] == 'PRI');
			$fld['auto_increment']	= (strpos($A[5], 'auto_increment') !== false);
			$fld['binary']			= (strpos($type,'blob') !== false);
			$fld['unsigned']		= (strpos($type,'unsigned') !== false);
			if (!$fld['binary']) {
				$d = $A[4];
				if ($d != '' && $d != 'NULL') {
					$fld['has_default'] = true;
					$fld['default_value'] = $d;
				} else {
					$fld['has_default'] = false;
				}
			}
			$retarr[strtolower($fld['name'])] = $fld;
		}
*/
		return $retarr;
	}

	/**
	* Meta Tables
	*/
	function meta_tables($DB_PREFIX = '') {
/*
		$sql = 'SELECT tablename,\'T\' FROM pg_tables WHERE tablename NOT LIKE \'pg\_%\'
					AND tablename NOT IN (\'sql_features\', \'sql_implementation_info\', \'sql_languages\', \'sql_packages\', \'sql_sizing\', \'sql_sizing_profiles\') 
				UNION 
					SELECT viewname,\'V\' FROM pg_views WHERE viewname NOT LIKE \'pg\_%\'';
		$q = $this->db->query($sql);
		while ($a = $this->db->fetch_row($q)) {
			$name = $a['0'];
			// Skip tables without prefix of current connection
			if (strlen($DB_PREFIX) && substr($name, 0, strlen($DB_PREFIX)) != $DB_PREFIX) {
				continue;
			}
			$tables[$name] = $name;
		}
		return $tables;
*/
	}
}
