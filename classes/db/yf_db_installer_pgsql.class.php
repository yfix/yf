<?php

load('db_installer', 'framework', 'classes/db/');
class yf_db_installer_pgsql extends yf_db_installer {
	// TODO


	/**
	* Trying to repair given table structure (and possibly data)
	*/
	function _auto_repair_table($sql, $db_error) {

echo $sql."<br />";
print_r($db_error);
echo "<br />";

		// Try to refresh tables names cache (error #942 means "table or view does not exist")
/*
		// (error #1054 means "Unknown column %s")
		if (!in_array($db_error['code'], array(1146, 1054))) {
			return false;
		}
*/
		// TEMPORARY, NEED TO BE REPLACED WITH REAL PGSQL error code
		if (substr($db_error["message"], -strlen("does not exist")) == "does not exist") {
			// Try to get table name from error message
			preg_match("#relation \"dbt_([a-z0-9\_]+)\" does not exist#ims", $db_error['message'], $m);
			$item_to_repair = trim($m[1]);
			// Try to repair table
			if (!empty($item_to_repair)) {
				$this->create_table($item_to_repair);
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
				$installer_result = _class_safe("installer")->_alter_table(array("table_name" => str_replace($DB_CONNECTION->DB_PREFIX, "", $m2[2]), "column_name" => $item_to_repair));
			}
*/

		}
		// Refresh tables cache
		if (file_exists(db()->_cache_tables_file)) {
			unlink(db()->_cache_tables_file);
		}
		$result = false;
		// Try to repair query
		if (substr($db_error["message"], -strlen("does not exist")) == "does not exist") {
			if (!empty($item_to_repair) && defined($item_to_repair)) {
				$sql = str_replace($item_to_repair, eval("return ".$item_to_repair.";"), $sql);
				$result = db()->query($sql);
			}

/*

		} elseif ($db_error['code'] == 1054) {
			if (!empty($installer_result)) {
				$result = db()->query($sql);
			}
*/

		}

		return $result;
	}

	/**
	* Do create table
	*/
	function _do_create_table ($full_table_name = "", $TABLE_STRUCTURE = "") {
		// We do not need mysql-based able structure
		$TABLE_STRUCTURE = "";
		$table_name = substr($full_table_name, strlen($DB_CONNECTION->DB_PREFIX));
		// Check if this table is a system one
		$IS_SYS_TABLE = (substr($table_name, 0, strlen("sys_")) == "sys_");
		// Try to get table "model" from the framework "share" folder
		$file_path = YF_PATH."share/db_installer/installer_".($IS_SYS_TABLE ? "sys" : "other")."_tables_structs_arrays.php";
		// Try to convert strings structure into arrays (if not done yet)
		if (!file_exists($file_path)) {
			$this->_create_struct_files(1);
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

				$table_struct[$field_name]["type"] = "INT4";
				unset($table_struct[$field_name]["length"]);

			} elseif (in_array($field_atts["type"], array("char","varchar","text","tinytext","mediumtext","longtext","blob","mediumblob","longblob"))) {

				$table_struct[$field_name]["type"] = "TEXT";
				unset($table_struct[$field_name]["length"]);

			} elseif ($field_atts["type"] == "date") {

				$table_struct[$field_name]["type"] = "DATE";
				$table_struct[$field_name]["length"] = "10";

			} elseif ($field_atts["type"] == "time") {

				$table_struct[$field_name]["type"] = "CHAR";
				$table_struct[$field_name]["length"] = "8";

			} elseif ($field_atts["type"] == "datetime") {

				$table_struct[$field_name]["type"] = "CHAR";
				$table_struct[$field_name]["length"] = "19";

			} elseif (in_array($field_atts["type"], array("enum","set"))) {

				$table_struct[$field_name]["type"] = "CHAR";
				$table_struct[$field_name]["length"] = "50";

			}
		}
		// Generate query
		foreach ((array)$table_struct as $field_name => $field_atts) {
			$tmp_struct[] = db()->enclose_field_name($field_name).
				" ".strtoupper(!empty($field_atts["auto_inc"]) ? "SERIAL" : $field_atts["type"]).
				(!empty($field_atts["length"]) ? "(".$field_atts["length"].")" : "").
				(!empty($field_atts["default"]) ? " default '".$field_atts["default"]."'" : "").
//				(!empty($field_atts["not_null"]) ? " NOT NULL" : "").
				(!empty($field_atts["auto_inc"]) ? " PRIMARY KEY" : "").
				"";
		}
		$TABLE_STRUCTURE = implode(",\r\n", $tmp_struct);
		// Try to execute query
		$sql = "CREATE TABLE ".db()->enclose_field_name($full_table_name)." (\r\n".$TABLE_STRUCTURE.")";
echo "<br>"."<br>".$sql."<br>"."<br>";
		$result = db()->query($sql);
		// Generate sequence SQL (emulate auto_increment)
		return $result;
	}

	/**
	* Do alter table structure
	*/
	function _do_alter_table ($table_name = "", $column_name = "", $table_struct = array()) {
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
		return db()->query($sql);
*/
	}

}
