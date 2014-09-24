<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_sqlite extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id = null;

	/**
	*/
	function __construct(array $params) {
		if (!class_exists('SQLite3')) {
			trigger_error('YF SQLite db driver require missing php extension sqlite3', E_USER_ERROR);
			return false;
		}
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
	function affected_rows($query_id = false) {
		return $this->db_connect_id ? $this->db_connect_id->changes() : false;
	}

	/**
	*/
	function insert_id($query_id = false) {
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
	function free_result($query_id = false) {
		if ($query_id) {
			$query_id = null;
		}
		return true;
	}

	/**
	*/
	function error() {
		if ($this->db_connect_id) {
			$code = $this->db_connect_id->lastErrorCode();
			$msg = $this->db_connect_id->lastErrorMsg();
			return array(
				'message'	=> $code && $code != 100 ? $msg : null,
				'code'		=> $code && $code != 100 ? $code : null,
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
		return $this->db_connect_id ? $this->db_connect_id->query('BEGIN') : false;
	}

	/**
	*/
	function commit() {
		return $this->db_connect_id ? $this->db_connect_id->query('COMMIT') : false;
	}

	/**
	*/
	function rollback() {
		return $this->db_connect_id ? $this->db_connect_id->query('ROLLBACK') : false;
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
		return '`'.trim($data, '`').'`';
	}

	/**
	*/
	function escape_val($data) {
		return '\''.trim($data, '\'').'\'';
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
}
