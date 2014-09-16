<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_mysql5 extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('mysql_connect')) {
			trigger_error('YF MySQL db driver require missing php extension mysql', E_USER_ERROR);
			return false;
		}
		$params['port'] = $params['port'] ?: 3306;
		if ($params['socket'] && !file_exists($params['socket'])) {
			$params['socket'] = '';
		}
		$params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : 'utf8');
		$this->params = $params;
		ini_set('mysql.connect_timeout', 2);
		$this->connect();
		if ($this->params['charset']) {
			// See http://php.net/manual/en/mysqlinfo.concepts.charset.php
			mysql_set_charset($this->params['charset']);
		}
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		if ($this->params['socket']) {
			$connect_host = $this->params['socket'];
		} else {
			$connect_port = $this->params['port'] && $this->params['port'] != $this->DEF_PORT ? $this->params['port'] : '';
			$connect_host = $this->params['host']. ($connect_port ? ':'.$connect_port : '');
		}
		$this->db_connect_id = $this->params['persist'] 
			? mysql_pconnect($connect_host, $this->params['user'], $this->params['pswd'], $this->params['ssl'] ? MYSQL_CLIENT_SSL : 0) 
			: mysql_connect($connect_host, $this->params['user'], $this->params['pswd'], true, $this->params['ssl'] ? MYSQL_CLIENT_SSL : 0);

		if (!$this->db_connect_id) {
			$this->_connect_error = 'cannot_connect_to_server';
			return $this->db_connect_id;
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
		return $this->db_connect_id;
	}

	/**
	*/
	function select_db($name) {
		return $this->db_connect_id ? mysql_select_db($name, $this->db_connect_id) : false;
	}

	/**
	*/
	function close() {
		return $this->db_connect_id ? mysql_close($this->db_connect_id) : false;
	}

	/**
	*/
	function query($query) {
		return $this->db_connect_id && strlen($query) ? mysql_query($query, $this->db_connect_id) : false;
	}

	/**
	*/
	function num_rows($query_id) {
		return $query_id ? mysql_num_rows($query_id) : false;
	}

	/**
	*/
	function affected_rows($query_id = false) {
		return $this->db_connect_id ? mysql_affected_rows($this->db_connect_id) : false;
	}

	/**
	*/
	function insert_id($query_id = false) {
		return $this->db_connect_id ? mysql_insert_id($this->db_connect_id) : false;
	}

	/**
	*/
	function fetch_row($query_id) {
		if ($query_id) {
			return mysql_fetch_row($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_assoc($query_id) {
		if ($query_id) {
			return mysql_fetch_assoc($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_array($query_id) {
		if ($query_id) {
			return mysql_fetch_array($query_id);
		}
		return false;
	}

	/**
	*/
	function fetch_object($query_id) {
		if ($query_id) {
			return mysql_fetch_object($query_id);
		}
		return false;
	}

	/**
	*/
	function real_escape_string($string) {
		if (!$this->db_connect_id) {
			return _class('db')->_mysql_escape_mimic($string);
		}
		if (is_float($string)) {
			return str_replace(',', '.', $string);
		} elseif (is_int($string)) {
			return $string;
		} elseif (is_bool($string)) {
			return (int)$string;
		}
		return mysql_real_escape_string($string, $this->db_connect_id);
	}

	/**
	*/
	function free_result($query_id = false) {
		if ($query_id) {
			return mysql_free_result($query_id);
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
}
