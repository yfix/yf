<?php

/**
 * User errors handler
 * 
 * @package		YF
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 * @revision	$Revision$
 */
class yf_user_errors {

	/** @var string */
	public $LOG_USER_ERRORS_FILE_NAME	= "user_errors.log";
	/** @var bool */
	public $LOG_INTO_DB	= true;
	/** @var bool */
	public $DB_LOG_ENV		= false;
	/** @var string Could be any sequence from GPFCS */
	public $ENV_ARRAYS		= "GPF";

	/**
	* Track user error message
	*
	* @param	string
	* @return	void
	*/
	function _track_error ($error_message = "") {
		if (empty($error_message)) {
			return false;
		}
		// Try to get user error message source
		$backtrace = debug_backtrace();
		$cur_trace	= $backtrace[1];
		$next_trace	= $backtrace[2];
		// Prepare log text
		$text = "## LOG STARTS AT ".date("Y-m-d H:i:s")."; QUERY_STRING: ".$_SERVER["QUERY_STRING"]."; REFERER: ".$_SERVER["HTTP_REFERER"]."; USER_ID: ".main()->USER_ID."; USER_GROUP: ".main()->USER_GROUP."; SITE_ID: ".SITE_ID."; USER_AGENT: ".$_SERVER["HTTP_USER_AGENT"]." ##\r\n";
		$text .= "URL: http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."\r\n";
		$text .= "SOURCE FILE: \"".$cur_trace["file"]."\" at LINE ".$cur_trace["line"]."; ".(!empty($next_trace["class"]) ? "METHOD: ".$next_trace["class"]."->".$next_trace["function"] : "FUNCTION: ".$next_trace["function"]).";\r\n";
		$text .= "MESSAGE: ".$error_message."\r\n";
		$text .= "## LOG ENDS ##\r\n";
		// Do add current error info to the log file
		$h = fopen(INCLUDE_PATH.$this->LOG_USER_ERRORS_FILE_NAME, "a");
		fwrite($h, $text);
		fclose($h);
		// Do store message into database (also check if that possible)
		if ($this->LOG_INTO_DB && is_object(db())) {
			$error_type = 0;
			// Prepare SQL
			$sql = db()->INSERT("log_user_errors", array(
				"error_level"	=> intval($error_type),
				"error_text"	=> _es($error_message),
				"source_file"	=> _es($cur_trace["file"]),
				"source_line"	=> intval($cur_trace["line"]),
				"date"			=> time(),
				"site_id"		=> (int)conf('SITE_ID'),
				"user_id"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
				"user_group"	=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
				"is_admin"		=> MAIN_TYPE_ADMIN ? 1 : 0,
				"ip"			=> _es(common()->get_ip()),
				"query_string"	=> _es(WEB_PATH."?".$_SERVER["QUERY_STRING"]),
				"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
				"referer"		=> _es($_SERVER["HTTP_REFERER"]),
				"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
				"env_data"		=> $this->DB_LOG_ENV ? _es($this->_prepare_env()) : "",
				"object"		=> _es($_GET["object"]),
				"action"		=> _es($_GET["action"]),
			), true);
			db()->_add_shutdown_query($sql);
		}
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
		if (false !== strpos($this->ENV_ARRAYS, "P") && !empty($_POST)) {
			$data["_POST"]		= $_POST;
		}
		if (false !== strpos($this->ENV_ARRAYS, "F") && !empty($_FILES)) {
			$data["_FILES"]		= $_FILES;
		}
		if (false !== strpos($this->ENV_ARRAYS, "C") && !empty($_COOKIE)) {
			$data["_COOKIE"]	= $_COOKIE;
		}
		if (false !== strpos($this->ENV_ARRAYS, "S") && !empty($_SESSION)) {
			$data["_SESSION"]	= $_SESSION;
		}
		return !empty($data) ? serialize($data) : "";
	}
}
