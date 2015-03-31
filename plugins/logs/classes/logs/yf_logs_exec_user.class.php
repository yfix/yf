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
	/** @var bool */
	public $LOG_IS_CACHE_ON		= true;
	/** @var bool */
	public $EXCLUDE_IPS			= array();

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
		$this->is = array(
			'is_logged_in'	=> (bool)$main->is_logged_in(),
			'is_common_page'=> $main->is_common_page(),
			'is_https'		=> $main->is_https(),
			'is_post'		=> $main->is_post(),
			'is_no_graphics'=> (bool)$main->no_graphics(),
			'is_ajax'		=> $main->is_ajax(),
			'is_spider'		=> $main->is_spider(),
			'is_redirect'	=> $main->is_redirect(),
			'is_console'	=> $main->is_console(),
			'is_unit_test'	=> $main->is_unit_test(),
			'is_dev'		=> $main->is_dev(),
			'is_debug'		=> $main->is_debug(),
			'is_banned'		=> $main->is_banned(),
			'is_403'		=> $main->is_403(),
			'is_404'		=> $main->is_404(),
			'is_503'		=> $main->is_503(),
			'is_cache_on'	=> $main->is_cache_on(),
		);
		$checks = array(
			'is_user_guest'	=> !$this->is['is_logged_in'] && !$this->LOG_IS_USER_GUEST,
			'is_user_member'=> $this->is['is_logged_in'] && !$this->LOG_IS_USER_MEMBER,
		);
		foreach((array)$this->is as $name => $val) {
			if ($name === 'is_logged_in') {
				continue;
			}
			$conf = 'LOG_'.strtoupper($name);
			$checks[$name] = $val && !$this->$conf;
		}
		if ($this->USE_STOP_LIST) {
			foreach ((array)$this->STOP_LIST as $_cur_pattern) {
				if (preg_match('/'.$_cur_pattern.'/i', $_SERVER['QUERY_STRING'])) {
					$checks['in_stop_list'] = true;
					break;
				}
			}
		}
		if ($this->EXCLUDE_IPS) {
			$ip = common()->get_ip();
			if ($ip && (isset($this->EXCLUDE_IPS[$ip]) || in_array($ip, $this->EXCLUDE_IPS))) {
				$checks['exclude_ip'] = true;
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
		$is = $this->is;
		$data = array(
			'user_id'		=> (int)$_SESSION['user_id'],
			'user_group'	=> (int)$_SESSION['user_group'],
			'date'			=> time(),
			'user_agent'	=> (string)$_SERVER['HTTP_USER_AGENT'],
			'referer'		=> (string)$_SERVER['HTTP_REFERER'],
			'query_string'	=> (string)$_SERVER['QUERY_STRING'],
			'request_uri'	=> (string)$_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'exec_time'		=> str_replace(',', '.', common()->_format_time_value($GLOBALS['time_end'] ?: microtime(true) - main()->_time_start)),
			'num_dbq'		=> (int)db()->NUM_QUERIES,
			'page_size'		=> (int)tpl()->_output_body_length,
			'memory'		=> (int)memory_get_peak_usage(),
			'site_id'		=> (int)conf('SITE_ID'),
			'ip'			=> (string)common()->get_ip(),
			'country'		=> (string)(conf('country') ?: $_SERVER['GEOIP_COUNTRY_CODE']),
			'lang'			=> (string)conf('language'),
			'utm_source'	=> strval($_GET['utm_source'] ?: ($_POST['utm_source'] ?: $_SESSION['utm_source'])),
			'is_common_page'=> (int)$is['is_common_page'],
			'is_https'		=> (int)$is['is_https'],
			'is_post'		=> (int)$is['is_post'],
			'is_no_graphics'=> (int)$is['is_no_graphics'],
			'is_ajax'		=> (int)$is['is_ajax'],
			'is_spider'		=> (int)$is['is_spider'],
			'is_redirect'	=> (int)$is['is_redirect'],
			'is_console'	=> (int)$is['is_console'],
			'is_unit_test'	=> (int)$is['is_unit_test'],
			'is_dev'		=> (int)$is['is_dev'],
			'is_debug'		=> (int)$is['is_debug'],
			'is_banned'		=> (int)$is['is_banned'],
			'is_403'		=> (int)$is['is_403'],
			'is_404'		=> (int)$is['is_404'],
			'is_503'		=> (int)$is['is_503'],
			'is_cache_on'	=> (int)$is['is_cache_on'],
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
