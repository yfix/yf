<?php

/**
* Save execution info log for user section
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_logs_exec_user {

	/** @var bool */
	public $LOGGING				= true;
	/** @var enum('db','file') */
	public $LOG_DRIVER			= 'file';
	/** @var */
	public $LOG_DIR_NAME		= 'logs/';
	/** @var bool */
	public $USE_STOP_LIST		= true;
	/** @var array Stop-list for logging (REGEXPs allowed here) */
	public $STOP_LIST			= array(
		'object=(aff|dynamic).*',
#		'task=(login|logout)',
	);
	/** @var bool */
	public $LOG_IS_USER_GUEST	= true;
	/** @var bool */
	public $LOG_IS_USER_MEMBER	= true;
	/** @var bool */
	public $LOG_IS_COMMON_PAGE	= true;
	/** @var bool */
	public $LOG_IS_HTTPS		= true;
	/** @var bool */
	public $LOG_IS_POST			= true;
	/** @var bool */
	public $LOG_IS_NO_GRAPHICS	= false;
	/** @var bool */
	public $LOG_IS_AJAX			= false;
	/** @var bool */
	public $LOG_IS_SPIDER		= false;
	/** @var bool */
	public $LOG_IS_REDIRECT		= false;
	/** @var bool */
	public $LOG_IS_UNIT_TEST	= false;
	/** @var bool */
	public $LOG_IS_CONSOLE		= false;
	/** @var bool */
	public $LOG_IS_DEV			= false;
	/** @var bool */
	public $LOG_IS_DEBUG		= false;
	/** @var bool */
	public $LOG_IS_BANNED		= true;
	/** @var bool */
	public $LOG_IS_404			= false;
	/** @var bool */
	public $LOG_IS_403			= false;
	/** @var bool */
	public $LOG_IS_503			= false;

	/**
	*/
	function _init () {
		if (!$this->LOG_DRIVER || !in_array($this->LOG_DRIVER, array('db', 'file'))) {
			$this->LOG_DRIVER = 'file';
		}
	}

	/**
	*/
	function allow () {
		if (!$this->LOGGING || MAIN_TYPE_ADMIN) { return false; }
		$main = main();
		if (!$main->is_logged_in() && !$this->LOG_IS_USER_GUEST) { return false; }
		if ($main->is_logged_in() && !$this->LOG_IS_USER_MEMBER) { return false; }
		if (!$main->is_common_page() && $this->LOG_IS_COMMON_PAGE) { return false; }
		if ($main->is_https() && !$this->LOG_IS_HTTPS) { return false; }
		if ($main->is_post() && !$this->LOG_IS_POST) { return false; }
		if ($main->no_graphics() && !$this->LOG_IS_NO_GRAPHICS) { return false; }
		if ($main->is_ajax() && !$this->LOG_IS_AJAX) { return false; }
		if ($main->is_spider() && !$this->LOG_IS_SPIDER) { return false; }
		if ($main->is_redirect() && !$this->LOG_IS_REDIRECT) { return false; }
		if ($main->is_console() && !$this->LOG_IS_CONSOLE) { return false; }
		if ($main->is_unit_test() && !$this->LOG_IS_UNIT_TEST) { return false; }
		if ($main->is_dev() && !$this->LOG_IS_DEV) { return false; }
		if ($main->is_debug() && !$this->LOG_IS_DEBUG) { return false; }
		if ($main->is_banned() && !$this->LOG_IS_BANNED) { return false; }
		if ($main->is_403() && !$this->LOG_IS_403) { return false; }
		if ($main->is_404() && !$this->LOG_IS_404) { return false; }
		if ($main->is_503() && !$this->LOG_IS_503) { return false; }
		if ($this->USE_STOP_LIST) {
			foreach ((array)$this->STOP_LIST as $_cur_pattern) {
				if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	* Do save
	*/
	function go () {
		if (!$this->allow()) {
			return false;
		}
		$data = array(
			'user_id'		=> (int)$_SESSION['user_id'],
			'user_group'	=> (int)$_SESSION['user_group'],
			'date'			=> time(),
			'ip'			=> (string)common()->get_ip(),
			'user_agent'	=> (string)$_SERVER['HTTP_USER_AGENT'],
			'referer'		=> (string)$_SERVER['HTTP_REFERER'],
			'query_string'	=> (string)$_SERVER['QUERY_STRING'],
			'request_uri'	=> (string)$_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'exec_time'		=> str_replace(',', '.', common()->_format_time_value($GLOBALS['time_end'] ?: microtime(true) - main()->_time_start)),
			'num_dbq'		=> (int)db()->NUM_QUERIES,
			'page_size'		=> (int)tpl()->_output_body_length,
			'site_id'		=> (int)conf('SITE_ID'),
			'utm_source'	=> (string)$_GET['utm_source'],
		);
// TODO: add all checks results from main()->is_*()
		if ($this->LOG_DRIVER == 'db') {
			$sql = db()->insert_safe('log_exec', $data);
			db()->_add_shutdown_query($sql);
		} elseif ($this->LOG_DRIVER == 'file') {
			$data['output_cache'] = '0';  // mean: exec full mode (not from output cache)
			$log_file_path	= STORAGE_PATH. $this->LOG_DIR_NAME. 'log_exec_'.gmdate('Y-m-d').'.log';
			$log_dir_path	= dirname($log_file_path);
			if (!file_exists($log_dir_path)) {
				_mkdir_m($log_dir_path);
			}
			file_put_contents($log_file_path, implode('#@#', $data).PHP_EOL, FILE_APPEND);
		}
	}
}
