<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_mysql extends yf_db_driver_pdo {

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
	public $HAS_MULTI_QUERY		= false;

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('mysql_connect')) {
			trigger_error('MySQL db driver require missing php extension mysql', E_USER_ERROR);
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
			conf_add('http_headers::X-Details','ME=(-1) MySql connection error');
			return $this->db_connect_id;
		}
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$dsn = 'mysql:host='.$params['host'];
		if ($this->params['port'] && $this->params['port'] != $this->DEF_PORT) {
			$dsn .= ';port='.$this->params['port'];
		}
#		if ($this->params['name']) {
#			$dsn .= ';dbname='.$this->params['name'];
#		}
		if ($this->params['socket']) {
			$dsn .= ';unix_socket='.$this->params['socket'];
		}
		if ($this->params['charset']) {
			$dsn .= ';charset='.$this->params['charset'];
		}
		$attrs = array();
		$attrs[PDO::ATTR_TIMEOUT] = 2;
		if ($this->params['persist']) {
			$attrs[PDO::ATTR_PERSISTENT] = true;
		}
		$this->db_connect_id = new PDO($dsn, $this->params['user'], $this->params['pswd'], $attrs);
		$pdo = &$this->db_connect_id;
		$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
		$pdo->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, 0);
		$pdo->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, 0);

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
		if (!$this->db_connect_id) {
			return false;
		}
		return $this->query('USE '.$name);
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
	function query($query = '') {
		if (!$this->db_connect_id) {
			return false;
		}
		$result = $this->db_connect_id->query($query);
		$this->_last_query_id = $result;
		if (!$result) {
			$error = $this->error();
			$query_error_code = $error['code'];
			$query_error = $error['message'];
			if ($query_error_code) {
				conf_add('http_headers::X-Details','ME=('.$query_error_code.') '.$query_error);
			}
			return false;
		}
		return $result;
	}

	/**
	*/
	function num_rows($query_id) {
		return $query_id ? $query_id->rowCount() : false;
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
		return $query_id->fetch(PDO::FETCH_NUM);
	}

	/**
	*/
	function fetch_assoc($query_id) {
		return $query_id->fetch(PDO::FETCH_ASSOC);
	}

	/**
	*/
	function fetch_array($query_id) {
		return $query_id->fetch(PDO::FETCH_BOTH);
	}

	/**
	*/
	function fetch_object($query_id) {
		return $query_id->fetch(PDO::FETCH_OBJ);
	}

	/**
	*/
	function real_escape_string($string) {
		return addslashes($string);
	}

	/**
	*/
	function free_result($query_id = 0) {
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
}
