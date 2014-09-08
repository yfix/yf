<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_pqsql extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id = null;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function __construct(array $params) {
		if (!function_exists('pg_connect')) {
			trigger_error('Postgres db driver require missing php extension pgsql', E_USER_ERROR);
			return false;
		}
		$this->params = $params;
		$this->connect();
		return $this->db_connect_id;
	}

	/**
	*/
	function connect() {
		$dsn = 'host='.$this->params['host'].' '
			. ($this->params['port'] ? ' port='.$this->params['port'].' ' : '');
		if (strlen($this->params['user'])) {
			$dsn .= ' user='.$this->params['user'].' ';
		}
		if (strlen($this->params['pswd'])) {
			$dsn .= ' password='.$this->params['pswd'].' ';
		}
#user = postgres
#pswd = ""
#port = 5432
#db = template1
		$db_name = $this->params['name'] ?: 'template1';
		$dsn .= ' dbname='.$db_name.' ';
		$dsn .= ' connect_timeout=5 ';

		$this->db_connect_id = $this->params['persist'] ? pg_pconnect($dsn) : pg_connect($dsn);
		if (!$this->db_connect_id) {
			$this->_connect_error = 'cannot_connect_to_server';
			return $this->db_connect_id;
		}
/*
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
*/
		return $this->db_connect_id;
	}

	/**
	*/
	function select_db($name) {
// TODO
		return true;
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			// Commit any remaining transactions
			if ($this->in_transaction) @pg_exec($this->db_connect_id, 'COMMIT');
			if ($this->query_result) @pg_freeresult($this->query_result);
			return @pg_close($this->db_connect_id);
		} else return false;
	}

	/**
	* Query method
	*/
	function query($query) {
		// Remove any pre-existing queries
		unset($this->query_result);
		if ($query != '') {
			$this->num_queries++;
			$query = str_replace('`', '"', $query);
			$query = preg_replace('/LIMIT ([0-9]+),([ 0-9]+)/', "LIMIT \\2 OFFSET \\1", $query);
			if ($transaction == BEGIN_TRANSACTION && !$this->in_transaction) {
				$this->in_transaction = TRUE;
				if (!@pg_exec($this->db_connect_id, 'BEGIN')) return false;
			}
			$this->query_result = @pg_exec($this->db_connect_id, $query);
			if ($this->query_result) {
				if ($transaction == END_TRANSACTION)	{
					$this->in_transaction = false;
					if (!@pg_exec($this->db_connect_id, 'COMMIT')) {
						@pg_exec($this->db_connect_id, 'ROLLBACK');
						return false;
					}
				}
				$this->last_query_text[$this->query_result] = $query;
				$this->rownum[$this->query_result] = 0;
				unset($this->row[$this->query_result]);
				unset($this->rowset[$this->query_result]);
				return $this->query_result;
			} else {
				if ($this->in_transaction) @pg_exec($this->db_connect_id, 'ROLLBACK');
				$this->in_transaction = false;
				return false;
			}
		} else {
			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@pg_exec($this->db_connect_id, 'COMMIT')) {
					@pg_exec($this->db_connect_id, 'ROLLBACK');
					return false;
				}
			}
			return true;
		}
	}

	/**
	* Very simple emulation of the mysqli multi_query
	*/
	function multi_query($queries = array()) {
		$result = array();
		foreach((array)$queries as $k => $sql) {
			$result[$k] = $this->query($sql);
		}
		return $result;
	}

	/**
	* Unbuffered query method
	*/
	function unbuffered_query($query = '') {
		return $this->query($query);
	}

	/**
	* Other query methods
	*/
	function num_rows($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		return $query_id ? @pg_numrows($query_id) : false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
/*
		if (empty($this->rownum[$query_id])) {
			return false;
		}
*/
		if ($query_id) {
			$this->row = @pg_fetch_array($query_id/*, $this->rownum[$query_id]*/);
			if ($this->row) {
				$this->rownum[$query_id]++;
				return $this->row;
			}
		}
		return false;
	}

	/**
	* Fetch Assoc
	*/
	function fetch_assoc($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
/*
		if (empty($this->rownum[$query_id])) {
			return false;
		}
*/
		if ($query_id) {
			$this->row = @pg_fetch_assoc($query_id/*, $this->rownum[$query_id]*/);
			if ($this->row) {
				$this->rownum[$query_id]++;
				return $this->row;
			}
		}
		return false;
	}

	/**
	* Insert Id
	*/
	function insert_id() {
		$query_id = $this->query_result;
		if ($query_id && $this->last_query_text[$query_id] != '') {
			if (preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is", $this->last_query_text[$query_id], $tablename))	{
				$query = "SELECT currval('" . $tablename[1] . "_id_seq') AS last_value";
				$temp_q_id =  @pg_exec($this->db_connect_id, $query);
				if (!$temp_q_id) return false;
				$temp_result = @pg_fetch_array($temp_q_id, 0, PGSQL_ASSOC);
				return ( $temp_result ) ? $temp_result['last_value'] : false;
			}
		}
		return false;
	}

	/**
	* Affected Rows
	*/
	function affected_rows($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		return $query_id ? @pg_cmdtuples($query_id) : false;
	}

	/**
	* Real Escape String
	*/
	function real_escape_string($string) {
		return pg_escape_string($string);
	}

	/**
	* Free Result
	*/
	function free_result($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		return $query_id ? @pg_freeresult($query_id) : false;
	}

	/**
	* Error
	*/
	function error($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		$result['message'] = @pg_errormessage($this->db_connect_id);
		$result['code'] = -1;
		return $result;
	}

	/**
	* Return database-specific limit of returned rows
	*/
	function limit($count, $offset) {
// TODO: make code cross-database
/*
		if ($count > 0) {
			$offset = ($offset > 0) ? $offset : 0;
			$sql .= 'LIMIT '.$offset.', '.$count;
		}
		return $sql;
*/
	}

	/**
	* Enclose field names
	*/
	function escape_key($data) {
		$data = '"'.$data.'"';
		return $data;
	}

	/**
	* Enclose field values
	*/
	function escape_val($data) {
		$data = '\''.$data.'\'';
		return $data;
	}

	/**
	*/
	function get_server_version() {
		if (!$this->db_connect_id) {
			return false;
		}
		$version = pg_version();
		return $version['server_version'];
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
// TODO
		return '';
	}
}
