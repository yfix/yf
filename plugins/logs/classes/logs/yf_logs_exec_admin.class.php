<?php

/**
* Save execution info log for admin section
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_logs_exec_admin {

	/** @var array Stop-list for logging (REGEXPs allowed here) */
	public $STOP_LIST				= [
#		'task=(login|logout)',
	];
	/** @var bool */
	public $USE_STOP_LIST			= true;
	/** @var bool */
	public $LOG_NO_GRAPHICS_PAGES	= true;
	/** @var bool */
	public $FILTER_BOTS				= false;
	/** @var bool */
	public $LOG_NOT_FOUND_PAGES		= true;
	/** @var enum('db','file') */
	public $LOG_DRIVER				= 'db';
	/** @var  */
	public $LOG_DIR_NAME			= 'logs/log_admin_exec/';
	/** @var bool */
	public $LOGGING					= true;
	/** @var array */
	public $EXCLUDE_IPS	= [
		'127.0.0.1',
	];
	/** @var array */
	public $EXCLUDE_USER_AGENTS	= [
		'zabbix',
		'acceptance_tests',
	];

	/**
	*/
	function _init () {
		if (!$this->LOG_DRIVER || !in_array($this->LOG_DRIVER, ['db', 'file'])) {
			$this->LOG_DRIVER = 'file';
		}
	}

	/**
	* Do save
	*/
	function go () {
		if (!$this->LOGGING || MAIN_TYPE_USER) {
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
		if ($this->EXCLUDE_IPS) {
			$ip = common()->get_ip();
			if ($ip && (isset($this->EXCLUDE_IPS[$ip]) || in_array($ip, $this->EXCLUDE_IPS))) {
				$checks['exclude_ip'] = true;
			}
		}
		if ($this->EXCLUDE_USER_AGENTS) {
			$ua = $_SERVER['HTTP_USER_AGENT'];
			foreach ((array)$this->EXCLUDE_USER_AGENTS as $pattern) {
				if (preg_match('~'.$pattern.'~i', $ua)) {
					$checks['exclude_ua'] = true;
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
		$exec_time = str_replace(',', '.', common()->_format_time_value($GLOBALS['time_end'] ?: microtime(true) - main()->_time_start));
		$query_string = $_SERVER['QUERY_STRING'];
// TODO: use parse_url here
/*
		list($object, $action) = explode('&action=', $query_string);
		if ($action == 'realtime') {
			return false;
		}
*/
		if (main()->is_console()) {
			$query_string = http_build_query($_GET);
		}
		$data = [
			'admin_id'		=> (int)main()->ADMIN_ID,
			'admin_group'	=> (int)main()->ADMIN_GROUP,
			'date'			=> time(),
			'ip'			=> common()->get_ip(),
			'user_agent'	=> (string)$_SERVER['HTTP_USER_AGENT'],
			'referer'		=> (string)$_SERVER['HTTP_REFERER'],
			'query_string'	=> (string)$query_string,
			'request_uri'	=> $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'exec_time'		=> $exec_time,
			'num_dbq'		=> (int)db()->NUM_QUERIES,
			'page_size'		=> (int)tpl()->_output_body_length,
			'site_id'		=> (int)conf('SITE_ID'),
			'server_id'		=> (int)conf('SERVER_ID'),
		];
		if ($this->LOG_DRIVER == 'db') {
			$sql = db()->insert_safe('log_admin_exec', $data, $as_sql = true);
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
