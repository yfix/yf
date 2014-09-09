<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_mysqli extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('mysqli_init')) {
			trigger_error('YF MySQLi db driver require missing php extension mysqli', E_USER_ERROR);
			return false;
		}
		$params['port'] = $params['port'] ?: 3306;
		if ($params['socket'] && !file_exists($params['socket'])) {
			$params['socket'] = '';
		}
		$params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : 'utf8');
		$this->params = $params;
		$this->connect();
		if ($params['charset']) {
			// See http://php.net/manual/en/mysqlinfo.concepts.charset.php
			mysqli_set_charset($this->db_connect_id, 'utf8');
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
		return $this->db_connect_id ? mysqli_select_db($this->db_connect_id, $name) : false;
	}

	/**
	*/
	function close() {
		return mysqli_close($this->db_connect_id);
	}

	/**
	*/
	function query($query) {
		return $this->db_connect_id && strlen($query) ? mysqli_query($this->db_connect_id, $query) : false;
	}

	/**
	*/
	function num_rows($query_id) {
		return $query_id ? mysqli_num_rows($query_id) : false;
	}

	/**
	*/
	function affected_rows($query_id = false) {
		return $this->db_connect_id ? mysqli_affected_rows($this->db_connect_id) : false;
	}

	/**
	*/
	function insert_id($query_id = false) {
		return $this->db_connect_id ? mysqli_insert_id($this->db_connect_id) : false;
	}

	/**
	*/
	function fetch_row($query_id) {
		return $query_id ? mysqli_fetch_row($query_id) : false;
	}

	/**
	*/
	function fetch_assoc($query_id) {
		return $query_id ? mysqli_fetch_assoc($query_id) : false;
	}

	/**
	*/
	function fetch_array($query_id) {
		return $query_id ? mysqli_fetch_array($query_id) : false;
	}

	/**
	*/
	function fetch_object($query_id) {
		return $query_id ? mysqli_fetch_object($query_id) : false;
	}

	/**
	*/
	function real_escape_string($string) {
		return $this->db_connect_id ? mysqli_real_escape_string($this->db_connect_id, $string) : addslashes($string);
	}

	/**
	*/
	function free_result($query_id) {
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
	function begin() {
		return $this->query('START TRANSACTION');
	}

	/**
	*/
	function commit() {
		return $this->query('COMMIT');
	}

	/**
	*/
	function rollback() {
		return $this->query('ROLLBACK');
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
		return $this->db_connect_id ? mysqli_get_server_info($this->db_connect_id) : false;
	}

	/**
	*/
	function get_host_info() {
		return $this->db_connect_id ? mysqli_get_host_info($this->db_connect_id) : false;
	}

	/**
	*/
	function prepare($query) {
		return mysqli_prepare($this->db_connect_id, $query);
	}

	/**
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
}
