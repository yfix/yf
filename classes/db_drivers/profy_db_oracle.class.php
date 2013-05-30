<?php

/**
* Oracle db class
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_db_oracle {

	/** @var @conf_skip */
	var $db_connect_id		= null;
	/** @var @conf_skip */
	var $query_result		= null;
	/** @var @conf_skip */
	var $in_transaction		= 0;
	/** @var @conf_skip */
	var $row				= array();
	/** @var @conf_skip */
	var $rowset				= array();
	/** @var @conf_skip */
	var $num_queries		= 0;
	/** @var @conf_skip */
	var $last_query_text	= "";
	/** @var @conf_skip */
	var $replace_quote		= "''";
	/** @var @conf_skip */
	var $no_null_strings	= 1;

	/** @var @conf_skip */
	var $META_TABLES_SQL	= "select table_name,table_type from cat where table_type in ('TABLE','VIEW')";
	/** @var @conf_skip */
	var $META_COLUMNS_SQL	= "select cname,coltype,width from col where tname='%s' order by colno";
	/** @var @conf_skip */
	var $META_DATABASES_SQL	= "SELECT USERNAME FROM ALL_USERS WHERE USERNAME NOT IN ('SYS','SYSTEM','DBSNMP','OUTLN') ORDER BY 1";
	/** @var @conf_skip */
	var $_genIDSQL			= "SELECT (%s.nextval) FROM DUAL";

	/**
	* Constructor
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database="", $persistency = false) {
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->server = $sqlserver;
		$this->dbname = $database;
		if ($this->persistency) {
			$this->db_connect_id = @OCIPLogon($this->user, $this->password, $this->server);
		} else {
			$this->db_connect_id = @OCINLogon($this->user, $this->password, $this->server);
		}
		if ($this->db_connect_id) {
			return $this->db_connect_id;
		} else {
			return false;
		}
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			// Commit outstanding transactions
			if ($this->in_transaction) {
				OCICommit($this->db_connect_id);
			}
			if ($this->query_result) {
				@OCIFreeStatement($this->query_result);
			}
			$result = @OCILogoff($this->db_connect_id);
			return $result;
		} else {
			return false;
		}
	}

	/**
	* Base query method
	*/
	function query($query = "") {
		if (!$this->db_connect_id) {
			return false;
		}
// FIXME: make this more accurate
		// Remove MySQL-style quotes
		$query = str_replace("`","\"",$query);
		$query = str_replace(" AS "," ",$query);
		// Remove any pre-existing queries
		unset($this->query_result);
		$this->query_result = @OCIParse($this->db_connect_id, $query);
		if (!$this->query_result) {
			return false;
		}
		$result = @OCIExecute($this->query_result);
//		return $this->query_result;
//		return $result;
		return false;
	}

	/**
	* Unbuffered query method
	*/
	function unbuffered_query($query = "") {
		return $this->query($query);
	}

	/**
	* Other query methods
	*/
	function num_rows($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			$result = @OCIFetchStatement($query_id, $this->rowset);
			// OCIFetchStatment kills our query result so we have to execute the statment again
			// if we ever want to use the query_id again.
			@OCIExecute($query_id, OCI_DEFAULT);
			return $result;
		} else {
			return false;
		}
	}

	/**
	* Affected Rows
	*/
	function affected_rows($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			$result = @OCIRowCount($query_id);
			return $result;
		} else {
			return false;
		}
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
		$result_row = "";
		$result = @OCIFetchInto($query_id, $result_row, OCI_ASSOC + OCI_RETURN_NULLS);
		if ($result_row == "") {
			return false;
		}
		for ($i = 0; $i < count($result_row); $i++) {
			list($key, $val) = each($result_row);
			$return_arr[strtolower($key)] = $val;
		}
		$this->row[$query_id] = $return_arr;
		return $this->row[$query_id];
	}

	/**
	* Fetch Assoc
	*/
	function fetch_assoc($query_id = 0) {
// TODO: need to separate results
		return $this->fetch_row($query_id);
	}

	/**
	* Fetch Row
	*/
	function fetch_array($query_id = 0) {
// TODO: need to separate results
		return $this->fetch_row($query_id);
	}

	/**
	* Insert Id
	*/
	function insert_id($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id && $this->last_query_text[$query_id] != "") {
			if (preg_match('#^(INSERT{1}|^INSERT INTO{1})[[:space:]]["]?([a-zA-Z0-9\_\-]+)["]?#i', $this->last_query_text[$query_id], $tablename)) {
				$query = "SELECT ".$tablename[2]."_id_seq.currval FROM DUAL";
				$stmt = @OCIParse($this->db_connect_id, $query);
				@OCIExecute($stmt,OCI_DEFAULT );
				$temp_result = @OCIFetchInto($stmt, $temp_result, OCI_ASSOC+OCI_RETURN_NULLS);
				if ($temp_result) {
					return $temp_result['CURRVAL'];
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	* Real Escape String
	*/
	function real_escape_string($string) {
		return addslashes($string);
#		if ($this->replace_quote[0] == '\\') {
#			$string = str_replace('\\', '\\\\', $string);
#		}
#		return str_replace("'", $this->replace_quote, $string);
	}

	/**
	* Free Result
	*/
	function free_result($query_id = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		if ($query_id) {
			$result = @OCIFreeStatement($query_id);
			return $result;
		} else {
			return false;
		}
	}

	/**
	* Error
	*/
	function error($query_id  = 0) {
		if (!$query_id) {
			$query_id = $this->query_result;
		}
		$result = @OCIError($query_id);
//		$result = @OCIError($this->db_connect_id);
		return $result;
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
	* Insert array of values into table
	*/
	function insert($table, $data, $only_sql = false, $DB_CONNECTION) {
		if (empty($table) || empty($data)) {
			return false;
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
			if ($this->no_null_strings && strlen($v) == 0) {
				$v = ' ';
			}
			$cols[$k] = $this->enclose_field_name($v);
		}
		// build the query
		$sql = "INSERT INTO ".
			$this->enclose_field_name(eval("return dbt_".$table.";")).
			" \r\n(".implode(', ', $cols).") VALUES \r\n".
			implode(", ", $values_array);

// Fix backticks
//$sql = str_replace("`","\`",$sql);
$sql = str_replace("`","",$sql);

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
// TODO: add code here
//		return $this->insert($table, $data, $only_sql, true, $DB_CONNECTION);
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
// TODO: make code cross-database
/*
		if ($count > 0) {
			$offset = ($offset > 0) ? $offset : 0;
			$sql .= "LIMIT ".$offset.", ".$count;
		}
		return $sql;
*/
	}

	/**
	* Enclose field names
	*/
	function enclose_field_name($data) {
		$data = "\"".$data."\"";
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
		return "";
	}

	/**
	*/
	function get_host_info() {
		if (!$this->db_connect_id) {
			return false;
		}
		return "";
	}
}
