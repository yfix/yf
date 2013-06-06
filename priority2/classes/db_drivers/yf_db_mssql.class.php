<?php

/**
* MS SQL db class
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_db_mssql {

	/** @var @conf_skip */
	var $db_connect_id		= null;
	/** @var @conf_skip */
	var $result				= null;

	/** @var @conf_skip */
	var $next_id			= null;
	/** @var @conf_skip */
	var $in_transaction		= 0;

	/** @var @conf_skip */
	var $row				= array();
	/** @var @conf_skip */
	var $rowset				= array();
	/** @var @conf_skip */
	var $limit_offset		= null;
	/** @var @conf_skip */
	var $query_limit_success= null;

	/** @var @conf_skip */
	var $num_queries		= 0;

	/** @var @conf_skip */
	var $META_TABLES_SQL	= 
		"SELECT name,case WHEN type='U' THEN 'T' ELSE 'V' END 
		FROM sysobjects 
		WHERE (type='U' OR type='V') 
			AND (name NOT IN ('sysallocations','syscolumns','syscomments','sysdepends','sysfilegroups','sysfiles','sysfiles1','sysforeignkeys','sysfulltextcatalogs','sysindexes','sysindexkeys','sysmembers','sysobjects','syspermissions','sysprotects','sysreferences','systypes','sysusers','sysalternates','sysconstraints','syssegments','REFERENTIAL_CONSTRAINTS','CHECK_CONSTRAINTS','CONSTRAINT_TABLE_USAGE','CONSTRAINT_COLUMN_USAGE','VIEWS','VIEW_TABLE_USAGE','VIEW_COLUMN_USAGE','SCHEMATA','TABLES','TABLE_CONSTRAINTS','TABLE_PRIVILEGES','COLUMNS','COLUMN_DOMAIN_USAGE','COLUMN_PRIVILEGES','DOMAINS','DOMAIN_CONSTRAINTS','KEY_COLUMN_USAGE','dtproperties'))";
	/** @var @conf_skip */
	var $META_COLUMNS_SQL	= // xtype==61 is datetime
		"SELECT c.name,t.name,c.length,
			(CASE WHEN c.xusertype=61 THEN 0 ELSE c.xprec END),
			(CASE WHEN c.xusertype=61 THEN 0 ELSE c.xscale END) 
		FROM syscolumns c 
			JOIN systypes t ON t.xusertype=c.xusertype 
			JOIN sysobjects o ON o.id=c.id 
		WHERE o.name='%s'";

	/**
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false) {
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->server = $sqlserver;
		$this->dbname = $database;
		$this->db_connect_id = ( $this->persistency ) ? mssql_pconnect($this->server, $this->user, $this->password) : mssql_connect($this->server, $this->user, $this->password);
		if ($this->db_connect_id && $this->dbname != "") {
			if (!mssql_select_db($this->dbname, $this->db_connect_id)) {
				mssql_close($this->db_connect_id);
				return false;
			}
		}
		return $this->db_connect_id;
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			// Commit any remaining transactions
			if ($this->in_transaction) @mssql_query("COMMIT", $this->db_connect_id);
			return @mssql_close($this->db_connect_id);
		} else return false;
	}

	/**
	* Query method
	*/
	function query($query = "", $transaction = false) {
		// Remove any pre-existing queries
		unset($this->result);
		unset($this->row);
		if ($query != "") {
			$this->num_queries++;
			if ($transaction == BEGIN_TRANSACTION && !$this->in_transaction) {
				if (!mssql_query("BEGIN TRANSACTION", $this->db_connect_id))
					return false;
				$this->in_transaction = TRUE;
			}
			// Does query contain any LIMIT code? If so pull out relevant start and num_results
			// This isn't terribly easy with MSSQL, whatever you do will potentially impact
			// performance compared to an 'in-built' limit
			//
			// Another issue is the 'lack' of a returned true value when a query is valid but has
			// no result set (as with all the other DB interfaces). It seems though that it's
			// 'fair' to say that if a query returns a false result (ie. no resource id) then the
			// SQL was valid but had no result set. If the query returns nothing but the rowcount
			// returns something then there's a problem. This may well be a false assumption though
			// ... needs checking under Windows itself.
			//
			if (preg_match("/^SELECT(.*?)(LIMIT ([0-9]+)[, ]*([0-9]+)*)?$/s", $query, $limits)) {
				$query = $limits[1];
				if (!empty($limits[2])) {
					$row_offset = $limits[4] ? $limits[3] : "";
					$num_rows = $limits[4] ? $limits[4] : $limits[3];
					$query = "TOP " . ( $row_offset + $num_rows ) . $query;
				}
				$this->result = mssql_query("SELECT $query", $this->db_connect_id); 
				if ($this->result) {
					$this->limit_offset[$this->result] = !empty($row_offset) ? $row_offset : 0;
					if ($row_offset > 0) mssql_data_seek($this->result, $row_offset);
				}
			} elseif (preg_match('#^INSERT #i', $query)) {
				if (mssql_query($query, $this->db_connect_id)) {
					$this->result = time() + microtime();
					$result_id = mssql_query("SELECT @@IDENTITY AS id, @@ROWCOUNT as affected", $this->db_connect_id);
					if ($result_id) {
						if ($row = mssql_fetch_array($result_id)) {
							$this->next_id[$this->db_connect_id] = $row['id'];	
							$this->affected_rows[$this->db_connect_id] = $row['affected'];
						}
					}
				}
			} else {
				if (mssql_query($query, $this->db_connect_id)) {
					$this->result = time() + microtime();
					$result_id = mssql_query("SELECT @@ROWCOUNT as affected", $this->db_connect_id);
					if ($result_id) {
						if ($row = mssql_fetch_array($result_id)) 
							$this->affected_rows[$this->db_connect_id] = $row['affected'];
					}
				}
			}
			if (!$this->result) {
				if ($this->in_transaction) {
					mssql_query("ROLLBACK", $this->db_connect_id);
					$this->in_transaction = false;
				}
				return false;
			}
			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@mssql_query("COMMIT", $this->db_connect_id)) {
					@mssql_query("ROLLBACK", $this->db_connect_id);
					return false;
				}
			}
			return $this->result;
		} else {
			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@mssql_query("COMMIT", $this->db_connect_id)) {
					@mssql_query("ROLLBACK", $this->db_connect_id);
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
		if (!$query_id) $query_id = $this->result;
		if ($query_id) return !empty($this->limit_offset[$query_id]) ? mssql_num_rows($query_id) - $this->limit_offset[$query_id] : @mssql_num_rows($query_id);
		else return false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) $query_id = $this->result;
		if ($query_id) {
			empty($row);
			$row = mssql_fetch_array($query_id);
			while(list($key, $value) = @each($row)) $row[$key] = stripslashes($value);
			@reset($row);
			return $row;
		} else return false;
	}

	/**
	* Insert Id
	*/
	function insert_id() {
		return $this->next_id[$this->db_connect_id] ? $this->next_id[$this->db_connect_id] : false;
	}

	/**
	* Affected Rows
	*/
	function affected_rows() {
		return $this->affected_rows[$this->db_connect_id] ? $this->affected_rows[$this->db_connect_id] : false;
	}

	/**
	* Real Escape String
	*/
	function real_escape_string($string) {
		return addslashes($string);
	}

	/**
	* Free Result
	*/
	function free_result($query_id = 0) {
		if (!$query_id) $query_id = $this->result;
		return $query_id ? mssql_free_result($query_id) : false;
	}

	/**
	* Error
	*/
	function error($query_id = 0) {
		$result['message'] = @mssql_get_last_message();
		return $result;
	}

	/**
	* Meta Columns
	*/
	function &meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false) {
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
	function &meta_tables() {
		$Q = $this->query($this->META_TABLES_SQL);
		while ($A = @$this->fetch_row($Q)) {
			// Skip non-system tables
			if (substr($A['0'], 0, strlen(DB_PREFIX)) != DB_PREFIX) continue;
			$tables[] = $A['0'];
		}
		return $tables;
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
