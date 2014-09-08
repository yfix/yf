<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_sqlite extends yf_db_driver_pdo {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function __construct(array $params) {
		if (!extension_loaded('pdo_sqlite')) {
			trigger_error('YF PDO SQLite db driver require missing php extension pdo_sqlite', E_USER_ERROR);
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
		$dsn = 'sqlite:'.$this->params['name'];
		$attrs = array();
		$attrs[PDO::ATTR_TIMEOUT] = 2;
		if ($this->params['persist']) {
			$attrs[PDO::ATTR_PERSISTENT] = true;
		}
		$this->db_connect_id = new PDO($dsn, null, null, $attrs);
		$pdo = &$this->db_connect_id;
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
	function affected_rows($query_id = false) {
		return $this->_last_query_id ? $this->_last_query_id->rowCount() : false;
	}

	/**
	*/
	function insert_id($query_id = false) {
		return $this->db_connect_id->lastInsertId();
	}

	/**
	*/
	function fetch_row($query_id) {
		return $query_id ? $query_id->fetch(PDO::FETCH_NUM) : false;
	}

	/**
	*/
	function fetch_assoc($query_id) {
		return $query_id ? $query_id->fetch(PDO::FETCH_ASSOC) : false;
	}

	/**
	*/
	function fetch_array($query_id) {
		return $query_id ? $query_id->fetch(PDO::FETCH_BOTH) : false;
	}

	/**
	*/
	function fetch_object($query_id) {
		return $query_id ? $query_id->fetch(PDO::FETCH_OBJ) : false;
	}

	/**
	*/
	function real_escape_string($string) {
		return $this->db_connect_id ? substr($this->db_connect_id->quote($string), 1, -1) : addslashes($string);
	}

	/**
	*/
	function free_result($query_id = false) {
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
	*/
	function begin() {
		return $this->db_connect_id->beginTransaction();
	}

	/**
	*/
	function commit() {
		return $this->db_connect_id->commit();
	}

	/**
	*/
	function rollback() {
		return $this->db_connect_id->rollBack();
	}

	/**
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
		return $this->db_connect_id ? $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_VERSION) : false;
	}

	/**
	*/
	function get_host_info() {
		return $this->db_connect_id ? $this->db_connect_id->getAttribute(PDO::ATTR_SERVER_INFO) : false;
	}
}
