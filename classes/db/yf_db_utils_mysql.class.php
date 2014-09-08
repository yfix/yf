<?php

/**
*/
load('db_utils_driver', 'framework', 'classes/db/');
class yf_db_utils_mysql extends yf_db_utils_driver {

	/**
	*/
	public function _get_supported_field_types() {
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
		return array(
			'bit','int','real','double','float','decimal','numeric'
		);
	}

	/**
	*/
	public function _get_supported_table_options() {
		return array(
			'engine'	=> 'ENGINE',
			'charset'	=> 'DEFAULT CHARSET',
		);
	}

	/**
	* Slow method, but returning all info about indexes for selected database at once.
	* Useful for analytics and getting overall picture.
	*/
	public function list_all_database_indexes($db_name = '', $extra = array(), &$error = false) {
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
	* Note: The 'SHOW PROCEDURE|FUNCTION CODE' feature is disabled; you need MySQL built with '--with-debug' to have it working (code:1289)
	*/
	public function list_procedures($db_name = '', $extra = array(), &$error = false) {
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
	public function procedure_exists($name, $extra = array(), &$error = false) {
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
	public function procedure_info($name, $extra = array(), &$error = false) {
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
	public function drop_procedure($name, $extra = array(), &$error = false) {
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
	public function create_procedure($name, $sql_body, $sql_params = '', $extra = array(), &$error = false) {
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
	public function list_functions($extra = array(), &$error = false) {
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
	public function function_exists($name, $extra = array(), &$error = false) {
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
	public function function_info($name, $extra = array(), &$error = false) {
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
	public function drop_function($name, $extra = array(), &$error = false) {
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
	public function create_function($name, $sql_body, $sql_returns_type, $sql_params = '', $extra = array(), &$error = false) {
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
	public function list_events($db_name = '', $extra = array(), &$error = false) {
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
	public function event_exists($name, $extra = array(), &$error = false) {
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
	public function event_info($name, $extra = array(), &$error = false) {
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
	public function drop_event($name, $extra = array(), &$error = false) {
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
	public function create_event($name, $event_shedule, $event_body, $extra = array(), &$error = false) {
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
	public function list_users($extra = array(), &$error = false) {
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
	public function user_exists($name, $extra = array(), &$error = false) {
		$users = $this->list_users($extra, $error);
		return (bool)isset($users[$name]);
	}

	/**
	*/
	public function user_info($name, $extra = array(), &$error = false) {
		$users = $this->list_users($extra, $error);
		return isset($users[$name]) ? $users[$name] : false;
	}

	/**
	*/
	public function delete_user($name, $extra = array(), &$error = false) {
		list($host, $user) = explode('@', $name);
		$sql = 'DELETE FROM mysql.user WHERE host='.$this->_escape_val($host).' AND user='.$this->_escape_val($user);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function add_user($name, array $data, $extra = array(), &$error = false) {
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
	public function update_user($name, array $data, $extra = array(), &$error = false) {
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
	*/
	public function check_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'CHECK TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function optimize_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'OPTIMIZE TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function repair_table($table, $extra = array(), &$error = false) {
		if (!$table) {
			$error = 'table_name is empty';
			return false;
		}
		$sql = 'REPAIR TABLE '.$this->_escape_table_name($table);
		return $extra['sql'] ? $sql : $this->db->query($sql);
	}

	/**
	*/
	public function list_collations($extra = array()) {
		return $this->db->get_all('SHOW COLLATION');
	}

	/**
	*/
	public function list_charsets($extra = array()) {
		return $this->db->get_all('SHOW CHARACTER SET');
	}
}
