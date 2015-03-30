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
	public $LOG_IS_SPIDER		= true;
	/** @var bool */
	public $LOG_IS_REDIRECT		= true;
	/** @var bool */
	public $LOG_IS_UNIT_TEST	= false;
	/** @var bool */
	public $LOG_IS_CONSOLE		= false;
	/** @var bool */
	public $LOG_IS_DEV			= false;
	/** @var bool */
	public $LOG_IS_DEBUG		= true;
	/** @var bool */
	public $LOG_IS_BANNED		= true;
	/** @var bool */
	public $LOG_IS_404			= true;
	/** @var bool */
	public $LOG_IS_403			= true;
	/** @var bool */
	public $LOG_IS_503			= true;

	/**
	*/
	function _init () {
		if (!$this->LOG_DRIVER) {
			$this->LOG_DRIVER = 'file';
		}
		if (!is_array($this->LOG_DRIVER)) {
			$this->LOG_DRIVER = array($this->LOG_DRIVER);
		}
	}

	/**
	*/
	function allow () {
		if (!$this->LOGGING || MAIN_TYPE_ADMIN) { return false; }
		$main = main();
		$checks = array(
			'is_user_guest'	=> !$main->is_logged_in() && !$this->LOG_IS_USER_GUEST,
			'is_user_member'=> $main->is_logged_in() && !$this->LOG_IS_USER_MEMBER,
			'is_common_page'=> !$main->is_common_page() && $this->LOG_IS_COMMON_PAGE,
			'is_https'		=> $main->is_https() && !$this->LOG_IS_HTTPS,
			'is_post'		=> $main->is_post() && !$this->LOG_IS_POST,
			'is_no_graphics'=> $main->no_graphics() && !$this->LOG_IS_NO_GRAPHICS,
			'is_ajax'		=> $main->is_ajax() && !$this->LOG_IS_AJAX,
			'is_spider'		=> $main->is_spider() && !$this->LOG_IS_SPIDER,
			'is_redirect'	=> $main->is_redirect() && !$this->LOG_IS_REDIRECT,
			'is_console'	=> $main->is_console() && !$this->LOG_IS_CONSOLE,
			'is_unit_test'	=> $main->is_unit_test() && !$this->LOG_IS_UNIT_TEST,
			'is_dev'		=> $main->is_dev() && !$this->LOG_IS_DEV,
			'is_debug'		=> $main->is_debug() && !$this->LOG_IS_DEBUG,
			'is_banned'		=> $main->is_banned() && !$this->LOG_IS_BANNED,
			'is_403'		=> $main->is_403() && !$this->LOG_IS_403,
			'is_404'		=> $main->is_404() && !$this->LOG_IS_404,
			'is_503'		=> $main->is_503() && !$this->LOG_IS_503,
			'is_stop_list'	=> false,
		);
		if ($this->USE_STOP_LIST) {
			foreach ((array)$this->STOP_LIST as $_cur_pattern) {
				if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
					$checks['stop_list'] = true;
					break;
				}
			}
		}
		$this->checks = $checks;
		foreach ((array)$checks as $name => $is_denied) {
			if ($is_denied) {
				$this->log_denied_because = $name;
				return false;
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
// TODO: add all checks results from main()->is_*()
		);
		if (in_array('db', $this->LOG_DRIVER)) {
			$sql = db()->insert_safe('log_exec', $data);
			db()->_add_shutdown_query($sql);
		}
		if (in_array('file', $this->LOG_DRIVER)) {
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
