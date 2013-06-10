<?php

/**
* DB2 db class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_db_db2 {

	/** @var @conf_skip */
	public $db_connect_id;
	/** @var @conf_skip */
	public $query_result;
	/** @var @conf_skip */
	public $query_resultset;
	/** @var @conf_skip */
	public $query_numrows;
	/** @var @conf_skip */
	public $next_id;
	/** @var @conf_skip */
	public $row = array();
	/** @var @conf_skip */
	public $rowset = array();
	/** @var @conf_skip */
	public $row_index;
	/** @var @conf_skip */
	public $num_queries = 0;

	/**
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false) {
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->dbname = $database;
		$this->server = $sqlserver;
		$this->db_connect_id = $this->persistency ? odbc_pconnect($this->server, "", "") : odbc_connect($this->server, "", "");
		if ($this->db_connect_id) {
			@odbc_autocommit($this->db_connect_id, off);
			return $this->db_connect_id;
		} else return false;
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			if ($this->query_result)	@odbc_free_result($this->query_result);
			$result = @odbc_close($this->db_connect_id);
			return $result;
		} else return false;
	}

	/**
	* Query method
	*/
	function query($query = "", $transaction = false) {
		// Remove any pre-existing queries
		unset($this->query_result);
		unset($this->row);
		if ($query != "") {
			$this->num_queries++;
			if (!preg_match('#^INSERT #i',$query)) {
				if (preg_match("#LIMIT#i", $query)) {
					preg_match("/^(.*)LIMIT ([0-9]+)[, ]*([0-9]+)*/s", $query, $limits);
					$query = $limits[1];
					if ($limits[3]) {
						$row_offset = $limits[2];
						$num_rows = $limits[3];
					} else {
						$row_offset = 0;
						$num_rows = $limits[2];
					}
					$query .= " FETCH FIRST ".($row_offset+$num_rows)." ROWS ONLY OPTIMIZE FOR ".($row_offset+$num_rows)." ROWS";
					$this->query_result = odbc_exec($this->db_connect_id, $query);
					$query_limit_offset = $row_offset;
					$this->result_numrows[$this->query_result] = $num_rows;
				} else {
					$this->query_result = odbc_exec($this->db_connect_id, $query);
					$row_offset = 0;
					$this->result_numrows[$this->query_result] = 5E6;
				}
				$result_id = $this->query_result;
				if ($this->query_result && preg_match('#^SELECT#i', $query)) {
					for($i = 1; $i < odbc_num_fields($result_id)+1; $i++) 
						$this->result_field_names[$result_id][] = odbc_field_name($result_id, $i);
					$i =  $row_offset + 1;
					$k = 0;
					while(odbc_fetch_row($result_id, $i) && $k < $this->result_numrows[$result_id])	{
						for($j = 1; $j < count($this->result_field_names[$result_id])+1; $j++)
							$this->result_rowset[$result_id][$k][$this->result_field_names[$result_id][$j-1]] = odbc_result($result_id, $j);
						$i++;
						$k++;
					}
					$this->result_numrows[$result_id] = $k;
					$this->row_index[$result_id] = 0;
				} else {
					$this->result_numrows[$result_id] = @odbc_num_rows($result_id);
					$this->row_index[$result_id] = 0;
				}
			} else {
				if (preg_match('#^(INSERT|UPDATE) #i', $query)) $query = preg_replace("/\\\'/s", "''", $query);
				$this->query_result = odbc_exec($this->db_connect_id, $query);
				if ($this->query_result) {
					$sql_id = "VALUES(IDENTITY_VAL_LOCAL())";
					$id_result = odbc_exec($this->db_connect_id, $sql_id);
					if ($id_result) {
						$row_result = odbc_fetch_row($id_result);
						if ($row_result)	$this->next_id[$this->query_result] = odbc_result($id_result, 1);
					}
				}
				odbc_commit($this->db_connect_id);
				$this->query_limit_offset[$this->query_result] = 0;
				$this->result_numrows[$this->query_result] = 0;
			}
			return $this->query_result;
		} else return false;
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
		if ($query_id) return $this->result_numrows[$query_id];
		else return false;
	}

	/**
	* Affected Rows
	*/
	function affected_rows($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		if ($query_id) return $this->result_numrows[$query_id];
		else return false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		if ($query_id) {
			if ($this->row_index[$query_id] < $this->result_numrows[$query_id]) {
				$result = $this->result_rowset[$query_id][$this->row_index[$query_id]];
				$this->row_index[$query_id]++;
				return $result;
			} else return false;
		} else return false;
	}

	/**
	* Insert Id
	*/
	function insert_id($query_id = 0) {
		if (!$query_id) $query_id = $this->query_result;
		if ($query_id) return $this->next_id[$query_id];
		else return false;
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
	function free_result($query_id = 0)	{
		if (!$query_id) $query_id = $this->query_result;
		if ($query_id) {
			$result = @odbc_free_result($query_id);
			return $result;
		} else return false;
	}

	/**
	* Error
	*/
	function error($query_id = 0) {
//		$result['code'] = @odbc_error($this->db_connect_id);
//		$result['message'] = @odbc_errormsg($this->db_connect_id);
		return "";
	}

	/**
	* Meta Tables
	*/
	function meta_tables($db_name) {
// TODO: check this
		if (!$query_id) $query_id = $this->query_result;
		return odbc_tables ($query_id, '', '', $db_name);
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
