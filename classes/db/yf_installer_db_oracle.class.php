<?php

class yf_installer_db_oracle {

	/**
	* Framework construct
	*/
	function _init() {
		$this->PARENT_OBJ = _class('installer_db', 'classes/db/');
	}

	/**
	* Trying to repair given table structure (and possibly data)
	*/
	function _auto_repair_table($sql, $db_error, $DB_CONNECTION) {
		// Check allowed errors
		if (!in_array($db_error['code'], array(942))) {
			return false;
		}
		// Try to refresh tables names cache (error #942 means "table or view does not exist")
		if ($db_error['code'] == 942) {
			// Try to get table name from error message
			preg_match("#[\s\t\.]+dbt_([a-z0-9\_]+)#ims", str_replace("\"", "", $db_error['sqltext']), $m);
			$item_to_repair = trim($m[1]);
			// Try to repair table
			if (!empty($item_to_repair)) {
				$this->PARENT_OBJ->create_table($item_to_repair);
			}
/*
		} elseif ($db_error['code'] == 1054) {
			// Try to get column name from error message
			preg_match("#Unknown column [\']([a-z_0-9]+)[\'] in \'field list\'#ims", $db_error['message'], $m);
$item_to_repair = $m[1];
			// Try to get table name from SQL
			preg_match("#[\s\t]+(UPDATE|FROM|INTO)[\s\t]+[\`]([a-z_0-9]+)[\`]#ims", $sql, $m2);
			// Try to repair table
			if (!empty($item_to_repair) && !empty($m2[2])) {
				$installer_result = main()->call_class_method("installer", "classes/", "_alter_table", array("table_name" => str_replace($DB_CONNECTION->DB_PREFIX, "", $m2[2]), "column_name" => $item_to_repair));
			}
*/
		}
		// Refresh tables cache
		if (file_exists($DB_CONNECTION->_cache_tables_file)) {
			unlink($DB_CONNECTION->_cache_tables_file);
		}
		$result = false;
		// Try to repair query
		if ($db_error['code'] == 942) {
			if (!empty($item_to_repair) && defined($item_to_repair)) {
				$sql = str_replace($item_to_repair, eval("return ".$item_to_repair.";"), $sql);
//$DB_CONNECTION->close();
//$DB_CONNECTION->connect(DB_HOST, DB_USER, DB_PSWD, DB_NAME);
				$result = $DB_CONNECTION->query($sql);
			}
/*

		} elseif ($db_error['code'] == 1054) {
			if (!empty($installer_result)) {
				$result = $DB_CONNECTION->query($sql);
			}
*/
		}
		return $result;
	}

	/**
	* Do create table
	*/
	function _do_create_table ($full_table_name = "", $TABLE_STRUCTURE = "", $DB_CONNECTION) {
		// We do not need mysql-based able structure
		$TABLE_STRUCTURE = "";
		$table_name = substr($full_table_name, strlen($DB_CONNECTION->DB_PREFIX));
		// Check if this table is a system one
		$IS_SYS_TABLE = (substr($table_name, 0, strlen("sys_")) == "sys_");
		// Try to get table "model" from the framework "share" folder
		$file_path = YF_PATH."share/installer_".($IS_SYS_TABLE ? "sys" : "other")."_tables_structs_arrays.php";
		// Try to convert strings structure into arrays (if not done yet)
		if (!file_exists($file_path)) {
			$this->PARENT_OBJ->_create_struct_files(1);
		}
		// Last check for file existance
		if (!file_exists($file_path)) {
			return false;
		}
		@eval(" ?>".file_get_contents($file_path)."<?php ");
		// Check if we successfully loaded data
		if (!isset($data)) {
			return false;
		}
		$table_struct = $data[$IS_SYS_TABLE ? substr($table_name, strlen("sys_")) : $table_name]["fields"];
		if (empty($table_struct)) {
			return false;
		}
		// Convert Mysql-based structure into native SQL code
		foreach ((array)$table_struct as $field_name => $field_atts) {
			// Convert type
			if (in_array($field_atts["type"], array("int", "tinyint","smallint","mediumint","bigint"))) {

				$table_struct[$field_name]["type"] = "INTEGER";
				unset($table_struct[$field_name]["length"]);

			} elseif (in_array($field_atts["type"], array("float","double"))) {

				$table_struct[$field_name]["type"] = "NUMBER";
				$table_struct[$field_name]["length"] = $table_struct[$field_name]["length"].",16";

			} elseif (in_array($field_atts["type"], array("char","varchar","text","tinytext","mediumtext","longtext","blob","mediumblob","longblob"))) {

				$table_struct[$field_name]["type"] = "VARCHAR2";
				$table_struct[$field_name]["length"] = "4000";

			} elseif ($field_atts["type"] == "date") {

				$table_struct[$field_name]["type"] = "DATE";
				$table_struct[$field_name]["length"] = "10";

			} elseif ($field_atts["type"] == "time") {

				$table_struct[$field_name]["type"] = "VARCHAR2";
				$table_struct[$field_name]["length"] = "8";

			} elseif ($field_atts["type"] == "datetime") {

				$table_struct[$field_name]["type"] = "VARCHAR2";
				$table_struct[$field_name]["length"] = "19";

			} elseif (in_array($field_atts["type"], array("enum", "set"))) {

				$table_struct[$field_name]["type"] = "VARCHAR2";
				$table_struct[$field_name]["length"] = "50";

			}
		}
		// Generate query
		foreach ((array)$table_struct as $field_name => $field_atts) {
			$tmp_struct[] = $DB_CONNECTION->enclose_field_name($field_name).
				" ".strtoupper($field_atts["type"]).
				(!empty($field_atts["length"]) ? "(".$field_atts["length"].")" : "").
				(!empty($field_atts["default"]) ? " default '".$field_atts["default"]."'" : "").
//				(!empty($field_atts["not_null"]) ? " NOT NULL" : "").
				" NULL".
				(!empty($field_atts["auto_inc"]) ? " PRIMARY KEY" : "").
				"";
		}
		$TABLE_STRUCTURE = implode(",\r\n", $tmp_struct);
		// Try to execute query
		$sql = "CREATE TABLE ".$DB_CONNECTION->enclose_field_name($full_table_name)." (\r\n".$TABLE_STRUCTURE.")";
		$result = $DB_CONNECTION->query($sql);
		// Prepare sequence data
		$_SEQUENCE_NAME		= substr("seq_".$table_name, 0, 30);	// Max length for oracle
		$_TRIGGER_NAME		= substr("trig_".$table_name, 0, 30);	// Max length for oracle
		$_AUTO_INC_FIELD	= "\"id\"";
		// Drop old sequence
  		$sql1 = "DROP SEQUENCE ".$DB_CONNECTION->enclose_field_name($_SEQUENCE_NAME);
		$DB_CONNECTION->query($sql1);
		// Generate sequence SQL (emulate auto_increment)
		$sql2 = "CREATE SEQUENCE ".$DB_CONNECTION->enclose_field_name($_SEQUENCE_NAME);
		$DB_CONNECTION->query($sql2);
		// Create trigger
		$sql3 = "CREATE OR REPLACE TRIGGER ".$DB_CONNECTION->enclose_field_name($_TRIGGER_NAME)." 
				BEFORE 
				insert ON ".$DB_CONNECTION->enclose_field_name($full_table_name)." 
				FOR EACH ROW 
				WHEN (new.".($_AUTO_INC_FIELD)." IS NULL OR new.".($_AUTO_INC_FIELD)." = 0) 
				BEGIN
					select ".$DB_CONNECTION->enclose_field_name($_SEQUENCE_NAME).".nextval 
					into :new.".($_AUTO_INC_FIELD)." from dual;
				END;";
		$DB_CONNECTION->query($sql3);
//		return $result;
		return true;
	}

	/**
	* Do alter table structure
	*/
	function _do_alter_table ($table_name = "", $column_name = "", $table_struct = array(), $DB_CONNECTION) {
		// Shorthand for the column structure
		$column_struct = $table_struct[$column_name];
// TODO
/*
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
*/
	}
}
