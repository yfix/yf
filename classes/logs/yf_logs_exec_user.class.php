<?php

/**
* Save execution info log for user section
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
		if (!$this->LOGGING || MAIN_TYPE_ADMIN) {
			return false;
		}
		if (main()->NO_GRAPHICS && !$this->LOG_NO_GRAPHICS_PAGES) {
			return false;
		}
		if ($GLOBALS['task_not_found'] && !$this->LOG_NOT_FOUND_PAGES) {
			return false;
		}
		if ($this->USE_STOP_LIST) {
			foreach ((array)$this->STOP_LIST as $_cur_pattern) {
				if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
					return false;
				}
			}
		}
		if ($this->FILTER_BOTS && !main()->USER_ID) {
			$SPIDER_NAME = common()->_is_spider($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
			if ($SPIDER_NAME) {
				return false;
			}
		}
		$exec_time = str_replace(',', '.', common()->_format_time_value($GLOBALS['time_end'] ?: microtime(true) - main()->_time_start));
		$data = array(
			'user_id'		=> (int)$_SESSION['user_id'],
			'user_group'	=> (int)$_SESSION['user_group'],
			'date'			=> time(),
			'ip'			=> common()->get_ip(),
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
			'referer'		=> $_SERVER['HTTP_REFERER'],
			'query_string'	=> $_SERVER['QUERY_STRING'],
			'request_uri'	=> $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'exec_time'		=> $exec_time,
			'num_dbq'		=> (int)db()->NUM_QUERIES,
			'page_size'		=> (int)tpl()->_output_body_length,
			'site_id'		=> (int)conf('SITE_ID'),
		);
		if ($this->LOG_DRIVER == 'db') {
			$sql = db()->insert_safe('log_exec', $data);
			db()->_add_shutdown_query($sql);
		} elseif ($this->LOG_DRIVER == 'file') {
			$data['output_cache'] = '0';  // mean: exec full mode (not from output cache)
			$log_file_path	= INCLUDE_PATH. $this->LOG_DIR_NAME. gmdate('Y-m-d').'.log';
			$log_dir_path	= dirname($log_file_path);
			if (!file_exists($log_dir_path)) {
				_mkdir_m($log_dir_path);
			}
			file_put_contents($log_file_path, implode('#@#', $data).PHP_EOL, FILE_APPEND);
		}
	}
}
