<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_pgsql extends yf_db_driver_pdo {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function __construct(array $params) {
		if (!extension_loaded('pdo_pgsql')) {
			trigger_error('YF PDO PgSQL db driver require missing php extension pdo_pgsql', E_USER_ERROR);
			return false;
		}
		$params['port'] = $params['port'] ?: $this->DEF_PORT;
		$this->params = $params;
		$this->connect();
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$dsn = 'pgsql:host='.$this->params['host'];
		if ($this->params['port'] && $this->params['port'] != $this->DEF_PORT) {
			$dsn .= ';port='.$this->params['port'];
		}
		$dsn .= ';dbname='.($this->params['name'] ?: 'template1');
		$attrs = array();
		$attrs[PDO::ATTR_TIMEOUT] = 2;
		if ($this->params['persist']) {
			$attrs[PDO::ATTR_PERSISTENT] = true;
		}
		$this->db_connect_id = new PDO($dsn, $this->params['user'], $this->params['pswd'], $attrs);
		$pdo = &$this->db_connect_id;
		$pdo->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, true);

		if (!$this->db_connect_id) {
			$this->_connect_error = 'cannot_connect_to_server';
			return $this->db_connect_id;
		}
		return $this->db_connect_id;
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
		return $query_id ? $query_id->rowCount() : false;
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
		return addslashes($string);
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
			return 'LIMIT '.$count. ($offset > 0 ? ' OFFSET '.$offset : '');
		}
		return false;
	}

	/**
	*/
	function escape_key($data) {
		return '"'.$data.'"';
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
