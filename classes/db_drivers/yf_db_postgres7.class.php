<?php

/**
* Postgres7 db class
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_db_postgres7 {

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
	var $rownum				= array();
	/** @var @conf_skip */
	var $num_queries		= 0;

	/** @var @conf_skip */
    var $META_TABLES_SQL	= "SELECT tablename,'T' FROM pg_tables WHERE tablename NOT LIKE 'pg\_%'
		AND tablename NOT IN ('sql_features', 'sql_implementation_info', 'sql_languages',
		 'sql_packages', 'sql_sizing', 'sql_sizing_profiles') 
		UNION 
    	    SELECT viewname,'V' FROM pg_views WHERE viewname NOT LIKE 'pg\_%'";

	/** @var @conf_skip */
	var $META_COLUMNS_SQL	= "SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum 
		FROM pg_class c, pg_attribute a,pg_type t 
		WHERE relkind IN ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) AND a.attname NOT LIKE '....%%'
		AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum";

	/**
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false) {
		$this->connect_string = "";
		if (strlen($sqluser)) $this->connect_string .= "user=".$sqluser." ";
		if (strlen($sqlpassword)) $this->connect_string .= "password=".$sqlpassword." ";
		if ($sqlserver) {
			if (preg_match('#:#', $sqlserver)) {
				list($sqlserver, $sqlport) = split(":", $sqlserver);
				$this->connect_string .= "host=".$sqlserver." port=".$sqlport." ";
			} elseif ($sqlserver != "localhost") $this->connect_string .= "host=".$sqlserver." ";
		}
		if ($database) {
			$this->dbname = $database;
			$this->connect_string .= "dbname=".$database;
		}
		$this->persistency = $persistency;
		$this->db_connect_id = $this->persistency ? pg_pconnect($this->connect_string) : pg_connect($this->connect_string);
		return $this->db_connect_id ? $this->db_connect_id : false;
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			// Commit any remaining transactions
			if ($this->in_transaction) @pg_exec($this->db_connect_id, "COMMIT");
			if ($this->query_result) @pg_freeresult($this->query_result);
			return @pg_close($this->db_connect_id);
		} else return false;
	}

	/**
	* Query method
	*/
	function query($query = "", $transaction = false) {
		// Remove any pre-existing queries
		unset($this->query_result);
		if ($query != "") {
			$this->num_queries++;
			$query = str_replace("`", "\"", $query);
			$query = preg_replace("/LIMIT ([0-9]+),([ 0-9]+)/", "LIMIT \\2 OFFSET \\1", $query);
			if ($transaction == BEGIN_TRANSACTION && !$this->in_transaction) {
				$this->in_transaction = TRUE;
				if (!@pg_exec($this->db_connect_id, "BEGIN")) return false;
			}
			$this->query_result = @pg_exec($this->db_connect_id, $query);
			if ($this->query_result) {
				if ($transaction == END_TRANSACTION)	{
					$this->in_transaction = false;
					if (!@pg_exec($this->db_connect_id, "COMMIT")) {
						@pg_exec($this->db_connect_id, "ROLLBACK");
						return false;
					}
				}
				$this->last_query_text[$this->query_result] = $query;
				$this->rownum[$this->query_result] = 0;
				unset($this->row[$this->query_result]);
				unset($this->rowset[$this->query_result]);
				return $this->query_result;
			} else {
				if ($this->in_transaction) @pg_exec($this->db_connect_id, "ROLLBACK");
				$this->in_transaction = false;
				return false;
			}
		} else {
			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@pg_exec($this->db_connect_id, "COMMIT")) {
					@pg_exec($this->db_connect_id, "ROLLBACK");
					return false;
				}
			}
			return true;
		}
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
		if ($query_id && $this->last_query_text[$query_id] != "") {
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
			$cols[$k] = $this->enclose_field_name($v);
		}
		// build the query
		$sql = "INSERT INTO ".
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
		$version = pg_version();
		return $version['server_version'];
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
