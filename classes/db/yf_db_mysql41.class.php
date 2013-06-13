<?php

#load("yf_db_driver.abstract", "classes/db/");
require dirname(__FILE__)."/yf_db_driver.abstract.class.php";

/**
* MySQL4.1.x db class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_db_mysql41 extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;
	/** @var @conf_skip */
	public $query_result		= null;
	/** @var @conf_skip */
	public $num_queries		= 0;
	/** @var @conf_skip */
	public $in_transaction		= 0;
	/** @var @conf_skip */
	public $mTrxLevel			= 0;
	/** @var @conf_skip */
	public $META_TABLES_SQL	= "SHOW TABLES";	
	/** @var @conf_skip */
	public $META_COLUMNS_SQL	= "SHOW COLUMNS FROM %s";
	/** @var @conf_skip */
	public $DEF_CHARSET		= "utf8";
	/** @var @conf_skip */
	public $DEF_PORT		   = 3306;
	/** @var @conf_skip */
	public $SQL_NO_CACHE		= false;

	/**
	* Constructor
	*/
	function __construct($server, $user, $password, $database, $persistency = false, $use_ssl = false, $port = "", $socket = "", $charset = "") {
		$this->persistency	= $persistency;
		$this->user			= $user;
		$this->password		= $password;
		$this->server		= $server;
		$this->dbname		= $database;
		$this->port			= $port ? $port : $DEF_PORT;
		$this->socket		= $socket;
		ini_set('mysql.connect_timeout', 2);
		if (!file_exists($socket)) {
			$this->socket = "";
		}
		if ($this->socket) {
			$connect_host = $this->socket;
		} else {
			$connect_port = $this->port && $this->port != $this->DEF_PORT ? $this->port : "";
			$connect_host = $this->server. ($connect_port ? ":".$connect_port : "");
		}
		$this->db_connect_id = $this->persistency 
			? mysql_pconnect($connect_host, $this->user, $this->password, $use_ssl ? MYSQL_CLIENT_SSL : 0) 
			: mysql_connect($connect_host, $this->user, $this->password, true, $use_ssl ? MYSQL_CLIENT_SSL : 0);

		if (!$this->db_connect_id) {
			conf_add('http_headers::X-Details','ME=(-1) MySql connection error');
			return false;
		}
		if ($this->dbname != "") {
			$dbselect = mysql_select_db($this->dbname, $this->db_connect_id);
			if (!$dbselect) {
				mysql_close($this->db_connect_id);
				$this->db_connect_id = $dbselect;
			}
		}
		if (empty($charset)) {
			$charset = defined("DB_CHARSET") ? DB_CHARSET : $this->DEF_CHARSET;
		}
		if ($charset) {
			$this->query("SET NAMES ". $charset);
		}
		return $this->db_connect_id;
	}

	/**
	* Close transaction
	*/
	function close() {
		if ($this->db_connect_id) {
			// Commit any remaining transactions
			if ($this->in_transaction) {
				mysql_query("COMMIT", $this->db_connect_id);
			}
			return mysql_close($this->db_connect_id);
		}
		return false;
	}

	/**
	* Base query method
	*/
	function query($query = "", $transaction = false) {
		// Remove any pre-existing queries
		unset($this->query_result);
		if ($query != "") {

			$this->num_queries++;
			if ($transaction == BEGIN_TRANSACTION && !$this->in_transaction) {
				$result = mysql_query("BEGIN", $this->db_connect_id);
				if (!$result) return false;
				$this->in_transaction = TRUE;
			}
			$this->query_result = mysql_query($query, $this->db_connect_id);

		} elseif ($transaction == END_TRANSACTION && $this->in_transaction)

			$result = mysql_query("COMMIT", $this->db_connect_id);

		if ($this->query_result) {

			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!mysql_query("COMMIT", $this->db_connect_id)) {
					mysql_query("ROLLBACK", $this->db_connect_id);
					return false;
				}
			}
			return $this->query_result;

		} else {
			$query_error_code = mysql_errno($this->db_connect_id);
			$query_error = mysql_error($this->db_connect_id);
			conf_add('http_headers::X-Details','ME=('.$query_error_code.') '.$query_error);

			if ($this->in_transaction) {
				mysql_query("ROLLBACK", $this->db_connect_id);
				$this->in_transaction = false;
			}
			return false;
		}
	}

	/**
	* Unbuffered query method
	*/
	function unbuffered_query($query = "") {
		mysql_unbuffered_query($query, $this->db_connect_id);
	}

	/**
	* Other query methods
	*/
	function num_rows($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		return $query_id ? mysql_num_rows($query_id) : false;
	}

	/**
	* Affected Rows
	*/
	function affected_rows() {
		return $this->db_connect_id ? mysql_affected_rows($this->db_connect_id) : false;
	}

	/**
	* Insert Id
	*/
	function insert_id() {
		return $this->db_connect_id ? mysql_insert_id($this->db_connect_id) : false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_row($query_id);
		}
		return false;
	}

	/**
	* Fetch Assoc
	*/
	function fetch_assoc($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_assoc($query_id);
		}
		return false;
	}

	/**
	* Fetch Array
	*/
	function fetch_array($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			return mysql_fetch_array($query_id);
		}
		return false;
	}

	/**
	* Real Escape String
	*/
	function real_escape_string($string) {
		return mysql_real_escape_string($string, $this->db_connect_id);
	}

	/**
	* Free Result
	*/
	function free_result($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			mysql_free_result($query_id);
			return true;
		}
		return false;
	}

	/**
	* Error
	*/
	function error() {
		$result['message'] = mysql_error($this->db_connect_id);
		$result['code'] = mysql_errno($this->db_connect_id);
		return $result;
	}

	/**
	* Meta Columns
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false) {
		$retarr = array();

		$Q = $this->query(sprintf($this->META_COLUMNS_SQL, $table));
		while ($A = $this->fetch_row($Q)) {
			$fld = array();

			$fld["name"]= $A[0];
			$type		= $A[1];

			// split type into type(length):
			if ($FULL_INFO) {
				$fld["scale"] = null;
			}
			if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
				$fld["type"] = $query_array[1];
				$fld["max_length"] = is_numeric($query_array[2]) ? $query_array[2] : -1;
				if ($FULL_INFO) {
					$fld["scale"] = is_numeric($query_array[3]) ? $query_array[3] : -1;
				}
			} elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
				$fld["type"] = $query_array[1];
				$fld["max_length"] = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
				$fld["type"] = $query_array[1];
				$fld["max_length"] = max(array_map("strlen",explode(",",$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld["max_length"] = ($fld["max_length"] == 0 ? 1 : $fld["max_length"]);
			} else {
				$fld["type"] = $type;
				$fld["max_length"] = -1;
			}

			if ($FULL_INFO) {
				$fld["not_null"]		= ($A[2] != 'YES');
				$fld["primary_key"]		= ($A[3] == 'PRI');
				$fld["auto_increment"]	= (strpos($A[5], 'auto_increment') !== false);
				$fld["binary"]			= (strpos($type,'blob') !== false);
				$fld["unsigned"]		= (strpos($type,'unsigned') !== false);
				if (!$fld["binary"]) {
					$d = $A[4];
					if ($d != '' && $d != 'NULL') {
						$fld["has_default"] = true;
						$fld["default_value"] = $d;
					} else {
						$fld["has_default"] = false;
					}
				}
			}

			if ($KEYS_NUMERIC) {
				$retarr[] = $fld;
			} else {
				$retarr[strtolower($fld["name"])] = $fld;
			}
		}
		return $retarr;
	}

	/**
	* Meta Tables
	*/
	function meta_tables($DB_PREFIX = "") {
		$Q = $this->query($this->META_TABLES_SQL);
		while ($A = $this->fetch_row($Q)) {
			// Skip tables without prefix of current connection
			if (strlen($DB_PREFIX) && substr($A['0'], 0, strlen($DB_PREFIX)) != $DB_PREFIX) {
				continue;
			}
			$tables[$A['0']] = $A['0'];
		}
		return $tables;
	}

	/**
	* Begin a transaction, or if a transaction has already started, continue it
	*/
	function begin( $fname = 'Database::begin' ) {
		if ( !$this->mTrxLevel ) {
			$this->immediateBegin( $fname );
		} else {
			$this->mTrxLevel++;
		}
	}

	/**
	* End a transaction, or decrement the nest level if transactions are nested
	*/
	function commit( $fname = 'Database::commit' ) {
		if ( $this->mTrxLevel ) {
			$this->mTrxLevel--;
		}
		if ( !$this->mTrxLevel ) {
			$this->immediateCommit( $fname );
		}
	}

	/**
	* Rollback a transaction
	*/
	function rollback( $fname = 'Database::rollback' ) {
		$this->query( 'ROLLBACK', $fname );
		$this->mTrxLevel = 0;
	}

	/**
	* Begin a transaction, committing any previously open transaction
	*/
	function immediateBegin( $fname = 'Database::immediateBegin' ) {
		$this->query( 'BEGIN', $fname );
		$this->mTrxLevel = 1;
	}
	
	/**
	* Commit transaction, if one is open
	*/
	function immediateCommit( $fname = 'Database::immediateCommit' ) {
		$this->query( 'COMMIT', $fname );
		$this->mTrxLevel = 0;
	}

	/**
	* Insert array of values into table
	*/
	function insert($table, $data, $only_sql = false, $replace = false, $DB_CONNECTION, $ignore = false) {
		if (empty($table) || empty($data)) {
			return false;
		}
		if (is_string($replace)) {
			$replace = false;
		}
		$values_array = array();
		// Try to check if array is two-dimensional
		foreach ((array)$data as $cur_row) {
			$is_multiple = is_array($cur_row) ? 1 : 0;
			break;
		}
		// Prepare column names and values
		if ($is_multiple) {
			foreach ((array)$data as $cur_row) {
				if (empty($cols)) {
					$cols	= array_keys($cur_row);
				}
				$cur_values = array_values($cur_row);
				foreach ((array)$cur_values as $k => $v) {
					$cur_values[$k] = $this->enclose_field_value($v);
				}
				$values_array[] = "(".implode(', ', $cur_values)."\r\n)";
			}
		} else {
			$cols	= array_keys($data);
			$values = array_values($data);
			foreach ((array)$values as $k => $v) {
				$values[$k] = $this->enclose_field_value($v);
			}
			$values_array[] = "(".implode(', ', $values)."\r\n)";
		}
		foreach ((array)$cols as $k => $v) {
			$cols[$k] = $this->enclose_field_name($v);
		}
		// build the query
		$sql = ($replace ? "REPLACE" : "INSERT"). ($ignore ? " IGNORE" : "")." INTO ".
			$this->enclose_field_name($table).
			" \r\n(".implode(', ', $cols).") VALUES \r\n".
			implode(", ", $values_array);
		// Return SQL text
		if ($only_sql) {
			return $sql;
		}
		// execute the query
		return $DB_CONNECTION->query($sql);
	}

	/**
	* Replace array of values into table
	*/
	function replace($table, $data, $only_sql = false, $DB_CONNECTION) {
		return $this->insert($table, $data, $only_sql, true, $DB_CONNECTION);
	}

	/**
	* Update table with given values
	*/
	function update($table, $data, $where, $only_sql = false, $DB_CONNECTION) {
		if (empty($table) || empty($data) || empty($where)) {
			return false;
		}
		// $where contains numeric id
		if (is_numeric($where)) {
			$where = "`id`=".intval($where);
		}
		// Prepare column names and values
		$tmp_data = array();
		foreach ((array)$data as $k => $v) {
			if (empty($k)) {
				continue;
			}
			$tmp_data[$k] = $this->enclose_field_name($k)." = ".$this->enclose_field_value($v);
		}
		// build the query
		$sql = "UPDATE ".$this->enclose_field_name($table)
			." SET ".implode(', ', $tmp_data)
			.(!empty($where) ? " WHERE ".$where : '');
		// Return SQL text
		if ($only_sql) {
			return $sql;
		}
		// execute the query
		return $DB_CONNECTION->query($sql);
	}

	/**
	* Return database-specific limit of returned rows
	*/
	function limit($count, $offset) {
		if ($count > 0) {
			$offset = ($offset > 0) ? $offset : 0;
			$sql .= "LIMIT ".$offset.", ".$count;
		}
		return $sql;
	}

	/**
	* Enclose field names
	*/
	function enclose_field_name($data) {
		$data = "`".$data."`";
		return $data;
	}

	/**
	* Enclose field values
	*/
	function enclose_field_value($data) {
		$data = "'".$data."'";
		return $data;
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
