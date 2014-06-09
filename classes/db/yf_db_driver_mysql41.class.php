<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_mysql41 extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;
	/** @var @conf_skip */
	public $query_result		= null;
	/** @var @conf_skip */
	public $num_queries			= 0;
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
	public $HAS_MULTI_QUERY		= false;

	/**
	*/
	function __construct($server, $user, $password, $database, $persistency = false, $use_ssl = false, $port = '', $socket = '', $charset = '', $allow_auto_create_db = false) {
		if (!function_exists('mysql_connect')) {
			trigger_error('MySQL db driver require missing php extension mysql', E_USER_ERROR);
			return false;
		}
		if (is_array($server)) {
			$params = $server;
			$server = '';
		}
		$this->server		= $params['host'] ?: $server;
		$this->user			= $params['user'] ?: $user;
		$this->password		= $params['pswd'] ?: $password;
		$this->dbname		= $params['name'] ?: $database;
		$this->persistency	= isset($params['persist']) ? $params['persist'] : $persistency;
		$this->ssl			= isset($params['ssl']) ? $params['ssl'] : $use_ssl;
		$this->port			= ($params['port'] ?: $port) ?: $this->DEF_PORT;
		$this->socket		= $params['socket'] ?: $socket;
		if (!file_exists($this->socket)) {
			$this->socket = '';
		}
		$this->charset		= ($params['charset'] ?: $charset) ?: (defined('DB_CHARSET') ? DB_CHARSET : $this->DEF_CHARSET);
		$this->ALLOW_AUTO_CREATE_DB	= isset($params['allow_auto_create_db']) ? $params['allow_auto_create_db'] : $allow_auto_create_db;

		ini_set('mysql.connect_timeout', 2);

		$this->connect();

		if (!$this->db_connect_id) {
			conf_add('http_headers::X-Details','ME=(-1) MySql connection error');
			return false;
		}
		if ($this->charset) {
			$this->query('SET NAMES '. $this->charset);
		}
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		if ($this->socket) {
			$connect_host = $this->socket;
		} else {
			$connect_port = $this->port && $this->port != $this->DEF_PORT ? $this->port : '';
			$connect_host = $this->server. ($connect_port ? ':'.$connect_port : '');
		}
		$this->db_connect_id = $this->persistency 
			? mysql_pconnect($connect_host, $this->user, $this->password, $use_ssl ? MYSQL_CLIENT_SSL : 0) 
			: mysql_connect($connect_host, $this->user, $this->password, true, $use_ssl ? MYSQL_CLIENT_SSL : 0);

		if (!$this->db_connect_id) {
			$this->_connect_error = true;
			return false;
		}
		if ($this->dbname != '') {
			$dbselect = mysql_select_db($this->dbname, $this->db_connect_id);
			// Try to create database, if not exists and if allowed
			if (!$dbselect && $this->ALLOW_AUTO_CREATE_DB && preg_match('/^[a-z0-9][a-z0-9_]+[a-z0-9]$/i', $this->dbname)) {
				mysql_query('CREATE DATABASE IF NOT EXISTS '.$this->dbname, $this->db_connect_id);
			}
			$dbselect = mysql_select_db($this->dbname, $this->db_connect_id);
			if (!$dbselect) {
				mysql_close($this->db_connect_id);
				$this->db_connect_id = $dbselect;
			}
		}
	}

	/**
	*/
	function close() {
		if ($this->db_connect_id) {
			return mysql_close($this->db_connect_id);
		}
		return false;
	}

	/**
	*/
	function query($query = '') {
		if (!$this->db_connect_id) {
			return false;
		}
		// Remove any pre-existing queries
		unset($this->query_result);
		if ($query != '') {
			$this->num_queries++;
			$this->query_result = mysql_query($query, $this->db_connect_id);
		}
		if ($this->query_result) {
			return $this->query_result;
		} else {
			$query_error_code = mysql_errno($this->db_connect_id);
			$query_error = mysql_error($this->db_connect_id);
			conf_add('http_headers::X-Details','ME=('.$query_error_code.') '.$query_error);
			return false;
		}
	}

	/**
	* Very simple emulation of the mysqli multi_query
	*/
	function multi_query($queries = array()) {
		$result = array();
		foreach((array)$queries as $k => $sql) {
			$result[$k] = $this->query($sql);
		}
		return $result;
	}

	/**
	*/
	function unbuffered_query($query = '') {
		mysql_unbuffered_query($query, $this->db_connect_id);
	}

	/**
	*/
	function num_rows($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		return $query_id ? mysql_num_rows($query_id) : false;
	}

	/**
	*/
	function affected_rows() {
		return $this->db_connect_id ? mysql_affected_rows($this->db_connect_id) : false;
	}

	/**
	*/
	function insert_id() {
		return $this->db_connect_id ? mysql_insert_id($this->db_connect_id) : false;
	}

	/**
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_row($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_assoc($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_assoc($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_array($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_array($query_id);
		}
		return false;
	}

	/**
	*/
	function real_escape_string($string) {
		if (!$this->db_connect_id) {
			return addslashes($string);
		}
		return mysql_real_escape_string($string, $this->db_connect_id);
	}

	/**
	*/
	function free_result($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			mysql_free_result($query_id);
			return true;
		}
		return false;
	}

	/**
	*/
	function error() {
		if ($this->db_connect_id) {
			return array(
				'message'	=> mysql_error($this->db_connect_id),
				'code'		=> mysql_errno($this->db_connect_id),
			);
		} elseif ($this->_connect_error) {
			return array(
				'message'	=> 'YF: Connect error',
				'code'		=> '9999',
			);
		}
		return false;
	}

	/**
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = true) {
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
			} elseif (preg_match('/^(enum|set)\((.*)\)$/i', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = max(array_map('strlen',explode(',',$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld['max_length'] = ($fld['max_length'] == 0 ? 1 : $fld['max_length']);
				$values = array();
				foreach (explode(',', $query_array[2]) as $v) {
					$v = trim(trim($v), '\'"');
					if (strlen($v)) {
						$values[$v] = $v;
					}
				}
				$fld['values'] = $values;
			} else {
				$fld['type'] = $type;
				$fld['max_length'] = -1;
			}

#			if ($FULL_INFO) {
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
#			}

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
			$sql .= 'LIMIT '.($offset ? $offset.', ' : ''). $count;
		}
		return $sql;
	}

	/**
	*/
	function escape_key($data) {
		return '`'.$data.'`';
	}

	/**
	*/
	function escape_val($data) {
		return '\''.$data.'\'';
	}

	/**
	*/
	function get_server_version() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysql_get_server_info($this->db_connect_id);
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysql_get_host_info($this->db_connect_id);
	}

	/**
	*/
	function ping() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysql_ping($this->db_connect_id);
	}
}
