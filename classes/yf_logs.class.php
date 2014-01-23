<?php

/**
* Logging some common actions
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_logs {

// TODO: connect all logs drivers from classes/logs/

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
	/** @var int @conf_skip Current log level limit ('0' for disabling), could be: 0|E_ERROR|E_WARNING|E_NOTICE */
	public $CUR_LOG_LEVEL				= E_NOTICE;
	/** @var array @conf_skip Error levels text representation */
	public $_error_levels_names = array(
		E_ERROR		=> 'error',
		E_WARNING	=> 'warning',
		E_NOTICE	=> 'notice',
	);
	/** @var bool Turn logging of user actions for the stats on/off */
	public $LOG_USER_ACTIONS	= true;
	/** @var array @conf_skip Available action names*/
	public $_avail_action_names = array(
		'visit',
		'review',
		'add_comment',
		'del_comment',
		'add_friend',
		'del_friend',
	);
	/** @var bool Only if main()->LOG_EXEC enabled */
	public $LOG_EXEC_USER	= false;
	/** @var bool Only if main()->LOG_EXEC enabled */
	public $LOG_EXEC_ADMIN	= true;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
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
			$IP = $_SERVER['REMOTE_ADDR'];
		}
		if ($this->STORE_USER_AUTH) {
			db()->INSERT('log_auth', array(
				'user_id'	=> intval($A['id']),
				'login'		=> _es($A['login']),
				'group'		=> intval($A['group']),
				'date'		=> time(),
				'session_id'=> session_id(),
				'ip'		=> $IP,
				'user_agent'=> _es(getenv('HTTP_USER_AGENT')),
				'referer'	=> _es(getenv('HTTP_REFERER')),
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
		$sql = 'UPDATE '.db('user').' SET 
				last_login	= '.time().', 
				num_logins	= num_logins + 1
			WHERE id='.intval($A['id']);
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
				'UPDATE '.db('admin').' SET 
					last_login	= '.time().', 
					num_logins	= num_logins + 1
				WHERE id='.intval($A['id'])
			);
		}
		// Prepare db record
		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER['REMOTE_ADDR'];
		}
		if ($this->STORE_ADMIN_AUTH) {
			db()->INSERT('log_admin_auth', array(
				'admin_id'	=> intval($A['id']),
				'login'		=> _es($A['login']),
				'group'		=> intval($A['group']),
				'date'		=> time(),
				'session_id'=> session_id(),
				'ip'		=> $IP,
				'user_agent'=> _es(getenv('HTTP_USER_AGENT')),
				'referer'	=> _es(getenv('HTTP_REFERER')),
			));
			conf('_log_admin_auth_insert_id', db()->INSERT_ID());
		}
	}

	/**
	* Save debug log
	*/
	function _save_debug_log($text = '', $log_level = E_NOTICE, $trace = array(), $simple = false) {
		if (empty($log_level) || !isset($this->_error_levels_names[$log_level])) {
			$log_level = E_NOTICE;
		}
		if (empty($this->CUR_LOG_LEVEL) || $log_level > $this->CUR_LOG_LEVEL) {
			return false;
		}
		$LOGS_DIR = INCLUDE_PATH.'logs/';
		_mkdir_m($LOGS_DIR);

		$log_data = '';
		$log_data .= !$simple ? date('Y-m-d H:i:s').' ['.$this->_error_levels_names[$log_level].'] ' : '';
		$log_data .= $text;
//		$log_data .= !$simple ? '  ('.$trace['file'].' on line '.$trace['line'].')' : '';
		$log_data .= !$simple ? ' | '.str_replace("\n", ' ', main()->trace_string()) : '';
		$log_data .= "\n";

		file_put_contents($LOGS_DIR.'debug_logs.log', $log_data, FILE_APPEND);
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
		$LOGS_DIR = INCLUDE_PATH.'logs/';
		_mkdir_m($DEBUG_LOGS_DIR);
		// Prepare log data
		$log_data = date('Y-m-d H:i:s').' ['.$this->_error_levels_names[$log_level].'] '.$text.'  ('.$trace['file'].' on line '.$trace['line'].")\r\n";
		// Save info to file
		if ($fh = @fopen($LOGS_DIR.'debug_logs.log', 'a')) {
			@fwrite($fh, $log_data);
			@fclose($fh);
		}
*/
	}

	/**
	* Save queries log
	*/
	function store_db_queries_log () {
		return _class('logs_db_queries', 'classes/logs/')->go();
	}

	/**
	* Log user actions to the DB for creating statistics reports
	*/
	function _log_user_action($action_name, $owner_id, $object_name = '', $object_id = 0) {
		if (!$this->LOG_USER_ACTIONS || !in_array($action_name, $this->_avail_action_names)) {
			return false;
		}
		db()->insert_safe('log_user_action', array(
			'owner_id'		=> intval($owner_id),
			'action_name'	=> $action_name,
			'member_id'		=> main()->USER_ID,
			'object_name'	=> $object_name,
			'object_id'		=> intval($object_id),
			'add_date'		=> time(),
		));
		return true;			
	}

	/**
	* Log script execution params
	*/
	function log_exec () {
		if (MAIN_TYPE_ADMIN && $this->LOG_EXEC_ADMIN) {
			return _class('logs_exec_admin', 'classes/logs/')->go();
		} elseif (MAIN_TYPE_USER && $this->LOG_EXEC_USER) {
			return _class('logs_exec_user', 'classes/logs/')->go();
		}
	}
}
