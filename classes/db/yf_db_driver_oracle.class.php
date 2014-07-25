<?php

load('db_driver', 'framework', 'classes/db/');
class yf_db_driver_oracle extends yf_db_driver {

	/** @var @conf_skip */
	public $db_connect_id		= null;
	/** @var @conf_skip */
	public $query_result		= null;
	/** @var @conf_skip */
	public $in_transaction		= 0;
	/** @var @conf_skip */
	public $row					= array();
	/** @var @conf_skip */
	public $rowset				= array();
	/** @var @conf_skip */
	public $num_queries			= 0;
	/** @var @conf_skip */
	public $last_query_text		= '';
	/** @var @conf_skip */
	public $replace_quote		= "''";
	/** @var @conf_skip */
	public $no_null_strings		= 1;
	/** @var @conf_skip */
	public $META_TABLES_SQL		= 'select table_name,table_type from cat where table_type in (\'TABLE\',\'VIEW\')';
	/** @var @conf_skip */
	public $META_COLUMNS_SQL	= 'select cname,coltype,width from col where tname=\'%s\' order by colno';
	/** @var @conf_skip */
	public $META_DATABASES_SQL	= 'SELECT USERNAME FROM ALL_USERS WHERE USERNAME NOT IN (\'SYS\',\'SYSTEM\',\'DBSNMP\',\'OUTLN\') ORDER BY 1';
	/** @var @conf_skip */
	public $_genIDSQL			= 'SELECT (%s.nextval) FROM DUAL';

	/**
	* Constructor
	*/
	function __construct(array $params) {
		if (!function_exists('OCINLogon')) {
			trigger_error('Oracle db driver require missing php extension OCI', E_USER_ERROR);
			return false;
		}
		$this->params = $params;
		if ($this->params['persistency']) {
			$this->db_connect_id = @OCIPLogon($this->params['user'], $this->params['pswd'], $this->params['host']);
		} else {
			$this->db_connect_id = @OCINLogon($this->params['user'], $this->params['pswd'], $this->params['host']);
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
	function query($query = '') {
		if (!$this->db_connect_id) {
			return false;
		}
// FIXME: make this more accurate
		// Remove MySQL-style quotes
		$query = str_replace('`','"',$query);
		$query = str_replace(' AS ',' ',$query);
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
		$result_row = '';
		$result = @OCIFetchInto($query_id, $result_row, OCI_ASSOC + OCI_RETURN_NULLS);
		if ($result_row == '') {
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
		if ($query_id && $this->last_query_text[$query_id] != '') {
			if (preg_match('#^(INSERT{1}|^INSERT INTO{1})[[:space:]]["]?([a-zA-Z0-9\_\-]+)["]?#i', $this->last_query_text[$query_id], $tablename)) {
				$query = 'SELECT '.$tablename[2].'_id_seq.currval FROM DUAL';
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
#		return str_replace('\'', $this->replace_quote, $string);
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
	function meta_tables($DB_PREFIX = '') {
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

			$fld['name']= $A[0];
			$type		= $A[1];

			// split type into type(length):
			if ($FULL_INFO) {
				$fld['scale'] = null;
			}
			if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
				if ($FULL_INFO) {
					$fld['scale'] = is_numeric($query_array[3]) ? $query_array[3] : -1;
				}
			} elseif (preg_match('/^(.+)\((\d+)/', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $query_array)) {
				$fld['type'] = $query_array[1];
				$fld['max_length'] = max(array_map('strlen',explode(',',$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld['max_length'] = ($fld['max_length'] == 0 ? 1 : $fld['max_length']);
			} else {
				$fld['type'] = $type;
				$fld['max_length'] = -1;
			}

			if ($FULL_INFO) {
				$fld['not_null']		= ($A[2] != 'YES');
				$fld['primary_key']		= ($A[3] == 'PRI');
				$fld['auto_increment']	= (strpos($A[5], 'auto_increment') !== false);
				$fld['binary']			= (strpos($type,'blob') !== false);
				$fld['unsigned']		= (strpos($type,'unsigned') !== false);
				if (!$fld['binary']) {
					$d = $A[4];
					if ($d != '' && $d != 'NULL') {
						$fld['has_default'] = true;
						$fld['default_value'] = $d;
					} else {
						$fld['has_default'] = false;
					}
				}
			}

			if ($KEYS_NUMERIC) {
				$retarr[] = $fld;
			} else {
				$retarr[strtolower($fld['name'])] = $fld;
			}
		}
		return $retarr;
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
}
