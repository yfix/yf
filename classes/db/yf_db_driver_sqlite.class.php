<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_sqlite extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	*/
	function __construct(array $params) {
		if (!class_exists('SQLite3')) {
			trigger_error('SQLite db driver require missing php extension sqlite3', E_USER_ERROR);
			return false;
		}
		$params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : $this->DEF_CHARSET);
		$this->params = $params;

		$this->connect();

		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$this->db_connect_id = new SQLite3($this->params['name']);
		if (!$this->db_connect_id) {
			$this->_connect_error = 'cannot_connect_to_server';
		}
		return $this->db_connect_id;
	}

	/**
	*/
	function select_db($name) {
		return true;
	}

	/**
	*/
	function query($query) {
		if (!$this->db_connect_id) {
			return false;
		}
		$result = $this->db_connect_id->query($query);
		$this->_last_query_id = $result;
		if (!$result) {
			$error = $this->error();
			$query_error_code = $error['code'];
			$query_error = $error['message'];
			return false;
		}
		return $result;
	}

	/**
	*/
	function close() {
		return $this->db_connect_id ? $this->db_connect_id->close() : false;
	}

	/**
	*/
	function num_rows($query_id) {
		if ($query_id) {
			$num_rows = 0;
			while ($a = $this->fetch_row($query_id)) {
				$num_rows++;
			}
			$query_id->reset();
			return $num_rows;
		}
		return false;
	}

	/**
	*/
	function affected_rows() {
		return $this->db_connect_id ? $this->db_connect_id->changes() : false;
	}

	/**
	*/
	function insert_id() {
		return $this->db_connect_id ? $this->db_connect_id->lastInsertRowID() : false;
	}

	/**
	*/
	function fetch_row($query_id) {
		return $query_id ? $query_id->fetchArray(SQLITE3_NUM) : false;
	}

	/**
	*/
	function fetch_assoc($query_id) {
		return $query_id ? $query_id->fetchArray(SQLITE3_ASSOC) : false;
	}

	/**
	*/
	function fetch_array($query_id) {
		return $query_id ? $query_id->fetchArray(SQLITE3_BOTH) : false;
	}

	/**
	*/
	function fetch_object($query_id) {
		return $query_id ? array_to_object($query_id->fetchArray(SQLITE3_ASSOC)) : false;
	}

	/**
	*/
	function real_escape_string($string) {
		return $this->db_connect_id ? $this->db_connect_id->escapeString($string) : false;
	}

	/**
	*/
	function free_result($query_id) {
		if ($query_id) {
			$query_id = null;
		}
		return true;
	}

	/**
	*/
	function error() {
		if ($this->db_connect_id) {
			return array(
				'message'	=> $this->db_connect_id->lastErrorMsg(),
				'code'		=> $this->db_connect_id->lastErrorCode(),
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
	* Begin a transaction
	*/
	function begin() {
		return $this->db_connect_id ? $this->db_connect_id->query('BEGIN') : false;
	}

	/**
	* End a transaction
	*/
	function commit() {
		return $this->db_connect_id ? $this->db_connect_id->query('COMMIT') : false;
	}

	/**
	* Rollback a transaction
	*/
	function rollback() {
		return $this->db_connect_id ? $this->db_connect_id->query('ROLLBACK') : false;
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
		return $this->db_connect_id ? $this->db_connect_id->version() : false;
	}

	/**
	*/
	function get_host_info() {
		return $this->get_server_version();
	}

	/**
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = true) {
		$cols = array();
		$sql = 'PRAGMA table_info('.$table.')';
		$q = $this->query($sql);
		while ($a = $this->fetch_assoc($q)) {
			$name = $a['name'];
			$cols[$name] = $a;
		}
		return $cols;
	}

	/**
	*/
	function meta_tables($DB_PREFIX = '') {
		$sql = 'SELECT name	FROM sqlite_master WHERE type = "table"	AND name <> "sqlite_sequence"';
		$q = $this->query($sql);
		while ($a = $this->fetch_assoc($q)) {
			$name = $a['name'];
			// Skip tables without prefix of current connection
			if (strlen($DB_PREFIX) && substr($name, 0, strlen($DB_PREFIX)) != $DB_PREFIX) {
				continue;
			}
			$tables[$name] = $name;
		}
		return $tables;
	}
}
