<?php

class profy_installer_db_mysql {

	/** @var int */
	var $NUM_RETRIES = 3;
	/** @var int */
	var $RETRY_DELAY = 3;
	/** @var string */
	var $DEFAULT_CHARSET = "utf8";
	/** @var array */
	var $_KNOWN_TABLE_OPTIONS = array(
		"ENGINE",
		"TYPE",
		"AUTO_INCREMENT",
		"AVG_ROW_LENGTH",
		"CHARACTER SET",
		"DEFAULT CHARACTER SET",
		"CHECKSUM",
		"COLLATE",
		"DEFAULT COLLATE",
		"COMMENT",
		"CONNECTION",
		"DATA DIRECTORY",
		"DELAY_KEY_WRITE",
		"INDEX DIRECTORY",
		"INSERT_METHOD",
		"MAX_ROWS",
		"MIN_ROWS",
		"PACK_KEYS",
		"PASSWORD",
		"ROW_FORMAT",
		"UNION",
	);

	/**
	* Framework construct
	*/
	function _init() {
		$this->PARENT_OBJ = _class('installer_db', 'classes/installer/');
		$this->_DEF_TABLE_OPTIONS = array(
			"DEFAULT CHARSET"	=> $this->DEFAULT_CHARSET,
			"ENGINE"			=> "MyISAM",
		);
	}

	/**
	* Trying to repair given table structure (and possibly data)
	*/
	function _auto_repair_table($sql, $db_error, $DB_CONNECTION) {
		if (!is_object($this->PARENT_OBJ)) {
			return false;
		}
		$sql = trim($sql);
		// #1191 Can't find FULLTEXT index matching the column list
		if ($this->PARENT_OBJ->RESTORE_FULLTEXT_INDEX && in_array($db_error['code'], array(1191))) {
			foreach ((array)conf('fulltext_needed_for') as $_fulltext_field) {
				list($f_table, $f_field) = explode(".", $_fulltext_field);
				if (empty($f_table) || false === strpos($sql, $f_table) || empty($f_field)) {
					continue;
				}
				// Check if such index already exists
				foreach ((array)$DB_CONNECTION->query_fetch_all("SHOW INDEX FROM `".$f_table."`", "Key_name") as $k => $v) {
					if ($v["Column_name"] != $f_field) {
						continue;
					}
					if ($v["Index_type"] == "FULLTEXT") {
						// Continue the outer loop
						continue 2;
					}
				}
				$DB_CONNECTION->query("ALTER TABLE `".$f_table."` ADD FULLTEXT KEY `".$f_field."` (`".$f_field."`)");
			}
			// Execute original query again
			$result = $DB_CONNECTION->query($sql);
			return $result;
		}

		// Errors related to server high load (currently we will handle only SELECTs)
		// #2013 means "Lost connection to MySQL server during query"
		// #1205 means "Lock wait timeout expired. Transaction was rolled back" (InnoDB)
		// #1213 means "Transaction deadlock. You should rerun the transaction." (InnoDB)
		if (in_array($db_error['code'], array(2013,1205,1213)) && substr($sql, 0, strlen("SELECT ")) == "SELECT ") {
			$result = false;
			// Try 5 times with delay
			for ($i = 0; $i <= $this->NUM_RETRIES; $i++) {
				$result = $DB_CONNECTION->db->query($sql);
				// Stop after success
				if (!empty($result)) {
					break;
				// Wait some time and try again
				} else {
					sleep($this->RETRY_DELAY);
				}
			}
			return $result;
		}
		// Try to refresh tables names cache (error #1146 means "Table %s doesn't exist")
		// (error #1054 means "Unknown column %s")
		if (!in_array($db_error['code'], array(1146, 1054))) {
			return false;
		}
		if ($db_error['code'] == 1146) {
			// Try to get table name from error message
			preg_match("#Table [\'][a-z_0-9]+\.([a-z_0-9]+)[\'] doesn\'t exist#ims", $db_error['message'], $m);
			$item_to_repair = trim($m[1]);
			// Cut dottes from name
			$dot_pos = strpos($item_to_repair, ".");
			if (false !== $dot_pos) {
				$item_to_repair = substr($item_to_repair, $dot_pos);
			}
			// Cut dottes from name (again)
			$dot_pos = strpos($item_to_repair, ".");
			if (false !== $dot_pos) {
				$item_to_repair = substr($item_to_repair, $dot_pos);
			}
			if (substr($item_to_repair, 0, strlen($DB_CONNECTION->DB_PREFIX)) == $DB_CONNECTION->DB_PREFIX) {
				$item_to_repair = substr($item_to_repair, strlen($DB_CONNECTION->DB_PREFIX));
			}
			// Try to repair table
			if (!empty($item_to_repair)) {
				$this->PARENT_OBJ->create_table(str_replace("dbt_", "", $item_to_repair), $DB_CONNECTION);
			}
		} elseif ($db_error['code'] == 1054) {
			// Try to get column name from error message
			preg_match("#Unknown column [\']([a-z_0-9]+)[\'] in#ims", $db_error['message'], $m);
			$item_to_repair = $m[1];
			// Cut dottes from name
			$dot_pos = strpos($item_to_repair, ".");
			if (false !== $dot_pos) {
				$item_to_repair = substr($item_to_repair, $dot_pos);
			}
			// Cut dottes from name (again)
			$dot_pos = strpos($item_to_repair, ".");
			if (false !== $dot_pos) {
				$item_to_repair = substr($item_to_repair, $dot_pos);
			}
			// Try to get table name from SQL
			preg_match("#[\s\t]*(UPDATE|FROM|INTO)[\s\t]+[\`]{0,1}([a-z_0-9]+)[\`]{0,1}#ims", $sql, $m2);
			$table_to_repair = $m2[2];
			// Cut dottes from name
			$dot_pos = strpos($table_to_repair, ".");
			if (false !== $dot_pos) {
				$table_to_repair = substr($table_to_repair, $dot_pos);
			}
			// Cut dottes from name (again)
			$dot_pos = strpos($table_to_repair, ".");
			if (false !== $dot_pos) {
				$table_to_repair = substr($table_to_repair, $dot_pos);
			}
			if (substr($table_to_repair, 0, strlen($DB_CONNECTION->DB_PREFIX)) == $DB_CONNECTION->DB_PREFIX) {
				$table_to_repair = substr($table_to_repair, strlen($DB_CONNECTION->DB_PREFIX));
			}
			// Try to repair table
			if (!empty($item_to_repair) && !empty($m2[2])) {
				$this->PARENT_OBJ->alter_table($table_to_repair, $item_to_repair, $DB_CONNECTION);
			}
		}
		// Refresh tables cache
		if (file_exists($DB_CONNECTION->_cache_tables_file)) {
			unlink($DB_CONNECTION->_cache_tables_file);
		}
		$result = false;
		// Try to repair query
		if ($db_error['code'] == 1146) {
			if (!empty($item_to_repair) && defined($item_to_repair)) {
				$sql = str_replace($item_to_repair, eval("return ".$item_to_repair.";"), $sql);
				$result = $DB_CONNECTION->query($sql);
			}
		} elseif ($db_error['code'] == 1054) {
			if (!empty($installer_result)) {
				$result = $DB_CONNECTION->query($sql);
			}
		}
		return $result;
	}

	/**
	* Do create table
	*/
	function _do_create_table ($full_table_name = "", $TABLE_STRUCTURE = "", $DB_CONNECTION) {
		if (!is_object($this->PARENT_OBJ)) {
			return false;
		}
		$TABLE_OPTIONS = $this->_DEF_TABLE_OPTIONS;

		$_options_to_merge = array();
		// Get table options from table structure
		// Example: /** ENGINE=MEMORY **/
		if (preg_match("#\/\*\*([^\*\/]+)\*\*\/\$#i", trim($TABLE_STRUCTURE), $m)) {
			// Cut comment with options from source table structure
			// to prevent misunderstanding
			$TABLE_STRUCTURE = str_replace($m[0], "", $TABLE_STRUCTURE);

			$_raw_options = str_replace(array("\r","\n","\t"), array("",""," "), trim($m[1]));

			$_pattern = "/(".implode("|", $this->_KNOWN_TABLE_OPTIONS).")[\s]{0,}=[\s]{0,}([\']{0,1}[^\'\,]+[\']{0,1})/ims";
			if (preg_match_all($_pattern, $_raw_options, $m)) {
				foreach ((array)$m[0] as $_id => $v) {
					$_option_key = strtoupper(trim($m[1][$_id]));
					$_option_val = trim($m[2][$_id]);
					if (!in_array($_option_key, $this->_KNOWN_TABLE_OPTIONS)) {
						continue;
					}
					$_options_to_merge[$_option_key] = $_option_val;
				}
			}
		}
		if (!empty($_options_to_merge)) {
			foreach ((array)$_options_to_merge as $k => $v) {
				$TABLE_OPTIONS[$k] = $v;
			}
		}
		$_tmp = array();
		foreach ((array)$TABLE_OPTIONS as $k => $v) {
			$_tmp[$k] = $k."=".$v;
		}
		$_table_options_string = "";
		if (!empty($_tmp)) {
			$_table_options_string = " ".implode(", ", $_tmp);
		}
		// Try to create table
		$sql = "CREATE TABLE "
			.($this->PARENT_OBJ->USE_SQL_IF_NOT_EXISTS ? "IF NOT EXISTS" : "")
			." ".$DB_CONNECTION->enclose_field_name($full_table_name)
			." (\r\n".
			$TABLE_STRUCTURE
			.")".$_table_options_string;
		// Try to execute query
		$result = $DB_CONNECTION->query($sql);
		return $result;
	}

	/**
	* Do alter table structure
	*/
	function _do_alter_table ($table_name = "", $column_name = "", $table_struct = array(), $DB_CONNECTION) {
		if (!is_object($this->PARENT_OBJ)) {
			return false;
		}
		// Shorthand for the column structure
		$column_struct = $table_struct[$column_name];
		// Fix for the "int" default value
		if ($column_struct["type"] != "int" && $column_struct["default"] == "") {
			unset($column_struct["default"]);
		}
		// Generate "ALTER TABLE" query
		$sql = "ALTER TABLE `".$DB_CONNECTION->DB_PREFIX.$table_name."`\r\n".
			"\tADD `"._es($column_name)."` ".strtoupper($column_struct["type"]).
			(!empty($column_struct["length"])	? "(".$column_struct["length"].")" : "").
			(!empty($column_struct["attrib"])	? " ".$column_struct["attrib"]."" : "").
			(!empty($column_struct["not_null"])	? " NOT NULL" : "").
			(isset($column_struct["default"])	? " DEFAULT '".$column_struct["default"]."'" : "").
			(!empty($column_struct["auto_inc"])	? " AUTO_INCREMENT" : "").
			";";
		// Do execute generated query
		return $DB_CONNECTION->query($sql);
	}
}
