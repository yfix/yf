<?php

/**
* Database structure installer
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_installer_db {

	/** @var bool */
	public $USE_SQL_IF_NOT_EXISTS	= true;
	/** @var array @conf_skip Structures for the system tables */
	public $SYS_TABLES_STRUCTS		= array();
	/** @var array @conf_skip Structures for the other common used tables */
	public $OTHER_TABLES_STRUCTS	= array();
	/** @var array @conf_skip System tables required data */
	public $SYS_TABLES_DATAS		= array();
	/** @var array @conf_skip Other common used tables required data */
	public $OTHER_TABLES_DATAS		= array();
	/** @var array @conf_skip */
	public $_external_files = array(
		"SYS_TABLES_STRUCTS"	=> "installer_sys_tables_structs.php",
		"SYS_TABLES_DATAS"		=> "installer_sys_tables_datas.php",
		"OTHER_TABLES_STRUCTS"	=> "installer_other_tables_structs.php",
		"OTHER_TABLES_DATAS"	=> "installer_other_tables_datas.php",
	);
	/** @var array @conf_skip Required patterns */
	public $_patterns	= array(
		"table"		=> "/^CREATE[\s\t]*TABLE[\s\t]*[\`]{0,1}([^\s\t\`]+)[\`]{0,1}[\s\t]*\((.*)\)([^\(]*)\$/ims",
		"split"		=> "/[\n]+,?/",
		"field"		=> "/[\`]{0,1}([^\s\t\`]+)[\`]{0,1}[\s\t]+?([^\s\t]+)(.*)/ims",
// TODO: key could contain several fields
		"key"		=> "/(PRIMARY|UNIQUE){0,1}[\s\t]*?KEY[\s\t]*?[\`]{0,1}([a-z\_]*)[\`]{0,1}[\s\t]*?\(([^\)]+)\)/ims",
// TODO: character_set with collate
		"collate"	=> "/collate[\s\t][\"\'][a-z\_][\"\']/i",
		"default"	=> "/(,|unsigned|not null|null|zerofill|auto_increment|default)/i",
		"type"		=> "/([a-z]+)[\(]*([^\)]*)[\)]*/ims",
	);
	/** @var string @conf_skip Abstract database type */
	public $db_type				= "";
	/** @var int Lifetime for caches */
	public $CACHE_TTL				= 86400; // 1*3600*24 = 1 day
	/** @var bool */
	public $USE_LOCKING			= false;
	/** @var int */
	public $LOCK_TIMEOUT			= 600;
	/** @var string */
	public $LOCK_FILE_NAME			= "uploads/installer_db.lock";
	/** @var bool */
	public $RESTORE_FULLTEXT_INDEX	= true;
	/** @var bool */
	public $PARTITION_BY_COUNTRY	= false;
	/** @var bool */
	public $PARTITION_BY_MONTH		= false;

	/**
	* Framework constructor
	*/
	function _init () {
		// Prepare lock file
		if ($this->USE_LOCKING) {
			$this->LOCK_FILE_NAME = PROJECT_PATH. $this->LOCK_FILE_NAME;
		}
		// Load install data from external files
		$path_to_external_files = YF_PATH."share/";
		foreach ((array)$this->_external_files as $cur_array_name => $cur_file_name) {
			$data = array();
			if (!file_exists($path_to_external_files.$cur_file_name)) {
				continue;
			}
			include_once ($path_to_external_files.$cur_file_name);
			if (empty($data)) {
				continue;
			}
			// Do load info
			$this->$cur_array_name = my_array_merge((array)$this->$cur_array_name, (array)$data);
		}
		// Project has higher priority than framework (allow to change anything in project)
		// Try to load db structure from project file
		// Sample contents part: 	$project_data["OTHER_TABLES_STRUCTS"] = my_array_merge((array)$project_data["OTHER_TABLES_STRUCTS"], array(
		$structure_file = PROJECT_PATH. "project_db_structure.php";
		if (file_exists($structure_file)) {
			include_once ($structure_file);
		}
		foreach((array)$project_data as $cur_array_name => $_cur_data) {
			$this->$cur_array_name = my_array_merge((array)$this->$cur_array_name, (array)$_cur_data);
		} 
		// Get current abstract db type
		if (in_array(DB_TYPE, array("mysql","mysql4","mysql41","mysql5"))) {
			$this->db_type = "mysql";
		} elseif (in_array(DB_TYPE, array("ora","oci8","oracle","oracle10"))) {
			$this->db_type = "oracle";
		} elseif (in_array(DB_TYPE, array("pgsql","postgre","postgres","postgres7","postgres8"))) {
			$this->db_type = "postgres";
		}
	}

	/**
	* Try to auto-repair table
	*/
	function _auto_repair_table ($sql, $db_error, $DB_CONNECTION) {
		// Load sub-module
		if (empty($this->db_type)) {
			return false;
		}
		return _class("installer_db_".$this->db_type, "classes/installer/installer_db/")->_auto_repair_table($sql, $db_error, $DB_CONNECTION);
	}

	/**
	* Do create table
	*/
	function create_table ($table_name = "", $DB_CONNECTION) {
		$table_found = false;
		if (empty($table_name)) {
			return false;
		}
		if ($this->USE_LOCKING && !$this->_get_lock()) {
			return false;
		}
		// Prevent repairing twice
		if (isset($this->_installed_tables[$table_name])) {
			return false;
		}
		// Try to find table name inside system tables
		if (isset($this->SYS_TABLES_STRUCTS[$table_name])) {
			$table_found		= true;
			$TABLE_STRUCTURE	= $this->SYS_TABLES_STRUCTS[$table_name];
			$TABLE_DATAS		= $this->SYS_TABLES_DATAS[$table_name];
			$full_table_name	= $DB_CONNECTION->DB_PREFIX. "sys_".$table_name;
		}
		// Then try to find in other tables
		if (!$table_found && isset($this->OTHER_TABLES_STRUCTS[$table_name])) {
			$table_found		= true;
			$TABLE_STRUCTURE	= $this->OTHER_TABLES_STRUCTS[$table_name];
			$TABLE_DATAS		= $this->OTHER_TABLES_DATAS[$table_name];
			$full_table_name	= $DB_CONNECTION->DB_PREFIX. $table_name;
		}
		// Not partitioned at first
		$p_table_name = "";
		// Try sharding by year/month (example: db('stats_cars_2009_08'), db('stats_cars_2009_07'), db('stats_cars_2009_06') from db('stats_cars'))
		if (!$table_found && $this->PARTITION_BY_MONTH) {
			$_t_name = $p_table_name ? $p_table_name : $table_name;
			$p_month		= (int)substr($_t_name, -2);
			$p_year			= (int)substr($_t_name, -7, 4);
			if ($p_year >= 1970 && $p_year <= 2050 && $p_month >= 1 && $p_month <= 12) {
				$p_table_name	= substr($_t_name, 0, -8);
			}
		}
		if ($p_table_name) {
			if (isset($this->SYS_TABLES_STRUCTS[$p_table_name])) {
				$table_found		= true;
				$TABLE_STRUCTURE	= $this->SYS_TABLES_STRUCTS[$p_table_name];
				$TABLE_DATAS		= $this->SYS_TABLES_DATAS[$table_name]; // No error in name!
				$full_table_name	= $DB_CONNECTION->DB_PREFIX. "sys_".$table_name;
			}
			// Then try to find in other tables
			if (!$table_found && isset($this->OTHER_TABLES_STRUCTS[$p_table_name])) {
				$table_found		= true;
				$TABLE_STRUCTURE	= $this->OTHER_TABLES_STRUCTS[$p_table_name];
				$TABLE_DATAS		= $this->OTHER_TABLES_DATAS[$table_name]; // No error in name!
				$full_table_name	= $DB_CONNECTION->DB_PREFIX. $table_name;
			}
		}
		// Try sharding by country (example: db('cars_es'), db('cars_uk'), db('cars_de') from db('cars'))
		if (!$table_found && $this->PARTITION_BY_COUNTRY) {
			$_t_name = $p_table_name ? $p_table_name : $table_name;
			$p_country		= substr($_t_name, -3);
			if ($p_country{0} == "_") {
				$p_country		= substr($p_country, 1);
			}
			if (preg_match("/[a-z]{2}/", $p_country)) {
				$p_table_name	= substr($_t_name, 0, -3);
			}
		}
		if ($p_table_name) {
			if (isset($this->SYS_TABLES_STRUCTS[$p_table_name])) {
				$table_found		= true;
				$TABLE_STRUCTURE	= $this->SYS_TABLES_STRUCTS[$p_table_name];
				$TABLE_DATAS		= $this->SYS_TABLES_DATAS[$table_name]; // No error in name!
				$full_table_name	= $DB_CONNECTION->DB_PREFIX. "sys_".$table_name;
			}
			// Then try to find in other tables
			if (!$table_found && isset($this->OTHER_TABLES_STRUCTS[$p_table_name])) {
				$table_found		= true;
				$TABLE_STRUCTURE	= $this->OTHER_TABLES_STRUCTS[$p_table_name];
				$TABLE_DATAS		= $this->OTHER_TABLES_DATAS[$table_name]; // No error in name!
				$full_table_name	= $DB_CONNECTION->DB_PREFIX. $table_name;
			}
		}
		// Do not touch!!!
		$this->_installed_tables[$table_name] = 1;
		// Stop here if we do not know about given table name
		if (!$table_found || empty($TABLE_STRUCTURE)) {
			return false;
		}
		// Load sub-module
		if (empty($this->db_type)) {
			return false;
		}
		// Try to create table
		$result = _class("installer_db_".$this->db_type, "classes/installer/installer_db/")->_do_create_table($full_table_name, $TABLE_STRUCTURE, $DB_CONNECTION);
		if (!$result) {
			return false;
		}
		// Temporary hack for the insert actions
		define("dbt_".$table_name, $full_table_name);
		// Check if we also need to insert some data into new system table
		foreach ((array)$TABLE_DATAS as $query_array) {
			// Prepare data for sql
			foreach ((array)$query_array as $k => $v) {
				$query_array[$k] = _es($v);
			}
			$result = $DB_CONNECTION->INSERT($full_table_name, $query_array);
		}
		if ($this->USE_LOCKING) {
			$this->_release_lock();
		}
		return $result;
	}

	/**
	* Do alter table structure
	*/
	function alter_table ($table_name = "", $column_name = "", $DB_CONNECTION) {
		if ($this->USE_LOCKING && !$this->_get_lock()) {
			return false;
		}
		// Force cut off prefix
		if (substr($table_name, 0, strlen($DB_CONNECTION->DB_PREFIX)) == $DB_CONNECTION->DB_PREFIX) {
			$table_name = substr($table_name, strlen($DB_CONNECTION->DB_PREFIX));
		}
		// Get available tables names
		$avail_tables = $DB_CONNECTION->meta_tables();
		// Unrecognized table name
		if (!in_array($DB_CONNECTION->DB_PREFIX.$table_name, $avail_tables)) {
			return false;
		}
		// Check if this table is a system one
		$IS_SYS_TABLE = (substr($table_name, 0, strlen("sys_")) == "sys_");
		// Try to get table "model" from the framework "share" folder
		clearstatcache();
		$file_path = PROJECT_PATH."core_cache/installer_".($IS_SYS_TABLE ? "sys" : "other")."_tables_structs_arrays.php";
		// Refresh old file
		if (file_exists($file_path) && (filemtime($this->_cache_tables_file) < (time() - $this->CACHE_TTL))) {
			unlink($file_path);
			clearstatcache();
		}
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
		// Possibly this is partitioned table
		if (empty($table_struct)) {
			$table_found = false;
			// Try partition by country (example: db('cars_es'), db('cars_uk'), db('cars_de') from db('cars'))
			$p_table_name	= "";
			if (!$table_found && $this->PARTITION_BY_COUNTRY) {
				$p_country		= substr($table_name, -3);
				if ($p_country{0} == "_") {
					$p_country		= substr($p_country, 1);
				}
				if (preg_match("/[a-z]{2}/", $p_country)) {
					$p_table_name	= substr($table_name, 0, -3);
				}
				$table_struct = $data[$IS_SYS_TABLE ? substr($p_table_name, strlen("sys_")) : $p_table_name]["fields"];
				if ($table_struct) {
					$table_found = true;
				}
			}
			// Try partition by year/month (example: db('stats_cars_2009_08'), db('stats_cars_2009_07'), db('stats_cars_2009_06') from db('stats_cars'))
			if (!$table_found && $this->PARTITION_BY_MONTH && !$p_table_name) {
				$p_month		= (int)substr($table_name, -2);
				$p_year			= (int)substr($table_name, -7, 4);
				if ($p_year >= 1970 && $p_year <= 2050 && $p_month >= 1 && $p_month <= 12) {
					$p_table_name	= substr($table_name, 0, -8);
				}
				$table_struct = $data[$IS_SYS_TABLE ? substr($p_table_name, strlen("sys_")) : $p_table_name]["fields"];
				if ($table_struct) {
					$table_found = true;
				}
			}
		}
		// Check if we have such field in the current table structure
		// (then probably we have a simple mistake)
		if (!isset($table_struct[$column_name])) {
			return false;
		}
		// Load sub-module
		if (empty($this->db_type)) {
			return false;
		}
		// Do alter table structure
		$result = _class("installer_db_".$this->db_type, "classes/installer/installer_db/")->_do_alter_table($table_name, $column_name, $table_struct, $DB_CONNECTION);
		if ($this->USE_LOCKING) {
			$this->_release_lock();
		}
		return $result;
	}

	/**
	* 
	*/
	function _db_table_struct_into_array ($raw_data = "") {
		$struct_array	= array();
		// Check if we have full table definition or cutted one
		if (preg_match($this->_patterns["table"], $raw_data, $m9)) {
			$table_raw_data = $raw_data;
			$table_name		= $m9[1];
			$raw_data		= $m9[2];
		}
		// Cleanup raw first
		$cur_raw_lines = preg_split($this->_patterns["split"], trim(str_replace("\t", " ", $raw_data)));
		foreach ((array)$cur_raw_lines as $cur_line) {
			$m			= array();
			$m_t		= array();
			$def_value	= "";
			// First we check if current line contains key or regular field
			$IS_KEY = preg_match($this->_patterns["key"], $cur_line);
			// Do parse
			$res = preg_match($IS_KEY ? $this->_patterns["key"] : $this->_patterns["field"], $cur_line, $m);
			if (empty($res)) {
				continue;
			}
			// Switch between processing key and regular field
			if ($IS_KEY) {
				// Prepare key params
				$key_fields = explode(",", str_replace(array("`","'","\""), "", $m[3]));
				$key_name	= !empty($m[2]) ? $m[2] : implode("_", $key_fields);
				$key_type	= !empty($m[1]) ? strtolower($m[1]) : "key";
				// Prepare index definition array
				$struct_array["keys"][$key_name] = array(
					"fields"	=> $key_fields,
					"type"		=> $key_type,
				);
			} else {
				// Cut off collate string (if exists)
				$m[3] = preg_replace($this->_patterns["collate"], "", $m[3]);
				// Prepare field type
				preg_match($this->_patterns["type"], $m[2], $m_t);
				// Prepare field params
				$field_name		= $m[1];
				$field_length	= $m_t[2];
				$field_arrtib	= (false !== strpos(strtolower($m[3]), "unsigned")) ? "unsigned" : "";
				$field_not_null	= (false !== strpos(strtolower($m[3]), "not null")) ? 1 : 0;
				$field_auto_inc	= (false !== strpos(strtolower($m[3]), "auto_increment")) ? 1 : 0;
				$field_default	= trim(str_replace(array("\"", "'"), "", preg_replace($this->_patterns["default"], "", $m[3])));
				$field_type		= preg_replace("/[^a-z]/i", "", strtolower($m_t[1]));
				// Fix default value
				if (!strlen($field_default) && (false !== strpos($field_type, "int") || in_array($field_type, array("float","double")))) {
					$field_default = "0";
				}
				// Store data
				$struct_array["fields"][$field_name] = array(
					"type"		=> $field_type,
					"length"	=> $field_length,
					"attrib"	=> $field_attrib,
					"not_null"	=> $field_not_null,
					"default"	=> $field_default,
					"auto_inc"	=> $field_auto_inc,
				);
			}
		}
		return $struct_array;
	}

	/**
	* 
	*/
	function _get_table_struct_array_by_name ($table_name = "", $DB_CONNECTION) {
		$data2 = $DB_CONNECTION->query_fetch("SHOW CREATE TABLE `".$table_name."`");
		$table_struct = $data2["Create Table"];
		return $this->_db_table_struct_into_array($table_struct);
	}

	/**
	* 
	*/
	function _get_all_struct_array ($only_what = "", $DB_CONNECTION) {
		$structs_array = array();
		// Clean up tables from system prefixes
		foreach ((array)$DB_CONNECTION->meta_tables() as $full_table_name) {
			$is_sys_table = (false !== strpos($full_table_name, $DB_CONNECTION->DB_PREFIX."sys_"));
			// Skip non-sys tables
			if ($only_what == "sys" && !$is_sys_table) {
				continue;
			}
			if ($only_what == "other" && $is_sys_table) {
				continue;
			}
			$structs_array[substr(str_replace("sys_", "", $full_table_name), strlen($DB_CONNECTION->DB_PREFIX))] = $this->_get_table_struct_array_by_name($full_table_name, $DB_CONNECTION);
		}
		return $structs_array;
	}

	/**
	* 
	*/
	function _format_struct_array ($data = array(), $add_header = "", $add_footer = "") {
		$output = "";
		$output .= "<?php\n";
		$output .= $add_header;
		$output .= "// AUTO GENERATED ON ".date("Y-m-d H:i:s").".\n";
		$output .= "// DO NOT EDIT THIS FILE DIRECTLY! ALL CHANGES WILL BE LOST!\n";
// TODO: possibly use my_array_merge
		$output .= "\$data = array_merge((array)\$data, array(\n";
		foreach ((array)$data as $table_name => $table_params) {
			$output .= "\"".$table_name."\" => array(\n";
			$output .= "\t\"fields\" => array(\n";
			foreach ((array)$table_params["fields"] as $field_name => $field_params) {
				$output .= "\t\t\"".$field_name."\" => array(";
				foreach ((array)$field_params as $param_name => $param_value) {
					$output .= "\"".$param_name."\" => \"".$param_value."\", ";
				}
				$output .= "),\n";
			}
			$output .= "\t),\n";
			$output .= "\t\"keys\" => array(\n";
			foreach ((array)$table_params["keys"] as $key_name => $key_params) {
				$output .= "\t\t\"".$key_name."\" => array(";
				$output .= "\"fields\" => array(";
				foreach ((array)$key_params["fields"] as $param_name => $param_value) {
					$output .= "\"".$param_value."\",";
				}
				$output .= "),";
				$output .= "\"type\" => \"".$key_params["type"]."\",";
				$output .= "),\n";
			}
			$output .= "\t),\n";
			$output .= "),\n";
		}
		$output .= "));\n";
		$output .= $add_footer;
		$output .= "?>";
		return $output;
	}

	/**
	* 
	*/
	function _create_struct_files ($FORCE_OVERWRITE = false) {
		// Code to insert into footer of the other tables structs
		$footer = '
	// Try to load chat tables
	$chat_tables_structs_file = PROJECT_PATH."core_cache/installer_chat_tables_structs_arrays.php";
	if (file_exists($chat_tables_structs_file)) {
		include_once($chat_tables_structs_file);
	}
	// Try to load forum tables
	$forum_tables_structs_file = PROJECT_PATH."core_cache/installer_forum_tables_structs_arrays.php";
	if (file_exists($forum_tables_structs_file)) {
		include_once($forum_tables_structs_file);
	}'."\n";
		$SHARE_PATH		= YF_PATH."share/";
		$CACHE_PATH		= PROJECT_PATH."core_cache/";
		_mkdir_m($CACHE_PATH);
		$this->_convert_struct_files(
			$this->SYS_TABLES_STRUCTS,
			$CACHE_PATH."installer_sys_tables_structs_arrays.php",
			$FORCE_OVERWRITE
		);
		$this->_convert_struct_files(
			$this->OTHER_TABLES_STRUCTS,
			$CACHE_PATH."installer_other_tables_structs_arrays.php",
			$FORCE_OVERWRITE,
			"",
			$footer
		);
		$this->_convert_struct_files(
			$SHARE_PATH."installer_forum_tables_structs.php",
			$CACHE_PATH."installer_forum_tables_structs_arrays.php",
			$FORCE_OVERWRITE
		);
		$this->_convert_struct_files(
			$SHARE_PATH."installer_chat_tables_structs.php",
			$CACHE_PATH."installer_chat_tables_structs_arrays.php",
			$FORCE_OVERWRITE
		);
	}

	/**
	* 
	*/
	function _convert_struct_files ($file_path_strings = "", $file_path_arrays = "", $FORCE_OVERWRITE = false, $add_header = "", $add_footer = "") {
		// Cache file is old
		if (file_exists($file_path_arrays) && filemtime($file_path_arrays) < (time() - $this->CACHE_TTL)) {
			$FORCE_OVERWRITE = true;
		}
		if (file_exists($file_path_arrays) && !$FORCE_OVERWRITE) {
			return false;
		}
		if (is_array($file_path_strings)) {
			$data = $file_path_strings;
			$file_path_strings = array(); // Clean some memory
		} else {
			if (!file_exists($file_path_strings)) {
				return false;
			}
			@eval(" ?>".file_get_contents($file_path_strings)."<?php ");
		}
		if (!isset($data)) {
			return false;
		}
		// Loop through raw tables datas
		$struct_array = array();
		foreach ((array)$data as $item_name => $raw_data) {
			$struct_array[$item_name] = $this->_db_table_struct_into_array($raw_data);
		}
		$file_text = $this->_format_struct_array($struct_array, $add_header, $add_footer);
		return file_put_contents($file_path_arrays, $file_text);
	}

	/**
	* Get lock installer
	*/
	function _get_lock () {
		if (!$this->USE_LOCKING) {
			return false;
		}
		clearstatcache();
		if (file_exists($this->LOCK_FILE_NAME)) {
			// Timed out lock file
			if ((time() - filemtime($this->LOCK_FILE_NAME)) > $this->LOCK_TIMEOUT) {
				unlink($this->LOCK_FILE_NAME);
			} else {
				return false;
			}
		}
		// Put lock file
		file_put_contents($this->LOCK_FILE_NAME, time());
		return true;
	}

	/**
	* Get lock
	*/
	function _release_lock () {
		if (!$this->USE_LOCKING) {
			return false;
		}
		unlink($this->LOCK_FILE_NAME);
		return true;
	}
}
