<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_mysqli extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;
	/** @var @conf_skip */
	public $META_TABLES_SQL		= 'SHOW TABLES';	
	/** @var @conf_skip */
	public $META_COLUMNS_SQL	= 'SHOW COLUMNS FROM %s';
	/** @var @conf_skip */
	public $DEF_CHARSET			= 'utf8';
	/** @var @conf_skip */
	public $DEF_PORT			= 3306;
	/** @var @conf_skip */
	public $SQL_NO_CACHE		= false;
	/** @var @conf_skip */
	public $ALLOW_AUTO_CREATE_DB= false;
	/** @var @conf_skip */
	public $HAS_MULTI_QUERY		= true;

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('mysqli_init')) {
			trigger_error('MySQLi db driver require missing php extension mysql', E_USER_ERROR);
			return false;
		}
		$params['port'] = $params['port'] ?: $this->DEF_PORT;
		if ($params['socket'] && !file_exists($params['socket'])) {
			$params['socket'] = '';
		}
		$params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : $this->DEF_CHARSET);
		$this->params = $params;

		$this->connect();

		if (!$this->db_connect_id) {
			conf_add('http_headers::X-Details','ME=(-1) MySqli connection error');
			return $this->db_connect_id;
		}
		if ($params['charset']) {
			// See http://php.net/manual/en/mysqlinfo.concepts.charset.php
			mysqli_set_charset($this->db_connect_id, 'utf8'); // $this->query('SET NAMES '. $params['charset']);
		}
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$this->db_connect_id = mysqli_init();
		if (!$this->db_connect_id) {
			$this->_connect_error = 'cannot_connect_to_server';
			$this->db_connect_id = null;
			return false;
		}
		if ($this->params['socket']) {
			$connect_host = $this->params['socket'];
		} else {
			$connect_port = $this->params['port'] && $this->params['port'] != $this->DEF_PORT ? $this->params['port'] : '';
			$connect_host = ($this->params['persist'] ? 'p:' : '').$this->params['host']. ($connect_port ? ':'.$connect_port : '');
		}
		mysqli_options($this->db_connect_id, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
		$is_connected = mysqli_real_connect($this->db_connect_id, $this->params['host'], $this->params['user'], $this->params['pswd'], '', $this->params['port'], $this->params['socket'], $this->params['ssl'] ? MYSQLI_CLIENT_SSL : 0);
		if (!$is_connected) {
			$this->_connect_error = 'cannot_connect_to_server';
			return false;
		}
		if ($this->params['name'] != '') {
			$dbselect = $this->select_db($this->params['name']);
			// Try to create database, if not exists and if allowed
			if (!$dbselect && $this->params['allow_auto_create_db'] && preg_match('/^[a-z0-9][a-z0-9_]+[a-z0-9]$/i', $this->params['name'])) {
				$res = $this->query('CREATE DATABASE IF NOT EXISTS '.$this->params['name']);
				if ($res) {
					$dbselect = $this->select_db($this->params['name']);
				}
			}
			if (!$dbselect) {
				$this->_connect_error = 'cannot_select_db';
			}
			return $dbselect;
		}
	}

	/**
	*/
	function select_db($name) {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysqli_select_db($this->db_connect_id, $name);
	}

	/**
	*/
	function close() {
		return mysqli_close($this->db_connect_id);
	}

	/**
	* Base query method
	*/
	function query($query = '') {
		if (!$this->db_connect_id) {
			return false;
		}
		$result = mysqli_query($this->db_connect_id, $query);
		if (!$result) {
			$query_error = mysqli_error($this->db_connect_id);
			$query_error_code = mysqli_errno($this->db_connect_id);
			if ($query_error_code) {
				conf_add('http_headers::X-Details','ME=('.$query_error_code.') '.$query_error);
			}
			return false;
		}
		return $result;
	}

	/**
	* Very simple emulation of the mysqli multi_query
	*/
	function multi_query($queries = array()) {
		if (is_string($queries)) {
			return $this->_multi_query($queries);
		}
// TODO: check and implement this:
/*
$query  = 'SELECT CURRENT_USER();';
$query .= 'SELECT Name FROM City ORDER BY ID LIMIT 20, 5';

if ($mysqli->multi_query($query)) {
    do {
        if ($result = $mysqli->store_result()) {
            while ($row = $result->fetch_row()) {
                printf('%s\n', $row[0]);
            }
            $result->free();
        }
        if ($mysqli->more_results()) {
            printf('-----------------\n');
        }
    } while ($mysqli->next_result());
}
*/
		$result = array();
		foreach((array)$queries as $k => $sql) {
			$result[$k] = $this->query($sql);
		}
		return $result;
	}

	/**
	* Multi query method (specific for this driver)
	*/
	function _multi_query($query = '') {
		if (!$query) {
			return false;
		}
		return mysqli_multi_query($this->db_connect_id, $query);
	}

	/**
	*/
	function unbuffered_query($query = '') {
		mysqli_unbuffered_query($this->db_connect_id, $query);
	}

	/**
	*/
	function num_rows($query_id = 0) {
		if ($query_id) {
			return mysqli_num_rows($query_id);
		}
		return false;
	}

	/**
	*/
	function affected_rows() {
		return $this->db_connect_id ? mysqli_affected_rows($this->db_connect_id) : false;
	}

	/**
	*/
	function insert_id() {
		return $this->db_connect_id ? mysqli_insert_id($this->db_connect_id) : false;
	}

	/**
	*/
	function fetch_row($query_id = 0) {
		if ($query_id) {
			return mysqli_fetch_row($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_assoc($query_id = 0) {
		if ($query_id) {
			return mysqli_fetch_assoc($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_array($query_id = 0) {
		if ($query_id) {
			return mysqli_fetch_array($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_object($query_id = 0) {
		if ($query_id) {
			return mysqli_fetch_object($query_id);
		}
		return false;
	}

	/**
	*/
	function real_escape_string($string) {
		if (!$this->db_connect_id) {
			return addslashes($string);
		}
		return mysqli_real_escape_string($this->db_connect_id, $string);
	}

	/**
	*/
	function free_result($query_id = 0) {
		if ($query_id) {
			mysqli_free_result($query_id);
			// We need this for compatibility, because mysqli_free_result() returns "void"
			return true;
		}
		return true;
	}

	/**
	*/
	function error() {
		if ($this->db_connect_id) {
			return array(
				'message'	=> mysqli_error($this->db_connect_id),
				'code'		=> mysqli_errno($this->db_connect_id),
			);
		} elseif ($this->_connect_error) {
			return array(
				'message'	=> 'YF: Connect error: '.$this->_connect_error,
				'code'		=> '9999',
			);
		}
		return false;
	}

	/**
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false) {
		$retarr = array();

		$Q = $this->query(sprintf($this->META_COLUMNS_SQL, $table));
		while ($A = $this->fetch_row($Q)) {
			$fld = array();

			$fld['name']= $A[0];
			$type		= $A[1];

			// split type into type(length):
			if ($FULL_INFO) {
				$fld['scale'] = null;
			}
			if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
				if ($FULL_INFO) {
					$fld['scale'] = is_numeric($query_array[3]) ? $query_array[3] : -1;
				}
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

			if ($FULL_INFO) {
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
			}

			if ($KEYS_NUMERIC) {
				$retarr[] = $fld;
			} else {
				$retarr[strtolower($fld['name'])] = $fld;
			}
		}
		return $retarr;
	}

	/**
	*/
	function meta_tables($DB_PREFIX = '') {
		$Q = $this->query($this->META_TABLES_SQL);
		while ($A = $this->fetch_row($Q)) {
			// Skip tables without prefix of current connection
			if (strlen($DB_PREFIX) && substr($A['0'], 0, strlen($DB_PREFIX)) != $DB_PREFIX) {
				continue;
			}
			$tables[$A['0']] = $A['0'];
		}
		return $tables;
	}

	/**
	* Begin a transaction
	*/
	function begin() {
		return $this->query('START TRANSACTION');
	}

	/**
	* End a transaction
	*/
	function commit() {
		return $this->query('COMMIT');
	}

	/**
	* Rollback a transaction
	*/
	function rollback() {
		return $this->query('ROLLBACK');
	}

	/**
	* Return database-specific limit of returned rows
	*/
	function limit($count, $offset) {
		if ($count > 0) {
			$offset = ($offset > 0) ? $offset : 0;
			$sql .= 'LIMIT '.$offset.', '.$count;
		}
		return $sql;
	}

	/**
	* Enclose field names
	*/
	function escape_key($data) {
		return '`'.$data.'`';
	}

	/**
	* Enclose field values
	*/
	function escape_val($data) {
		return '\''.$data.'\'';
	}

	/**
	* Prepare statement
	*/
	function prepare($query) {
		return mysqli_prepare($this->db_connect_id, $query);
	}

	/**
	* Bind statement param
	*/
	function bind_params($stmt, $data = array()) {
		foreach ((array)$data as $k => $v) {
			$var_type = substr($k, 0, 1);
			$var_name = substr($k, 2);
			$types_string .= $var_type;
			$params[]	= '$data[\''.$k.'\']';
		}
		return eval('return mysqli_stmt_bind_param($stmt, \''.$types_string.'\', '.implode(',', $params).');');
	}

	/**
	* Execute statement
	*/
	function execute($stmt) {
		return mysqli_stmt_execute($stmt);
	}

	/**
	* Query with preparing
	*/
	function query_fetch_prepared($query, $data = array()) {
		$stmt = mysqli_prepare($this->db_connect_id, $query);
		$this->bind_params($stmt, $data);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $result);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		return $result;
	}

	/**
	*/
	function get_server_version() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysqli_get_server_info($this->db_connect_id);
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysqli_get_host_info($this->db_connect_id);
	}

	/**
	*/
	function ping() {
		return mysqli_ping($this->db_connect_id);
	}
}
