<?php

/**
 * Database abstraction layer
 *
 * @package		YF
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_db {

	/** @var string Type of database (default) */
	public $DB_TYPE					= 'mysql';
	/** @var bool Use tables names caching */
	public $CACHE_TABLE_NAMES		= false;
	/** @var int @conf_skip Number of queries */
	public $NUM_QUERIES				= 0;
	/** @var array Query log array */
	public $_LOG					= array();
	/** @var int Tables cache lifetime (while developing need to be short) (else need to be very large) */
	public $TABLE_NAMES_CACHE_TTL	= 3600; // 1*3600*24 = 1 day
	/** @var bool Auto-connect on/off */
	public $AUTO_CONNECT			= false;
	/** @var bool Use backtrace in error message */
	public $ERROR_BACKTRACE			= true;
	/** @var bool Use backtrace to get where query was called from (will be used only when DEBUG_MODE is enabled) */
	public $USE_QUERY_BACKTRACE		= true;
	/** @var bool Auto-repairing on error (table not exists) on/off */
	public $ERROR_AUTO_REPAIR		= true;
	/** @var string Folder where databases drivers are stored */
	public $DB_DRIVERS_DIR			= 'classes/db/';
	/** @var int Num tries to reconnect in common mode (will be useful if db server is overloaded) (Set to '0' for disabling) */
	public $RECONNECT_NUM_TRIES		= 3;
	/** @var int Num tries to reconnect inside CONSOLE_MODE (will be useful if db server is overloaded and sometimes we lost connection to it) (Set to '0' for disabling) */
	public $RECONNECT_CONSOLE_TRIES	= 1000;
	/** @var int Time to wait between reconnects (in seconds) */
	public $RECONNECT_DELAY			= 1;
	/** @var bool Use logarithmic increase or reconnect time */
	public $RECONNECT_DELAY_LOG_INC	= 1;
	/** @var bool Use locking for reconnects or not */
	public $RECONNECT_USE_LOCKING	= false;
	/** @var array List of mysql error codes to use for reconnect tries. See also http://dev.mysql.com/doc/refman/5.0/en/error-messages-client.html */
	public $RECONNECT_MYSQL_ERRORS	= array(1053, 1317, 2000, 2002, 2003, 2004, 2005, 2006, 2008, 2012, 2013, 2020, 2027, 2055);
	/** @var string */
	public $RECONNECT_LOCK_FILE_NAME	= 'db_cannot_connect_[DB_HOST]_[DB_NAME]_[DB_USER]_[DB_PORT].lock';
	/** @var int Time in seconds between unlock reconnect */
	public $RECONNECT_LOCK_TIMEOUT	= 30;
	/** @var bool Connection required or not (else E_USER_WARNING will be thrown not E_USER_ERROR) */
	public $CONNECTION_REQUIRED		= false;
	/** @var bool Allow to use shutdown queries or not */
	public $USE_SHUTDOWN_QUERIES	= true;
	/** @var bool Allow to cache specified queries results */
	public $ALLOW_CACHE_QUERIES		= false;
	/** @var bool Max number of cached queries */
	public $CACHE_QUERIES_LIMIT		= 100;
	/** @var bool Max number of logged queries (set to 0 to unlimited) */
	public $LOGGED_QUERIES_LIMIT	= 1000;
	/** @var bool Gather affected rows stats (will be used only when DEBUG_MODE is enabled) */
	public $GATHER_AFFECTED_ROWS	= true;
	/** @var bool Store db queries to file */
	public $LOG_ALL_QUERIES			= false;
	/** @var bool Store db queries to file */
	public $LOG_SLOW_QUERIES		= false;
	/** @var string Log queries file name */
	public $FILE_NAME_LOG_ALL		= 'db_queries.log';
	/** @var string Log queries file name */
	public $FILE_NAME_LOG_SLOW		= 'slow_queries.log';
	/** @var float */
	public $SLOW_QUERIES_TIME_LIMIT	= 0.2;
	/** @var bool Add additional engine details to the SQL as comment for later use */
	public $INSTRUMENT_QUERIES		= false;
	/** @var array */
	public $_instrument_items		= array();
	/** @var bool @conf_skip Internal var (default value) */
	public $_tried_to_connect		= false;
	/** @var bool @conf_skip Internal var (default value) */
	public $_connected				= false;
	/** @var mixed @conf_skip Driver instance */
	public $db						= null;
	/** @var string Tables names prefix */
	public $DB_PREFIX				= null;
	/** @var string */
	public $DB_HOST					= '';
	/** @var string */
	public $DB_NAME					= '';
	/** @var string */
	public $DB_USER					= '';
	/** @var string */
	public $DB_PSWD					= '';
	/** @var int */
	public $DB_PORT					= '';
	/** @var string */
	public $DB_CHARSET				= '';
	/** @var string */
	public $DB_SOCKET				= '';
	/** @var bool */
	public $DB_SSL					= false;
	/** @var bool */
	public $DB_PERSIST				= false;
	/** @var bool In case of true - we will try to avoid any data/structure modification queries to not break replication */
	public $DB_REPLICATION_SLAVE	= false;
	/** @var bool Adding SQL_NO_CACHE to SELECT queries: useful to find long running queries */
	public $SQL_NO_CACHE			= false;
	/** @var bool Needed for installation and repairing process */
	public $ALLOW_AUTO_CREATE_DB	= false;
	/** @var bool Use sql query revisions for update/insert/replace/delete */
	public $QUERY_REVISIONS			= false;
	/** @var bool update_safe, insert_safe, update_batch_safe: use additional checking for exising table fields */
	public $FIX_DATA_SAFE			= true;
	/** @var bool Trigger to act silently or not in *_safe methods */
	public $FIX_DATA_SAFE_SILENT	= false;
	/** @var array Filled automatically from generated file */
	public $_need_sys_prefix		= array();

	/**
	* Constructor
	*/
	function __construct($db_type = '', $db_prefix = null, $db_replication_slave = null) {
		$this->_load_tables_with_sys_prefix();
		// Type/driver of database server
		$this->DB_TYPE = !empty($db_type) ? $db_type : DB_TYPE;
		if (!defined('DB_PREFIX') && empty($db_prefix)) {
			define('DB_PREFIX', '');
		}
		$this->DB_PREFIX = !empty($db_prefix) ? $db_prefix : DB_PREFIX;
		// Check if this is primary database connection
		$debug_index = $GLOBALS['DEBUG']['db_instances'] ? count($GLOBALS['DEBUG']['db_instances']) : 0;
		if ($debug_index < 1) {
			$this->IS_PRIMARY_CONNECTION = true;
		} else {
			$this->IS_PRIMARY_CONNECTION = false;
		}
		// Trying to override replication slave setting
		if (isset($db_replication_slave)) {
			$this->DB_REPLICATION_SLAVE = (bool)$db_replication_slave;
		} elseif ($this->IS_PRIMARY_CONNECTION && defined('DB_REPLICATION_SLAVE')) {
			$this->DB_REPLICATION_SLAVE = (bool)DB_REPLICATION_SLAVE;
		}
		// Track db class instances
		$GLOBALS['DEBUG']['db_instances'][$debug_index] = &$this;
		if (defined('DEBUG_MODE') && DEBUG_MODE) {
			$GLOBALS['DEBUG']['db_instances_trace'][$debug_index] = $this->_trace_string();
		}
	}

	/**
	*/
	function get_driver_family($db_type = '') {
		$db_type = strtolower($db_type ?: $this->DB_TYPE);
		// Get current abstract db type
		if (in_array($db_type, array('db_type','mysql','mysqli','pdo_mysql','mysql5','mysql4','mysql41'))) {
			$name = 'mysql';
		} elseif (in_array($db_type, array('pgsql','pdo_pgsql','postgre','postgres','postgres7','postgres8','postgres9'))) {
			$name = 'pgsql';
		} elseif (in_array($db_type, array('sqlite','sqlite3','pdo_sqlite'))) {
			$name = 'sqlite';
		} else {
			$name = $db_type;
		}
		return $name;
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Framework constructor
	*/
	function _init() {
		// Perform auto-connection to db if needed
		if (($this->AUTO_CONNECT || MAIN_TYPE == 'admin') && !$this->NO_AUTO_CONNECT) {
			$this->connect();
		}
		$this->_set_debug_items();
		if (main()->CONSOLE_MODE) {
			$this->enable_silent_mode();
		}
		// Set shutdown function
		if ($this->USE_SHUTDOWN_QUERIES) {
			register_shutdown_function(array($this, '_execute_shutdown_queries'));
		}
		if ($this->LOG_ALL_QUERIES || $this->LOG_SLOW_QUERIES) {
			register_shutdown_function(array($this, '_log_queries'));
		}
		// Turn off tables repairing if we are dealing with slave server
		if ($this->DB_REPLICATION_SLAVE) {
			$this->ERROR_AUTO_REPAIR = false;
		}
	}

	/**
	*/
	function _load_tables_with_sys_prefix() {
		if ($this->_need_sys_prefix) {
			return $this->_need_sys_prefix;
		}
		$paths = array(
			'app'	=> APP_PATH. 'share/db_sys_prefix_tables.php',
			'yf'	=> YF_PATH. 'share/db_sys_prefix_tables.php',
		);
		$data = array();
		foreach ($paths as $path) {
			if (file_exists($path)) {
				$_data = require $path;
				if ($_data && is_array($_data)) {
					$data += $_data;
				}
			}
		}
		$this->_need_sys_prefix = $data;
		return (array)$data;
	}

	/**
	* Connect db driver and then connect to db
	*/
	function connect($db_host = '', $db_user = '', $db_pswd = null, $db_name = null, $force = false, $params = array()) {
		if (is_array($db_host)) {
			$params = $db_host;
			$db_host = '';
		}
		if (!is_array($params)) {
			$params = array();
		}
		if ($params['reconnect'] || $params['force']) {
			$force = true;
		}
		if (!empty($this->_tried_to_connect) && !$force) {
			return $this->_connected;
		}
		$this->_connect_start_time = microtime(true);
		if (!$params['reconnect']) {
			$this->DB_HOST = ($params['host'] ?: $db_host) ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
			$this->DB_USER = ($params['user'] ?: $db_user) ?: (defined('DB_USER') ? DB_USER : 'root');
			// db_pswd can be empty string
			$_db_pswd = isset($params['pswd']) ? $params['pswd'] : $db_pswd;
			$this->DB_PSWD = !is_null($_db_pswd) ? $_db_pswd : (defined('DB_PSWD') ? DB_PSWD : '');
			// db_name can be empty string - means we working in special mode, just connecting to server
			$_db_name = isset($params['name']) ? $params['name'] : $db_name;
			$this->DB_NAME = !is_null($_db_name) ? $_db_name : (defined('DB_NAME') ? DB_NAME : '');
			$this->DB_PORT = ($params['port'] ?: $db_port) ?: (defined('DB_PORT') ? DB_PORT : '');
			$this->DB_SOCKET = ($params['socket'] ?: $db_socket) ?: (defined('DB_SOCKET') ? DB_SOCKET : '');
			$this->DB_SSL = ($params['ssl'] ?: $db_ssl) ?: (defined('DB_SSL') ? DB_SSL : false);
			$this->DB_CHARSET = ($params['charset'] ?: $db_charset) ?: (defined('DB_CHARSET') ? DB_CHARSET : '');
			if (isset($params['prefix'])) {
				$this->DB_PREFIX = $params['prefix'];
			}
			$allow_auto_create_db = isset($params['auto_create_db']) ? $params['auto_create_db'] : $allow_auto_create_db;
			if (!is_null($allow_auto_create_db)) {
				$this->ALLOW_AUTO_CREATE_DB	= $allow_auto_create_db;
			}
		}
		$driver_class_name = main()->load_class_file('db_driver_'. $this->DB_TYPE, $this->DB_DRIVERS_DIR);
		// Create new instanse of the driver class
		if (!empty($driver_class_name) && class_exists($driver_class_name) && !is_object($this->db)) {
			if ($this->RECONNECT_USE_LOCKING) {
				$lock_file = $this->_get_reconnect_lock_path($this->DB_HOST, $this->DB_USER, $this->DB_NAME, $this->DB_PORT);
				clearstatcache();
				if (file_exists($lock_file)) {
					// Timed out lock file
					if ((time() - filemtime($lock_file)) > $this->RECONNECT_LOCK_TIMEOUT) {
						unlink($lock_file);
					} else {
						return false;
					}
				}
			}
			$driver_params = array(
				'host'		=> $this->DB_HOST,
				'user'		=> $this->DB_USER,
				'pswd'		=> $this->DB_PSWD,
				'name'		=> $this->DB_NAME,
				'persist'	=> $this->DB_PERSIST,
				'ssl'		=> $this->DB_SSL,
				'port'		=> $this->DB_PORT,
				'socket'	=> $this->DB_SOCKET,
				'charset'	=> $this->DB_CHARSET,
				'allow_auto_create_db' => $this->ALLOW_AUTO_CREATE_DB,
			);
			// Try to connect several times
			$tries = $this->RECONNECT_NUM_TRIES;
			if (main()->is_console() && !main()->is_unit_test()) {
				$tries = $this->RECONNECT_CONSOLE_TRIES;
			}
			for ($i = 1; $i <= $tries; $i++) {
				$this->db = new $driver_class_name($driver_params);
				if (!is_object($this->db) || !($this->db instanceof yf_db_driver)) {
					trigger_error('DB: Wrong driver', $this->CONNECTION_REQUIRED ? E_USER_ERROR : E_USER_WARNING);
					break;
				}
				$implemented = array();
				foreach (get_class_methods($this->db) as $method) {
					if ($method[0] != '_') {
						$implemented[$method] = $method;
					}
				}
				$this->db->implemented = $implemented;
				// Stop after success
				if (!empty($this->db->db_connect_id)) {
					break;
				// Wait some time and try again (use logarithmic increase)
				} else {
					$multiplier = 1;
					if ($this->RECONNECT_DELAY_LOG_INC) {
						$multiplier = $i + ($this->RECONNECT_DELAY <= 1 ? 1 : 0);
					}
					$sleep_time = $this->RECONNECT_DELAY * $multiplier;
					sleep($sleep_time);
				}
			}
			// Put lock file
			if ($this->RECONNECT_USE_LOCKING && !$this->db->db_connect_id) {
				file_put_contents($lock_file, gmdate('Y-m-d H:i:s').' GMT');
			}
		}
		$this->_tried_to_connect = true;
		// Stop execution If connection has failed
		if (!$this->db->db_connect_id) {
			trigger_error('DB: ERROR CONNECTING TO DATABASE', $this->CONNECTION_REQUIRED ? E_USER_ERROR : E_USER_WARNING);
		} else {
			$this->db->SQL_NO_CACHE = $this->SQL_NO_CACHE;
			$this->_connected = true;
		}
		$this->_connection_time += (microtime(true) - $this->_connect_start_time);
		return $this->_connected;
	}

	/**
	* Close connection to db
	*/
	function close() {
		$this->_connected = false;
		$this->_tried_to_connect = false;
		$result = $this->db->close();
		unset($this->db);
		return $result;
	}

	/**
	* Prepare statement to execute
	*/
	function prepare($sql, $params = array()) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db) || !$this->db->implemented['prepare']) {
			return false;
		}
		return $this->db->prepare($sql, $params);
	}

	/**
	* Execute prepared statement
	*/
	function execute($stmt, $params = array()) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db) || !$this->db->implemented['execute']) {
			return false;
		}
		return $this->db->execute($stmt, $params);
	}

	/**
	* Function return resource ID of the query
	*/
	function &query($sql) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db)) {
			return false;
		}
		$this->NUM_QUERIES++;
		if (DEBUG_MODE) {
			$query_time_start = microtime(true);
			if ($this->SQL_NO_CACHE && $this->get_driver_family() === 'mysql') {
				$q = strtoupper(substr(ltrim($sql), 0, 100));
				if (substr($q, 0, 6) == 'SELECT' && false === strpos($q, 'SQL_NO_CACHE')) {
					$sql = preg_replace('/^[\s\t]*(SELECT)[\s\t]+/ims', '$1 SQL_NO_CACHE ', $sql);
				}
			}
		}
		if ($this->INSTRUMENT_QUERIES) {
			$sql = $this->_instrument_query($sql);
		}
		$query_allowed = true;
		if ($this->DB_REPLICATION_SLAVE && preg_match('/^[\s\t]*(UPDATE|INSERT|DELETE|ALTER|CREATE|RENAME|TRUNCATE)[\s\t]+/ims', $sql)) {
			$query_allowed = false;
		}
		if ($query_allowed) {
			$result = $this->db->query($sql);
		}
		$db_error = false;
		if (!$result && $query_allowed) {
			$db_error = $this->db->error();
		}
		if (!$result && $query_allowed && $db_error) {
			// Try to reconnect if we see some these errors: http://dev.mysql.com/doc/refman/5.0/en/error-messages-client.html
			if ($this->get_driver_family() === 'mysql' && in_array($db_error['code'], $this->RECONNECT_MYSQL_ERRORS)) {
				$this->db = null;
				$reconnect_successful = $this->connect(array('reconnect' => true));
				if ($reconnect_successful) {
					$result = $this->db->query($sql);
				}
			}
		}
		$log_allowed = (DEBUG_MODE || $this->LOG_ALL_QUERIES || $this->LOG_SLOW_QUERIES);
		if ($log_allowed) {
			$log_id = $this->_query_log($sql, $this->USE_QUERY_BACKTRACE ? $this->_trace_string() : array(), $db_error);
		}
		if (!$result && $query_allowed && $db_error && $this->ERROR_AUTO_REPAIR) {
			$result	= $this->_repair_table($sql, $db_error);
		}
		if (!$result && $db_error) {
			$this->_query_show_error($sql, $db_error, (DEBUG_MODE && $this->ERROR_BACKTRACE) ? $this->_trace_string() : '');
		}
		// This part needed to update debug log after executing query, but ensure correct order of queries
		if ($log_allowed && $log_id) {
			$this->_update_query_log($log_id, $result, $query_time_start);
		}
		return $result;
	}

	/**
	*/
	function _query_show_error($sql, $db_error, $_trace = '') {
		$old_db_error = $db_error;
		$db_error = $this->db->error();
		if (empty($db_error) || empty($db_error['message'])) {
			$db_error = $old_db_error;
		}
		$msg = 'DB: QUERY ERROR: '.$sql. ';'. PHP_EOL. 'CAUSE: '.$db_error['message']
			. ($db_error['code'] ? ' (code:'.$db_error['code'].')' : '')
			. ($db_error['offset'] ? ' (offset:'.$db_error['offset'].')' : '')
			. (main()->USE_CUSTOM_ERRORS ? '' : $_trace. PHP_EOL)
		;
		trigger_error($msg, E_USER_WARNING);
	}

	/**
	*/
	function _query_log($sql, $_trace = array(), $db_error = false) {
		$_log_allowed = true;
		// Save memory on high number of query log entries
		if ($this->LOGGED_QUERIES_LIMIT && count($this->_LOG) >= $this->LOGGED_QUERIES_LIMIT) {
			$_log_allowed = false;
		}
		if (!$_log_allowed) {
			return false;
		}
		$this->_LOG[] = array(
			'sql'	=> $sql,
			'rows'	=> 0,
			'error'	=> $db_error,
			'time'	=> 0,
			'trace'	=> $_trace,
		);
		return count($this->_LOG) - 1;
	}

	/**
	*/
	function _update_query_log($log_id, $result, $query_time_start = 0) {
		if (!isset($this->_LOG[$log_id])) {
			return false;
		}
		$log = &$this->_LOG[$log_id];
		$time = (float)microtime(true) - (float)$query_time_start;
		$sql = $log['sql'];
		if ($this->GATHER_AFFECTED_ROWS && $result) {
			$_sql_type = strtoupper(rtrim(substr(ltrim($sql), 0, 7)));
			$rows = null;
			if (in_array($_sql_type, array('INSERT', 'UPDATE', 'REPLACE', 'DELETE'))) {
				$rows = $this->affected_rows();
			} elseif ($_sql_type == 'SELECT') {
				$rows = $this->num_rows($result);
			}
		}
		$log['time'] = $time;
		$log['rows'] = $rows;
	}

	/**
	* Function execute unbuffered query
	*/
	function unbuffered_query($sql) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->query($sql);
	}

	/**
	*/
	function multi_query($sql = array()) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db)) {
			return false;
		}
		if (!$this->db->HAS_MULTI_QUERY) {
			$result = array();
			foreach ((array)$sql as $k => $_sql) {
				$result[$k] = $this->query($_sql);
			}
			return $result;
		} else {
			return $this->db->multi_query($sql);
		}
	}

	/**
	* Alias of insert() with auto-escaping of data
	*/
	function insert_safe($table, $data, $only_sql = false, $replace = false, $ignore = false, $on_duplicate_key_update = false, $extra = array()) {
		$data = $this->_fix_data_safe($table, $data, $extra);
		return $this->insert($table, $this->es($data), $only_sql, $replace, $ignore, $on_duplicate_key_update, $extra);
	}

	/**
	* Insert array of values into table
	*/
	function insert($table, $data, $only_sql = false, $replace = false, $ignore = false, $on_duplicate_key_update = false, $extra = array()) {
		if ($this->DB_REPLICATION_SLAVE && !$only_sql) {
			return false;
		}
		if (!strlen($table) || !is_array($data)) {
			return false;
		}
		if (is_string($replace)) {
			$replace = false;
		}
		$values_array = array();
		// Try to check if array is two-dimensional
		foreach ((array)$data as $cur_row) {
			$is_multiple = is_array($cur_row) ? 1 : 0;
			break;
		}
		$cols = array();
		if ($is_multiple) {
			foreach ((array)$data as $cur_row) {
				if (empty($cols)) {
					$cols = array_keys($cur_row);
				}
				// This method ensures that SQL will consist of same key=value pairs, even if in some sub-array they will be missing
				foreach ((array)$cols as $col) {
					$cur_values[$col] = $cur_row[$col];
				}
				$values_array[] = '('.implode(', ', $this->escape_val($cur_values)).PHP_EOL.')';
			}
		} elseif (count($data)) {
			$cols	= array_keys($data);
			$values = array_values($data);
			foreach ((array)$values as $k => $v) {
				$values[$k] = $this->escape_val($v);
			}
			$values_array[] = '('.implode(', ', $values).PHP_EOL.')';
		}
		foreach ((array)$cols as $k => $v) {
			unset($cols[$k]);
			$cols[$v] = $this->escape_key($v);
		}
		$sql = '';
		if (count($cols) && count($values_array)) {
			$sql = ($replace ? 'REPLACE' : 'INSERT'). ($ignore ? ' IGNORE' : '')
				.' INTO '.$this->_escape_table_name($table).PHP_EOL
				.' ('.implode(', ', $cols).') VALUES '
				.PHP_EOL.implode(', ', $values_array);
			if ($on_duplicate_key_update) {
				$sql .= PHP_EOL.' ON DUPLICATE KEY UPDATE ';
				$tmp = array();
				foreach ((array)$cols as $col => $col_escaped) {
					if ($col == 'id') {
						continue;
					}
					$tmp[] = $col_escaped.' = VALUES('.$col_escaped.')';
				}
				$sql .= implode(', ', $tmp);
			}
		}
		if (!$sql) {
			return false;
		}
		if ($only_sql) {
			return $sql;
		}
		if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
			$this->_save_query_revision(__FUNCTION__, $table, array('data' => $data, 'replace' => $replace, 'ignore' => $ignore));
		}
		return $this->query($sql);
	}

	/**
	* Alias, forced to add INSERT IGNORE
	*/
	function insert_ignore($table, $data, $only_sql = false, $replace = false, $extra = array()) {
		return $this->insert($table, $data, $only_sql, $replace, $ignore = true, $on_duplicate_key_update = false, $extra);
	}

	/**
	* Alias, forced to add INSERT ... ON DUPLICATE KEY UPDATE
	*/
	function insert_on_duplicate_key_update($table, $data, $only_sql = false, $replace = false, $extra = array()) {
		$on_duplicate_key_update = true && ($this->get_driver_family() === 'mysql');
		return $this->insert($table, $data, $only_sql, $replace, $ignore = false, $on_duplicate_key_update, $extra);
	}

	/**
	* Alias of replace() with data auto-escape
	*/
	function replace_safe($table, $data, $only_sql = false, $extra = array()) {
		$replace = true && in_array($this->get_driver_family(), array('mysql','sqlite'));
		return $this->insert_safe($table, $data, $only_sql, $replace, $ignore = false, $on_duplicate_key_update = false, $extra);
	}

	/**
	* Replace array of values into table
	*/
	function replace($table, $data, $only_sql = false) {
		$replace = true && in_array($this->get_driver_family(), array('mysql','sqlite'));
		return $this->insert($table, $data, $only_sql, $replace);
	}

	/**
	*/
	function get_table_columns_cached($table) {
		$cache_name = __FUNCTION__.'|'.$table.'|'.$this->DB_HOST.'|'.$this->DB_PORT.'|'.$this->DB_NAME.'|'.$this->DB_PREFIX;
		$data = cache_get($cache_name);
		if (!$data) {
			$data = $this->meta_columns($table);
			cache_set($cache_name, $data);
		}
		return $data;
	}

	/**
	*/
	function _fix_data_safe($table, $data = array(), $extra = array()) {
		if (!$this->FIX_DATA_SAFE) {
			return $data;
		}
		$cols = $this->get_table_columns_cached($table);
		if (!$cols) {
			$msg = __CLASS__.'->'.__FUNCTION__.': columns for table '.$table.' is empty, truncating data array';
			if (!$extra['silent'] && !$this->FIX_DATA_SAFE_SILENT) {
				trigger_error($msg, E_USER_WARNING);
			}
			return false;
		}
		$is_data_3d = false;
		// Try to check if array is two-dimensional
		foreach ((array)$data as $cur_row) {
			$is_data_3d = is_array($cur_row) ? 1 : 0;
			break;
		}
		$not_existing_cols = array();
		if ($is_data_3d) {
			foreach ((array)$data as $k => $_data) {
				foreach ((array)$_data as $name => $v) {
					if (!isset($cols[$name])) {
						$not_existing_cols[$name] = $name;
						unset($data[$k][$name]);
					}
				}
			}
		} else {
			foreach ((array)$data as $name => $v) {
				if (!isset($cols[$name])) {
					$not_existing_cols[$name] = $name;
					unset($data[$name]);
				}
			}
		}
		if ($not_existing_cols) {
			$msg = __CLASS__.'->'.__FUNCTION__.': not existing columns for table '.$table.': '.implode(', ', $not_existing_cols);
			if (!$extra['silent'] && !$this->FIX_DATA_SAFE_SILENT) {
				trigger_error($msg, E_USER_WARNING);
			}
		}
		return $data;
	}

	/**
	* Alias of update() with data auto-escape
	*/
	function update_safe($table, $data, $where, $only_sql = false, $extra = array()) {
		$data = $this->_fix_data_safe($table, $data, $extra);
		return $this->update($table, $this->es($data), $where, $only_sql);
	}

	/**
	* Update table with given values
	*/
	function update($table, $data, $where, $only_sql = false, $extra = array()) {
		if ($this->DB_REPLICATION_SLAVE && !$only_sql) {
			return false;
		}
		if (empty($table) || empty($data) || empty($where)) {
			return false;
		}
		// $where contains numeric id
		if (is_numeric($where)) {
			$where = 'id='.intval($where);
		}
		$tmp_data = array();
		foreach ((array)$data as $k => $v) {
			if (empty($k)) {
				continue;
			}
			$tmp_data[$k] = $this->escape_key($k).' = '.$this->escape_val($v);
		}
		$sql = '';
		if (count($tmp_data)) {
			$sql = 'UPDATE '.$this->_escape_table_name($table).' SET '.implode(', ', $tmp_data). (!empty($where) ? ' WHERE '.$where : '');
		}
		if (!$sql) {
			return false;
		}
		if ($only_sql) {
			return $sql;
		}
		if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
			$this->_save_query_revision(__FUNCTION__, $table, array('data' => $data, 'where' => $where));
		}
		return $this->query($sql);
	}

	/**
	* Execute database query and fetch result as assoc array (for queries that returns only 1 row)
	*/
	function query_fetch($query, $use_cache = true, $assoc = true) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		$storage = &$this->_db_results_cache;
		if ($use_cache && $this->ALLOW_CACHE_QUERIES && isset($storage[$query])) {
			return $storage[$query];
		}
		$data = null;
		$q = $this->query($query);
		if (!empty($q)) {
			if ($assoc) {
				$data = @$this->db->fetch_assoc($q);
			} else {
				$data = @$this->db->fetch_row($q);
			}
			$this->free_result($q);
			// Store result in variable cache
			if ($use_cache && $this->ALLOW_CACHE_QUERIES && !isset($storage[$query])) {
				$storage[$query] = $data;
				// Permanently turn off queries cache (and free some memory) if case of limit reached
				if ($this->CACHE_QUERIES_LIMIT && count($storage) > $this->CACHE_QUERIES_LIMIT) {
					$this->ALLOW_CACHE_QUERIES = false;
					$storage = null;
				}
			}
		}
		return $data;
	}

	/**
	* Alias
	*/
	function get($query, $use_cache = true) {
		return $this->query_fetch($query, $use_cache, true);
	}

	/**
	* Alias, return first value
	*/
	function get_one($query, $use_cache = true) {
		$result = $this->query_fetch($query, $use_cache, true);
		if (!$result) {
			return false;
		}
		// Foreach needed here as we do not know first key name
		foreach (array_keys($result) as $key) {
			return $result[$key];
		}
		return false;
	}

	/**
	* Alias, return 2d array, where key is first field and value is the second,
	* Example: 'SELECT id, name FROM p_static_pages' => array('1' => 'page1', '2' => 'page2')
	* Example: 'SELECT name FROM p_static_pages' => array('page1', 'page2')
	*/
	function get_2d($query, $use_cache = true) {
		$result = $this->query_fetch_all($query, $use_cache, true);
		// Get 1st and 2nd keys from first sub-array
		if (is_array($result) && $result) {
			$keys = array_keys(current($result));
		}
		if (!$keys) {
			return false;
		}
		$out = array();
		foreach ((array)$result as $id => $data) {
			if (isset($keys[1])) {
				$out[$data[$keys[0]]] = $data[$keys[1]];
			} else {
				$out[] = $data[$keys[0]];
			}
		}
		return $out;
	}

	/**
	* Generate multi-level (up to 4) array from incoming query, useful to save some code on generating this often.
	* Example: get_deep_array('SELECT department_id, user_id, name FROM t_personal', 2)  =>
	*	[ 25 => [ 654 => [
	*		'department_id' => 25,
	*		'user_id' => 654,
	*		'name' => 'Peter',
	*	]]]
	*/
	function get_deep_array($sql, $max_levels = 0, $use_cache = true) {
		if (!$max_levels || $max_levels > 4) {
			$max_levels = 4;
		}
		$out = array();
		$q = $this->query($sql);
		if (!$q) {
			return false;
		}
		$row = $this->fetch_assoc($q);
		$levels = count($row);
		if (!is_array($row) || !$levels) {
			return false;
		}
		if ($levels > $max_levels) {
			$levels = $max_levels;
		}
		$k = array_keys($row);
		$a = array();
		do {
			if ($levels == 1) {
				$a[ $row[$k[0]] ] = $row;
			} elseif ($levels == 2) {
				$a[ $row[$k[0]] ][ $row[$k[1]] ] = $row;
			} elseif ($levels == 3) {
				$a[ $row[$k[0]] ][ $row[$k[1]] ][ $row[$k[2]] ] = $row;
			} elseif ($levels == 4) {
				$a[ $row[$k[0]] ][ $row[$k[1]] ][ $row[$k[2]] ][ $row[$k[3]] ] = $row;
			}
		} while ($row = $this->fetch_assoc($q));
		return $a;
	}

	/**
	* Alias
	*/
	function query_fetch_assoc($query, $use_cache = true) {
		return $this->query_fetch($query, $use_cache, true);
	}

	/**
	* Same as 'query_fetch' except fetching as row not assoc
	*/
	function query_fetch_row($query, $use_cache = true) {
		return $this->query_fetch($query, $use_cache, false);
	}

	/**
	* Alias
	*/
	function get_all($query, $key_name = null, $use_cache = true) {
		return $this->query_fetch_all($query, $key_name, $use_cache);
	}

	/**
	* Execute database query and fetch result into assotiative array
	*/
	function query_fetch_all($query, $key_name = null, $use_cache = true) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		$params = array();
		if (is_array($use_cache)) {
			$params = $use_cache;
			$use_cache = isset($params['use_cache']) ? $params['use_cache'] : true;
		}
		$storage = &$this->_db_results_cache;
		if ($use_cache && $this->ALLOW_CACHE_QUERIES && isset($storage[$query])) {
			if ($params['as_object']) {
				return array_to_object($storage[$query]);
			}
			return $storage[$query];
		}
		$data = null;
		$q = $this->query($query);
		if ($q) {
			// If $key_name is specified - then save to $data using it as key
			while ($a = @$this->db->fetch_assoc($q)) {
				if ($key_name != null && $key_name != '-1') {
					$data[$a[$key_name]] = $a;
				} elseif (isset($a['id']) && $key_name != '-1') {
					$data[$a['id']] = $a;
				} else {
					$data[] = $a;
				}
			}
			@$this->free_result($q);
		}
		// Store result in variable cache
		if ($use_cache && $this->ALLOW_CACHE_QUERIES && !isset($storage[$query])) {
			$storage[$query] = $data;
			// Permanently turn off queries cache (and free some memory) if case of limit reached
			if ($this->CACHE_QUERIES_LIMIT && count($storage) > $this->CACHE_QUERIES_LIMIT) {
				$this->ALLOW_CACHE_QUERIES = false;
				$storage = null;
			}
		}
		if ($params['as_object']) {
			return array_to_object($data);
		}
		return $data;
	}

	/**
	* Execute database query and fetch result as assoc array (for queries that returns only 1 row)
	*/
	function query_fetch_cached($query, $cache_ttl = 600) {
		$cache_key = 'SQL_'.__FUNCTION__.'_'.$this->DB_HOST.'_'.$this->DB_NAME.'_'.abs(crc32($query));
		$data = cache_get($cache_key);
		if (!empty($data)) {
			return $data;
		}
		$data = $this->query_fetch($query);
		cache_set($cache_key, $data);
		return $data;
	}

	/**
	* Alias with core cache
	*/
	function query_fetch_all_cached($query, $key_name = null, $cache_ttl = 600) {
		$cache_key = 'SQL_'.__FUNCTION__.'_'.$this->DB_HOST.'_'.$this->DB_NAME.'_'.abs(crc32($query));
		$data = cache_get($cache_key);
		if (!empty($data)) {
			return $data;
		}
		$data = $this->query_fetch_all($query, $key_name);
		cache_set($cache_key, $data);
		return $data;
	}

	/**
	* Alias
	*/
	function get_cached($query, $cache_ttl = 600) {
		return $this->query_fetch_cached($query, $cache_ttl);
	}

	/**
	* Alias
	*/
	function get_all_cached($query, $key_name = null, $cache_ttl = 600) {
		return $this->query_fetch_all_cached($query, $key_name, $cache_ttl);
	}

	/**
	* Execute database query and the calculate number of rows
	*/
	function query_num_rows($query) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		$Q = $this->query($query);
		$result = $this->db->num_rows($Q);
		$this->free_result($Q);
		return $result;
	}

	/**
	* Function return fetched array with both text and numeric indexes
	*/
	function fetch_array($result) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->fetch_array($result);
	}

	/**
	* Function return fetched array with text indexes
	*/
	function fetch_assoc($result) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->fetch_assoc($result);
	}

	/**
	* Alias
	*/
	function fetch($result) {
		return $this->fetch_assoc($result);
	}

	/**
	* Function return fetched array with numeric indexes
	*/
	function fetch_row($result) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->fetch_row($result);
	}

	/**
	* Function return fetched object with assoc var names
	*/
	function fetch_object($result) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->fetch_object($result);
	}

	/**
	* Function return number of rows in the query
	*/
	function num_rows($result) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->num_rows($result);
	}

	/**
	* Function escapes characters for using in query
	*/
	function es($string) {
		if (!$this->_connected && !$this->connect()) {
			return $this->_mysql_escape_mimic($string);
		}
		// Helper method for passing here whole arrays as param
		if (is_array($string)) {
			foreach ((array)$string as $k => $v) {
				$string[$k] = $this->real_escape_string($v);
			}
			return $string;
		}
		return $this->db->real_escape_string($string);
	}

	/**
	* Alias
	*/
	function real_escape_string($string) {
		return $this->es($string);
	}

	/**
	* Alias
	*/
	function escape_string($string) {
		return $this->es($string);
	}

	/**
	* Alias
	*/
	function escape($string) {
		return $this->es($string);
	}

	/**
	* Begin a transaction, or if a transaction has already started, continue it
	*/
	function begin() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->begin();
	}

	/**
	* End a transaction, or decrement the nest level if transactions are nested
	*/
	function commit() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->commit();
	}

	/**
	* Rollback a transaction
	*/
	function rollback() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->rollback();
	}

	/**
	* Return columns info for selected table
	*/
	function meta_columns($table) {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		$table = $this->_escape_table_name($table);
		if (!strlen($table)) {
			return false;
		}
		return $this->utils()->meta_columns($table);
	}

	/**
	* Return tables list for current database
	*/
	function meta_tables() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		$table = $this->_escape_table_name($table);
		if (!strlen($table)) {
			return false;
		}
		return $this->utils()->meta_tables($this->DB_PREFIX);
	}

	/**
	* Free result assosiated with a given query resource
	*/
	function free_result($result) {
		if (!$this->_connected && !$this->connect() && empty($result)) {
			return false;
		}
		return $this->db->free_result($result);
	}

	/**
	* Return database error
	*/
	function error() {
		if (!is_object($this->db)) {
			return false;
		}
		return $this->db->error();
	}

	/**
	* Return last insert id
	*/
	function insert_id() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->insert_id();
	}

	/**
	* Get number of affected rows
	*/
	function affected_rows() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->affected_rows();
	}

	/**
	* Return database-specific limit of returned rows
	*/
	function limit($count, $offset = null) {
		if (!$this->_connected && !$this->connect()) {
			$sql = '';
			if ($count > 0) {
				$offset = ($offset > 0) ? $offset : 0;
				$sql = 'LIMIT '.($offset ? $offset.', ' : ''). $count;
			}
			return $sql;
		}
		return $this->db->limit($count, $offset);
	}

	/**
	*/
	function escape_key($data) {
		if (is_array($data)) {
			$func = __FUNCTION__;
			foreach ((array)$data as $k => $v) {
				$data[$k] = $this->$func($v);
			}
			return $data;
		}
		if (!is_object($this->db)) {
			return '`'.$data.'`';
		}
		return $this->db->escape_key($data);
	}

	/**
	*/
	function escape_val($data) {
		if (is_array($data)) {
			$func = __FUNCTION__;
			foreach ((array)$data as $k => $v) {
				$data[$k] = $this->$func($v);
			}
			return $data;
		}
		if (!is_object($this->db)) {
			return '\''.$data.'\'';
		}
		return $this->db->escape_val($data);
	}

	/**
	* Alias
	*/
	function enclose_field_name($data) {
		return $this->escape_key($data);
	}

	/**
	* Alias
	*/
	function enclose_field_value($data) {
		return $this->escape_val($data);
	}

	/**
	*/
	function get_server_version() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->get_server_version();
	}

	/**
	*/
	function get_host_info() {
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		return $this->db->get_host_info();
	}

	/**
	* 'Silent' mode (logging off, tracing off, debugging off)
	*/
	function enable_silent_mode() {
		$this->ALLOW_CACHE_QUERIES	= false;
		$this->GATHER_AFFECTED_ROWS	= false;
		$this->USE_SHUTDOWN_QUERIES = false;
		$this->LOG_ALL_QUERIES		= false;
		$this->LOG_SLOW_QUERIES		= false;
		$this->USE_QUERY_BACKTRACE	= false;
		$this->ERROR_BACKTRACE		= false;
		$this->LOGGED_QUERIES_LIMIT = 1;
	}

	/**
	* Add query to shutdown array
	*/
	function _add_shutdown_query($sql = '') {
		if (empty($sql)) {
			return false;
		}
		// If shutdown execution is disabled - then execute this query immediatelly
		if (!$this->USE_SHUTDOWN_QUERIES) {
			return $this->query($sql);
		} else {
			// Add query to the array
			$this->_SHUTDOWN_QUERIES[] = $sql;
		}
		return true;
	}

	/**
	* Execute shutdown queries
	*/
	function _execute_shutdown_queries() {
		if (!$this->USE_SHUTDOWN_QUERIES || $this->_shutdown_executed) {
			return false;
		}
		foreach ((array)$this->_SHUTDOWN_QUERIES as $sql) {
			$this->query($sql);
		}
		// Prevent executing this method more than once
		$this->_shutdown_executed = true;
	}

	/**
	* Create unique temporary table name
	*/
	function _get_unique_tmp_table_name () {
		return $this->DB_PREFIX.'tmp__'.substr(abs(crc32(rand().microtime(true))), 0, 8);
	}

	/**
	* Do Log
	*/
	function _log_queries () {
		// Restore startup working directory
		@chdir(main()->_CWD);

		if (!isset($this->_queries_logged)) {
			$this->_queries_logged = true;
		} else {
			return false;
		}
		_class_safe('logs')->store_db_queries_log();
	}

	/**
	* Get reconnect lock file name
	*/
	function _get_reconnect_lock_path($db_host = '', $db_user = '', $db_name = '', $db_port = '') {
		$params = array(
			'[DB_HOST]'	=> $db_host ? $db_host : $this->DB_HOST,
			'[DB_NAME]'	=> $db_name ? $db_name : $this->DB_NAME,
			'[DB_USER]'	=> $db_user ? $db_user : $this->DB_USER,
			'[DB_PORT]'	=> $db_port ? $db_port : $this->DB_PORT,
		);
		$file_name = str_replace(array_keys($params), array_values($params), $this->RECONNECT_LOCK_FILE_NAME);
		return INCLUDE_PATH. $file_name;
	}

	/**
	* Get real table name from its short variant
	*/
	function _real_name ($name) {
		if (isset($this->_found_tables[$name])) {
			return $this->_found_tables[$name];
		}
		$name = (in_array($name, $this->_need_sys_prefix) ? 'sys_' : ''). $name;
		if (strlen($this->DB_PREFIX) && substr($name, 0, strlen($this->DB_PREFIX)) != $this->DB_PREFIX) {
			return $this->DB_PREFIX. $name;
		} else {
			return $name;
		}
	}

	/**
	* Try to fix table name
	*/
	function _fix_table_name($name = '') {
		if (!strlen($name)) {
			return '';
		}
		if (substr($name, 0, strlen('dbt_')) == 'dbt_') {
			$name = substr($name, strlen('dbt_'));
		}
		$orig_name = $name;
		$name_wo_db_prefix = $name;
		if ($this->DB_PREFIX && substr($name, 0, strlen($this->DB_PREFIX)) == $this->DB_PREFIX) {
			$name_wo_db_prefix = substr($name, strlen($this->DB_PREFIX));
		}
		return $this->DB_PREFIX. (in_array($name_wo_db_prefix, $this->_need_sys_prefix) ? 'sys_' : ''). $name_wo_db_prefix;
	}

	/**
	* Trying to repair given table structure (and possibly data)
	*/
	function _repair_table($sql, $db_error) {
		if (empty($db_error) || !$this->ERROR_AUTO_REPAIR) {
			return false;
		}
		return _class('db_installer_'.$this->get_driver_family(), 'classes/db/')->_auto_repair_table($sql, $db_error, $this);
	}

	/**
	* Simple trace without dumping whole objects
	*/
	function _trace() {
		$trace = array();
		foreach (debug_backtrace() as $k => $v) {
			if (!$k) {
				continue;
			}
			$v['object'] = isset($v['object']) && is_object($v['object']) ? get_class($v['object']) : null;
			$trace[$k - 1] = $v;
		}
		return $trace;
	}

	/**
	* Print nice
	*/
	function _trace_string() {
		$e = new Exception();
		return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), 1, -1));
	}

	/**
	* Special init for the debug info items
	*/
	function _set_debug_items() {
		if (!$this->INSTRUMENT_QUERIES) {
			return false;
		}
		$cpu_usage = function_exists('getrusage') ? getrusage() : array();

		$this->_instrument_items = array(
			'memory_usage'		=> function_exists('memory_get_usage') ? memory_get_usage() : '',
			'cpu_user'			=> $cpu_usage['ru_utime.tv_sec'] * 1e6 + $cpu_usage['ru_utime.tv_usec'],
			'cpu_system'		=> $cpu_usage['ru_stime.tv_sec'] * 1e6 + $cpu_usage['ru_stime.tv_usec'],
			'GET_object'		=> $_GET['object'],
			'GET_action'		=> $_GET['action'],
			'GET_id'			=> $_GET['id'],
			'GET_page'			=> $_GET['page'],
			'user_id'			=> $_SESSION['user_id'],
			'user_group'		=> $_SESSION['user_group'],
			'session_id'		=> session_id(),
			'request_id'		=> md5($_SERVER['REMOTE_PORT']. $_SERVER['REMOTE_ADDR']. $_SERVER['REQUEST_URI']. microtime(true)),
			'request_method'	=> $_SERVER['REQUEST_METHOD'],
			'request_uri'		=> $_SERVER['REQUEST_URI'],
			'http_host'			=> $_SERVER['HTTP_HOST'],
			'remote_addr'		=> $_SERVER['REMOTE_ADDR'],
		);
		return true;
	}

	/**
	* Get debug item value
	*/
	function _get_debug_item($name = '') {
		if (!$this->INSTRUMENT_QUERIES) {
			return '';
		}
		return $this->_instrument_items[$name];
	}

	/**
	* Add instrumentation info to the query for highload SQL debug and profile
	*/
	function _instrument_query($query_sql = '', $keys = array('request_id', 'session_id', 'SESSION_user_id', 'GET_object', 'GET_action')) {
		$query_header = '';
		if ($query_sql) {
			// the first frame is the original caller
			$frame = array_pop(debug_backtrace());
			// Add the PHP source location
			$query_header = '-- File: '.$frame['file']."\t".'Line: '.$frame['line']."\t".'Function: '.$frame['function']."\t";
			foreach ((array)$keys as $x => $key) {
				$val = $this->_get_debug_item($key);
				if($val) {
					$val = str_replace(array("\t","\n","\0"), '', $val);
					// all other chars are safe in comments
					$key = strtolower(str_replace(array(': ',"\t","\n","\0"), '', $key));
					// Add the requested instrumentation keys
					$query_header .= "\t".$key.': '.$val;
				}
			}
		}
		return $query_header. PHP_EOL. $query_sql;
	}

	/**
	* Helper
	*/
	function delete($table, $where, $as_sql = false) {
		// Do not allow wide deletes, to prevent awful mistakes, use plain db()->query('DELETE ...') instead
		if (!$where) {
			return false;
		}
		$where_func = 'where';
		if (is_numeric($where)) {
			$where_func = 'whereid';
		} elseif (is_array($where)) {
			$is_all_numeric = true;
			foreach ($where as $k => $v) {
				if (!is_numeric($k) || !is_numeric($v)) {
					$is_all_numeric = false;
					break;
				}
			}
			if ($is_all_numeric) {
				$where_func = 'whereid';
			}
		}
		$sql = $this->from($table)->$where_func($where)->delete($_as_sql = true);
		if (false === strpos(strtoupper($sql), 'WHERE')) {
			return false;
		}
		if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS && !$as_sql) {
			$this->_save_query_revision(__FUNCTION__, $table, array('where' => $where, 'cond' => $cond));
		}
		return $as_sql ? $sql : $this->query($sql);
	}

	/**
	*/
	function update_batch_safe($table, $data, $index = null, $only_sql = false) {
		$data = $this->_fix_data_safe($table, $data);
		return $this->update_batch($table, $this->es($data), $index, $only_sql);
	}

	/**
	*/
	function update_batch($table, $data, $index = null, $only_sql = false) {
		if ($this->DB_REPLICATION_SLAVE && !$only_sql) {
			return false;
		}
		if (!$this->_connected && !$this->connect()) {
			return false;
		}
		if (!is_object($this->db)) {
			return false;
		}
		if (!$index) {
			$index = 'id';
		}
		if (!strlen($table) || !$data || !is_array($data) || !$index) {
			return false;
		}
		$this->_set_update_batch($data, $index);
		if (count($this->qb_set) === 0) {
			return false;
		}
		$affected_rows = 0;
		$records_at_once = 100;
		$out = '';
		for ($i = 0, $total = count($this->qb_set); $i < $total; $i += $records_at_once) {
			$sql = $this->_update_batch($table, array_slice($this->qb_set, $i, $records_at_once), $index);
			if ($only_sql) {
				$out .= $sql.';'.PHP_EOL;
			} else {
				$this->query($sql);
				$affected_rows += $this->affected_rows();
			}
		}
		$this->qb_set = array();
		if ( ! $only_sql) {
			$out = $affected_rows;
		}
		return $out;
	}

	/**
	*/
	function _update_batch($table, $values, $index) {
		$index = $this->escape_key($index);
		$ids = array();
		foreach ((array)$values as $key => $val) {
			$ids[] = $val[$index];
			foreach (array_keys($val) as $field) {
				if ($field !== $index) {
					$final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
				}
			}
		}
		$cases = '';
		foreach ((array)$final as $k => $v) {
			$cases .= $k.' = CASE '.PHP_EOL. implode(PHP_EOL, $v). PHP_EOL. 'ELSE '.$k.' END, ';
		}
		if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
			$this->_save_query_revision(__FUNCTION__, $table, array('data' => $values, 'index' => $index));
		}
		return 'UPDATE '.$this->_escape_table_name($table).' SET '.substr($cases, 0, -2). ' WHERE '.$index.' IN('.implode(',', $ids).')';
	}

	/**
	*/
	function _set_update_batch($key, $index = '') {
		if ( ! is_array($key)) {
			return false;
		}
		foreach ((array)$key as $k => $v) {
			$index_set = FALSE;
			$clean = array();
			foreach ((array)$v as $k2 => $v2) {
				if ($k2 === $index)	{
					$index_set = TRUE;
				}
				$clean[$this->escape_key($k2)] = $this->escape_val($v2);
			}
			if ($index_set === FALSE) {
				//return $this->display_error('db_batch_missing_index');
				return false;
			}
			$this->qb_set[] = $clean;
		}
		return $this;
	}

	/**
	*/
	function utils() {
		$cname = 'db_utils_'.$this->get_driver_family();
		$obj = clone _class($cname, 'classes/db/');
		$obj->db = $this;
		return $obj;
	}

	/**
	*/
	function split_sql($sql) {
		return $this->utils()->split_sql($sql);
	}

	/**
	*/
	function query_builder() {
		$cname = 'db_query_builder_'.$this->get_driver_family();
		$obj = clone _class($cname, 'classes/db/');
		$obj->db = $this;
		return $obj;
	}

	/**
	* Query builder shortcut
	*/
	function select() {
		return $this->query_builder()->select(array('__args__' => func_get_args()));
	}

	/**
	* Query builder shortcut
	*/
	function from() {
		return $this->query_builder()->from(array('__args__' => func_get_args()));
	}

	/**
	* ORM shortcut
	*/
	function model($name, $params = array()) {
		$model = $this->_model_load($name);
		$params && $model->_set_params($params);
		return $model;
	}

	/**
	* Load new model object
	*/
	function _model_load($name) {
		if (!$this->_models_preloaded) {
			$this->_models_preload();
		}
		$model_class = $name.'_model';
		$main_modules = &main()->modules;
		if (isset($main_modules[$model_class])) {
			return $main_modules[$model_class];
		}
		$avail_models = &$this->_models_preloaded;
		$model_path = $avail_models[$name];
		if (!isset($avail_models[$name])) {
			throw new Exception('Not able to load model: '.$name);
			return false;
		}
		if (!class_exists($model_class)) {
			require_once $model_path;
			if (!class_exists($model_class)) {
				throw new Exception('Not able to find model class: '.$model_class.' by path: '.$model_path);
				return false;
			}
		}
		$model_obj = _class($model_class);
		$model_obj->_db = $this;
		return $model_obj;
	}

	/**
	* Preload models for ORM
	*/
	function _models_preload() {
		if ($this->_models_preloaded) {
			return true;
		}
		load('model', 'framework', 'classes/');
		$suffix = '_model'.YF_CLS_EXT;
		$s_len = strlen($suffix);
		$globs = array(
			'yf'				=> YF_PATH.'share/models/*'.$suffix,
			'yf_plugins'		=> YF_PATH.'plugins/*/share/models/*'.$suffix,
			'project'			=> PROJECT_PATH.'share/models/*'.$suffix,
			'project_plugins'	=> PROJECT_PATH.'plugins/*/share/models/*'.$suffix,
			'project_app_path'	=> APP_PATH.'models/*'.$suffix,
		);
		$models = array();
		foreach($globs as $glob) {
			foreach (glob($glob) as $path) {
				$name = trim(substr(basename($path), 0, -$s_len));
				if ($name) {
					$models[$name] = $path;
				}
			}
		}
		$this->_models_preloaded = $models;
	}

	/**
	*/
	function _save_query_revision($method, $table, $params = array()) {
		$trace = main()->trace_string();
		$trace = array_slice(explode(PHP_EOL, $trace), 1, 5);
		$extra = array(
			'get_object'	=> $_GET['object'],
			'get_action'	=> $_GET['action'],
			'get_id'		=> $_GET['id'],
			'trace'			=> $trace,
		);
		$to_insert = array(
			'date'			=> date('Y-m-d H:i:s'),
			'data_new'		=> $params['data'] ? json_encode($params['data']) : '',
			'data_old'		=> $params['data_old'],
			'data_diff'		=> $params['data_diff'],
			'user_id'		=> main()->ADMIN_ID,
			'user_group'	=> main()->ADMIN_GROUP,
			'site_id'		=> conf('SITE_ID'),
			'server_id'		=> conf('SERVER_ID'),
			'ip'			=> common()->get_ip(),
			'query_method'	=> $method,
			'query_table'	=> $table,
			'extra'			=> json_encode($extra),
			'url'			=> $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
		);
		$sql = $this->insert_safe('sys_db_revisions', $to_insert, $only_sql = true);
		$this->_add_shutdown_query($sql);
	}

	/**
	*/
	function _escape_table_name($name = '') {
		$name = trim($name);
		if (!strlen($name)) {
			return false;
		}
		$db = '';
		$table = '';
		if (strpos($name, '.') !== false) {
			list($db, $table) = explode('.', $name);
			$db = trim($db);
			$table = trim($table);
		} else {
			$table = $name;
		}
		if (!strlen($table)) {
			return false;
		}
		$table = $this->_fix_table_name($table);
		return (strlen($db) ? $this->escape_key($db).'.' : ''). $this->escape_key($table);
	}

	/**
	*/
	function _mysql_escape_mimic($string) {
		if (is_array($string)) {
	        return array_map(array($this, __FUNCTION__), $string);
		}
		if (is_float($string)) {
			return str_replace(',', '.', $string);
		} elseif (is_int($string)) {
			return $string;
		} elseif (is_bool($string)) {
			return (int)$string;
		}
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $string);
	}
}
