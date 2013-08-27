<?php

/**
* Simple database manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_db_manager {

	/** @var bool */
	public $AUTO_GET_TABLES_STATUS		= 0;
	/** @var string @conf_skip */
	public $TABLES_CONSTS_PREFIX		= "dbt_";
	/** @var bool */
	public $USE_HIGHLIGHT				= 1;
	/** @var int Number of records in extended export mode to use in one INSERT block */
	public $EXPORT_EXTENDED_PER_BLOCK	= 500;
	/** @var string Path where auto-backups will be stored */
	public $BACKUP_PATH				= "backup_sql/";
	/** @var string Path to mysql.exe */
	public $MYSQL_CLIENT_PATH			= "d:\\www\\mysql\\bin\\";
	/** @var int Max number of backups files to store */
	public $MAX_BACKUP_FILES			= 5;


	/**
	* Constructor
	*/
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"tables"		=> 'multi_select("tables",		$this->_tables_names,	$selected, false, 2, " size=10 class=small_for_select ", false)',
			"export_type"	=> 'radio_box("export_type",	$this->_export_types,	$selected ? $selected : "insert", false, 2, "", false)',
			"compress"		=> 'radio_box("compress",		$this->_compress_types,	$selected ? $selected : "gzip", false, 2, "", false)',
		);
		$this->_export_types = array(
			"insert"	=> "INSERT",
			"replace"	=> "REPLACE",
		);
		$this->_compress_types = array(
			""		=> "None",
			"gzip"	=> "Gzip",
		);
	}

	/**
	* Default method
	*/
	function show () {
		$total_rows = 0;
		$total_size = 0;
		// Process tables
		foreach ((array)$this->_get_tables_infos() as $table_name => $table_info) {
			$total_rows += $table_info["rows"];
			$total_size += $table_info["data_size"];
			$small_table_name = substr($table_name, strlen(DB_PREFIX));
			// Process template
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"name"				=> _prepare_html($table_name),
				"engine"			=> _prepare_html($table_info["engine"]),
				"num_rows"			=> intval($table_info["rows"]),
				"data_size"			=> common()->format_file_size($table_info["data_size"]),
				"collation"			=> _prepare_html($table_info["collation"]),
				"view_link"			=> "./?object=db_parser&table=".$small_table_name,
				"show_create_link"	=> "./?object=".$_GET["object"]."&action=show_create_table&table=".$small_table_name,
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&table=".$small_table_name,
				"export_link"		=> "./?object=".$_GET["object"]."&action=export&table=".$small_table_name,
				"structure_link"	=> "./?object=".$_GET["object"]."&action=structure&table=".$small_table_name,
			);
			$tables .= tpl()->parse($_GET["object"]."/table_item", $replace2);
		}
		$actions = array(
			"truncate"	=> "truncate",
			"drop"		=> "drop",
			"check"		=> "check",
			"optimize"	=> "optimize",
			"repair"	=> "repair",
		);
		$first_element = array("0" => t(" - select - "));
		$actions = my_array_merge($first_element, $actions);
		$actions_select_box = common()->select_box("group_action_select", $actions, $_GET["id"], false, 2, "onchange='group_action();'", false);
		
		// Process template
		$replace = array(
			"tables"			=> $tables,
			"total"				=> intval($i),
			"total_rows"		=> intval($total_rows),
			"total_size"		=> common()->format_file_size($total_size),
			"form_action"		=> "./?object=".$_GET["object"]."&action=import",
			"export_link"		=> "./?object=".$_GET["object"]."&action=export",
			"global_check_link"	=> "./?object=".$_GET["object"]."&action=check_structure",
			"backup_link"		=> "./?object=".$_GET["object"]."&action=show_backup",
			"ajax_status_link"	=> "./?object=".$_GET["object"]."&action=ajax_status",
			
			"actions_select_box"=> $actions_select_box,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}
	
	/**
	*
	*/
	function structure () {
		$table_name = DB_PREFIX.$_GET["table"];
		
		$Q = db()->query("SHOW FULL COLUMNS FROM ".$table_name."");
		while ($A = db()->fetch_assoc($Q)) {
			$table_info[] = $A;
			
			$replace2 = array(
				"field"		=> $A["Field"],
				"type"		=> $A["Type"],
				"collation"		=> $A["Collation"],
				"null"		=> $A["Null"],
				"key"		=> $A["Key"],
				"default"	=> $A["Default"],
				"extra"		=> $A["Extra"],
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
			);
			
			
			$items .= tpl()->parse($_GET["object"]."/structure_item", $replace2);
		}
		
		// show index for table
		$Q = db()->query("SHOW INDEX FROM ".$table_name."");
		while ($A = db()->fetch_assoc($Q)) {
			$table_index[] = $A;
		}
		
		
		$replace = array(
			"items"				=> $items,
			"table_index"		=> $table_index,
			"show_create_table"		=> $table_index,
			"show_create_table"		=> $this->show_create_table(true),
		);
		
		return tpl()->parse($_GET["object"]."/structure_main", $replace);
	}
	
	/**
	*
	*/
	function truncate () {
		main()->NO_GRAPHICS = true;
		
		if(empty($_POST["tables"])){
			return false;
		}
		
		$tables = rtrim($_POST["tables"], ",");
		$tables = explode(",", $tables);
		
		foreach ((array)$tables as $table){
			db()->query("TRUNCATE ".$table."");
		}
		
		echo "<b>truncate <span style='color:green'>complete!</span></b>";
	}
	
	/**
	*
	*/
	function drop () {
		main()->NO_GRAPHICS = true;
		
		if(empty($_POST["tables"])){
			return false;
		}
		
		$tables = rtrim($_POST["tables"], ",");
		$tables = explode(",", $tables);
		
		foreach ((array)$tables as $table){
			db()->query("DROP TABLE ".$table."");
		}
		
		echo "<b>drop <span style='color:green'>complete!</span></b>";
	}
	
	/**
	*
	*/
	function optimize () {
		main()->NO_GRAPHICS = true;
		
		if(empty($_POST["tables"])){
			return false;
		}
		
		$tables = rtrim($_POST["tables"], ",");
		
		$text .= "<table>";
		$Q = db()->query("OPTIMIZE TABLE ".$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= "<tr>";
			$text .= "<td>".$A["Table"]."</td>";
			$text .= "<td>optimize</td>";
			$text .= "<td>".$A["Msg_text"]."</td>";
			$text .= "</tr>";
		}
		$text .= "</table>";
		
		echo $text;
	}
	
	/**
	*
	*/
	function check () {
		main()->NO_GRAPHICS = true;
		
		if(empty($_POST["tables"])){
			return false;
		}
		
		$tables = rtrim($_POST["tables"], ",");

		$text .= "<table>";
		$Q = db()->query("CHECK TABLE ".$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= "<tr>";
			$text .= "<td>".$A["Table"]."</td>";
			$text .= "<td>check</td>";
			$text .= "<td>".$A["Msg_text"]."</td>";
			$text .= "</tr>";
		}
		$text .= "</table>";
		
		
		echo $text;
	}
	
	/**
	*
	*/
	function repair () {
		main()->NO_GRAPHICS = true;
		
		if(empty($_POST["tables"])){
			return false;
		}
		
		$tables = rtrim($_POST["tables"], ",");
		
		$text .= "<table>";
		$Q = db()->query("REPAIR TABLE ".$tables);
		while ($A = db()->fetch_assoc($Q)) {
			$text .= "<tr>";
			$text .= "<td>".$A["Table"]."</td>";
			$text .= "<td>repair</td>";
			$text .= "<td>".$A["Msg_text"]."</td>";
			$text .= "</tr>";
		}
		$text .= "</table>";
		
		echo $text;
	}
	
	
	

	/**
	* AJAX based tables status
	*/
	function ajax_status () {
		main()->NO_GRAPHICS = true;

		$this->AUTO_GET_TABLES_STATUS = true;

		$info = $this->_get_tables_infos();
		foreach ((array)$info as $k => $table_info) {
			$total_rows += $table_info["rows"];
			$total_size += $table_info["data_size"];

			$info[$k]["data_size"]	= common()->format_file_size($table_info["data_size"]);
		}
		$info["__total_rows"] = $total_rows;
		$info["__total_size"] = common()->format_file_size($total_size);

		return print common()->json_encode($info);
	}

	/**
	* Get tables info
	*/
	function _get_tables_infos () {
		// Get detailed tables info
		if ($this->AUTO_GET_TABLES_STATUS) {
			$Q = db()->query("SHOW TABLE STATUS LIKE '".DB_PREFIX."%'");
			while ($A = db()->fetch_assoc($Q)) {
				$table_name = $A["Name"];
				if (substr($table_name, 0, strlen(DB_PREFIX)) != DB_PREFIX) {
					continue;
				}
				$tables_infos[$table_name] = array(
					"name"		=> $table_name,
					"engine"	=> $A["Engine"],
					"rows"		=> $A["Rows"],
					"data_size"	=> $A["Data_length"],
					"collation"	=> $A["Collation"],
				);
			}

		} else {

			// Get just tables names
			$Q = db()->query("SHOW TABLES LIKE '".DB_PREFIX."%'");
			while ($A = db()->fetch_row($Q)) {
				$table_name = $A[0];
				if (substr($table_name, 0, strlen(DB_PREFIX)) != DB_PREFIX) {
					continue;
				}
				$tables_infos[$table_name] = array(
					"name"		=> $table_name,
					"engine"	=> "",
					"rows"		=> "",
					"data_size"	=> "",
					"collation"	=> "",
				);
			}

		}
		return $tables_infos;
	}

	/**
	* Show table structure as "SHOW CREATE TABLE" (specific for MySQL)
	*/
	function show_create_table ($return_text = false) {
		$table_name = DB_PREFIX.$_GET["table"];
		$A = db()->query_fetch("SHOW CREATE TABLE "._es($table_name)."");
		$text = $A["Create Table"];
		// Process template
		$replace = array(
			"table_name"	=> _prepare_html($table_name),
			"text"			=> nl2br(_prepare_html($text, 0)),
		);
		
		
		if($return_text){
			return $replace["text"];
		}
		
		return tpl()->parse($_GET["object"]."/show_create_table", $replace);
	}

	/**
	* Import SQL
	*/
	function import () {

		if (!empty($_FILES['sql_file']["tmp_name"])) {
			$path = $_FILES['sql_file']["tmp_name"];

// FIXME: add ability to parse ZIP files

			//Decompress
			if ($_FILES['sql_file']["type"] == "application/x-gzip") {
				if (@function_exists('gzopen')) {
					$file = @gzopen($path, 'rb');
					if (!$file) {
						return false;
					}
					$content = '';
					while (!gzeof($file)) {
						$content .= gzgetc($file);
					}
					gzclose($file);
				} else {
					return false;
				}
			} elseif($_FILES['sql_file']["type"] == "text/plain" || substr($_FILES['sql_file']["name"],-4,4) == ".sql") {
				$content = file_get_contents($path);
			}

			if (strlen($content) < 20) {
				_re("Filetype not supported");
			}

			$_POST["sql"] = $content;
		}

		$exec_success = false;
		$splitted_sql = array();
		// Process SQL
		$POSTED_SQL = $_POST["sql"] ? $_POST["sql"] : urldecode($_GET["id"]);
		if (!empty($POSTED_SQL)) {
			$_query_time_start = microtime(true);
//			$_POST["sql"] = preg_replace("/^#[^\n]+\$/ims", "", $_POST["sql"]);
			$this->_split_sql($splitted_sql, $POSTED_SQL);
			// Execute SQL
			foreach ((array)$splitted_sql as $item_info) {
				if ($item_info["empty"] == 1) {
					continue;
				}
				$result = db()->query($item_info["query"]);
				if (!$result) {
					$db_error = db()->error();
					_re(t("Error while executing the query<br />\r\n<br />\r\n @text1<br />\r\n<br />\r\nCAUSE: @text2", array("@text1" => nl2br(_prepare_html($item_info["query"], 0)), "@text2" => $db_error["message"])));
					break;
				}
			}
			if (!empty($splitted_sql) && !empty($result) && !common()->_error_exists()) {
				$exec_success = true;
			}
// TODO: display number of affected rows on each query even if debug_mode is turned off
			$_query_exec_time = microtime(true) - $_query_time_start;
		}
		$sql = &$_POST["sql"];
		$num_queries = count($splitted_sql);
		// Try to fetch last result (if the last query type is "SELECT")
		$fetch_result = "";
		if ($num_queries && $num_queries < 100) {
			$last_query = end($splitted_sql);
			$last_query = trim(preg_replace("/^#[^\n]+\$/ims", "", str_replace("\r", "\n", trim($last_query["query"]))));
			$last_query = preg_replace("/[\n]{2,}/ims", "\n", $last_query);
		}
		$last_query_total = 0;
		// Check if we on the right way
		$data = array();
		if ($last_query) {
			$tmp_last_query = preg_replace("#/\*.*?\*/#ms", "", preg_replace("#\s+#", " ", str_replace(array("\r","\n","\t"), " ", trim($last_query))));
			$tmp_last_query = trim($tmp_last_query, ")({}[]");
			list($tmp_first_keyword,) = explode(" ", $tmp_last_query);
			$tmp_first_keyword = strtoupper($tmp_first_keyword);
			if (in_array($tmp_first_keyword, array("SELECT","SHOW","DESCRIBE","EXPLAIN"))) {

				$last_query_total = db()->num_rows($result);
				if (!preg_match("/\sLIMIT\s+[0-9]+/ims", $last_query) && $tmp_first_keyword == "SELECT") {
					$add_sql = " LIMIT 0,30";
				}
				$Q = db()->query($last_query. $add_sql);
				while ($A = db()->fetch_assoc($Q)) {
					$data[] = $A;
				}
			}
		}
		if (!empty($data)) {
			$_first_item = current($data);
			$_num_fields = count($_first_item);
			$fetch_result .= "<pre style='background:#ccc;' align='left'><b>"._prepare_html($last_query)."</b></pre>Total records: <b>".intval($last_query_total)."</b><br />\n";
			$fetch_result .= "<table border='0' cellspacing='0' cellpadding='0' style='border:1px solid black;'>\n";
			$fetch_result .= "<tr valign='top' style='background:#000;color:white;font-weight:bold;'>\n";
			foreach ((array)$_first_item as $_name => $_value) {
				$fetch_result .= "<td style='border:1px solid black;padding-right:2px;padding-left:2px;'><b>".$_name."</b></td>\n";
			}
			$fetch_result .= "</tr>\n";
			foreach ((array)$data as $i => $_fields) {
				$fetch_result .= "<tr valign='top'".(!($i % 2) ? " style='background:#ccc;'" : "").">\n";
				foreach ((array)$_fields as $_name => $_value) {
					if (strlen($_value) > 200) {
						$_value = substr($_value, 0, 200)." ...";
					}
					$fetch_result .= "<td style='border:1px solid black;padding-right:2px;padding-left:2px;'>"._prepare_html($_value)."</b></td>\n";
				}
				$fetch_result .= "</tr>\n";
			}
			$fetch_result .= "</table>\n";
		}
		// Show form
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"error_message"		=> _e(),
			"sql"				=> strlen($sql) < 10000 ? nl2br(_prepare_html($sql, 0)) : t('%num queries executed successfully', array('%num' => $num_queries)),
			"exec_success"		=> (int)($exec_success),
			"exec_time"			=> $_query_exec_time ? common()->_format_time_value($_query_exec_time) : "",
			"back_link"			=> "./?object=".$_GET["object"],
			"num_queries"		=> intval($num_queries),
			"fetch_result"		=> $fetch_result,
		);
		return tpl()->parse($_GET["object"]."/import", $replace);
	}

	/**
	* Export SQL
	*/
	function export ($params = array()) {
		$SINGLE_TABLE = !empty($_GET["table"]) ? DB_PREFIX. $_GET["table"] : "";
		// Gather some statistics
		if ($SINGLE_TABLE) {
			// Get detailed tables info
			$A = db()->query_fetch("SHOW TABLE STATUS LIKE '".$SINGLE_TABLE."'");
			$_single_table_info = array(
				"name"		=> $A["Name"],
				"engine"	=> $A["Engine"],
				"rows"		=> $A["Rows"],
				"data_size"	=> $A["Data_length"],
				"collation"	=> $A["Collation"],
			);
		}
		// Prepare tables list
		if (!isset($this->_tables_names)) {
			foreach ((array)db()->meta_tables() as $cur_table_name) {
				$this->_tables_names[$cur_table_name] = $cur_table_name;
			}
		}
		$SILENT_MODE = $params["silent_mode"];
		$USE_TEMP_FILE = false;
		if (!$SINGLE_TABLE || $_single_table_info["rows"] >= 10000 || $_single_table_info["size"] >= 1000000) {
			$USE_TEMP_FILE = true;
		}
// check for uses
		if(!empty($params["where"])){
			$USE_TEMP_FILE = false;
		}
		// Do export SQL
		if (!empty($_POST["go"]) || $SILENT_MODE) {

			set_time_limit(600);

			// Prepare params
			if ($params["single_table"]) {
				$SINGLE_TABLE = $params["single_table"];
			}
			$TABLES = $_POST["tables"];
			if ($params["tables"]) {
				$TABLES = $params["tables"];
			}
			$INSERT_FULL		= $_POST["full_inserts"];
			if ($params["full_inserts"]) {
				$INSERT_FULL = $params["full_inserts"];
			}
			$INSERT_EXTENDED	= $_POST["ext_inserts"];
			if ($params["ext_inserts"]) {
				$INSERT_EXTENDED = $params["ext_inserts"];
			}
			$EXPORT_TYPE = $_POST["export_type"];
			if ($params["export_type"]) {
				$EXPORT_TYPE = $params["export_type"];
			}
			$EXPORTED_SQL		= "";
			$tables_to_export = array();
			// Do export single table
			if (!empty($SINGLE_TABLE)) {
				$tables_to_export[$SINGLE_TABLE] = $params["where"][$SINGLE_TABLE];
			} elseif (!empty($TABLES)) {
				// Do export multiple tables
				foreach ((array)$TABLES as $cur_table_name) {
					if (!isset($this->_tables_names[$cur_table_name])) {
						continue;
					}
					$tables_to_export[$cur_table_name] = $params["where"][$cur_table_name];
				}
			} else {
				// By default we exporting all tables datas
				foreach ((array)$this->_tables_names as $v) {
					$tables_to_export[$v] = $params["where"][$v];
				}
			}
			// Check for errors
			if (empty($tables_to_export)) {
				_re(t("No tables to export!"));
			}
			if (!isset($this->_export_types[$EXPORT_TYPE])) {
				_re(t("Wrong export type!"));
			}
			// Try mysqldump
// checking
			if ($USE_TEMP_FILE) {
				$_temp_file_path = $this->_quick_export_with_mysqldump($tables_to_export);
				if ($_temp_file_path && file_exists($_temp_file_path) && filesize($_temp_file_path) > 2) {
					$QUICK_DUMPED = true;
				}
			}
				// Set defence from overloading
// TODO
//				$tables_infos $this->_get_tables_infos();
			// Do process exporting
			if (!common()->_error_exists() && !$QUICK_DUMPED) {

				if ($USE_TEMP_FILE) {
					$_temp_file_name	= "db_export".($SINGLE_TABLE ? "__".$SINGLE_TABLE : "")."_".date("YmdHis", time()).".sql";
					$_temp_file_path	= INCLUDE_PATH."uploads/tmp/".$_temp_file_name;
					_mkdir_m(dirname($_temp_file_path));
					if (file_exists(dirname($_temp_file_path))) {
						$fh = fopen($_temp_file_path, "w");
						// Second temp file
						$_temp_file_name2	= $_temp_file_name.".tmp";
						$_temp_file_path2	= $_temp_file_path.".tmp";
					} else {
						$USE_TEMP_FILE = false;
					}
				}
				if ($params["add_create_table"]) {
					$_add_create_table = "\n/*!40101 SET NAMES utf8 */;\n";
					if ($USE_TEMP_FILE) {
						fwrite($fh, $_add_create_table);
					} else {
						$EXPORTED_SQL	= $_add_create_table;
					}
				}
				// Process selected tables
				foreach ((array)$tables_to_export as $cur_table_name => $WHERE_COND) {
					$sql_1 = $sql_2 = $sql_3 = $sql_4 = "";
					$cols_names_array = array();
					$counter = 0;
					if ($params["add_create_table"]) {
						$A = db()->query_fetch("SHOW CREATE TABLE ".db()->enclose_field_name($cur_table_name));
						$_table_sql_header = "\nDROP TABLE IF EXISTS ".db()->enclose_field_name($cur_table_name).";\n";
						$_table_sql_header .= str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $A["Create Table"]).";\n\n";
						if ($USE_TEMP_FILE) {
							fwrite($fh, $_table_sql_header);
						} else {
							$EXPORTED_SQL	.= $_table_sql_header;
						}
					}
					// Get colums for the current table
					$meta_columns = db()->meta_columns($cur_table_name);
					foreach ((array)$meta_columns as $cur_col_name => $cur_col_info) {
						$cols_names_array[$cur_col_name] = db()->enclose_field_name($cur_col_name);
					}
					$sql_1	= ($EXPORT_TYPE == "insert" ? "INSERT" : "REPLACE")." INTO ".db()->enclose_field_name($cur_table_name)." ";
					$sql_2	= $INSERT_FULL ? "(".implode(", ", $cols_names_array).") " : "";
					$sql_3	= "VALUES \n";
					// Get data for the current table
					$Q = db()->query(
						"SELECT * FROM ".db()->enclose_field_name(_es($cur_table_name))
						.($WHERE_COND ? " WHERE ".$WHERE_COND : "")
					);
					if (!db()->num_rows($Q)) {
						continue;
					}
					// Write into temporary file
					if ($USE_TEMP_FILE) {
						$fh2 = fopen($_temp_file_path2, "w");
						if ($INSERT_EXTENDED) {
							fwrite($fh2, $sql_1. $sql_2. $sql_3);
						}
					}
					while ($A = db()->fetch_assoc($Q)) {
						$cols_values_array = array();
						foreach ((array)$meta_columns as $cur_col_name => $cur_col_info) {
							$cols_values_array[$cur_col_name] = db()->enclose_field_value(_es(stripslashes($A[$cur_col_name])));
						}
						$need_break		= $INSERT_EXTENDED && $counter >= $this->EXPORT_EXTENDED_PER_BLOCK;
						if ($need_break && strlen($sql_4)) {
							$sql_4 = substr($sql_4, 0, -2).";";
							if ($USE_TEMP_FILE && $fh2) {
								fseek($fh2, -2, SEEK_CUR);
								fwrite($fh2, ";");
							}
						}
						$sql_4_tmp = "";
						$sql_4_tmp .= !$INSERT_EXTENDED || $need_break ? "\n".$sql_1. $sql_2. $sql_3 : "";
						$sql_4_tmp .= "(".implode(", ", $cols_values_array).")";
						$sql_4_tmp .= $INSERT_EXTENDED ? "," : ";";
						$sql_4_tmp .= "\n";
						// Break counter
						if ($need_break) {
							$counter = 0;
						} else {
							$counter++;
						}
						// Write into temporary file
						if ($USE_TEMP_FILE && $fh2) {
							fwrite($fh2, $sql_4_tmp);
						} else {
							$sql_4 .= $sql_4_tmp;
						}
					}
					// Cut trailing comma
					if ($INSERT_EXTENDED) {
						$sql_4 = substr($sql_4, 0, -2).";";
						if ($USE_TEMP_FILE && $fh2) {
							fseek($fh2, -2, SEEK_CUR);
							fwrite($fh2, ";");
						}
					}
					// Write into temporary file
					if ($USE_TEMP_FILE && $fh2) {
						fclose($fh2);
					}
					// Glue all SQL parts togetther with options
					if ($USE_TEMP_FILE) {
						fwrite($fh, file_get_contents($_temp_file_path2));
						unlink($_temp_file_path2);
					} else {
						$EXPORTED_SQL .= ($INSERT_EXTENDED ? $sql_1. $sql_2. $sql_3 : ""). $sql_4. "\n";
					}
				}
				if ($USE_TEMP_FILE) {
					fclose($fh);
				}
			}
			$EXPORTED_SQL = trim($EXPORTED_SQL);
			// Compress SQL and throw as file
			if ($_POST["compress"]) {
				// Create temporary name
				$_exported_name = "export".($SINGLE_TABLE ? "__".$SINGLE_TABLE : "").".sql";

				if ($USE_TEMP_FILE) {
					$_exported_file_path = $_temp_file_path;
				} else {
					$_exported_file_path = INCLUDE_PATH."uploads/tmp/".$_exported_name;
					_mkdir_m(dirname($_exported_file_path));
					if (file_exists(dirname($_exported_file_path))) {
						file_put_contents($_exported_file_path, $EXPORTED_SQL);
					}
				}
			}
			// Compress, stage 2
			if ($_POST["compress"] && file_exists($_exported_file_path) && filesize($_exported_file_path) > 2) {
				// Free some memory
				$EXPORTED_SQL = null;
				// Try to Gzip result (degrade gracefully if could not gzip)
				$gzip_path	= defined("OS_WINDOWS") && OS_WINDOWS ? "d:\\" : "";
				exec($gzip_path."gzip -fq9 ".$_exported_file_path);
				if (file_exists($_exported_file_path.".gz") && filesize($_exported_file_path.".gz") > 2) {
					if (file_exists($_exported_file_path)) {
						unlink($_exported_file_path);
					}
					$_exported_name			.= ".gz";
					$_exported_file_path	.= ".gz";
				// Manual method
				} elseif (function_exists("gzwrite")) {
					$gz = gzopen ($_exported_file_path.".gz", 'w1');
					gzwrite ($gz, file_get_contents($_exported_file_path));
					gzclose ($gz);
					if (file_exists($_exported_file_path.".gz") && filesize($_exported_file_path.".gz") > 2) {
						unlink($_exported_file_path);
						$_exported_name			.= ".gz";
						$_exported_file_path	.= ".gz";
					}
				}
				// Throw headers
				main()->NO_GRAPHICS = true;
				header("Content-Type: application/force-download; name=\"".$_exported_name."\"");
				header("Content-Disposition: attachment; filename=\"".$_exported_name."\"");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".intval(filesize($_exported_file_path)));
				// Throw content
				readfile($_exported_file_path);
				// Cleanup temp
				unlink($_exported_file_path);
				exit();
				return false; // Not needed with exit(), but leave it here :-)
			}
			if ($USE_TEMP_FILE && file_exists($_temp_file_path)) {
				$EXPORTED_SQL = file_get_contents($_temp_file_path);
				unlink($_temp_file_path);
			}
			// Silently return output
			if ($SILENT_MODE) {
				return $EXPORTED_SQL;
			}
			// Text mode export
			if (!common()->_error_exists()) {
				$replace2 = array(
					"sql_text"	=> _prepare_html($EXPORTED_SQL, 0),
					"back_link"	=> "./?object=".$_GET["object"],
				);
				return tpl()->parse($_GET["object"]."/export_text_result", $replace2);
			}
		}
		// Show form
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]._add_get(),
			"error_message"		=> _e(),
			"back_link"			=> "./?object=".$_GET["object"],
			"single_table"		=> _prepare_html($SINGLE_TABLE),
			"tables_box"		=> $this->_box("tables", ""),
			"export_type_box"	=> $this->_box("export_type", ""),
			"compress_box"		=> $this->_box("compress", ""),
			"table_num_rows"	=> intval($_single_table_info["rows"]),
			"table_size"		=> common()->format_file_size($_single_table_info["data_size"]),
		);
		return tpl()->parse($_GET["object"]."/export", $replace);
	}

	/**
	* Try quick export with mysqldump
	*/
	function _quick_export_with_mysqldump($tables_to_export = array()) {
		if (count($tables_to_export) == 1) {
			$SINGLE_TABLE = current($tables_to_export);
		}
		// Prepare temp name
		$_temp_file_name	= "db_export".($SINGLE_TABLE ? "__".$SINGLE_TABLE : "")."_".date("YmdHis", time()).".sql";
		$_temp_file_path	= INCLUDE_PATH."uploads/tmp/".$_temp_file_name;
		_mkdir_m(dirname($_temp_file_path));

		$mysql_path	= defined("OS_WINDOWS") && OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : "";

		$cmd = $mysql_path."mysqldump --host=".DB_HOST." --user=".DB_USER." --password=".DB_PSWD." "
					. "--opt --comments=false --quote-names "
					. DB_NAME." ".implode(" ", array_keys($tables_to_export))
					. " > ".$_temp_file_path;
		exec($cmd);

		return $_temp_file_path;
	}

	/**
	* Sort array of files by creation date (use for usort)
	*/
	function _sort_by_date ($a, $b) {
		if ($a["file_mtime"] == $b["file_mtime"]) {
			return 0;
		}
		return ($a["file_mtime"] > $b["file_mtime"]) ? -1 : 1;
	}

	/**
	* Show available backups and backuping form
	*/
	function show_backup() {
		$backup_folder_path = INCLUDE_PATH. $this->BACKUP_PATH;

		if ($_FILES['import_file']['tmp_name']){
			$import_data = file_get_contents($_FILES['import_file']['tmp_name']);
			file_put_contents($backup_folder_path. $_FILES['import_file']['name'], $import_data);
		}

		// Find all backups in backup folder
		$backup_files = _class("dir")->scan_dir($backup_folder_path, true, "/\.(sql|gz)$/i");

		$_files_infos = array();
		if (!empty($backup_files)) {		
			foreach ((array)$backup_files as $fpath) {
				$_files_infos[] = array(
					"fpath"		=> $fpath,
					"file_mtime"=> filemtime($fpath),
					"file_size"	=> filesize($fpath),
				);
			}
		}
		usort($_files_infos, array(&$this, "_sort_by_date"));
		foreach ((array)$_files_infos as $_info) {
			$fpath = $_info["fpath"];
			$id = urlencode(basename($fpath));
			$replace2 = array(
				"backup_date"	=> _format_date($_info["file_mtime"], "long"),
				"backup_fsize"	=> common()->format_file_size($_info["file_size"]),
				"backup_name"	=> basename($fpath),
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete_backup&id=".$id,
				"restore_url"	=> "./?object=".$_GET["object"]."&action=restore&id=".$id,
				"download_url"	=> "./?object=".$_GET["object"]."&action=export_backup&id=".$id,
			);
			$items .= tpl()->parse($_GET["object"]."/backup_item", $replace2);
		}

		// Show form
		$replace = array(
			"items"				=> $items,
			"form_action"		=> "./?object=".$_GET["object"]."&action=backup",
			"import_form_action"=> "./?object=".$_GET["object"]."&action=show_backup",
			"error_message"		=> _e(),
			"back_link"			=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/backup", $replace);
	}


	/**
	* Delete backup file
	*/
	function delete_backup() {
		$fname = urldecode($_GET["id"]);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {
			unlink($fpath);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Export backup
	*/
	function export_backup() {
		$fname = urldecode($_GET["id"]);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {

			$body = file_get_contents($fpath);
			main()->NO_GRAPHICS = true;
			// Throw headers
			header("Content-Type: application/force-download; name=\"".$fname."\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".strlen($body));
			header("Content-Disposition: attachment; filename=\"".$fname."\"");
			// Throw content
			echo $body;
		}
		exit;
	}

	/**
	* Restore from backup
	*/
	function restore() {
		$fname = urldecode($_GET["id"]);
		$fpath = INCLUDE_PATH. $this->BACKUP_PATH. $fname;
		if (file_exists($fpath)) {
			$command = (OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : "")."mysql --user=".DB_USER." --password=".DB_PSWD." --host=".DB_HOST." ".DB_NAME." < \"".$fpath."\"";
			$result = system($command);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* backup db
	*/
	function backup($silent_mode = false) {
		$fname_start	= $_SERVER["HTTP_HOST"];
		$backup_path	= INCLUDE_PATH. $this->BACKUP_PATH;
		$mysql_path		= OS_WINDOWS ? $this->MYSQL_CLIENT_PATH : "";
		$gzip_path		= OS_WINDOWS ? "d:\\" : "";

		if (!file_exists($backup_path)) {
			mkdir($backup_path);
			file_put_contents($backup_path.".htaccess", "Order Allow, Deny\r\nDeny From All");
		}

		$backup_name = $backup_path. $fname_start."-".date("YmdHis").".sql";
		// Backup with mysqldump
		$cmd = $mysql_path."mysqldump --user=".DB_USER." ".(DB_PSWD ? "-p ".DB_PSWD : "")." --opt --comments=false --quote-names ".DB_NAME." > ".$backup_name;
		exec($cmd);

		// Success with mysqldump
		if (!file_exists($backup_name)) {
			// Try our internal exporter method
			// Prepare db export params
			$params = array(
				"single_table"		=> "",
				"tables"			=> "",//array(db('menus'), db('menu_items')),
				"full_inserts"		=> 1,
				"ext_inserts"		=> 1,
				"export_type"		=> "insert",
				"silent_mode"		=> true,
				"add_create_table"	=> true,
			);
			$EXPORTED_SQL = $this->export($params);
			if (!function_exists("_file_put_contents")) {
				$this->_file_put_contents($backup_name, $EXPORTED_SQL);
			}
		}
		// Gzip result
		exec($gzip_path."gzip -fq9 ".$backup_name);
		if (file_exists($backup_name.".gz") && filesize($backup_name.".gz") > 2) {
			if (file_exists($backup_name)) {
				unlink($backup_name);
			}
			$backup_name .= ".gz";
		}

		// Garbage collect
		$files = _class("dir")->scan_dir($backup_path, true, "/\.(sql|gz)$/i");
		foreach ((array)$files as $item_name) {
			$mtimes[filemtime($item_name)] = $item_name;						
		}

		$max_files = $this->MAX_BACKUP_FILES; // Number of old files to leave
		$num_files = count($files);
		if ($num_files > $max_files) {
			ksort($mtimes);
			foreach ((array)$mtimes as $v) {
				unlink($v);
				$num_files--;
				if ($num_files <= $max_files) {
					break;
				}
			}
		}

		if ($silent_mode) {
			return $backup_name;
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* put data from db in file
	*/
	function _file_put_contents ($filename, $data) {
		if (!$fp = @fopen($filename, "w")) return false;
		$res = fwrite($fp, $data, strlen($data));
		fclose ($fp);
		return $res;
	}

	/**
	* Removes comment lines and splits up large sql files into individual queries
	*
	* @param   array	the splitted sql commands
	* @param   string   the sql commands
	*
	* @return  boolean  always true
	* @access  public
	*/
	function _split_sql(&$ret, $sql) {
		// do not trim
		$sql			= rtrim($sql, "\n\r");
		$sql_len		= strlen($sql);
		$char			= '';
		$string_start	= '';
		$in_string		= FALSE;
		$nothing	 	= TRUE;
		$time0			= time();
		$is_headers_sent= headers_sent();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];
			// We are in a string, check for not escaped end of strings except for
			// backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i		 = strpos($sql, $string_start, $i);
					// No end of string found -> add the current substring to the
					// returned array
					if (!$i) {
						$ret[] = array('query' => $sql, 'empty' => $nothing);
						return TRUE;
					}
					// Backquotes or no backslashes before quotes: it's indeed the
					// end of the string -> exit the loop
					else if ($string_start == '' || $sql[$i-1] != '\\') {
						$string_start	  = '';
						$in_string		 = FALSE;
						break;
					}
					// one or more Backslashes before the presumed end of string...
					else {
						// ... first checks for escaped backslashes
						$j					 = 2;
						$escaped_backslash	 = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the end of the
						// string -> exit the loop
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string	 = FALSE;
							break;
						}
						// ... else loop
						else {
							$i++;
						}
					}
				}
			}
			// lets skip comments (/*, -- and #)
			else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
				$i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
				// didn't we hit end of string?
				if ($i === FALSE) {
					break;
				}
				if ($char == '/') $i++;
			}
			// We are not in a string, first check for delimiter...
			else if ($char == ';') {
				// if delimiter found, add the parsed part to the returned array
				$ret[]	  = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
				$nothing	= TRUE;
				$sql		= ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len	= strlen($sql);
				if ($sql_len) {
					$i	  = -1;
				} else {
					// The submited statement(s) end(s) here
					return TRUE;
				}
			}
			// ... then check for start of a string,...
			else if (($char == '"') || ($char == '\'') || ($char == '')) {
				$in_string	= TRUE;
				$nothing	  = FALSE;
				$string_start = $char;
			} elseif ($nothing) {
				$nothing = FALSE;
			}
			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1	 = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
				if (!$is_headers_sent) {
					header('X-YFPing: Pong');
				}
			}
		}
		// add any rest to the returned array
		if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
			$ret[] = array('query' => $sql, 'empty' => $nothing);
		}
		return TRUE;
	}

	/**
	* Reads (and decompresses) a (compressed) file into a string
	*
	* @param   string   the path to the file
	* @param   string   the MIME type of the file, if empty MIME type is autodetected
	*
	* @return  string   the content of the file or
	*		  boolean  FALSE in case of an error.
	*/
	function _read_sql_file($path, $mime = '') {
		if (!file_exists($path)) {
			return FALSE;
		}
		switch ($mime) {
			case '':
				$file = @fopen($path, 'rb');
				if (!$file) {
					return FALSE;
				}
				$test = fread($file, 3);
				fclose($file);
				if ($test[0] == chr(31) && $test[1] == chr(139)) {
					return $this->_read_sql_file($path, 'application/x-gzip');
				}
				if ($test == 'BZh') {
					return $this->_read_sql_file($path, 'application/x-bzip');
				}
				if ($test == 'PK'.chr(3)) {
					return $this->_read_sql_file($path, 'application/zip');
				}
				return $this->_read_sql_file($path, 'text/plain');
			case 'text/plain':
				$file = @fopen($path, 'rb');
				if (!$file) {
					return FALSE;
				}
				$content = fread($file, filesize($path));
				fclose($file);
				break;
			case 'application/x-gzip':
				if (@function_exists('gzopen')) {
					$file = @gzopen($path, 'rb');
					if (!$file) {
						return FALSE;
					}
					$content = '';
					while (!gzeof($file)) {
						$content .= gzgetc($file);
					}
					gzclose($file);
				} else {
					return FALSE;
				}
				break;
			case 'application/x-bzip':
				if (@function_exists('bzdecompress')) {
					$file = @fopen($path, 'rb');
					if (!$file) {
						return FALSE;
					}
					$content = fread($file, filesize($path));
					fclose($file);
					$content = bzdecompress($content);
				} else {
					return FALSE;
				}
				break;
			case 'application/zip':
// FIXME: need to add decompress code
/*
*/
				break;
			default:
				return FALSE;
		}
		return $content;
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Create Backup",
				"url"	=> "./?object=".$_GET["object"]."&action=show_backup",
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Database Manager");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
			"export"			=> "Export SQL",
			"show_create_table" => "",
		);			  		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}

	function _hook_widget__db_tables ($params = array()) {
// TODO
	}
}
