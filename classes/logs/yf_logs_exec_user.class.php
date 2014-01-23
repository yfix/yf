<?php

/**
* Save execution info log
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_logs_exec_user {

	/** @var array Stop-list for logging (REGEXPs allowed here) */
	public $STOP_LIST				= array(
		'object=(task_loader|aff).*',
#		'task=(login|logout)',
	);
	/** @var bool */
	public $USE_STOP_LIST			= true;
	/** @var bool */
	public $LOG_NO_GRAPHICS_PAGES	= false;
	/** @var bool */
	public $FILTER_BOTS				= false;
	/** @var bool */
	public $LOG_NOT_FOUND_PAGES		= false;
	/** @var enum('db','file') */
	public $LOG_DRIVER				= 'file';
	/** @var  */
	public $LOG_DIR_NAME			= 'logs/log_exec/';
	/** @var bool */
	public $LOGGING					= true;

	/**
	* 
	*/
	function _init () {
		if (!$this->LOG_DRIVER || !in_array($this->LOG_DRIVER, array('db', 'file'))) {
			$this->LOG_DRIVER = 'file';
		}
	}

	/**
	* Do save
	*/
	function go () {
		if (!$this->LOGGING) {
			return false;
		}
		// Stop for now for the admin section
		if (MAIN_TYPE_ADMIN) {
			return false;
		}
		// Stop on page that set main()->NO_GRAPHICS flag
		if (main()->NO_GRAPHICS && !$this->LOG_NO_GRAPHICS_PAGES) {
			return false;
		}
		// Skip logging tasks with 'not found' status
		if ($GLOBALS['task_not_found'] && !$this->LOG_NOT_FOUND_PAGES) {
			return false;
		}
		// Try to search current query string in the stop list
		if ($this->USE_STOP_LIST) {
			foreach ((array)$this->STOP_LIST as $_cur_pattern) {
				if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
					return false;
				}
			}
		}
		// Check if current user is a bot and skip logging here if needed
		if ($this->FILTER_BOTS && !main()->USER_ID) {
			$SPIDER_NAME = common()->_is_spider($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
			if ($SPIDER_NAME) {
				return false;
			}
		}
		$exec_time = $GLOBALS['time_end'] ? $GLOBALS['time_end'] : common()->_format_time_value(microtime(true) - main()->_time_start);
		// Avoid wrong insering of floats with comma into database, also validate float
		$exec_time = str_replace(',','.', floatval(str_replace(',','.', $exec_time)));
		// Create and execute db query
		if ($this->LOG_DRIVER == 'db') {
			$sql = db()->INSERT('log_exec', array(
				'user_id'		=> intval($_SESSION['user_id']),
				'user_group'	=> intval($_SESSION['user_group']),
				'date'			=> time(),
				'ip'			=> _es(common()->get_ip()),
				'user_agent'	=> _es($_SERVER['HTTP_USER_AGENT']),
				'referer'		=> _es($_SERVER['HTTP_REFERER']),
				'query_string'	=> _es($_SERVER['QUERY_STRING']),
				'request_uri'	=> _es($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']),
				'exec_time'		=> $exec_time,
				'num_dbq'		=> intval(db()->NUM_QUERIES),
				'page_size'		=> intval(strlen(tpl()->CACHE['main']['string'])),
				'site_id'		=> (int)conf('SITE_ID'),
			), 1);
			db()->_add_shutdown_query($sql);
		// Log into file
		} else {
			$log_file_path	= INCLUDE_PATH. $this->LOG_DIR_NAME. gmdate('Y-m-d').'.log';
			$log_dir_path	= dirname($log_file_path);
			if (!file_exists($log_dir_path)) {
				_mkdir_m($log_dir_path);
			}
			$t = '';
			$t .= '#@#'.intval($_SESSION['user_id']);
			$t .= '#@#'.intval($_SESSION['user_group']);
			$t .= '#@#'.time();
			$t .= '#@#'.common()->get_ip();
			$t .= '#@#'.$_SERVER['HTTP_USER_AGENT'];
			$t .= '#@#'.$_SERVER['HTTP_REFERER'];
			$t .= '#@#'.$_SERVER['QUERY_STRING'];
			$t .= '#@#'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$t .= '#@#'.$exec_time;
			$t .= '#@#'.intval(db()->NUM_QUERIES);
			$t .= '#@#'.intval(strlen(tpl()->CACHE['main']['string']));
			$t .= '#@#'.(int)conf('SITE_ID');
			$t .= '#@#0'; // mean: exec full mode (not from output cache)
			$t .= PHP_EOL;
			file_put_contents($log_file_path, $t, FILE_APPEND);
		}
	}
}
