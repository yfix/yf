<?php

/**
* Logging some common actions
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_logs {

	/** @var bool Turn logging on/off */
	public $_LOGGING					= false;
	/** @var bool Store user auth into log table */
	public $STORE_USER_AUTH			= false;
	/** @var bool Update user's record for last login */
	public $UPDATE_LAST_LOGIN			= false;
	/** @var bool Store user auth into log table */
	public $STORE_ADMIN_AUTH			= true;
	/** @var bool Update admin's record for last login */
	public $UPDATE_ADMIN_LAST_LOGIN	= true;
	/** @var int @conf_skip Current log level limit ("0" for disabling), could be: 0|E_ERROR|E_WARNING|E_NOTICE */
	public $CUR_LOG_LEVEL				= E_NOTICE;
	/** @var array @conf_skip Error levels text representation */
	public $_error_levels_names = array(
		E_ERROR		=> "error",
		E_WARNING	=> "warning",
		E_NOTICE	=> "notice",
	);
	/** @var bool Turn logging of user actions for the stats on/off */
	public $LOG_USER_ACTIONS	= true;
	/** @var array @conf_skip Available action names*/
	public $_avail_action_names = array(
		"visit",
		"review",
		"add_comment",
		"del_comment",
		"add_friend",
		"del_friend",
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Constructor
	*/
	function _init() {
		$site_logging = conf('site_logging');
		if (isset($site_logging)) {
			$this->_LOGGING = $site_logging;
		}
	}

	/**
	* Store user authentication in log table
	*/
	function store_user_auth($A = array()) {
		// Check if looging needed
		if (!is_array($A) || !$this->_LOGGING) {
			return false;
		}
		// Update user last login
		$this->_update_last_login($A);
		// Prepare db record
		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER["REMOTE_ADDR"];
		}
		if ($this->STORE_USER_AUTH) {
			db()->INSERT("log_auth", array(
				"user_id"	=> intval($A['id']),
				"login"		=> _es($A['login']),
				"group"		=> intval($A['group']),
				"date"		=> time(),
				"session_id"=> session_id(),
				"ip"		=> $IP,
				"user_agent"=> _es(getenv("HTTP_USER_AGENT")),
				"referer"	=> _es(getenv("HTTP_REFERER")),
			));
			conf('_log_auth_insert_id', db()->INSERT_ID());
		}
	}

	/**
	* Store user authentication in log table
	*/
	function _update_last_login($A = array()) {
		// Check if looging needed
		if (!is_array($A) || !$this->_LOGGING || !$this->UPDATE_LAST_LOGIN) {
			return false;
		}
		// Prepare db record
		$sql = "UPDATE ".db('user')." SET 
				last_login	= ".time().", 
				num_logins	= num_logins + 1
			WHERE id=".intval($A["id"]);
		db()->_add_shutdown_query($sql);
	}

	/**
	* Store admin authentication in log table
	*/
	function store_admin_auth($A = array()) {
		// Check if looging needed
		if (!is_array($A) || !$this->_LOGGING) {
			return false;
		}
		if ($this->UPDATE_ADMIN_LAST_LOGIN) {
			db()->query(
				"UPDATE ".db('admin')." SET 
					last_login	= ".time().", 
					num_logins	= num_logins + 1
				WHERE id=".intval($A["id"])
			);
		}
		// Prepare db record
		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER["REMOTE_ADDR"];
		}
		if ($this->STORE_ADMIN_AUTH) {
			db()->INSERT("log_admin_auth", array(
				"admin_id"	=> intval($A['id']),
				"login"		=> _es($A['login']),
				"group"		=> intval($A['group']),
				"date"		=> time(),
				"session_id"=> session_id(),
				"ip"		=> $IP,
				"user_agent"=> _es(getenv("HTTP_USER_AGENT")),
				"referer"	=> _es(getenv("HTTP_REFERER")),
			));
			conf('_log_admin_auth_insert_id', db()->INSERT_ID());
		}
	}

	/**
	* Save debug log
	*/
	function _save_debug_log($text = "", $log_level = E_NOTICE, $trace = array(), $simple = false) {
		if (empty($log_level) || !isset($this->_error_levels_names[$log_level])) {
			$log_level = E_NOTICE;
		}
		if (empty($this->CUR_LOG_LEVEL) || $log_level > $this->CUR_LOG_LEVEL) {
			return false;
		}
		$LOGS_DIR = INCLUDE_PATH."logs/";
		_mkdir_m($LOGS_DIR);

		$log_data = "";
		$log_data .= !$simple ? date("Y-m-d H:i:s")." [".$this->_error_levels_names[$log_level]."] " : "";
		$log_data .= $text;
//		$log_data .= !$simple ? "  (".$trace["file"]." on line ".$trace["line"].")" : "";
		$log_data .= !$simple ? " | ".str_replace("\n", " ", main()->trace_string()) : "";
		$log_data .= "\n";

		file_put_contents($LOGS_DIR."debug_logs.log", $log_data, FILE_APPEND);
	}

	/**
	* Store slow pages (even when DEBUG_MODE is turned off)
	*/
	function store_slow_pages() {
// TODO
/*
		if (empty($this->STORE_SLOW_PAGES)) {
			return false;
		}
		// Prepare logs dir
		$LOGS_DIR = INCLUDE_PATH."logs/";
		_mkdir_m($DEBUG_LOGS_DIR);
		// Prepare log data
		$log_data = date("Y-m-d H:i:s")." [".$this->_error_levels_names[$log_level]."] ".$text."  (".$trace["file"]." on line ".$trace["line"].")\r\n";
		// Save info to file
		if ($fh = @fopen($LOGS_DIR."debug_logs.log", "a")) {
			@fwrite($fh, $log_data);
			@fclose($fh);
		}
*/
	}

	/**
	* Save queries log
	*/
	function store_db_queries_log () {
		if (empty(db()->QUERY_LOG)) {
			return false;
		}
		// Prepare logs dir
		$logs_dir = INCLUDE_PATH."logs/";
		_mkdir_m($logs_dir);
		// Prepare header
		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER["REMOTE_ADDR"];
		}
		$log_header = 
			"## ".date("Y-m-d H:i:s")."; "
			."SITE_ID: ".conf('SITE_ID')."; "
			."IP = ".$IP."; "
			."QUERY_STRING = ".WEB_PATH."?".$_SERVER['QUERY_STRING']."; "
			.(!empty($_SERVER['REQUEST_URI']) ? "URL: http://".$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']."; " : "")
			.(!empty($_SERVER['HTTP_REFERER']) ? "REFERER = ".$_SERVER['HTTP_REFERER']."; " : "")
			."##\r\n";
		// All queries
		if (db()->LOG_ALL_QUERIES && !empty(db()->FILE_NAME_LOG_ALL)) {
			$c = 0;
			$h = fopen($logs_dir.db()->FILE_NAME_LOG_ALL, "a");
			fwrite($h, $log_header);
			foreach ((array)db()->QUERY_LOG as $id => $text) {
				if (substr($text, 0, strlen("EXPLAIN")) == "EXPLAIN" || substr($text, 0, strlen("SHOW SESSION STATUS")) == "SHOW SESSION STATUS") {
					continue;
				}
				$log_entry = 
					++$c.") "
					.common()->_format_time_value(db()->QUERY_EXEC_TIME[$id]).";\t"
					.$text."; "
					.(isset(db()->QUERY_AFFECTED_ROWS[$text]) ? " # affected_rows: ".intval(db()->QUERY_AFFECTED_ROWS[$text]).";" : "")
					."\r\n";
				fwrite($h, $log_entry);
			}
			fwrite($h, "####\r\n");
			fclose($h);
		}
		// Slow queries
		if (db()->LOG_SLOW_QUERIES && !empty(db()->FILE_NAME_LOG_SLOW)) {
			$c = 0;
			foreach ((array)db()->QUERY_LOG as $id => $text) {
				if (db()->QUERY_EXEC_TIME[$id] < (float)db()->SLOW_QUERIES_TIME_LIMIT) {
					continue;
				}
				// Get explain info about queries
				$_explain_result = array();
				if (substr(db()->DB_TYPE, 0, 5) == "mysql" && preg_match("/^[\(]*select/ims", $text)) {
					$_explain_result = db()->query_fetch_all("EXPLAIN ".$text);
				}
				$_cur_trace		= db()->QUERY_BACKTRACE_LOG[$id];
				$add_text = ""
					.(isset(db()->QUERY_AFFECTED_ROWS[$text]) ? " # affected_rows: ".intval(db()->QUERY_AFFECTED_ROWS[$text])."; " : "")
					.(!empty($_cur_trace) ? "# ".$_cur_trace["file"]." on line ".$_cur_trace["line"]." (db->".$_cur_trace["function"].(!empty($_cur_trace["inside_method"]) ? " inside ".$_cur_trace["inside_method"] : "")."; " : "")
					.(!empty($_explain_result) ? $this->_format_db_explain_result($_explain_result) : "");
				$slow_queries[] = 
					++$c.") "
					.common()->_format_time_value(db()->QUERY_EXEC_TIME[$id]).";\t"
					.$text."; "
					.($add_text ? "\r\n".$add_text : "")
					."\r\n";
			}
			if (!empty($slow_queries)) {
				$h = fopen($logs_dir.db()->FILE_NAME_LOG_SLOW, "a");
				fwrite($h, $log_header);
				foreach ((array)$slow_queries as $text) {
					fwrite($h, $text);
				}
				fwrite($h, "####\r\n");
				fclose($h);
			}
		}
	}

	/**
	* Format result returned by db query "EXPLAIN ..."
	* 
	* @access	private
	* @return	string
	*/
	function _format_db_explain_result($explain_result = array()) {
		if (empty($explain_result)) {
			return false;
		}
		// Get max lengths for all rows
		foreach ((array)$explain_result as $_num => $_data) {
			foreach ((array)$_data as $k => $v) {
				if (strlen($v) > $max_row_lengths[$k]) {
					$max_row_lengths[$k] = strlen($v);
				}
				if (strlen($k) > $max_row_lengths[$k]) {
					$max_row_lengths[$k] = strlen($k);
				}
			}
		}
		$body .= "\r\n";
		// Header
		$body .= "|";
		foreach ((array)$explain_result[0] as $k => $v) {
			$body .= $k. str_repeat(" ", $max_row_lengths[$k] - strlen($k) + 1)."|";
		}
		$body .= "\r\n";
		$body .= "|".str_repeat("-", array_sum($max_row_lengths) + count($max_row_lengths) * 2 - 1)."|\r\n";
		// Data
		foreach ((array)$explain_result as $_num => $_data) {
			$body .= "|";
			foreach ((array)$_data as $k => $v) {
				$body .= $v. str_repeat(" ", $max_row_lengths[$k] - strlen($v) + 1)."|";
			}
			$body .= "\r\n";
		}
		// Cut last NL
		if (substr($body, -2) == "\r\n") {
			$body = substr($body, 0, -2);
		}
		return $body;
	}

	/**
	* Log user actions to the DB for creating statistics reports
	*/
	function _log_user_action($action_name, $owner_id, $object_name = "", $object_id = 0) {
		if (!$this->LOG_USER_ACTIONS || !in_array($action_name, $this->_avail_action_names)) {
			return false;
		}

		// In a case with friends owner_id should be a person that added a friend, member_id - a person who add

		// Create record in DB
		$sql_array = array(
			"owner_id"		=> intval($owner_id),
			"action_name"	=> _es($action_name),
			"member_id"		=> $this->USER_ID,
			"object_name"	=> _es($object_name),
			"object_id"		=> intval($object_id),
			"add_date"		=> time(),
		);
		db()->INSERT("log_user_action", $sql_array);
		return true;			
	}
}
