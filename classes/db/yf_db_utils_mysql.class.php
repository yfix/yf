<?php

// TODO: implement migrations like in ROR, based on these methods

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_mysql extends yf_db_utils_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
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
	function list_databases($extra = array()) {
		$sql = 'SHOW DATABASES';
		return $extra['sql'] ? $sql : $this->db->get_2d($sql);
	}

	/**
	*/
	function create_database($name, $extra = array()) {
		$sql = 'CREATE DATABASE '.($extra['if_not_exists'] ? 'IF NOT EXISTS ' : '').''.$this->db->_es($name).'';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function drop_database($name, $extra = array()) {
		$sql = 'DROP DATABASE '.($extra['if_exists'] ? 'IF EXISTS ' : '').''.$this->db->_es($name).'';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function alter_database($name, $extra = array()) {
		foreach ((array)$extra as $k => $v) {
			$params[$k] = $k.' = '.$v;
		}
		$sql = 'ALTER DATABASE '.$this->db->_es($name).' '.implode(' ', $params);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function rename_database($name, $new_name, $extra = array()) {
		$sql[] = $this->create_database($new_name, $extra);
		foreach ((array)$this->list_tables($name) as $t) {
			$sql[] = $this->rename_table($name.'.'.$t, $new_name.'.'.$t, $extra);
		}
		$sql[] = $this->drop_database($name, $extra);
		return $extra['sql'] ? implode(PHP_EOL, $sql) : true;
	}

	/**
	*/
	function list_tables($extra = array(), &$error = false) {
		return (array)$this->db->get_2d('show tables');
	}

	/**
	*/
	function table_exists($name, $extra = array(), &$error = false) {
		if (!$name) {
			$error = 'name is empty';
			return false;
		}
		return (bool)in_array($name, (array)$this->list_tables());
	}

	/**
	*/
	function create_table($name, $extra = array(), &$error = false) {
		if (!$name) {
			$error = 'name is empty';
			return false;
		}
		$path = YF_PATH. 'share/db_installer/sql/'.$name.'.sql.php';
// TODO: optionally check if table really exists in database: show_tables and in_array(...)
// TODO: use glob for YF and PROJECT
		if (!file_exists($path)) {
			$error = 'file not exists: '.$path;
			return false;
		}
		include $path;
		if (!$data) {
			$error = 'data is empty';
			return false;
		}
		$sql = 'CREATE TABLE IF NOT EXISTS '.$this->db->_fix_table_name($name).' ('. PHP_EOL. $data. PHP_EOL. ') ENGINE=InnoDB DEFAULT CHARSET=utf8;'. PHP_EOL;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function drop_table($name, $extra = array(), &$error = false) {
		if (!$name) {
			$error = 'name is empty';
			return false;
		}
		$sql = 'DROP TABLE IF EXISTS '.$this->db->_fix_table_name($name).';'.PHP_EOL;
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function alter_table($name, $extra = array()) {
		foreach ((array)$extra as $k => $v) {
			$params[$k] = $k.' = '.$v;
		}
		$sql = 'ALTER TABLE '.$this->db->_es($name).' '.implode(' ', $params);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function rename_table($name, $new_name, $extra = array()) {
		$sql = 'RENAME TABLE '.$this->db->_es($name).' TO '.$this->db->_es($new_name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function truncate_table($name, $extra = array()) {
		$sql = 'TRUNCATE TABLE '.$this->db->_es($name).'';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function check_table($name, $extra = array()) {
		$sql = 'TRUNCATE TABLE '.$this->db->_es($name).'';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function optimize_table($name, $extra = array()) {
		$sql = 'OPTIMIZE TABLE '.$this->db->_es($name).'';
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function repair_table($name, $extra = array()) {
		$sql = 'REPAIR TABLE '.$this->db->_es($name);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	function list_indexes($table, $extra = array()) {
		/*$this->db->query("
			SELECT *
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = {$this->db->_fix_table_name($table)} AND TABLE_SCHEMA = DATABASE()
			AND REFERENCED_COLUMN_NAME IS NULL
		");*/
		$indexes = array();
		foreach ($this->db->get_all('SHOW INDEX FROM ' . $this->db->_fix_table_name($table)) as $row) {
			$indexes[$row['Key_name']]['name'] = $row['Key_name'];
			$indexes[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$indexes[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		return array_values($indexes);
	}

	/**
	*/
	function add_index($table, $fields, $extra = array()) {
# CREATE INDEX id_index ON lookup (id)
// TODO
	}

	/**
	*/
	function drop_index($table, $name) {
# DROP INDEX index_name ON tbl_name
// TODO
	}

	/**
	*/
	function list_foreign_keys($table, $extra = array()) {
		$keys = array();
		$sql = 'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE() 
				AND REFERENCED_TABLE_NAME IS NOT NULL 
				AND TABLE_NAME = '. $this->db->_fix_table_name($table);
		foreach ($this->db->get_all($sql) as $id => $row) {
			$keys[$id]['name'] = $row['CONSTRAINT_NAME']; // foreign key name
			$keys[$id]['local'] = $row['COLUMN_NAME']; // local columns
			$keys[$id]['table'] = $row['REFERENCED_TABLE_NAME']; // referenced table
			$keys[$id]['foreign'] = $row['REFERENCED_COLUMN_NAME']; // referenced columns
		}
		return array_values($keys);
	}

	/**
	*/
	function add_foreign_key($table, $fields, $extra = array()) {
// TODO
	}

	/**
	*/
	function drop_foreign_key($table, $fields, $extra = array()) {
// TODO
	}

	/**
	*/
	function list_columns($table, $extra = array()) {
		/*$this->db->query("
			SELECT *
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = {$this->db->_fix_table_name($table)} AND TABLE_SCHEMA = DATABASE()
		");*/
		$columns = array();
		foreach ($this->db->get_all('SHOW FULL COLUMNS FROM '. $this->db->_fix_table_name($table)) as $row) {
			$type = explode('(', $row['Type']);
			$columns[] = array(
				'name'		=> $row['Field'],
				'table'		=> $table,
				'nativetype'=> strtoupper($type[0]),
				'size'		=> isset($type[1]) ? (int) $type[1] : NULL,
				'unsigned'	=> (bool) strstr($row['Type'], 'unsigned'),
				'nullable'	=> $row['Null'] === 'YES',
				'default'	=> $row['Default'],
				'autoincrement' => $row['Extra'] === 'auto_increment',
				'primary'	=> $row['Key'] === 'PRI',
				'vendor'	=> (array) $row,
			);
		}
		return $columns;
	}

	/**
	*/
	function add_column($table, $name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function rename_column($table, $name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function alter_column($table, $name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function drop_column($table, $name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function list_views($extra = array()) {
// TODO
		/*$this->connection->query("
			SELECT TABLE_NAME as name, TABLE_TYPE = 'VIEW' as view
			FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
		");*/
/*
		$tables = array();
		foreach ($this->connection->query('SHOW FULL TABLES') as $row) {
			$tables[] = array(
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			);
		}
		return $tables;
*/
	}

	/**
	*/
	function create_view($name, $data, $extra = array()) {
# CREATE VIEW test.v AS SELECT * FROM t;
// TODO
	}

	/**
	*/
	function drop_view($name, $extra = array()) {
# DROP VIEW view_name
// TODO
	}

	/**
	*/
	function list_procedures($extra = array()) {
// TODO
	}

	/**
	*/
	function create_procedure($name, $data, $extra = array()) {
# CREATE PROCEDURE simpleproc (OUT param1 INT)
# BEGIN
#    SELECT COUNT(*) INTO param1 FROM t;
# END//
// TODO
	}

	/**
	*/
	function drop_procedure($name, $data, $extra = array()) {
# DROP PROCEDURE name
// TODO
	}

	/**
	*/
	function list_functions($extra = array()) {
// TODO
	}

	/**
	*/
	function create_function($name, $data, $extra = array()) {
# CREATE FUNCTION hello (s CHAR(20))
# RETURNS CHAR(50) DETERMINISTIC
# RETURN CONCAT('Hello, ',s,'!');
// TODO
	}

	/**
	*/
	function drop_function($name, $data, $extra = array()) {
# DROP FUNCTION name
// TODO
	}

	/**
	*/
	function list_triggers($extra = array()) {
	}

	/**
	*/
	function create_trigger($name, $data, $extra = array()) {
# CREATE    [DEFINER = { user | CURRENT_USER }]    TRIGGER trigger_name    trigger_time trigger_event     ON tbl_name FOR EACH ROW    trigger_body
// TODO
	}

	/**
	*/
	function drop_trigger($name, $data, $extra = array()) {
# DROP TRIGGER [IF EXISTS] [schema_name.]trigger_name
// TODO
	}

	/**
	*/
	function list_events($extra = array()) {
// TODO
	}

	/**
	*/
	function create_event($name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function drop_event($name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function list_users($extra = array()) {
// TODO
	}

	/**
	*/
	function create_user($name, $data, $extra = array()) {
// TODO
	}

	/**
	*/
	function drop_user($name, $data, $extra = array()) {
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
