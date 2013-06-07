<?php

/**
* Custom error handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_errors {

	/** @var bool Log errors to the error file? */
	var $LOG_ERRORS_TO_FILE		= false;
	/** @var bool Log warnings to the error file? */
	var $LOG_WARNINGS_TO_FILE	= false;
	/** @var bool Log notices to the error file? */
	var $LOG_NOTICES_TO_FILE	= false;
	/** @var bool Send errors via email? */
	var $SEND_ERRORS_TO_MAIL	= false;
	/** @var bool Send warnings via email? */
	var $SEND_WARNINGS_TO_MAIL	= false;
	/** @var bool Send notices via email? */
	var $SEND_NOTICES_TO_MAIL	= false;
	/** @var int Error reporting level */
	var $ERROR_REPORTING		= 0;
	/** @var string 
	* The filename of the log file. 
	* NOTE: $error_log_filename will only be used if you have log_errors Off and ;error_log filename in php.ini 
	* if log_errors is On, and error_log is set, the filename in error_log will be used. 
	*/ 
	var $error_log_filename		= "error_logs.log";
	/** @var string The recipient email to mail errors to */
	var $email_to				= "";
	/** @var string Recipient address */
	var $_email_addr_to			= "";
	/** @var string Recipient name */
	var $_email_name_to			= "";
	/** @var string @conf_skip Holds the total error report to be used by mail_error() */
	var $mail_buffer			= "";
	/** @var bool Show start and end log headers or not */
	var $_SHOW_BORDERS			= false;
	/** @var bool @conf_skip Started log output or not */
	var $_LOG_STARTED			= false;
	/** @var bool Log error messages into database */
	var $LOG_INTO_DB			= false;
	/** @var bool Log into these data: $_GET, $_POST */
	var $DB_LOG_ENV				= true;
	/** @var Use compact format */
	var $USE_COMPACT_FORMAT		= true;
	/** @var string Could be any sequence from GPFCS */
	var $ENV_ARRAYS				= "GPF";
	/** @var bool Quickly turn off notices */
	var $NO_NOTICES				= true;
	/** @var array @conf_skip Standard error types */
	var $error_types = array(
		1		=> "E_ERROR",
		2		=> "E_WARNING",
		4		=> "E_PARSE",
		8		=> "E_NOTICE",
		16		=> "E_CORE_ERROR",
		32		=> "E_CORE_WARNING",
		64		=> "E_COMPILE_ERROR",
		128		=> "E_COMPILE_WARNING",
		256		=> "E_USER_ERROR",
		512		=> "E_USER_WARNING",
		1024	=> "E_USER_NOTICE",
		2047	=> "E_ALL",
		2048	=> "E_STRICT",
		4096	=> "E_RECOVERABLE_ERROR",
		8192	=> "E_DEPRECATED",
		16384	=> "E_USER_DEPRECATED",
	);

	/**
	* Constructor
	*/
	function __construct () {
		if (defined("ERROR_REPORTING")) {
			conf('ERROR_REPORTING', (int)ERROR_REPORTING);
		}
		if (conf('ERROR_REPORTING')) {
			error_reporting((int)conf('ERROR_REPORTING'));
		}
		$this->set_mail_receiver('yf_framework_site_admin', defined('SITE_ADMIN_EMAIL') ? SITE_ADMIN_EMAIL : 'php_test@127.0.0.1');
		$this->set_log_file_name(defined('ERROR_LOGS_FILE') ? ERROR_LOGS_FILE : INCLUDE_PATH. 'error_logs.log');
		$this->set_flags(defined('ERROR_HANDLER_FLAGS') ? ERROR_HANDLER_FLAGS : "110000");
		$this->set_reporting_level();
		ini_set("ignore_repeated_errors", 1);
		ini_set("ignore_repeated_source", 1);
		set_error_handler(array($this, 'ERROR_HANDLER'), $this->NO_NOTICES ? E_ALL ^ E_NOTICE : E_ALL);
		register_shutdown_function(array($this, 'error_handler_destructor'));
		
		set_exception_handler(array($this,  'exception_handler' ));
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Destructor
	*/
	function error_handler_destructor() {
		// Restore startup working directory
		@chdir(main()->_CWD);
		// Send the email if needed
		if (strlen($this->mail_buffer)) {
			common()->send_mail("", "error_handler", $this->_email_addr_to, $this->_email_name_to, 'Error Report', "", "<pre>".$this->mail_buffer."</pre>");
		}
		// Send the endian log text if errors exists
		if ($this->_LOG_STARTED && $this->_SHOW_BORDERS) {
			$this->_do_save_log_info("END EXECUTION\r\n", 1);
		}
	} 

	function exception_handler($exception) {
		// these are our templates
		$traceline = "#%s %s(%s): %s(%s)";
		$msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

		// alter your trace as you please, here
		$trace = $exception->getTrace();
		foreach ($trace as $key => $stackPoint) {
			// I'm converting arguments to their type
			// (prevents passwords from ever getting logged as anything other than 'string')
			$trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
		}

		// build your tracelines
		$result = array();
		foreach ($trace as $key => $stackPoint) {
			$result[] = sprintf(
				$traceline,
				$key,
				$stackPoint['file'],
				$stackPoint['line'],
				$stackPoint['function'],
				implode(', ', $stackPoint['args'])
			);
		}
		// trace always ends with {main}
		$result[] = '#' . ++$key . ' {main}';

		// write tracelines into main template
		$msg = sprintf(
			$msg,
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			implode("\n", $result),
			$exception->getFile(),
			$exception->getLine()
		);

		// log or echo as you please
		error_log($msg);

		if (DEBUG_MODE) {
			echo "<pre>".$msg."</pre>";
		}
	}

	/**
	* The error handling routine set by set_error_handler()
	*/
	function ERROR_HANDLER ($error_type, $error_msg, $error_file, $error_line, $error_context) {
		// quickly turn off notices logging
		if ($this->NO_NOTICES && ($error_type == E_NOTICE || $error_type == E_USER_NOTICE)) {
			return true;
		}
		$log_message = "";
		$save_log	= false;
		$send_mail	= false;
		// Process critical errors
		if ($error_type == E_ERROR || $error_type == E_USER_ERROR) {
			if ($this->LOG_ERRORS_TO_FILE) {
				$save_log = true;
			}
			if ($this->SEND_ERRORS_TO_MAIL) {
				$send_mail = true;
			}
			if ($this->LOG_INTO_DB) {
				$save_in_db = true;
			}
		// Process warnings errors
		} elseif ($error_type == E_WARNING || $error_type == E_USER_WARNING) {
			if ($this->LOG_WARNINGS_TO_FILE) {
				$save_log = true;
			}
			if ($this->SEND_WARNINGS_TO_MAIL) {
				$send_mail = true;
			}
			if ($this->LOG_INTO_DB) {
				$save_in_db = true;
			}
		// Process notices
		} elseif ($error_type == E_NOTICE || $error_type == E_USER_NOTICE) {
			if ($this->LOG_NOTICES_TO_FILE) {
				$save_log = true;
			}
			if ($this->SEND_NOTICES_TO_MAIL) {
				$send_mail = true;
			}
			if ($this->LOG_INTO_DB) {
				$save_in_db = false;
			}
		} elseif ($error_type == E_DEPRECATED) {
			return true;
		} 
		if (in_array($error_type, array(E_USER_ERROR, E_USER_WARNING, E_WARNING))) {
			$msg = $this->error_types[$error_type].":".$error_msg;
			main()->_last_core_error_msg	= $msg;
			main()->_all_core_error_msgs[]	= $msg;
		}
		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER['REMOTE_ADDR'];
		}
		// Create log message if needed
		if ($save_log || $send_mail) {
			$DIVIDER = "\r\n";
			if ($this->USE_COMPACT_FORMAT) {
				$DIVIDER = "#@#";
			}
			// Create logging message
			$log_message  = date("Y-m-d H:i:s"). $DIVIDER;
			$log_message .= $this->error_types[$error_type]. $DIVIDER;
			$log_message .= str_replace(array("\r","\n"), "", $error_msg).";".$DIVIDER;
			$log_message .= "SOURCE=".$error_file." on line ".$error_line. $DIVIDER;
			$log_message .= "SITE_ID=".conf('SITE_ID'). $DIVIDER;
			$log_message .= "IP=".$IP. $DIVIDER;
			$log_message .= "QUERY_STRING=".WEB_PATH. (strlen($_SERVER["QUERY_STRING"]) ? "?".$_SERVER["QUERY_STRING"] : ""). $DIVIDER;
			$log_message .= "URL=http://".$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']. $DIVIDER;
			$log_message .= "REFERER=".$_SERVER['HTTP_REFERER']. $DIVIDER;
			$log_message .= $this->_log_display_array("GET"). $DIVIDER;
			$log_message .= $this->_log_display_array("POST"). $DIVIDER;
			$log_message .= $this->_log_display_array("FILES"). $DIVIDER;
			$log_message .= $this->_log_display_array("COOKIE"). $DIVIDER;
			$log_message .= $this->_log_display_array("SESSION");
			$log_message .= "USER_AGENT=".$_SERVER['HTTP_USER_AGENT']. $DIVIDER;
			$log_message .= "\n";
		}
		// Save log message if needed
		if ($save_log) {
			if (!$this->_LOG_STARTED) {
				if ($this->_SHOW_BORDERS) {
					$this->_do_save_log_info("START EXECUTION\r\n", 1);
				}
				$this->_LOG_STARTED = true;
			}
			$this->_do_save_log_info($log_message);
		}
		// Send mail notification if needed
		if ($send_mail) {
			$this->mail_buffer .= $log_message;
		}
		// Do store message into database (also check if that possible)
		if ($save_in_db && is_object(db()) && !empty(db()->_connected)) {
			// Prepare SQL
			$sql = db()->INSERT("log_core_errors", array(
				"error_level"	=> intval($error_type),
				"error_text"	=> _es($error_msg),
				"source_file"	=> _es($error_file),
				"source_line"	=> intval($error_line),
				"date"			=> time(),
				"site_id"		=> (int)conf('SITE_ID'),
				"user_id"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
				"user_group"	=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
				"is_admin"		=> MAIN_TYPE_ADMIN ? 1 : 0,
				"ip"			=> _es($IP),
				"query_string"	=> _es(WEB_PATH. (strlen($_SERVER["QUERY_STRING"]) ? "?".$_SERVER["QUERY_STRING"] : "")),
				"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
				"referer"		=> _es($_SERVER["HTTP_REFERER"]),
				"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
				"env_data"		=> $this->DB_LOG_ENV ? _es($this->_prepare_env()) : "",
				"object"		=> _es($_GET["object"]),
				"action"		=> _es($_GET["action"]),
			), true);
			db()->_add_shutdown_query($sql);
		}
		// Check if need to show error message to the user
		if (DEBUG_MODE && ($this->ERROR_REPORTING & $error_type) && strlen($log_message)) {
			echo "<b>".$this->error_types[$error_type]."</b>: ". $error_msg." (<i>".$error_file." on line ".$error_line."</i>)<br />\r\n";
		}
		// For critical errors stop execution here
		if ($error_type == E_ERROR || $error_type == E_USER_ERROR) {
			exit("<center><h3>SOMETHING WRONG WITH THE SYSTEM...".($error_type == E_USER_ERROR ? "<br>". $error_msg : "")."</h3></center>");
		}
		return true; 
	}

	/**
	* Display array
	*/
	function _log_display_array($array_name = "") {
		if (empty($array_name)) {
			return "";
		}
		$A = eval("return \$_".$array_name.";");
		if (empty($A)) {
			return "";
		}
		$output = str_replace(array("\r","\n"), "", var_export($A, 1));
		$output = preg_replace("/^array \((.*?)[\,]{0,1}\)$/i", "\$1", $output);
		return "_".$array_name."=".$output;
	}

	/**
	* Save log info to file or stdout
	*/
	function _do_save_log_info($log_message, $add_time = false) {
		if ($add_time) {
			$log_message = date("Y-m-d H:i:s")." - ".$log_message;
		}
		// Save log to file
		if ($this->error_log_filename == '') {
			error_log($log_message, 0);
		} else {
			$log_dir = dirname($this->error_log_filename);
			if (!file_exists($log_dir)) {
				_mkdir_m($log_dir);
			}
			error_log($log_message, 3, $this->error_log_filename);
		}
	}

	/**
	* This method will set which email address error reports are sent to.
	*/
	function set_mail_receiver($recipient_name, $recipient_address) {
		$this->email_to = $recipient_name. ' <'. $recipient_address .'>';
		$this->_email_addr_to = $recipient_address;
		$this->_email_name_to = $recipient_name;
	}

	/**
	* Method that changes the filename of the generated log file.
	*/
	function set_log_file_name($filename) { 
		$this->error_log_filename = $filename;
	}

	/**
	* Method that changes the logging flags.
	*/
	function set_flags($input = array()) {
		$this->LOG_ERRORS_TO_FILE		= (bool) $input{0};
		$this->LOG_WARNINGS_TO_FILE		= (bool) $input{1};
		$this->LOG_NOTICES_TO_FILE		= (bool) $input{2};
		$this->SEND_ERRORS_TO_MAIL		= (bool) $input{3};
		$this->SEND_WARNINGS_TO_MAIL	= (bool) $input{4};
		$this->SEND_NOTICES_TO_MAIL		= (bool) $input{5};
	}

	/**
	* Method that changes the error reporting level
	*/
	function set_reporting_level($level = false) {
		$this->ERROR_REPORTING = $level === false ? ini_get("error_reporting") : $level;
	}

	/**
	* Method that restores the error handler to the default error handler
	*/
	function restore_handler() { 
		restore_error_handler();
	}

	/**
	* Method that returns the error handler to ERROR_HANDLER()
	*/
	function return_handler() { 
		set_error_handler(array($this, 'ERROR_HANDLER'));
	}

	/**
	* This will print the associative array populated by backtrace data
	*/
	function show_backtrace() { 
		debug_print_backtrace();
	}

	/**
	* Track user error message
	*
	* @param	string
	* @return	void
	*/
	function _prepare_env () {
		$this->ENV_ARRAYS = strtoupper($this->ENV_ARRAYS);
		$data = array();
		// Include only desired arrays
		if (false !== strpos($this->ENV_ARRAYS, "G") && !empty($_GET)) {
			$data["_GET"]		= $_GET;
		}
		if (false !== strpos($this->ENV_ARRAYS, "P") && !empty($_GET)) {
			$data["_POST"]		= $_POST;
		}
		if (false !== strpos($this->ENV_ARRAYS, "F") && !empty($_GET)) {
			$data["_FILES"]		= $_FILES;
		}
		if (false !== strpos($this->ENV_ARRAYS, "C") && !empty($_GET)) {
			$data["_COOKIE"]	= $_COOKIE;
		}
		if (false !== strpos($this->ENV_ARRAYS, "S") && !empty($_SESSION)) {
			$data["_SESSION"]	= $_SESSION;
		}
		return !empty($data) ? serialize($data) : "";
	}
}
