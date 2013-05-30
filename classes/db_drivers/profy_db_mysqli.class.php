<?php

/**
* MySQL4.1.x db class
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_db_mysqli {

	/** @var @conf_skip */
	var $db_connect_id		= null;
	/** @var @conf_skip */
	var $query_result		= null;
	/** @var @conf_skip */
	var $num_queries		= 0;
	/** @var @conf_skip */
	var $in_transaction		= 0;
	/** @var @conf_skip */
	var $mTrxLevel			= 0;
	/** @var @conf_skip */
	var $META_TABLES_SQL	= "SHOW TABLES";	
	/** @var @conf_skip */
	var $META_COLUMNS_SQL	= "SHOW COLUMNS FROM %s";
    /** @var @conf_skip */
    var $DEF_CHARSET        = "utf8";
    /** @var @conf_skip */
    var $DEF_PORT           = 3306;
    /** @var @conf_skip */
    var $SQL_NO_CACHE		= false;

	/**
	*/
	function __construct($server, $user, $password, $database, $persistency = false, $use_ssl = false, $port = "", $socket = "", $charset = "") {
		$this->persistency	= $persistency;
		$this->user			= $user;
		$this->password		= $password;
		$this->server		= $server;
		$this->dbname		= $database;
		$this->port			= $port ? $port : $DEF_PORT;
		$this->socket		= $socket;

		$this->db_connect_id = mysqli_init();
		if (!$this->db_connect_id) {
			return false;
		}
		mysqli_options($this->db_connect_id, MYSQLI_OPT_CONNECT_TIMEOUT, 2);

		$connected = mysqli_real_connect($this->db_connect_id, $this->server, $this->user, $this->password, $this->dbname, $this->port, $this->socket, $use_ssl ? MYSQL_CLIENT_SSL : 0);
		if (!$connected) {
			return false;
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
		if (!$this->db_connect_id) {
			return false;
		}
		// Commit any remaining transactions
		if ($this->in_transaction) {
			mysqli_query($this->db_connect_id, "COMMIT");
		}
		return mysqli_close($this->db_connect_id);
	}

	/**
	* Base query method
	*/
	function query($query = "", $transaction = false) {
		// Remove any pre-existing queries
		unset($this->query_result);
		if ($query == "") {
			return false;
		}
		$this->num_queries++;
		$this->query_result = mysqli_query($this->db_connect_id, $query);
		$query_error = mysqli_error($this->db_connect_id);
		if ($query_error) {
			$query_error_code = mysqli_errno($this->db_connect_id);
			conf_add('http_headers::X-Details','ME=('.$query_error_code.') '.$query_error);
		}
		return $this->query_result;
	}

	/**
	* Unbuffered query method
	*/
	function unbuffered_query($query = "") {
		mysqli_unbuffered_query($this->db_connect_id, $query);
	}

	/**
	* Multi query method (specific for this driver)
	*/
	function _multi_query($query = "") {
		if ($query == "") {
			return false;
		}
		$this->num_queries++;
		return mysqli_multi_query($this->db_connect_id, $query);
	}

	/**
	* Other query methods
	*/
	function num_rows($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if (!$query_id) {
			return false;
		}
		return mysqli_num_rows($query_id);
	}

	/**
	* Affected Rows
	*/
	function affected_rows() {
		return $this->db_connect_id ? mysqli_affected_rows($this->db_connect_id) : false;
	}

	/**
	* Insert Id
	*/
	function insert_id() {
		return $this->db_connect_id ? mysqli_insert_id($this->db_connect_id) : false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if (!$query_id) {
			return false;
		}
		return mysqli_fetch_row($query_id);
	}

	/**
	* Fetch Assoc
	*/
	function fetch_assoc($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if (!$query_id) {
			return false;
		}
		return mysqli_fetch_assoc($query_id);
	}

	/**
	* Fetch Array
	*/
	function fetch_array($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if (!$query_id) {
			return false;
		}
		return mysqli_fetch_array($query_id);
	}

	/**
	* Real Escape String
	*/
	function real_escape_string($string) {
		if (!$this->db_connect_id) {
			return addcslashes($string);
		}
		return mysqli_real_escape_string($this->db_connect_id, $string);
	}

	/**
	* Free Result
	*/
	function free_result($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if (!$query_id) {
			return false;
		}
		mysqli_free_result($query_id);
		return true;
	}

	/**
	* Error
	*/
	function error() {
		$result['message'] = mysqli_error($this->db_connect_id);
		$result['code'] = mysqli_errno($this->db_connect_id);
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
			$this->enclose_field_name(eval("return dbt_".$table.";")).
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
		// Prepare column names and values
		$tmp_data = array();
		foreach ((array)$data as $k => $v) {
			$tmp_data[$k] = $this->enclose_field_name($k)." = ".$this->enclose_field_value($v);
		}
		// build the query
		$sql = "UPDATE ".$this->enclose_field_name(@eval("return dbt_".$table.";")).
			" SET ".implode(', ', $tmp_data). (!empty($where) ? " WHERE ".$where : '');
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
	* Prepare statement
	*/
	function prepare($query) {
		return mysqli_prepare($this->db_connect_id, $query);
	}

	/**
	* Bind statement param
	*/
	function bind_params($stmt, $data = array()) {
		foreach ((array)$data as $k => $v) {
			$var_type = substr($k, 0, 1);
			$var_name = substr($k, 2);
			$types_string .= $var_type;
			$params[]	= "\$data['".$k."']";
		}
		return eval("return mysqli_stmt_bind_param(\$stmt, \"".$types_string."\", ".implode(",", $params).");");
	}

	/**
	* Execute statement
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

	/**
	*/
	function get_server_version() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysqli_get_server_info($this->db_connect_id);
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
		return mysqli_get_host_info($this->db_connect_id);
	}
}
