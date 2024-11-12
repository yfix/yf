<?php

/**
 * Database abstraction layer.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_db
{
    /** @var string Type of database (default) */
    public $DB_TYPE = 'mysql';
    /** @var bool Switch caching on/off */
    public $NO_CACHE = false;
    /** @var bool Use tables names caching */
    public $CACHE_TABLE_NAMES = false;
    /** @var int @conf_skip Number of queries */
    public $NUM_QUERIES = 0;
    /** @var array Query log array */
    public $_LOG = [];
    /** @var int Tables cache lifetime (while developing need to be short) (else need to be very large) */
    public $TABLE_NAMES_CACHE_TTL = 3600; // 1*3600*24 = 1 day
    /** @var bool Auto-connect on/off */
    public $AUTO_CONNECT = false;
    /** @var bool Use backtrace in error message */
    public $ERROR_BACKTRACE = true;
    /** @var bool Use backtrace to get where query was called from (will be used only when DEBUG_MODE is enabled) */
    public $USE_QUERY_BACKTRACE = true;
    /** @var bool Auto-repairing on error (table not exists) on/off */
    public $ERROR_AUTO_REPAIR = true;
    /** @var string Folder where databases drivers are stored */
    public $DB_DRIVERS_DIR = 'classes/db/';
    /** @var int Num tries to reconnect in common mode (will be useful if db server is overloaded) (Set to '0' for disabling) */
    public $RECONNECT_NUM_TRIES = 3;
    /** @var int Num tries to reconnect inside CONSOLE MODE (will be useful if db server is overloaded and sometimes we lost connection to it) (Set to '0' for disabling) */
    public $RECONNECT_CONSOLE_TRIES = 1000;
    /** @var int Time to wait between reconnects (in seconds) */
    public $RECONNECT_DELAY = 1;
    /** @var bool Use logarithmic increase or reconnect time */
    public $RECONNECT_DELAY_LOG_INC = 1;
    /** @var bool Use locking for reconnects or not */
    public $RECONNECT_USE_LOCKING = false;
    /** @var array List of mysql error codes to use for reconnect tries. See also http://dev.mysql.com/doc/refman/5.0/en/error-messages-client.html */
    public $RECONNECT_MYSQL_ERRORS = [1053, 1317, 2000, 2002, 2003, 2004, 2005, 2006, 2008, 2012, 2013, 2020, 2027, 2055];
    /** @var string */
    public $RECONNECT_LOCK_FILE_NAME = 'db_cannot_connect_[DB_HOST]_[DB_NAME]_[DB_USER]_[DB_PORT].lock';
    /** @var int Time in seconds between unlock reconnect */
    public $RECONNECT_LOCK_TIMEOUT = 30;
    /** @var bool Connection required or not (else E_USER_WARNING will be thrown not E_USER_ERROR) */
    public $CONNECTION_REQUIRED = false;
    /** @var bool Allow to use shutdown queries or not */
    public $USE_SHUTDOWN_QUERIES = true;
    /** @var bool Allow to cache specified queries results */
    public $ALLOW_CACHE_QUERIES = false;
    /** @var bool Max number of cached queries */
    public $CACHE_QUERIES_LIMIT = 100;
    /** @var bool Max number of logged queries (set to 0 to unlimited) */
    public $LOGGED_QUERIES_LIMIT = 1000;
    /** @var bool Gather affected rows stats (will be used only when DEBUG_MODE is enabled) */
    public $GATHER_AFFECTED_ROWS = true;
    /** @var bool Store db queries to file */
    public $LOG_ALL_QUERIES = false;
    /** @var bool Store db queries to file */
    public $LOG_SLOW_QUERIES = false;
    /** @var string Log queries file name */
    public $FILE_NAME_LOG_ALL = 'db_queries.log';
    /** @var string Log queries file name */
    public $FILE_NAME_LOG_SLOW = 'slow_queries.log';
    /** @var float */
    public $SLOW_QUERIES_TIME_LIMIT = 0.2;
    /** @var bool Add additional engine details to the SQL as comment for later use */
    public $INSTRUMENT_QUERIES = false;
    /** @var array */
    public $_instrument_items = [];
    /** @var bool Currently only in DEBUG_MODE */
    public $SHOW_QUERY_WARNINGS = true;
    /** @var bool Currently only in DEBUG_MODE */
    public $SHOW_QUERY_INFO = true;
    /** @var bool @conf_skip Internal var (default value) */
    public $_tried_to_connect = false;
    /** @var bool @conf_skip Internal var (default value) */
    public $_connected = false;
    /** @var mixed @conf_skip Driver instance */
    public $db = null;
    /** @var string Tables names prefix */
    public $DB_PREFIX = null;
    /** @var string */
    public $DB_HOST = '';
    /** @var string */
    public $DB_NAME = '';
    /** @var string */
    public $DB_USER = '';
    /** @var string */
    public $DB_PSWD = '';
    /** @var int */
    public $DB_PORT = '';
    /** @var string */
    public $DB_CHARSET = '';
    /** @var string */
    public $DB_SOCKET = '';
    /** @var bool */
    public $DB_SSL = false;
    /** @var bool */
    public $DB_PERSIST = false;
    /** @var bool In case of true - we will try to avoid any data/structure modification queries to not break replication */
    public $DB_REPLICATION_SLAVE = false;
    /** @var bool Adding SQL_NO_CACHE to SELECT queries: useful to find long running queries */
    public $SQL_NO_CACHE = false;
    /** @var bool Needed for installation and repairing process */
    public $ALLOW_AUTO_CREATE_DB = false;
    /** @var bool Use sql query revisions for update/insert/replace/delete */
    public $QUERY_REVISIONS = false;
    /** @var bool update_safe, insert_safe, update_batch_safe: use additional checking for exising table fields */
    public $FIX_DATA_SAFE = true;
    /** @var bool Trigger to act silently or not in *_safe methods */
    public $FIX_DATA_SAFE_SILENT = false;
    /** @var array Filled automatically from generated file */
    public $_need_sys_prefix = [];

    public $IS_PRIMARY_CONNECTION = null;
    public $NO_AUTO_CONNECT = null;
    public $_connect_start_time = null;
    public $_connection_time = null;
    public $_last_query_error = null;
    public $_last_insert_id = null;
    public $_last_affected_rows = null;
    public $_db_results_cache = [];
    public $utils = null;
    public $migrator = null;
    public $installer = null;
    public $_SHUTDOWN_QUERIES = [];
    public $_shutdown_executed = null;
    public $_found_tables = [];
    public $_queries_logged = null;
    public $QUERY_REVISIONS_TABLES = [];
    public $QUERY_REVISIONS_METHODS = [];
    public $_repairs_by_sql = [];

    /**
     * Constructor.
     * @param mixed $db_type
     * @param null|mixed $db_prefix
     * @param null|mixed $db_replication_slave
     */
    public function __construct($db_type = '', $db_prefix = null, $db_replication_slave = null)
    {
        global $DEBUG;

        $this->_load_tables_with_sys_prefix();
        // Type/driver of database server
        $this->DB_TYPE = ! empty($db_type) ? $db_type : DB_TYPE;
        if ( ! defined('DB_PREFIX') && empty($db_prefix)) {
            define('DB_PREFIX', '');
        }
        $this->DB_PREFIX = ! empty($db_prefix) ? $db_prefix : DB_PREFIX;
        // Check if this is primary database connection
        $debug_index = count($DEBUG['db_instances'] ?? []);
        if ($debug_index < 1) {
            $this->IS_PRIMARY_CONNECTION = true;
        } else {
            $this->IS_PRIMARY_CONNECTION = false;
        }
        // Trying to override replication slave setting
        if (isset($db_replication_slave)) {
            $this->DB_REPLICATION_SLAVE = (bool) $db_replication_slave;
        } elseif ($this->IS_PRIMARY_CONNECTION && defined('DB_REPLICATION_SLAVE')) {
            $this->DB_REPLICATION_SLAVE = (bool) DB_REPLICATION_SLAVE;
        }
        // Track db class instances
        $DEBUG['db_instances'][$debug_index] = &$this;
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $DEBUG['db_instances_trace'][$debug_index] = $this->_trace_string();
        }
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $db_type
     */
    public function get_driver_family($db_type = '')
    {
        $db_type = strtolower($db_type ?: $this->DB_TYPE);
        // Get current abstract db type
        $families = [
            'mysql' => ['db_type', 'mysql', 'mysqli', 'pdo_mysql'],
            'pgsql' => ['pgsql', 'pdo_pgsql', 'postgre', 'postgres'],
        ];
        foreach ($families as $family => $aliases) {
            if (in_array($db_type, $aliases)) {
                $name = $family;
                break;
            }
        }
        if ( ! $name) {
            $name = $db_type;
        }
        return $name;
    }

    /**
     * Framework constructor.
     */
    public function _init()
    {
        // Perform auto-connection to db if needed
        if (($this->AUTO_CONNECT || MAIN_TYPE == 'admin') && ! $this->NO_AUTO_CONNECT) {
            $this->connect();
        }
        $this->_set_debug_items();
        if (main()->is_console()) {
            $this->enable_silent_mode();
        }
        // Set shutdown function
        if ($this->USE_SHUTDOWN_QUERIES) {
            register_shutdown_function([&$this, '_execute_shutdown_queries']);
        }
        if ($this->LOG_ALL_QUERIES || $this->LOG_SLOW_QUERIES) {
            register_shutdown_function([$this, '_log_queries']);
        }
        // Turn off tables repairing if we are dealing with slave server
        if ($this->DB_REPLICATION_SLAVE) {
            $this->ERROR_AUTO_REPAIR = false;
        }
    }


    public function _load_tables_with_sys_prefix()
    {
        if ($this->_need_sys_prefix) {
            return $this->_need_sys_prefix;
        }
        $paths = [
            'app' => APP_PATH . 'share/db_sys_prefix_tables.php',
            'yf' => YF_PATH . 'plugins/db/share/db_sys_prefix_tables.php',
        ];
        $data = [];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $_data = require $path;
                if ($_data && is_array($_data)) {
                    $data += $_data;
                }
            }
        }
        $this->_need_sys_prefix = $data;
        return (array) $data;
    }


    public function is_ready()
    {
        return (bool) $this->_connected;
    }

    /**
     * Connect db driver and then connect to db.
     * @param mixed $db_host
     * @param mixed $db_user
     * @param null|mixed $db_pswd
     * @param null|mixed $db_name
     * @param mixed $force
     * @param mixed $params
     */
    public function connect($db_host = '', $db_user = '', $db_pswd = null, $db_name = null, $force = false, $params = [])
    {
        if (is_array($db_host)) {
            $params = $db_host;
            $db_host = '';
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        if (($params['reconnect'] ?? false) || ($params['force'] ?? false)) {
            $force = true;
        }
        if ( ! empty($this->_tried_to_connect) && ! $force) {
            return $this->_connected;
        }
        $this->_connect_start_time = microtime(true);
        if ( ! ($params['reconnect'] ?? false)) {
            $this->_set_connect_params($db_host, $db_user, $db_pswd, $db_name, $force, $params);
        }
        $driver_class_name = main()->load_class_file('db_driver_' . $this->DB_TYPE, $this->DB_DRIVERS_DIR);
        // Create new instanse of the driver class
        if ( ! empty($driver_class_name) && class_exists($driver_class_name) && ! is_object($this->db)) {
            if ($this->RECONNECT_USE_LOCKING) {
                $lock_file = $this->_get_reconnect_lock_path();
                if (file_exists($lock_file)) {
                    if ((time() - filemtime($lock_file)) > $this->RECONNECT_LOCK_TIMEOUT) {
                        unlink($lock_file);
                    } else {
                        return false;
                    }
                }
            }
            $driver_params = $this->_get_connect_params();
            // Try to connect several times
            $tries = $this->RECONNECT_NUM_TRIES;
            if (main()->is_console() && ! main()->is_unit_test()) {
                $tries = $this->RECONNECT_CONSOLE_TRIES;
            }
            for ($i = 1; $i <= $tries; $i++) {
                $this->db = new $driver_class_name($driver_params);
                if ( ! is_object($this->db) || ! ($this->db instanceof yf_db_driver)) {
                    trigger_error('DB: Wrong driver', $this->CONNECTION_REQUIRED ? E_USER_ERROR : E_USER_WARNING);
                    break;
                }
                $implemented = [];
                foreach (get_class_methods($this->db) as $method) {
                    if ($method[0] != '_') {
                        $implemented[$method] = $method;
                    }
                }
                $this->db->implemented = $implemented;
                // Stop after success
                if ( ! empty($this->db->db_connect_id)) {
                    break;
                    // Wait some time and try again (use logarithmic increase)
                }
                $multiplier = 1;
                if ($this->RECONNECT_DELAY_LOG_INC) {
                    $multiplier = $i + ($this->RECONNECT_DELAY <= 1 ? 1 : 0);
                }
                $sleep_time = $this->RECONNECT_DELAY * $multiplier;
                sleep($sleep_time);
            }
            if ($this->RECONNECT_USE_LOCKING && ! $this->db->db_connect_id) {
                file_put_contents($lock_file, gmdate('Y-m-d H:i:s') . ' GMT');
            }
        }
        $this->_tried_to_connect = true;
        if ( ! $this->db->db_connect_id) {
            trigger_error('DB: ERROR CONNECTING TO DATABASE', $this->CONNECTION_REQUIRED ? E_USER_ERROR : E_USER_WARNING);
        } else {
            $this->db->SQL_NO_CACHE = $this->SQL_NO_CACHE;
            $this->_connected = true;
        }
        $this->_connection_time += (microtime(true) - $this->_connect_start_time);
        return $this->_connected;
    }


    public function _get_connect_params()
    {
        return [
            'host' => $this->DB_HOST,
            'user' => $this->DB_USER,
            'pswd' => $this->DB_PSWD,
            'name' => $this->DB_NAME,
            'persist' => $this->DB_PERSIST,
            'ssl' => $this->DB_SSL,
            'port' => $this->DB_PORT,
            'socket' => $this->DB_SOCKET,
            'charset' => $this->DB_CHARSET,
            'allow_auto_create_db' => $this->ALLOW_AUTO_CREATE_DB,
        ];
    }

    /**
     * @param mixed $db_host
     * @param mixed $db_user
     * @param null|mixed $db_pswd
     * @param null|mixed $db_name
     * @param mixed $force
     * @param mixed $params
     */
    public function _set_connect_params($db_host = '', $db_user = '', $db_pswd = null, $db_name = null, $force = false, $params = [])
    {
        if (is_array($db_host)) {
            $params = $db_host;
            $db_host = '';
        }
        if ( ! is_array($params)) {
            $params = [];
        }
        if (($params['reconnect'] ?? false) || ($params['force'] ?? false)) {
            $force = true;
        }
        $this->DB_HOST = ($params['host'] ?? $db_host) ?: (defined('DB_HOST') ? DB_HOST : 'localhost');
        $this->DB_USER = ($params['user'] ?? $db_user) ?: (defined('DB_USER') ? DB_USER : 'root');
        // db_pswd can be empty string
        $_db_pswd = $params['pswd'] ?? $db_pswd;
        $this->DB_PSWD = $_db_pswd ?? (defined('DB_PSWD') ? DB_PSWD : '');
        // db_name can be empty string - means we working in special mode, just connecting to server
        $_db_name = $params['name'] ?? $db_name;
        $this->DB_NAME = $_db_name ?? (defined('DB_NAME') ? DB_NAME : '');
        $this->DB_PORT = $params['port'] ?? (defined('DB_PORT') ? constant('DB_PORT') : '');
        $this->DB_SOCKET = $params['socket'] ?? (defined('DB_SOCKET') ? constant('DB_SOCKET') : '');
        $this->DB_SSL = $params['ssl'] ?? (defined('DB_SSL') ? constant('DB_SSL') : false);
        $this->DB_CHARSET = $params['charset'] ?? (defined('DB_CHARSET') ? constant('DB_CHARSET') : '');
        if (isset($params['prefix'])) {
            $this->DB_PREFIX = $params['prefix'];
        }
        $allow_auto_create_db = $params['auto_create_db'] ?? false;
        if ($allow_auto_create_db !== null) {
            $this->ALLOW_AUTO_CREATE_DB = $allow_auto_create_db;
        }
    }

    /**
     * Close connection to db.
     */
    public function close()
    {
        $this->_connected = false;
        $this->_tried_to_connect = false;
        $result = $this->db->close();
        unset($this->db);
        return $result;
    }

    /**
     * Prepare statement to execute.
     * @param mixed $sql
     * @param mixed $params
     */
    public function prepare($sql, $params = [])
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        if ( ! is_object($this->db) || ! $this->db->implemented['prepare']) {
            return false;
        }
        return $this->db->prepare($sql, $params);
    }

    /**
     * Execute prepared statement.
     * @param mixed $stmt
     * @param mixed $params
     */
    public function execute($stmt, $params = [])
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        if ( ! is_object($this->db) || ! $this->db->implemented['execute']) {
            return false;
        }
        return $this->db->execute($stmt, $params);
    }

    /**
     * Function return resource ID of the query.
     * @param mixed $sql
     */
    public function &query($sql)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        if ( ! is_object($this->db)) {
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
        if ( ! $result && $query_allowed) {
            $db_error = $this->db->error();
        }
        if ( ! $result && $query_allowed && $db_error) {
            // Try to reconnect if we see some these errors: http://dev.mysql.com/doc/refman/5.0/en/error-messages-client.html
            if ($this->get_driver_family() === 'mysql' && in_array($db_error['code'], $this->RECONNECT_MYSQL_ERRORS)) {
                $this->db = null;
                $reconnect_successful = $this->connect(['reconnect' => true]);
                if ($reconnect_successful) {
                    $result = $this->db->query($sql);
                }
            }
        }
        $log_allowed = (DEBUG_MODE || $this->LOG_ALL_QUERIES || $this->LOG_SLOW_QUERIES);
        if ($log_allowed) {
            $log_id = $this->_query_log($sql, $this->USE_QUERY_BACKTRACE ? $this->_trace_string() : [], $db_error);
        }
        if ( ! $result && $query_allowed && $db_error && $this->ERROR_AUTO_REPAIR) {
            $result = $this->_repair_table($sql, $db_error);
            if ($result) {
                $repair_done_ok = true;
            }
        }
        if ( ! $result && $db_error) {
            $this->_query_show_error($sql, $db_error, (DEBUG_MODE && $this->ERROR_BACKTRACE) ? $this->_trace_string() : '');
            $this->_last_query_error = $db_error;
        } else {
            $this->_last_query_error = null;
        }
        $need_insert_id = false;
        $_sql_type = strtoupper(rtrim(substr(ltrim($sql), 0, 7)));
        if (in_array($_sql_type, ['INSERT', 'UPDATE', 'REPLACE'])) {
            $need_insert_id = true;
        }
        $this->_last_insert_id = $result && $need_insert_id ? (int) $this->db->insert_id() : null;
        if ($this->GATHER_AFFECTED_ROWS) {
            $this->_last_affected_rows = $result ? (int) $this->db->affected_rows() : null;
        }
        // This part needed to update debug log after executing query, but ensure correct order of queries
        if ($log_allowed) {
            if (DEBUG_MODE && $this->SHOW_QUERY_WARNINGS && method_exists($this->db, 'get_last_warnings')) {
                $warnings = $this->db->get_last_warnings();
            }
            if (DEBUG_MODE && $this->SHOW_QUERY_INFO && method_exists($this->db, 'get_last_query_info')) {
                $info = $this->db->get_last_query_info();
            }
            $this->_update_query_log($log_id, $result, $query_time_start, $warnings, $info);
        }
        return $result;
    }

    /**
     * @param mixed $sql
     * @param mixed $db_error
     * @param mixed $_trace
     */
    public function _query_show_error($sql, $db_error, $_trace = '')
    {
        $old_db_error = $db_error;
        $db_error = $this->db->error();
        if (empty($db_error) || empty($db_error['message'])) {
            $db_error = $old_db_error;
        }
        $msg = 'DB: QUERY ERROR: ' . $sql . ';' . PHP_EOL . 'CAUSE: ' . $db_error['message']
            . ($db_error['code'] ? ' (code:' . $db_error['code'] . ')' : '')
            . ($db_error['offset'] ? ' (offset:' . $db_error['offset'] . ')' : '')
            . (main()->USE_CUSTOM_ERRORS ? '' : $_trace . PHP_EOL);
        trigger_error($msg, E_USER_WARNING);
    }

    /**
     * @param mixed $sql
     * @param mixed $_trace
     * @param mixed $db_error
     */
    public function _query_log($sql, $_trace = [], $db_error = false)
    {
        $_log_allowed = true;
        // Save memory on high number of query log entries
        if ($this->LOGGED_QUERIES_LIMIT && count((array) $this->_LOG) >= $this->LOGGED_QUERIES_LIMIT) {
            $_log_allowed = false;
        }
        if ( ! $_log_allowed) {
            return false;
        }
        $warnings = null;
        $info = null;
        $this->_LOG[] = [
            'sql' => $sql,
            'rows' => '',
            'insert_id' => '',
            'error' => $db_error,
            'info' => $info,
            'time' => '',
            'trace' => $_trace,
        ];
        return count((array) $this->_LOG) - 1;
    }

    /**
     * @param mixed $log_id
     * @param mixed $result
     * @param mixed $query_time_start
     * @param null|mixed $warnings
     * @param null|mixed $info
     */
    public function _update_query_log($log_id, $result, $query_time_start = 0, $warnings = null, $info = null)
    {
        if ( ! isset($this->_LOG[$log_id])) {
            return false;
        }
        $log = &$this->_LOG[$log_id];
        $time = (float) microtime(true) - (float) $query_time_start;
        $sql = $log['sql'];
        if ($this->GATHER_AFFECTED_ROWS && $result) {
            $_sql_type = strtoupper(rtrim(substr(ltrim($sql), 0, 7)));
            if (substr($_sql_type, 0, 4) === 'SHOW') {
                $_sql_type = 'SHOW';
            }
            $rows = null;
            //			if ($_sql_type == 'SELECT') {
            //				$rows = $this->num_rows($result);
            //			} elseif (in_array($_sql_type, ['INSERT', 'UPDATE', 'REPLACE', 'DELETE', 'SHOW'])) {
            $rows = $this->_last_affected_rows;
            //			}
        }
        $log['time'] = $time;
        $log['rows'] = $rows;
        $log['warning'] = $warnings;
        $log['info'] = $info;
        $log['insert_id'] = $this->_last_insert_id;
    }

    /**
     * Function execute unbuffered query.
     * @param mixed $sql
     */
    public function unbuffered_query($sql)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->query($sql);
    }

    /**
     * @param mixed $sql
     */
    public function multi_query($sql = [])
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        if ( ! is_object($this->db)) {
            return false;
        }
        if ( ! $this->db->HAS_MULTI_QUERY) {
            $result = [];
            foreach ((array) $sql as $k => $_sql) {
                $result[$k] = $this->query($_sql);
            }
            return $result;
        }
        return $this->db->multi_query($sql);
    }

    /**
     * Alias of insert() with auto-escaping of data.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     * @param mixed $replace
     * @param mixed $ignore
     * @param mixed $on_duplicate_key_update
     * @param mixed $extra
     */
    public function insert_safe($table, $data, $only_sql = false, $replace = false, $ignore = false, $on_duplicate_key_update = false, $extra = [])
    {
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if (is_array($only_sql)) {
            $extra += $only_sql;
            $only_sql = $extra['only_sql'];
        }
        $data = $this->_fix_data_safe($table, $data, $extra);
        return $this->insert($table, $this->es($data), $only_sql, $replace, $ignore, $on_duplicate_key_update, $extra);
    }

    /**
     * Insert array of values into table.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     * @param mixed $replace
     * @param mixed $ignore
     * @param mixed $on_duplicate_key_update
     * @param mixed $extra
     */
    public function insert($table, $data, $only_sql = false, $replace = false, $ignore = false, $on_duplicate_key_update = false, $extra = [])
    {
        if ($this->DB_REPLICATION_SLAVE && ! $only_sql) {
            return false;
        }
        if ( ! strlen($table) || ! is_array($data)) {
            return false;
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if (is_array($only_sql)) {
            $extra += $only_sql;
            $only_sql = $extra['only_sql'];
        }
        isset($extra['only_sql']) && $only_sql = $extra['only_sql'];
        isset($extra['replace']) && $replace = $extra['replace'];
        isset($extra['ignore']) && $ignore = $extra['ignore'];
        isset($extra['on_duplicate_key_update']) && $on_duplicate_key_update = $extra['on_duplicate_key_update'];
        is_string($replace) && $replace = false;

        $sql = $this->query_builder()->compile_insert($table, $data, compact('replace', 'ignore', 'on_duplicate_key_update') + $extra);
        if ( ! $sql) {
            return false;
        }
        if ($only_sql) {
            return $sql;
        }
        if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
            $this->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
        }
        return $this->query($sql);
    }

    /**
     * Alias, forced to add INSERT IGNORE.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     * @param mixed $replace
     * @param mixed $extra
     */
    public function insert_ignore($table, $data, $only_sql = false, $replace = false, $extra = [])
    {
        return $this->insert($table, $data, $only_sql, $replace, $ignore = true, $on_duplicate_key_update = false, $extra);
    }

    /**
     * Alias, forced to add INSERT ... ON DUPLICATE KEY UPDATE.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     * @param mixed $replace
     * @param mixed $extra
     */
    public function insert_on_duplicate_key_update($table, $data, $only_sql = false, $replace = false, $extra = [])
    {
        $on_duplicate_key_update = (true && ($this->get_driver_family() === 'mysql'));
        return $this->insert($table, $data, $only_sql, $replace, $ignore = false, $on_duplicate_key_update, $extra);
    }

    /**
     * Alias of replace() with data auto-escape.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     * @param mixed $extra
     */
    public function replace_safe($table, $data, $only_sql = false, $extra = [])
    {
        $replace = (true && in_array($this->get_driver_family(), ['mysql']));
        return $this->insert_safe($table, $data, $only_sql, $replace, $ignore = false, $on_duplicate_key_update = false, $extra);
    }

    /**
     * Replace array of values into table.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $only_sql
     */
    public function replace($table, $data, $only_sql = false)
    {
        $replace = (true && in_array($this->get_driver_family(), ['mysql']));
        return $this->insert($table, $data, $only_sql, $replace);
    }

    /**
     * Alias of update() with data auto-escape.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $where
     * @param mixed $only_sql
     * @param mixed $extra
     */
    public function update_safe($table, $data, $where, $only_sql = false, $extra = [])
    {
        $data = $this->_fix_data_safe($table, $data, $extra);
        return $this->update($table, $this->es($data), $where, $only_sql);
    }

    /**
     * Update table with given values.
     * @param mixed $table
     * @param mixed $data
     * @param mixed $where
     * @param mixed $only_sql
     * @param mixed $extra
     */
    public function update($table, $data, $where, $only_sql = false, $extra = [])
    {
        if ($this->DB_REPLICATION_SLAVE && ! $only_sql) {
            return false;
        }
        if (empty($table) || empty($data) || empty($where)) {
            return false;
        }
        if ( ! is_array($extra)) {
            $extra = [];
        }
        if (is_array($only_sql)) {
            $extra += $only_sql;
            $only_sql = $extra['only_sql'];
        }
        isset($extra['only_sql']) && $only_sql = $extra['only_sql'];
        $sql = $this->query_builder()->compile_update($table, $data, $where, $extra);
        if ( ! $sql) {
            return false;
        }
        if ($only_sql) {
            return $sql;
        }
        if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
            $this->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
        }
        return $this->query($sql);
    }

    /**
     * Execute database query and fetch result as assoc array (for queries that returns only 1 row).
     * @param mixed $sql
     * @param mixed $use_cache
     * @param mixed $assoc
     * @param mixed $return_sql
     */
    public function query_fetch($sql, $use_cache = true, $assoc = true, $return_sql = false)
    {
        if ( ! strlen($sql)) {
            return false;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        $storage = &$this->_db_results_cache;
        if ($use_cache && $this->ALLOW_CACHE_QUERIES && ! $this->NO_CACHE && isset($storage[$sql])) {
            return $storage[$sql];
        }
        $data = null;
        if ($this->get_driver_family() === 'mysql' && strtoupper(substr(ltrim($sql), 0, 6)) === 'SELECT') {
            $sql = rtrim(rtrim(rtrim($sql), ';'));
            if ( ! preg_match('~\s+LIMIT\s+[0-9,\s]+$~ims', strtoupper($sql))) {
                $sql .= ' LIMIT 1';
            }
        }
        // Mostly for unit tests, not a real use case
        if ($return_sql) {
            return $sql;
        }
        $q = $this->query($sql);
        if ( ! empty($q)) {
            if ($assoc) {
                $data = @$this->db->fetch_assoc($q);
            } else {
                $data = @$this->db->fetch_row($q);
            }
            $this->free_result($q);
            // Store result in variable cache
            if ($use_cache && $this->ALLOW_CACHE_QUERIES && ! $this->NO_CACHE && ! isset($storage[$sql])) {
                $storage[$sql] = $data;
                // Permanently turn off queries cache (and free some memory) if case of limit reached
                if ($this->CACHE_QUERIES_LIMIT && count((array) $storage) > $this->CACHE_QUERIES_LIMIT) {
                    $this->ALLOW_CACHE_QUERIES = false;
                    $storage = null;
                }
            }
        }
        return $data;
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param mixed $use_cache
     * @param mixed $assoc
     * @param mixed $return_sql
     */
    public function get($sql, $use_cache = true, $assoc = true, $return_sql = false)
    {
        return $this->query_fetch($sql, $use_cache, $assoc, $return_sql);
    }

    /**
     * Alias, return first value.
     * @param mixed $sql
     * @param mixed $use_cache
     * @param mixed $return_sql
     */
    public function get_one($sql, $use_cache = true, $return_sql = false)
    {
        if ( ! strlen($sql)) {
            return false;
        }
        $result = $this->query_fetch($sql, $use_cache, $assoc = true, $return_sql);
        if ( ! $result) {
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
     * Example: 'SELECT name FROM p_static_pages' => array('page1', 'page2').
     * @param mixed $sql
     * @param mixed $use_cache
     */
    public function get_2d($sql, $use_cache = true)
    {
        if ( ! strlen($sql)) {
            return false;
        }
        $result = $this->query_fetch_all($sql, $use_cache, true);
        // Get 1st and 2nd keys from first sub-array
        if (is_array($result) && $result) {
            $keys = array_keys(current($result));
        }
        if ( ! $keys) {
            return false;
        }
        $out = [];
        foreach ((array) $result as $id => $data) {
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
     *	]]].
     * @param mixed $sql
     * @param mixed $max_levels
     * @param mixed $use_cache
     */
    public function get_deep_array($sql, $max_levels = 0, $use_cache = true)
    {
        if ( ! strlen($sql)) {
            return false;
        }
        if ( ! $max_levels || $max_levels > 4) {
            $max_levels = 4;
        }
        $out = [];
        $q = $this->query($sql);
        if ( ! $q) {
            return false;
        }
        $row = $this->fetch_assoc($q);
        $levels = count((array) $row);
        if ( ! is_array($row) || ! $levels) {
            return false;
        }
        if ($levels > $max_levels) {
            $levels = $max_levels;
        }
        $k = array_keys($row);
        $a = [];
        do {
            if ($levels == 1) {
                $a[$row[$k[0]]] = $row;
            } elseif ($levels == 2) {
                $a[$row[$k[0]]][$row[$k[1]]] = $row;
            } elseif ($levels == 3) {
                $a[$row[$k[0]]][$row[$k[1]]][$row[$k[2]]] = $row;
            } elseif ($levels == 4) {
                $a[$row[$k[0]]][$row[$k[1]]][$row[$k[2]]][$row[$k[3]]] = $row;
            }
        } while ($row = $this->fetch_assoc($q));
        return $a;
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param mixed $use_cache
     */
    public function query_fetch_assoc($sql, $use_cache = true)
    {
        return $this->query_fetch($sql, $use_cache, true);
    }

    /**
     * Same as 'query_fetch' except fetching as row not assoc.
     * @param mixed $sql
     * @param mixed $use_cache
     */
    public function query_fetch_row($sql, $use_cache = true)
    {
        return $this->query_fetch($sql, $use_cache, false);
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param null|mixed $key_name
     * @param mixed $use_cache
     */
    public function get_all($sql, $key_name = null, $use_cache = true)
    {
        return $this->query_fetch_all($sql, $key_name, $use_cache);
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param null|mixed $key_name
     * @param mixed $use_cache
     */
    public function all($sql, $key_name = null, $use_cache = true)
    {
        return $this->query_fetch_all($sql, $key_name, $use_cache);
    }

    /**
     * Execute database query and fetch result into assotiative array.
     * @param mixed $sql
     * @param null|mixed $key_name
     * @param mixed $use_cache
     */
    public function query_fetch_all($sql, $key_name = null, $use_cache = true)
    {
        if ( ! strlen($sql)) {
            return false;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        $params = [];
        if (is_array($use_cache)) {
            $params = $use_cache;
            $use_cache = isset($params['use_cache']) ? $params['use_cache'] : true;
        }
        $storage = &$this->_db_results_cache;
        if ($use_cache && $this->ALLOW_CACHE_QUERIES && ! $this->NO_CACHE && isset($storage[$sql])) {
            if ($params['as_objects'] ?? false) {
                foreach ((array) $storage[$sql] as $k => $v) {
                    $storage[$sql][$k] = (object) $v;
                }
            }
            return $storage[$sql];
        }
        $data = null;
        $q = $this->query($sql);
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
        if ($use_cache && $this->ALLOW_CACHE_QUERIES && ! $this->NO_CACHE && ! isset($storage[$sql])) {
            $storage[$sql] = $data;
            // Permanently turn off queries cache (and free some memory) if case of limit reached
            if ($this->CACHE_QUERIES_LIMIT && count((array) $storage) > $this->CACHE_QUERIES_LIMIT) {
                $this->ALLOW_CACHE_QUERIES = false;
                $storage = null;
            }
        }
        if ($params['as_objects'] ?? false) {
            foreach ((array) $data as $k => $v) {
                $data[$k] = (object) $v;
            }
        }
        return $data;
    }

    /**
     * Execute database query and fetch result as assoc array (for queries that returns only 1 row).
     * @param mixed $sql
     * @param mixed $cache_ttl
     */
    public function query_fetch_cached($sql, $cache_ttl = 600)
    {
        $cache_key = 'SQL_' . __FUNCTION__ . '_' . $this->DB_HOST . '_' . $this->DB_NAME . '_' . abs(crc32($sql));
        $use_cache = true;
        if ($this->NO_CACHE) {
            $use_cache = false;
        }
        $data = [];
        if ($use_cache) {
            $data = cache_get($cache_key);
        }
        if ( ! $data) {
            $data = $this->query_fetch($sql);
            if ($use_cache) {
                cache_set($cache_key, $data);
            }
        }
        return $data;
    }

    /**
     * Alias with core cache.
     * @param mixed $sql
     * @param null|mixed $key_name
     * @param mixed $cache_ttl
     */
    public function query_fetch_all_cached($sql, $key_name = null, $cache_ttl = 600)
    {
        $cache_key = 'SQL_' . __FUNCTION__ . '_' . $this->DB_HOST . '_' . $this->DB_NAME . '_' . abs(crc32($sql));
        $use_cache = true;
        if ($this->NO_CACHE) {
            $use_cache = false;
        }
        $data = [];
        if ($use_cache) {
            $data = cache_get($cache_key);
        }
        if ( ! $data) {
            $data = $this->query_fetch_all($sql, $key_name);
            if ($use_cache) {
                cache_set($cache_key, $data);
            }
        }
        return $data;
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param mixed $cache_ttl
     */
    public function get_cached($sql, $cache_ttl = 600)
    {
        return $this->query_fetch_cached($sql, $cache_ttl);
    }

    /**
     * Alias.
     * @param mixed $sql
     * @param null|mixed $key_name
     * @param mixed $cache_ttl
     */
    public function get_all_cached($sql, $key_name = null, $cache_ttl = 600)
    {
        return $this->query_fetch_all_cached($sql, $key_name, $cache_ttl);
    }

    /**
     * Execute database query and the calculate number of rows.
     * @param mixed $sql
     */
    public function query_num_rows($sql)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        $Q = $this->query($sql);
        $result = $this->db->num_rows($Q);
        $this->free_result($Q);
        return $result;
    }

    /**
     * Function return fetched array with both text and numeric indexes.
     * @param mixed $result
     */
    public function fetch_array($result)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->fetch_array($result);
    }

    /**
     * Function return fetched array with text indexes.
     * @param mixed $result
     */
    public function fetch_assoc($result)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->fetch_assoc($result);
    }

    /**
     * Alias.
     * @param mixed $result
     */
    public function fetch($result)
    {
        return $this->fetch_assoc($result);
    }

    /**
     * Function return fetched array with numeric indexes.
     * @param mixed $result
     */
    public function fetch_row($result)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->fetch_row($result);
    }

    /**
     * Function return fetched object with assoc var names.
     * @param mixed $result
     */
    public function fetch_object($result)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->fetch_object($result);
    }

    /**
     * Function return number of rows in the query.
     * @param mixed $result
     */
    public function num_rows($result)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->num_rows($result);
    }

    /**
     * Transaction wrapper. Examples:
     * db()->transaction(function($db) {
     *	$user = $db->from('user')->first();
     *	$user['verified'] = true;
     *	return $db->update('user', $user);
     * }).
     * @param mixed $callback
     */
    public function transaction($callback)
    {
        if ( ! is_callable($callback)) {
            return false;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        $this->begin();
        $rolled_back = false;
        try {
            $result = $callback($this);
        } catch (Exception $e) {
            $result = false;
            $this->rollback();
            $rolled_back = true;
            throw $e;
        }
        if ($result === false || $rolled_back) {
            ! $rolled_back && $this->rollback();
            return false;
        }
        return $this->commit();
    }

    /**
     * Begin a transaction, or if a transaction has already started, continue it.
     */
    public function begin()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->begin();
    }

    /**
     * End a transaction, or decrement the nest level if transactions are nested.
     */
    public function commit()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->commit();
    }

    /**
     * Rollback a transaction.
     */
    public function rollback()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->rollback();
    }

    /**
     * Return columns info for selected table.
     * @param mixed $table
     */
    public function meta_columns($table)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        $table = $this->_escape_table_name($table);
        if ( ! strlen($table)) {
            return false;
        }
        return $this->utils()->meta_columns($table);
    }

    /**
     * Return tables list for current database.
     */
    public function meta_tables()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->utils()->meta_tables($this->DB_PREFIX);
    }

    /**
     * Free result assosiated with a given query resource.
     * @param mixed $result
     */
    public function free_result($result)
    {
        if ( ! $this->_connected && ! $this->connect() && empty($result)) {
            return false;
        }
        return $this->db->free_result($result);
    }

    /**
     * Return error of the latest executed query.
     * Difference from error() is that it will work correctly when repair enabled
     * (it executes lot of self queries and cleaning db api latest error).
     */
    public function last_error()
    {
        $var = $this->_last_query_error;
        return isset($var['code']) && ! empty($var['code']) ? $var : false;
        // TODO: use this only when repair table enabled and called.
    }

    /**
     * Return database error.
     */
    public function error()
    {
        if ( ! is_object($this->db)) {
            return false;
        }
        return $this->db->error();
    }

    /**
     * Return last insert id.
     */
    public function insert_id()
    {
        if (isset($this->_last_insert_id)) {
            return $this->_last_insert_id;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->insert_id();
    }

    /**
     * Get number of affected rows.
     */
    public function affected_rows()
    {
        if (isset($this->_last_affected_rows)) {
            return $this->_last_affected_rows;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->affected_rows();
    }

    /**
     * Return database-specific limit of returned rows.
     * @param mixed $count
     * @param null|mixed $offset
     */
    public function limit($count, $offset = null)
    {
        if ( ! $this->_connected && ! $this->connect()) {
            $sql = '';
            if ($count > 0) {
                $offset = ($offset > 0) ? $offset : 0;
                $sql = 'LIMIT ' . ($offset ? $offset . ', ' : '') . $count;
            }
            return $sql;
        }
        return $this->db->limit($count, $offset);
    }


    public function get_server_version()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->get_server_version();
    }


    public function get_host_info()
    {
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        return $this->db->get_host_info();
    }

    /**
     * Helper.
     * @param mixed $table
     * @param mixed $where
     * @param mixed $as_sql
     */
    public function delete($table, $where, $as_sql = false)
    {
        $sql = $this->from($table)->delete($where, $_as_sql = true);
        if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS && ! $as_sql) {
            $this->_save_query_revision(__FUNCTION__, $table, ['data' => $sql]);
        }
        return $as_sql ? $sql : $this->query($sql);
    }

    /**
     * @param mixed $table
     * @param mixed $data
     * @param null|mixed $index
     * @param mixed $only_sql
     */
    public function update_batch_safe($table, $data, $index = null, $only_sql = false)
    {
        $data = $this->_fix_data_safe($table, $data);
        return $this->update_batch($table, $this->es($data), $index, $only_sql);
    }

    /**
     * @param mixed $table
     * @param mixed $data
     * @param null|mixed $index
     * @param mixed $only_sql
     */
    public function update_batch($table, $data, $index = null, $only_sql = false)
    {
        if ($this->DB_REPLICATION_SLAVE && ! $only_sql) {
            return false;
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return false;
        }
        if ( ! is_object($this->db)) {
            return false;
        }
        if ( ! $index) {
            $index = 'id';
        }
        if ( ! strlen($table) || ! $data || ! is_array($data) || ! $index) {
            return false;
        }
        if (MAIN_TYPE_ADMIN && $this->QUERY_REVISIONS) {
            $_this = $this;
            $fname = __FUNCTION__;
            $params['slice_callback'] = function ($_data) use ($_this, $fname, $table, $index) {
                $_this->_save_query_revision($fname, $table, ['data' => $_data, 'index' => $index]);
            };
        }
        return $this->query_builder()->update_batch($table, $data, $index, $only_sql, $params);
    }

    /**
     * @param mixed $sql
     */
    public function split_sql($sql)
    {
        return $this->utils()->split_sql($sql);
    }

    /**
     * Query builder shortcut.
     */
    public function select()
    {
        return call_user_func_array([$this->query_builder(), __FUNCTION__], func_get_args());
    }

    /**
     * Query builder shortcut.
     */
    public function from()
    {
        return call_user_func_array([$this->query_builder(), __FUNCTION__], func_get_args());
    }

    /**
     * Query builder shortcut.
     */
    public function table()
    {
        return call_user_func_array([$this->query_builder(), __FUNCTION__], func_get_args());
    }


    public function utils()
    {
        if ( ! isset($this->utils)) {
            $cname = 'db_utils_' . $this->get_driver_family();
            $this->utils = _class($cname, 'classes/db/');
            $this->utils->db = $this;
        }
        return $this->utils;
    }


    public function migrator()
    {
        if ( ! isset($this->migrator)) {
            $cname = 'db_migrator_' . $this->get_driver_family();
            $this->migrator = _class($cname, 'classes/db/');
            $this->migrator->db = $this;
        }
        return $this->migrator;
    }


    public function installer()
    {
        if ( ! isset($this->installer)) {
            $cname = 'db_installer_' . $this->get_driver_family();
            $this->installer = _class($cname, 'classes/db/');
            $this->installer->db = $this;
        }
        return $this->installer;
    }


    public function query_builder()
    {
        $cname = 'db_query_builder_' . $this->get_driver_family();
        $qb = clone _class($cname, 'classes/db/');
        $qb->db = $this;
        return $qb;
    }

    /**
     * ORM shortcut.
     * @param mixed $name
     * @param mixed $params
     */
    public function model($name, $params = [])
    {
        $model = $this->_model_load($name);
        $params && $model->_set_params($params);
        return $model;
    }

    /**
     * Load new model object.
     * @param mixed $name
     */
    public function _model_load($name)
    {
        $main = main();
        $model_class = $name . '_model';
        $custom_storages = &$main->_custom_class_storages;
        $wildcard = '*_model';
        if ( ! isset($$custom_storages[$wildcard]['yf'])) {
            $yf_models_basic_storages = [
                'project_app_path' => [APP_PATH . 'models/'],
                'project_plugins' => [PROJECT_PATH . 'plugins/*/share/models/'],
                'project' => [PROJECT_PATH . 'share/models/'],
                'yf_plugins' => [YF_PATH . 'plugins/*/share/models/'],
                'yf' => [YF_PATH . 'share/models/'],
            ];
            foreach ($yf_models_basic_storages as $k => $v) {
                if ( ! isset($custom_storages[$wildcard][$k])) {
                    $custom_storages[$wildcard][$k] = $v;
                }
            }
        }
        $obj = _class_safe($model_class);
        // Special case to use preloaded models, try to load class name without postfix *_model
        if ( ! is_object($obj) || ! ($obj instanceof yf_model)) {
            $obj = _class_safe($name);
        }
        if ( ! is_object($obj) || ! ($obj instanceof yf_model)) {
            throw new Exception('Not able to load model: ' . $name);
            return false;
        }
        $model_obj = clone $obj;
        $model_obj->set_db_object($this);
        return $model_obj;
    }

    /**
     * Add query to shutdown array.
     * @param mixed $sql
     */
    public function _add_shutdown_query($sql = '')
    {
        if (empty($sql) || strlen($sql) < 5) {
            return false;
        }
        // If shutdown execution is disabled - then execute this query immediatelly
        if ( ! $this->USE_SHUTDOWN_QUERIES) {
            return $this->query($sql);
        }
        // Add query to the array
        $this->_SHUTDOWN_QUERIES[] = $sql;

        return true;
    }

    /**
     * Execute shutdown queries.
     */
    public function _execute_shutdown_queries()
    {
        if ( ! $this->USE_SHUTDOWN_QUERIES || isset($this->_shutdown_executed)) {
            return false;
        }
        foreach ((array) $this->_SHUTDOWN_QUERIES as $sql) {
            if (is_string($sql) && strlen($sql) > 5) {
                $this->query($sql);
            }
        }
        // Prevent executing this method more than once
        $this->_shutdown_executed = true;
    }

    /**
     * Create unique temporary table name.
     */
    public function _get_unique_tmp_table_name()
    {
        return $this->DB_PREFIX . 'tmp__' . substr(abs(crc32(rand() . microtime(true))), 0, 8);
    }

    /**
     * Do Log.
     */
    public function _log_queries()
    {
        // Restore startup working directory
        @chdir(main()->_CWD);

        if ( ! isset($this->_queries_logged)) {
            $this->_queries_logged = true;
        } else {
            return false;
        }
        _class_safe('logs')->store_db_queries_log();
    }

    /**
     * Get reconnect lock file name.
     */
    public function _get_reconnect_lock_path()
    {
        $pairs = [
            '[DB_HOST]' => $this->DB_HOST,
            '[DB_NAME]' => $this->DB_NAME,
            '[DB_USER]' => $this->DB_USER,
            '[DB_PORT]' => $this->DB_PORT,
        ];
        return STORAGE_PATH . str_replace(array_keys($pairs), array_values($pairs), $this->RECONNECT_LOCK_FILE_NAME);
    }

    /**
     * @param mixed $table
     * @param mixed $no_cache
     */
    public function get_table_columns_cached($table, $no_cache = false)
    {
        $cache_name = __FUNCTION__ . '|' . $table . '|' . $this->DB_HOST . '|' . $this->DB_PORT . '|' . $this->DB_NAME . '|' . $this->DB_PREFIX;
        if ($this->NO_CACHE) {
            $no_cache = true;
        }
        $data = [];
        if ( ! $no_cache) {
            $data = cache()->get($cache_name);
        }
        if ( ! $data) {
            $data = $this->meta_columns($table);
            if ( ! $no_cache) {
                cache()->set($cache_name, $data);
            }
        }
        return $data;
    }

    /**
     * @param mixed $table
     * @param mixed $data
     * @param mixed $extra
     */
    public function _fix_data_safe($table, $data = [], $extra = [])
    {
        if ( ! $this->FIX_DATA_SAFE || main()->is_unit_test()) {
            return $data;
        }
        $cols = $this->get_table_columns_cached($table, $extra['no_cache']);
        if ( ! $cols) {
            $msg = __CLASS__ . '->' . __FUNCTION__ . ': columns for table ' . $table . ' is empty, truncating data array';
            if ( ! $extra['silent'] && ! $this->FIX_DATA_SAFE_SILENT) {
                trigger_error($msg, E_USER_WARNING);
            }
            return false;
        }
        $is_data_3d = false;
        // Try to check if array is two-dimensional
        foreach ((array) $data as $cur_row) {
            $is_data_3d = is_array($cur_row) ? 1 : 0;
            break;
        }
        $not_existing_cols = [];
        $virtual_cols = [];
        $fixed_nulls = [];
        if ($is_data_3d) {
            foreach ((array) $data as $k => $_data) {
                foreach ((array) $_data as $name => $v) {
                    if ( ! isset($cols[$name])) {
                        $not_existing_cols[$name] = $name;
                        unset($data[$k][$name]);
                    } elseif (isset($cols[$name]['virtual']) && $cols[$name]['virtual']) {
                        $virtual_cols[$name] = $name;
                        unset($data[$k][$name]);
                    } elseif (($v === null || $v === 'NULL') && ! $cols[$name]['nullable']) {
                        $fixed_nulls[$name] = $name;
                        unset($data[$k][$name]);
                    }
                }
            }
        } else {
            foreach ((array) $data as $name => $v) {
                if ( ! isset($cols[$name])) {
                    $not_existing_cols[$name] = $name;
                    unset($data[$name]);
                } elseif (isset($cols[$name]['virtual']) && $cols[$name]['virtual']) {
                    $virtual_cols[$name] = $name;
                    unset($data[$name]);
                } elseif (($v === null || $v === 'NULL') && ! $cols[$name]['nullable']) {
                    $fixed_nulls[$name] = $name;
                    unset($data[$name]);
                }
            }
        }
        if ( ! $extra['silent'] && ! $this->FIX_DATA_SAFE_SILENT) {
            if ($not_existing_cols) {
                trigger_error(__CLASS__ . '->' . __FUNCTION__ . ': not existing columns for table "' . $table . '", columns: ' . implode(', ', $not_existing_cols), E_USER_NOTICE);
            }
            if ($virtual_cols) {
                trigger_error(__CLASS__ . '->' . __FUNCTION__ . ': removed virtual columns for table "' . $table . '", columns: ' . implode(', ', $virtual_cols), E_USER_NOTICE);
            }
            if ($fixed_nulls) {
                trigger_error(__CLASS__ . '->' . __FUNCTION__ . ': fixed nulls for table "' . $table . '", columns: ' . implode(', ', $fixed_nulls), E_USER_NOTICE);
            }
        }
        return $data;
    }

    /**
     * Get real table name from its short variant.
     * @param mixed $name
     */
    public function _real_name($name)
    {
        $name = trim($name);
        if ( ! strlen($name)) {
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
        if (isset($this->_found_tables[$name])) {
            return $this->_found_tables[$name];
        }
        $name = (in_array($name, $this->_need_sys_prefix) ? 'sys_' : '') . $name;
        $plen = strlen($this->DB_PREFIX);
        if ($plen && substr($name, 0, $plen) !== $this->DB_PREFIX) {
            return ($db ? $db . '.' : '') . $this->DB_PREFIX . $name;
        }
        return ($db ? $db . '.' : '') . $name;
    }

    /**
     * Try to fix table name.
     * @param mixed $name
     */
    public function _fix_table_name($name = '')
    {
        $name = trim($name);
        if ( ! strlen($name)) {
            return false;
        }
        $db = '';
        $table = '';
        if (strpos($name, '.') !== false) {
            list($db, $name) = explode('.', $name);
            $db = trim($db);
            $name = trim($name);
        }
        if ( ! strlen($name)) {
            return '';
        }
        if (substr($name, 0, strlen('dbt_')) == 'dbt_') {
            $name = substr($name, strlen('dbt_'));
        }
        $name_wo_db_prefix = $name;
        $plen = strlen($this->DB_PREFIX);
        if ($plen && substr($name, 0, $plen) === $this->DB_PREFIX) {
            $name_wo_db_prefix = substr($name, $plen);
        }
        return ($db ? $db . '.' : '') . $this->DB_PREFIX . (in_array($name_wo_db_prefix, $this->_need_sys_prefix) ? 'sys_' : '') . $name_wo_db_prefix;
    }

    /**
     * Trying to repair given table structure (and possibly data).
     * @param mixed $sql
     * @param mixed $db_error
     */
    public function _repair_table($sql, $db_error)
    {
        if (empty($db_error) || ! $this->ERROR_AUTO_REPAIR) {
            return false;
        }
        $driver_family = $this->get_driver_family();
        $code = $db_error['code'];
        if ($driver_family === 'mysql' && ! in_array($code, [
            1191, // Can't find FULLTEXT index matching the column list
            2013, // Lost connection to MySQL server during query
            1205, // Lock wait timeout expired. Transaction was rolled back (InnoDB)
            1213, // Transaction deadlock. You should rerun the transaction. (InnoDB)
            1146, // Table %s doesn't exist
            1054, // Unknown column %s
        ])) {
            return false;
        }
        return _class('db_installer_' . $driver_family, 'classes/db/')->repair($sql, $db_error, $this);
    }

    /**
     * @param mixed $method
     * @param mixed $table
     * @param mixed $params
     */
    public function _save_query_revision($method, $table, $params = [])
    {
        if (($allowed_methods = $this->QUERY_REVISIONS_METHODS)) {
            if ( ! in_array($method, $allowed_methods)) {
                return false;
            }
        }
        if (($allowed_tables = $this->QUERY_REVISIONS_TABLES)) {
            if ( ! in_array($table, $allowed_tables)) {
                return false;
            }
        }
        $to_insert = [
            'date' => date('Y-m-d H:i:s'),
            'data_new' => is_array($params['data']) ? 'json:' . json_encode($params['data']) : (string) $params['data'],
            'data_old' => is_array($params['data_old']) ? 'json:' . json_encode($params['data_old']) : (string) $params['data_old'],
            'data_diff' => is_array($params['data_diff']) ? 'json:' . json_encode($params['data_diff']) : (string) $params['data_diff'],
            'user_id' => main()->ADMIN_ID,
            'user_group' => main()->ADMIN_GROUP,
            'site_id' => conf('SITE_ID'),
            'server_id' => conf('SERVER_ID'),
            'ip' => common()->get_ip(),
            'query_method' => $method,
            'query_table' => $table,
            'extra' => json_encode([
                'get_object' => $_GET['object'],
                'get_action' => $_GET['action'],
                'get_id' => $_GET['id'],
                'get_page' => $_GET['page'],
                'trace' => array_slice(explode(PHP_EOL, main()->trace_string()), 1, 5),
            ]),
            'url' => (main()->is_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        ];
        $sql = $this->insert_safe('sys_db_revisions', $to_insert, $only_sql = true);
        $this->_add_shutdown_query($sql);
    }

    /**
     * Simple trace without dumping whole objects.
     */
    public function _trace()
    {
        $trace = [];
        foreach (debug_backtrace() as $k => $v) {
            if ( ! $k) {
                continue;
            }
            $v['object'] = isset($v['object']) && is_object($v['object']) ? get_class($v['object']) : null;
            $trace[$k - 1] = $v;
        }
        return $trace;
    }

    /**
     * Print nice.
     */
    public function _trace_string()
    {
        $e = new Exception();
        return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), 1, -1));
    }

    /**
     * Special init for the debug info items.
     */
    public function _set_debug_items()
    {
        if ( ! $this->INSTRUMENT_QUERIES) {
            return false;
        }
        $cpu_usage = function_exists('getrusage') ? getrusage() : [];
        $ip = common()->get_ip();
        $this->_instrument_items = [
            'memory_usage' => function_exists('memory_get_usage') ? memory_get_usage() : '',
            'cpu_user' => $cpu_usage['ru_utime.tv_sec'] * 1e6 + $cpu_usage['ru_utime.tv_usec'],
            'cpu_system' => $cpu_usage['ru_stime.tv_sec'] * 1e6 + $cpu_usage['ru_stime.tv_usec'],
            'get_object' => $_GET['object'],
            'get_action' => $_GET['action'],
            'get_id' => $_GET['id'],
            'get_page' => $_GET['page'],
            'user_id' => $_SESSION['user_id'],
            'user_group' => $_SESSION['user_group'],
            'session_id' => session_id(),
            'request_id' => md5($_SERVER['REMOTE_PORT'] . $ip . $_SERVER['REQUEST_URI'] . microtime(true)),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_host' => $_SERVER['HTTP_HOST'],
            'ip' => $ip,
        ];
        return true;
    }

    /**
     * Get debug item value.
     * @param mixed $name
     */
    public function _get_debug_item($name = '')
    {
        if ( ! $this->INSTRUMENT_QUERIES) {
            return '';
        }
        return $this->_instrument_items[$name];
    }

    /**
     * Add instrumentation info to the query for highload SQL debug and profile.
     * @param mixed $sql
     * @param mixed $keys
     */
    public function _instrument_query($sql = '', $keys = ['request_id', 'session_id', 'SESSION_user_id', 'GET_object', 'GET_action'])
    {
        $header = '';
        if ( ! $sql) {
            return '';
        }
        $trace = trace($skip_before = 3, $skip_after = 2);
        $trace = str_replace(["\t", "\n", "\0"], '', $trace);
        $header = '-- ' . $trace . "\t";
        foreach ((array) $keys as $x => $key) {
            $val = $this->_get_debug_item($key);
            if ( ! $val) {
                continue;
            }
            $val = str_replace(["\t", "\n", "\0"], '', $val);
            // all other chars are safe in comments
            $key = strtolower(str_replace([': ', "\t", "\n", "\0"], '', $key));
            // Add the requested instrumentation keys
            $header .= "\t" . $key . ': ' . $this->es($val);
        }
        return $header . PHP_EOL . $sql;
    }

    /**
     * 'Silent' mode (logging off, tracing off, debugging off).
     */
    public function enable_silent_mode()
    {
        $this->ALLOW_CACHE_QUERIES = false;
        $this->GATHER_AFFECTED_ROWS = false;
        $this->USE_SHUTDOWN_QUERIES = false;
        $this->LOG_ALL_QUERIES = false;
        $this->LOG_SLOW_QUERIES = false;
        $this->USE_QUERY_BACKTRACE = false;
        $this->ERROR_BACKTRACE = false;
        $this->LOGGED_QUERIES_LIMIT = 1;
    }

    /**
     * Function escapes characters for using in query.
     * @param mixed $string
     */
    public function es($string)
    {
        if ($string === null || $string === 'NULL') {
            return 'NULL';
        }
        if ( ! $this->_connected && ! $this->connect()) {
            return $this->_mysql_escape_mimic($string);
        }
        // Helper method for passing here whole arrays as param
        if (is_array($string)) {
            foreach ((array) $string as $k => $v) {
                if ($v === null || $v === 'NULL') {
                    $string[$k] = 'NULL';
                } else {
                    $string[$k] = $this->real_escape_string($v);
                }
            }
            return $string;
        }
        return $this->db->real_escape_string($string);
    }

    /**
     * Alias.
     * @param mixed $string
     */
    public function real_escape_string($string)
    {
        return $this->es($string);
    }

    /**
     * Alias.
     * @param mixed $string
     */
    public function escape_string($string)
    {
        return $this->es($string);
    }

    /**
     * Alias.
     * @param mixed $string
     */
    public function escape($string)
    {
        return $this->es($string);
    }

    /**
     * @param mixed $data
     */
    public function escape_key($data)
    {
        if (is_array($data)) {
            $func = __FUNCTION__;
            foreach ((array) $data as $k => $v) {
                $data[$k] = $this->$func($v);
            }
            return $data;
        }
        if ( ! is_object($this->db)) {
            return '`' . trim($data, '`') . '`';
        }
        return $this->db->escape_key($data);
    }

    /**
     * @param mixed $data
     */
    public function escape_val($data)
    {
        if ($data === null || $data === 'NULL') {
            return 'NULL';
        } elseif (is_array($data)) {
            $func = __FUNCTION__;
            foreach ((array) $data as $k => $v) {
                $data[$k] = $this->$func($v);
            }
            return $data;
        }
        if ( ! is_object($this->db)) {
            return '\'' . $data . '\'';
        }
        return $this->db->escape_val($data);
    }

    /**
     * @param mixed $name
     */
    public function _escape_table_name($name = '')
    {
        $name = trim($name);
        if ( ! strlen($name)) {
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
        if ( ! strlen($table)) {
            return false;
        }
        $table = $this->_fix_table_name($table);
        return (strlen($db) ? $this->escape_key($db) . '.' : '') . $this->escape_key($table);
    }

    /**
     * @param mixed $string
     */
    public function _mysql_escape_mimic($string)
    {
        if (is_array($string)) {
            return array_map([$this, __FUNCTION__], $string);
        }
        if ($string === null || $string === 'NULL') {
            return 'NULL';
        } elseif (is_float($string)) {
            return str_replace(',', '.', $string);
        } elseif (is_int($string)) {
            return $string;
        } elseif (is_bool($string)) {
            return (int) $string;
        }
        return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $string);
    }
}
