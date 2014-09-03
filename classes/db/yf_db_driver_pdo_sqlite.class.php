<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_sqlite extends yf_db_driver_pdo {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('mysql_connect')) {
			trigger_error('MySQL db driver require missing php extension mysql', E_USER_ERROR);
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
		$dsn = 'sqlite:'.$params['name'];
		$attrs = array();
		$attrs[PDO::ATTR_TIMEOUT] = 2;
		if ($this->params['persist']) {
			$attrs[PDO::ATTR_PERSISTENT] = true;
		}
		$this->db_connect_id = new PDO($dsn, null, null, $attrs);
		$pdo = &$this->db_connect_id;
#		$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$pdo->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, true);

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
	function close() {
		if ($this->db_connect_id) {
			$this->db_connect_id = null;
			return true;
		}
		return false;
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
	function num_rows($query_id) {
		if ($query_id) {
			$num_rows = 0;
			while ($a = $this->fetch_row($query_id)) {
				$num_rows++;
			}
			$query_id->closeCursor();
			$query_id->execute();
			return $num_rows;
		}
		return false;
	}

	/**
	*/
	function affected_rows() {
		return $this->_last_query_id ? $this->_last_query_id->rowCount() : false;
	}

	/**
	*/
	function insert_id() {
		return $this->db_connect_id->lastInsertId();
	}

	/**
	*/
	function fetch_row($query_id) {
		if (!$query_id) {
			return false;
		}
		return $query_id->fetch(PDO::FETCH_NUM);
	}

	/**
	*/
	function fetch_assoc($query_id) {
		if (!$query_id) {
			return false;
		}
		return $query_id->fetch(PDO::FETCH_ASSOC);
	}

	/**
	*/
	function fetch_array($query_id) {
		if (!$query_id) {
			return false;
		}
		return $query_id->fetch(PDO::FETCH_BOTH);
	}

	/**
	*/
	function fetch_object($query_id) {
		if (!$query_id) {
			return false;
		}
		return $query_id->fetch(PDO::FETCH_OBJ);
	}

	/**
	*/
	function real_escape_string($string) {
		return $this->db_connect_id ? substr($this->db_connect_id->quote($string), 1, -1) : addslashes($string);
	}

	/**
	*/
	function free_result($query_id = 0) {
		if (!$query_id) {
			return false;
		}
		$query_id = null;
		return true;
	}

	/**
	*/
	function error() {
		if ($this->db_connect_id) {
			$info = $this->db_connect_id->errorInfo();
			return array(
				'message'	=> $info[2],
				'code'		=> $info[1],
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
		return $this->db_connect_id->beginTransaction();
	}

	/**
	* End a transaction
	*/
	function commit() {
		return $this->db_connect_id->commit();
	}

	/**
	* Rollback a transaction
	*/
	function rollback() {
		return $this->db_connect_id->rollBack();
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
		return $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
		return $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_INFO);
	}

	/**
	*/
	function meta_columns($table) {
		$cols = array();
		$sql = 'PRAGMA table_info('.$table.')';
		$q = $this->query($sql);
		while ($a = $this->fetch_assoc($q)) {
			$name = $a['name'];
			$cols[$name] = $a;
		}
		return $cols;
		$retarr = array();
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
