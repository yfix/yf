<?php

load('db_driver_pdo', '', 'classes/db/');
class yf_db_driver_pdo_mysql extends yf_db_driver_pdo {

	/** @var @conf_skip */
	public $db_connect_id	= null;
	/** @var string */
	public $SQL_MODE		= '';

	/**
	*/
	function __construct(array $params) {
		if (!extension_loaded('pdo_mysql')) {
			trigger_error('YF PDO MySQL db driver missing php extension pdo_mysql', E_USER_ERROR);
			return false;
		}
		$params['port'] = $params['port'] ?: 3306;
		if ($params['socket'] && !file_exists($params['socket'])) {
			$params['socket'] = '';
		}
		$params['charset'] = $params['charset'] ?: (defined('DB_CHARSET') ? DB_CHARSET : 'utf8');
		$this->params = $params;
		$this->connect();
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$dsn = 'mysql:host='.$this->params['host'];
		if ($this->params['port'] && $this->params['port'] != $this->DEF_PORT) {
			$dsn .= ';port='.$this->params['port'];
		}
		if ($this->params['socket']) {
			$dsn .= ';unix_socket='.$this->params['socket'];
		}
		if ($this->params['charset']) {
			$dsn .= ';charset='.$this->params['charset'];
		}
		$attrs = [];
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
		} else {
			$this->query('SET SQL_MODE="'.$this->real_escape_string($this->SQL_MODE).'"');
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
		return $this->db_connect_id ? (bool)$this->query('USE '.$name) : false;
	}

	/**
	*/
	function real_escape_string($string) {
		if (is_null($string)) {
			return 'NULL';
		} elseif (is_float($string)) {
			return str_replace(',', '.', $string);
		} elseif (is_int($string)) {
			return $string;
		} elseif (is_bool($string)) {
			return (int)$string;
		}
		return _class('db')->_mysql_escape_mimic($string);
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
		if (is_null($data)) {
			return 'NULL';
		}
		return '\''.$data.'\'';
	}

	/**
	*/
	function get_last_warnings() {
		if (!$this->db_connect_id) {
			return false;
		}
		$q = $this->query('SHOW WARNINGS');
		if (!$q) {
			return false;
		}
		$warnings = [];
		// Example: Warning (1264): Data truncated for column 'Name' at row 1
		while ($a = $this->fetch_row($q)) {
			$warnings[] = printf('%s (%d): %s', $a[0], $a[1], $a[2]);
		}
		return $warnings;
	}
}
