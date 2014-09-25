<?php

load('db_driver_pdo', 'framework', 'classes/db/');
class yf_db_driver_pdo_sqlite extends yf_db_driver_pdo {

	/** @var @conf_skip */
	public $db_connect_id		= null;

	/**
	*/
	function __construct(array $params) {
		if (!extension_loaded('pdo_sqlite')) {
			trigger_error('YF PDO SQLite db driver require missing php extension pdo_sqlite', E_USER_ERROR);
			return false;
		}
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
	function real_escape_string($string) {
		return $this->db_connect_id ? substr($this->db_connect_id->quote($string), 1, -1) : addslashes($string);
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
}
