<?php

/**
* Database abstraction layer (Using ADODB library)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_adodb_db {

	/** @var string Type of database (default) @conf_skip */
	public $DB_TYPE				= "mysql";
	/** @var bool Use tables names caching @conf_skip */
	public $CACHE_TABLE_NAMES		= true;
	/** @var int @conf_skip Number of queries */
	public $NUM_QUERIES			= 0;
	/** @var array  @conf_skip Query log array */
	public $QUERY_LOG				= array();
	/** @var int Tables cache lifetime (while developing need to be short) (else need to be very large) @conf_skip */
	public $TABLE_NAMES_CACHE_TTL	= 86400; // @var 1*3600*24 = 1 day
	/** @var bool Auto-connect on/off @conf_skip */
	public $AUTO_CONNECT			= false;
	/** @var bool Use backtrace in error message @conf_skip */
	public $ERROR_BACKTRACE		= true;
	/** @var string Folder where databases drivers are stored @conf_skip */
	public $DB_DRIVERS_DIR			= "classes/db_drivers/";
	/** @var int Num tries to reconnect (will be useful if db server is overloaded) @conf_skip */
	public $NUM_RECONNECT_TRIES	= 3; // @var Set to "0" for disabling
	/** @var int Time to wait between reconnects (in seconds) @conf_skip */
	public $RECONNECT_DELAY		= 1;
	/** @var bool Connection required or not (else E_USER_WARNING will be thrown not E_USER_ERROR) @conf_skip */
	public $CONNECTION_REQUIRED	= 0;
	/** @var mixed @conf_skip Internal vars (default values) @conf_skip */
	public $_tried_to_connect		= false;
	/** @var mixed @conf_skip Internal var (default values) @conf_skip */
	public $_connected				= false;

	/**
	* Constructor
	*/
	function yf_adodb_db ($db_type = "", $no_parse_tables = 0) {
		// Add path to the ADODB library
		set_include_path (YF_PATH. "libs/adodb". PATH_SEPARATOR. get_include_path());
		require_once("adodb.inc.php");
		// Tables names cache file
		$this->_cache_tables_file = INCLUDE_PATH. "core_cache/cache_db_tables.php";
		// Type of database server
		$this->DB_TYPE = !empty($db_type) ? $db_type : DB_TYPE;
		// Perform auto-connection to db if needed
		if ($this->AUTO_CONNECT || MAIN_TYPE_ADMIN) $this->connect();
		// Put all table names into constants
		if (empty($no_parse_tables)) $this->_parse_tables();
	}

	/**
	* Connect db driver and then connect to db
	*/
	function connect($db_host = "", $db_user = "", $db_pswd = "", $db_name = "") {
		if (!empty($this->_tried_to_connect)) return $this->_connected;
		// Create new instanse of the driver class
		if (!is_object($this->db)) {
			$this->db = &ADONewConnection($this->DB_TYPE);
			// Try to connect several times
			for ($i = 0; $i <= $this->NUM_RECONNECT_TRIES; $i++) {
				$this->db->Connect(!empty($db_host) ? $db_host : DB_HOST, !empty($db_user) ? $db_user : DB_USER, !empty($db_pswd) ? $db_pswd : DB_PSWD, !empty($db_name) ? $db_name : DB_NAME, false);
				// Stop after success
				if ($this->db->IsConnected()) break;
				// Else wait some time and try again
				else sleep($this->RECONNECT_DELAY);
			}
			$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
		}
		// Stop execution If connection has failed
		if (!$this->db->IsConnected()) {
			trigger_error("DB: ERROR CONNECTING TO DATABASE", $this->CONNECTION_REQUIRED ? E_USER_ERROR : E_USER_WARNING);
			$this->_tried_to_connect = true;
		} else $this->_connected = true;
		return $this->_connected;
	}

	/**
	* Function return resource ID of the query
	*/
	function _parse_tables() {
		$included = false;
		// Get table names from cache
		clearstatcache();
		if (!empty($this->CACHE_TABLE_NAMES) && file_exists($this->_cache_tables_file)) {
			// Refresh cache file after 1 day
			if (filemtime($this->_cache_tables_file) < (time() - $this->TABLE_NAMES_CACHE_TTL)) {
				unlink($this->_cache_tables_file);
			} else {
				include($this->_cache_tables_file);
				$included = true;
			}
		}
		if (empty($included)) {
			// Do connect (if not done yet)
			if (!is_object($this->db)) $this->connect();
			// Do get current database tables array
			$tmp_tables = $this->meta_tables();
			// Clean up tables from system prefixes
			foreach ((array)$tmp_tables as $table_name) $tables["dbt_".substr(str_replace("sys_","",$table_name), strlen(DB_PREFIX))] = $table_name;
			// Process cleaned up tables
			foreach ((array)$tables as $k => $v) define($k, $v);
			// Put tables names to cache
			if (!empty($this->CACHE_TABLE_NAMES)) {
				foreach ((array)$tables as $k => $v) $file_text .= "define('".$k."','".$v."');\r\n";
				file_put_contents($this->_cache_tables_file, "<?php\r\n".$file_text."?>");
			}
		}
	}

	/**
	* Function return resource ID of the query
	*/
	function &query($sql, $REPLACE_QUOTES = true) {
		if (!$this->_connected) if (!$this->connect()) return false;
		$this->NUM_QUERIES++;
		if (DEBUG_MODE) {
			$this->_query_time_start = microtime(true);
		}
		// Simple replacing backticks (`) into double quotes (") for all dbs other than MySQL
		if (substr($this->DB_TYPE, 0, 5) != "mysql" && $REPLACE_QUOTES) {
			$sql = str_replace("`", "\"", $sql);
		}
		// Do execute SQL query
		$result = &$this->db->Execute($sql);
		if (!$result && main()->USE_CUSTOM_ERRORS) {
			$db_error = $this->db->error();
			if (DEBUG_MODE && $this->ERROR_BACKTRACE) {
				$backtrace	= debug_backtrace();
				$back_step	= $backtrace[1]["class"] != "db" ? 1 : 2;
				$trace_text	= " (<i> in \"".$backtrace[$back_step]["file"]."\" on line ".$backtrace[$back_step]["line"]."</i>) ";
			}
			trigger_error("DB: QUERY ERROR: ".$sql."<br />\r\n<b>CAUSE</b>: ".print_r($this->db->ErrorMsg(), 1). $trace_text."<br />\r\n", E_USER_WARNING);
		}
		if (DEBUG_MODE) {
			$this->QUERY_LOG[] = $sql;
			$this->QUERY_EXEC_TIME[] = (float)microtime(true) - (float)$this->_query_time_start;
		}
		return $result;
	}

	/**
	* Function execute unbuffered query
	*/
	function unbuffered_query($sql) {
		$result = &$this->db->Execute($sql);
		$this->NUM_QUERIES++;
		if (DEBUG_MODE) $this->QUERY_LOG[] = $sql;
	}

	/**
	* Function return fetched array with both text and numeric indexes
	*/
	function fetch_array(&$result) {
		if (!is_object($result)) return false;
		$fields = $result->fields;
		$result->MoveNext();
		return $fields;
	}

	/**
	* Function return fetched array with text indexes
	*/
	function fetch_assoc(&$result) {
		if (!is_object($result)) return false;
		$fields = $result->fields;
		$result->MoveNext();
		return $fields;
	}

	/**
	* Function return fetched array with numeric indexes
	*/
	function fetch_row (&$result) {
		if (!is_object($result)) return false;
		// Save old fetch mode
		$save_old =	$this->db->fetchMode;
		$this->db->SetFetchMode(ADODB_FETCH_NUM);
		// Do get result
		$fields = $result->fields;
		$result->MoveNext();
		// Restore fetch mode
		$this->db->SetFetchMode($save_old);
		return $fields;
	}

	/**
	* Function return number of rows in the query
	*/
	function num_rows (&$result) {
		if (!is_object($result)) return false;
		return $result->NumRows();
	}

	/**
	* Function escapes characters for using in query
	*/
	function real_escape_string ($string) {
		return $this->db->addq($string);
	}

	/**
	* Function escapes characters for using in query
	*/
	function escape_string ($string) {
		return $this->db->addq($string);
	}

	/**
	* Execute database query and fetch result as assoc array (for queries that returns only 1 row)
	*/
	function query_fetch($query) {
		$result = &$this->query($query);
		if (!is_object($result)) return false;
		$fields = $result->fields;
		return $fields;
	}

	/**
	* Alias for the "query_fetch"
	*/
	function query_fetch_assoc($query) {
		return $this->query_fetch($query);
	}

	/**
	* Same as "query_fetch" except fetching as row not assoc
	*/
	function query_fetch_row($query) {
		$result = &$this->query($query);
		if (!is_object($result)) return false;
		$fields = $this->fetch_row($result);
		return $fields;
	}

	/**
	* Execute database query and fetch result into assotiative array
	*/
	function query_fetch_all($query, $key_name = null) {
		// Do the query
		$Q = $this->query($query);
		// If $key_name is specified - then save to $result_set using it as key
		if ($key_name != null) {
			while ($A = $this->db->fetch_assoc($Q)) $result_set[$A[$key_name]] = $A;
		} else {
			while ($A = $this->db->fetch_assoc($Q)) $result_set[] = $A;
		}
		return $result_set;
	}

	/**
	* Execute database query and the calculate number of rows
	*/
	function query_num_rows($query) {
		$result = &$this->query($query);
		if (!is_object($result)) return false;
		return $result->NumRows();
	}

	/**
	* Real escape string (alias)
	*/
	function es($string) {
		return $this->real_escape_string($string);
	}

	/**
	* Real escape string with _filter_text (quick alias)
	*/
	function esf($string, $length = 0) {
		$string = _filter_text($string, $length);
		return $this->real_escape_string($string);
	}

	/**
	* Return meta columns info for selected table
	*/
	function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false) {
		return $this->db->MetaColumns($table, $KEYS_NUMERIC, $FULL_INFO);
	}

	/**
	* Return meta tables info
	*/
	function meta_tables() {
		return $this->db->MetaTables();
	}

	/**
	* Free result assosiated with a given query resource
	*/
	function free_result($result) {
		return $this->db->_close($result);
	}

	/**
	* Return database error
	*/
	function error() {
		return $this->db->ErrorMsg();
	}

	/**
	* Return last insert id
	*/
	function insert_id() {
		return $this->db->Insert_ID();
	}

	/**
	* Get number of affected rows
	*/
	function affected_rows() {
		return $this->db->Affected_Rows();
	}

	/**
	* Get server version
	*/
	function get_server_version() {
		$data = $this->db->ServerInfo();
		return $data['version'];
	}

	/**
	* Select limited number of rows
	*/
	function &select_limit($sql, $numrows = -1, $offset = -1, $inputarr = false) {
		$result = &$this->db->SelectLimit($sql, $numrows, $offset, $inputarr);
//		$result = &$this->db->SelectLimit2($sql, $numrows, $offset, $inputarr);
		if (DEBUG_MODE) $this->QUERY_LOG[] = $sql;
		return $result;
	}
}
