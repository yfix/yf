<?php

/**
* MS Access db class
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_db_msaccess {

	/** @var @conf_skip */
	var $db_connect_id		= null;
	/** @var @conf_skip */
	var $result_ids			= array();
	/** @var @conf_skip */
	var $result				= null;

	/** @var @conf_skip */
	var $next_id			= null;

	/** @var @conf_skip */
	var $num_rows			= array();
	/** @var @conf_skip */
	var $current_row		= array();
	/** @var @conf_skip */
	var $field_names		= array();
	/** @var @conf_skip */
	var $field_types		= array();
	/** @var @conf_skip */
	var $result_rowset		= array();

	/** @var @conf_skip */
	var $num_queries		= 0;

	/**
	*/
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = false) {
		$this->persistency = $persistency;
		$this->server = $sqlserver;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->dbname = $database;
		$this->db_connect_id = $this->persistency ? odbc_pconnect($this->server, $this->user, $this->password) : odbc_connect($this->server, $this->user, $this->password);
		return $this->db_connect_id ? $this->db_connect_id : false;
	}

	/**
	* Other base methods
	*/
	function close() {
		if ($this->db_connect_id) {
			if ($this->in_transaction) @odbc_commit($this->db_connect_id);
			if (count($this->result_rowset))	{
				unset($this->result_rowset);
				unset($this->field_names);
				unset($this->field_types);
				unset($this->num_rows);
				unset($this->current_row);
			}
			return @odbc_close($this->db_connect_id);
		} else return false;
	}

	/**
	* Query method
	*/
	function query($query = "", $transaction = false) {
		if ($query != "") {
			$this->num_queries++;
			if ($transaction == BEGIN_TRANSACTION && !$this->in_transaction) {
				if (!odbc_autocommit($this->db_connect_id, false)) return false;
				$this->in_transaction = TRUE;
			}
			$query = str_replace("LOWER(", "LCASE(", $query);
			$query = str_replace("`", "", $query);
			if (preg_match("/^SELECT(.*?)(LIMIT ([0-9]+)[, ]*([0-9]+)*)?$/s", $query, $limits)) {
				$query = $limits[1];
				if (!empty($limits[2])) {
					$row_offset = $limits[4] ? $limits[3] : "";
					$num_rows = $limits[4] ? $limits[4] : $limits[3];
					$query = "TOP " . ($row_offset + $num_rows) . $query;
				}
				$this->result = odbc_exec($this->db_connect_id, "SELECT $query"); 
				if ($this->result) {
					if (empty($this->field_names[$this->result])) {
						for($i = 1; $i < odbc_num_fields($this->result) + 1; $i++) {
							$this->field_names[$this->result][] = odbc_field_name($this->result, $i);
							$this->field_types[$this->result][] = odbc_field_type($this->result, $i);
						}
					}
					$this->current_row[$this->result] = 0;
					$this->result_rowset[$this->result] = array();
					$row_outer = isset($row_offset) ? $row_offset + 1 : 1;
					$row_outer_max = isset($num_rows) ? $row_offset + $num_rows + 1 : 1E9;
					$row_inner = 0;

					while(odbc_fetch_row($this->result) && $row_outer < $row_outer_max)	{
						$count_f_names = count($this->field_names[$this->result]);
						for($j = 0; $j < $count_f_names; $j++)
							$this->result_rowset[$this->result][$row_inner][$this->field_names[$this->result][$j]] = stripslashes(odbc_result($this->result, $j + 1));
						$row_outer++;
						$row_inner++;
					}
					$this->num_rows[$this->result] = count($this->result_rowset[$this->result]);	
					odbc_free_result($this->result);
				}
			} elseif (preg_match('#^INSERT #i', $query)) {
				$this->result = odbc_exec($this->db_connect_id, $query);

				if ($this->result) {
					$result_id = odbc_exec($this->db_connect_id, "SELECT @@IDENTITY");
					if ($result_id) {
						if (odbc_fetch_row($result_id)) {
							$this->next_id[$this->db_connect_id] = odbc_result($result_id, 1);	
							$this->affected_rows[$this->db_connect_id] = odbc_num_rows($this->result);
						}
					}
				}
			} else {
				$this->result = odbc_exec($this->db_connect_id, $query);
				if ( $this->result )
					$this->affected_rows[$this->db_connect_id] = odbc_num_rows($this->result);
			}

			if (!$this->result) {
				if ($this->in_transaction) {
					odbc_rollback($this->db_connect_id);
					odbc_autocommit($this->db_connect_id, true);
					$this->in_transaction = false;
				}
				return false;
			}

			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@odbc_commit($this->db_connect_id)) {
					odbc_rollback($this->db_connect_id);
					odbc_autocommit($this->db_connect_id, true);
					return false;
				}
				odbc_autocommit($this->db_connect_id, true);
			}
			return $this->result;
		} else {
			if ($transaction == END_TRANSACTION && $this->in_transaction) {
				$this->in_transaction = false;
				if (!@odbc_commit($this->db_connect_id)) {
					odbc_rollback($this->db_connect_id);
					odbc_autocommit($this->db_connect_id, true);
					return false;
				}
				odbc_autocommit($this->db_connect_id, true);
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
		return $query_id ? $this->num_rows[$query_id] : false;
	}

	/**
	* Fetch Row
	*/
	function fetch_row($query_id = 0) {
		if (!$query_id) $query_id = $this->result;
		$a = ($this->num_rows[$query_id] && $this->current_row[$query_id] < $this->num_rows[$query_id]) ? $this->result_rowset[$query_id][$this->current_row[$query_id]++] : false;
		if ($query_id) return ($this->num_rows[$query_id] && $this->current_row[$query_id] < $this->num_rows[$query_id]) ? $this->result_rowset[$query_id][$this->current_row[$query_id]++] : false;
		else return false;
	}

	/**
	* Fetch Assoc
	*/
	function fetch_assoc($query_id = 0)	{
		return $this->fetch_row($query_id);
	}

	/**
	* Fetch Array
	*/
	function fetch_array($query_id = 0)	{
		return $this->fetch_row($query_id);
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
		unset($this->num_rows[$query_id]);
		unset($this->current_row[$query_id]);
		unset($this->result_rowset[$query_id]);
		unset($this->field_names[$query_id]);
		unset($this->field_types[$query_id]);
		return true;
	}

	/**
	* Error
	*/
	function error() {
		$error['code'] = "";//odbc_error($this->db_connect_id);
		$error['message'] = "Error";//odbc_errormsg($this->db_connect_id);
		return $error;
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
