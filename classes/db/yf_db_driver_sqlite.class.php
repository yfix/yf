<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_sqlite extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;
	/** @var @conf_skip */
	public $query_result		= null;
	/** @var @conf_skip */
	public $META_TABLES_SQL		= '';	
	/** @var @conf_skip */
	public $META_COLUMNS_SQL	= '';

	/**
	*/
	function __construct(array $params) {
		if (!class_exists('SQLite3')) {
			trigger_error('SQLite db driver require missing php extension sqlite3', E_USER_ERROR);
			return false;
		}
		$this->params = $params;
		$server = $this->params['host'] . (($this->params['port']) ? ':' . $this->params['port'] : '');

		$error = '';
#		$this->db_connect_id = ($this->persistency) ? @sqlite_popen($server, 0666, $error) : @sqlite_open($server, 0666, $error);
		if ($this->db_connect_id) {
#			@sqlite_query('PRAGMA short_column_names = 1', $this->db_connect_id);
		}
		return ($this->db_connect_id) ? true : array('message' => $error);
	}

	/**
	* Base query method
	*/
	function query($query = '') {
		if ($query == '') return false;

		$this->query_result = false;
		$this->sql_add_num_queries($this->query_result);

		if (!$this->query_result) {
#			if (($this->query_result = @sqlite_query($query, $this->db_connect_id)) === false) {
				$this->sql_error($query);
			} elseif (strpos($query, 'SELECT') === 0 && $this->query_result) {
				$this->open_queries[(int) $this->query_result] = $this->query_result;
			}
		}
		return ($this->query_result) ? $this->query_result : false;
	}

	/**
	*/
	function close() {
#		return @sqlite_close($this->db_connect_id);
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
	* Unbuffered query
	*/
	function unbuffered_query($query = '') {
		return $this->query($query);
	}

	/**
	* Return number of rows
	* Not used within core code
	*/
	function num_rows($query_id = false) {
		if (!$query_id)	{
			$query_id = $this->query_result;
		}
#		return ($query_id) ? @sqlite_num_rows($query_id) : false;
	}

	/**
	* Return number of affected rows
	*/
	function affected_rows() {
#		return ($this->db_connect_id) ? @sqlite_changes($this->db_connect_id) : false;
	}

	/**
	* Fetch current row
	*/
	function fetch_row($query_id = false) {
		if (!$query_id)	{
			$query_id = $this->query_result;
		}
#		return ($query_id) ? @sqlite_fetch_array($query_id, SQLITE_ASSOC) : false;
	}

	/**
	* Fetch field
	* if rownum is false, the current row is used, else it is pointing to the row (zero-based)
	*/
	function fetch_field($field, $rownum = false, $query_id = false)	{
		if (!$query_id)	{
			$query_id = $this->query_result;
		}
		if ($query_id) {
			if ($rownum === false) {
#				return @sqlite_column($query_id, $field);
			} else {
				$this->sql_rowseek($rownum, $query_id);
#				return @sqlite_column($query_id, $field);
			}
		}
		return false;
	}

	/**
	* Seek to given row number
	* rownum is zero-based
	*/
	function row_seek($rownum, $query_id = false) {
		if (!$query_id)	{
			$query_id = $this->query_result;
		}
#		return ($query_id) ? @sqlite_seek($query_id, $rownum) : false;
	}

	/**
	* Get last inserted id after insert statement
	*/
	function insert_id() {
#		return ($this->db_connect_id) ? @sqlite_last_insert_rowid($this->db_connect_id) : false;
	}

	/**
	* Free sql result
	*/
	function free_result($query_id = false) {
		return true;
	}

	/**
	* Escape string used in sql query
	*/
	function real_escape_string($msg) {
#		return @sqlite_escape_string($msg);
	}

	/**
	* return sql error array
	* @access: private
	*/
	function error() {
		return array(
#			'message'	=> @sqlite_error_string(@sqlite_last_error($this->db_connect_id)),
#			'code'		=> @sqlite_last_error($this->db_connect_id)
		);
	}

	/**
	* Build LIMIT query
	*/
// TODO: convert to number of argumenets like in mysql
	function limit($query, $total, $offset = 0, $cache_ttl = 0) {
		if ($query == '') {
			return false;
		}
		$this->query_result = false; 
		// if $total is set to 0 we do not want to limit the number of rows
		if ($total == 0) {
			$total = -1;
		}
		$query .= PHP_EOL.' LIMIT ' . ((!empty($offset)) ? $offset . ', ' . $total : $total);
		return $this->sql_query($query, $cache_ttl); 
	}

	/**
	* SQL Transaction
	* @access: private
	*/
	function _sql_transaction($status = 'begin') {
		switch ($status) {
			case 'begin':
#				return @sqlite_query('BEGIN', $this->db_connect_id);
				break;
			case 'commit':
#				return @sqlite_query('COMMIT', $this->db_connect_id);
				break;
			case 'rollback':
#				return @sqlite_query('ROLLBACK', $this->db_connect_id);
				break;
		}
		return true;
	}

	/**
	* Build db-specific query data
	* @access: private
	*/
	function _custom_build($stage, $data) {
		return $data;
	}

	/**
	*/
	function get_server_version() {
		if (!$this->db_connect_id) {
			return false;
		}
// TODO
		return '';
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

	/**
	*/
	function ping() {
		if (!$this->db_connect_id) {
			return false;
		}
// TODO
		return '';
	}

	/**
	*/
	function begin() {
// TODO
	}

	/**
	*/
	function commit() {
// TODO
	}

	/**
	*/
	function rollback() {
// TODO
	}

	/**
	*/
	function connect() {
// TODO
	}

	/**
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false) {
// TODO
	}

	/**
	*/
	function meta_tables($DB_PREFIX = '') {
// TODO
	}

	function escape_key($data) {
		return '"'.addslashes($data).'"';
	}

	function escape_val($data) {
		return '"'.addslashes($data).'"';
	}
	function fetch_array($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
#			return sqlite_fetch_array($query_id);
		}
		return false;
	}
	function fetch_assoc($query_id = 0) {
		return $this->fetch_array($query_id);
	}
}
