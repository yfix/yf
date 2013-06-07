<?php

/**
* Class that parse db structure and allow to add/update/delete records
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_db_parser {

	/**
	* Function that shows contents of the db table
	*/
	function show() {
		if (empty($_GET["table"])) {
			return js_redirect("./?object=db_manager");
		}
		$T = &main()->init_class("table", "classes/", "db_table = ".DB_PREFIX.$_GET["table"]."~auto_parser=1");
		$T->show_add_button = true;
		$body .= $T->create();
		return $body;
	}

	/**
	* Editing record form
	*/
	function edit() {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"] && $_GET["table"]) {
			$F = &main()->init_class("form", "classes/", "db_table=".DB_PREFIX.$_GET["table"]." ~ action = ./?object=".$_GET["object"]."&action=update&id=".$_GET["id"]."&table=".$_GET["table"]."~auto_parser=1");
			$body .= $F->create();
 		} else {
			$body .= error_back();
		}
		return $body;
	}

	/**
	* This function calling to show form when set task to add new record to database
	*/
	function add() {
		if ($_GET["table"]) {
			$F = &main()->init_class("form", "classes/", "db_table=".DB_PREFIX.$_GET["table"]." ~ action = ./?object=".$_GET["object"]."&action=insert&table=".$_GET["table"]." ~ auto_parser=1");
			$body .= $F->create();
		} else {
			$body .= error_back();
		}
		return $body;
	}

	/**
	* This function updates current record (SAVE)
	*/
	function update() {
		$_GET["id"]		= intval($_GET["id"]);
		$_GET["table"]	= _es($_GET["table"]);
		// Check if table name passed through
		if ($_GET["id"] && $_GET["table"]) {
			$sql	= "UPDATE `".DB_PREFIX. $_GET["table"]."` SET \r\n";
			// Process table fields
			$fields = db()->meta_columns(DB_PREFIX. $_GET["table"]);
			// Process add_date
			if (empty($_POST["add_date"]) && isset($_POST["day"]) && isset($_POST["month"]) && isset($_POST["year"])) {
				$_POST["add_date"] = strtotime($_POST["year"]."-".$_POST["month"]."-".$_POST["day"]);
			}
			foreach ((array)$fields as $A) {
				$field_name		= $A["name"];
				$field_type		= $A["type"];
				$field_length	= $A["max_length"];
				// Skip ID field
				if ($field_name == "id") {
					continue;
				}
				if ($field_type == "int") {
					$sql .= "`".$field_name."` = ".intval($_POST[$field_name]).", \r\n";
				} else {
					if ($field_name == "password" && $field_length >= 32) {
						// Do not try to save not typed password
						if (empty($_POST[$field_name])) {
							continue;
						}
						$sql .= "`".$field_name."` = MD5('"._es($_POST[$field_name])."'), \r\n";
					} else {
						$sql .= "`".$field_name."` = '"._es($_POST[$field_name])."', \r\n";
					}
				}
			}
			$sql = substr($sql, 0, -4)." WHERE `id`=".$_GET["id"];
			db()->query($sql);
			// Refresh ALL system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh_all();
			// Return user back
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		} else {
			return error_back();
		}
	}

	/**
	* This function inserts new record
	*/
	function insert() {
		if ($_GET["table"]) {
			$sql = "INSERT INTO `".DB_PREFIX.$_GET["table"]."` (\r\n";
			// Process table fields
			$fields = db()->meta_columns(DB_PREFIX. $_GET["table"]);
			// Process add_date
			if (empty($_POST["add_date"]) && isset($_POST["day"]) && isset($_POST["month"]) && isset($_POST["year"])) {
				$_POST["add_date"] = strtotime($_POST["year"]."-".$_POST["month"]."-".$_POST["day"]);
			}
			foreach ((array)$fields as $A) {
				// Skip ID field
				if ($A["name"] == "id") {
					continue;
				}
				$sql .= "`".$A["name"]."`, \r\n";
			}
			$sql = substr($sql, 0, -4).") VALUES (";
			foreach ((array)$fields as $A) {
				$field_name		= $A["name"];
				$field_type		= $A["type"];
				$field_length	= $A["max_length"];
				// Skip ID field
				if ($field_name == "id") {
					continue;
				}
				// Process field types
				if ($field_type == "int") {
					$sql .= intval($_POST[$field_name]).", \r\n";
				} else {
					if ($field_name == "password" && $field_length >= 32) {
						// Do not try to save not typed password
						if (!$_POST[$field_name]) {
							continue;
						}
						$sql .= "MD5('".$_POST[$field_name]."'), \r\n";
					} else {
						$sql .= "'"._es($_POST[$field_name])."', \r\n";
					}
				}
			}
			$sql = substr ($sql, 0, -4).")\r\n";
			db()->query($sql);
			// Refresh ALL system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh_all();
			// Return user back
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		} else {
			return error_back();
		}
	}

	/**
	* This function delete record
	*/
	function delete() {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		if ($_GET["id"] && $_GET["table"]) {
			db()->query("DELETE FROM `".DB_PREFIX.$_GET["table"]."` WHERE `id`=".$_GET["id"]);
			// Refresh ALL system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh_all();
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		}
	}

	/**
	* This function delete record
	*/
	function group_delete() {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["table"]) {
			// Prepare ids to delete
			$IDS_TO_DELETE = array();
			foreach ((array)$_POST as $k => $v) {
				if (empty($v) || substr($k, 0, strlen("_group_")) != "_group_") {
					continue;
				}
				$cur_id = substr($k, strlen("_group_"));
				if (!empty($cur_id)) {
					$IDS_TO_DELETE[$cur_id] = $cur_id;
				}
			}
			if (isset($IDS_TO_DELETE[""])) {
				unset($IDS_TO_DELETE[""]);
			}
			if (!empty($IDS_TO_DELETE)) {
				db()->query ("DELETE FROM `".DB_PREFIX.$_GET["table"]."` WHERE `id` IN(".implode(",", $IDS_TO_DELETE).")");
				// Refresh ALL system cache
				if (main()->USE_SYSTEM_CACHE)	cache()->refresh_all();
			}
			// Return user back
			return js_redirect("./?object=".$_GET["object"]. _add_get());
		} else {
			return error_back();
		}
	}
}
