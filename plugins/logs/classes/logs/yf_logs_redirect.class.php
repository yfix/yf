<?php

/**
*/
class yf_logs_redirect {

	/** @var bool */
	public $LOG_REDIRECTS = true;
	/** @var array Stop-list for logging (REGEXPs allowed here) */
	public $EXCLUDE_URL_FROM = [
		'object=(aff|dynamic).*',
#		'task=(login|logout)',
	];
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
	function _save_log ($params = []) {
		if (!$this->LOG_REDIRECTS) {
			return false;
		}
		if ($this->EXCLUDE_URL_FROM) {
			foreach ((array)$this->EXCLUDE_URL_FROM as $pattern) {
				if (preg_match('~'.$pattern.'~i', $_SERVER['QUERY_STRING'])) {
					$checks['exclude_url_from'] = true;
					break;
				}
			}
		}
		$ip = common()->get_ip();
		if ($this->EXCLUDE_IPS) {
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
		// slice 2 first elements (__FUNCTION__ and $this->_go) and leave only 5 more trace elements to save space
		$trace = implode(PHP_EOL, array_slice(explode(PHP_EOL, main()->trace_string()), 2, 7));

		$is_https = ($_SERVER['HTTPS'] || $_SERVER['SSL_PROTOCOL']);

		return db()->insert_safe('log_redirects', [
			'url_from'		=> ($is_https ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'],
			'url_to'		=> $params['url_to'],
			'reason'		=> $params['reason'],
			'use_rewrite'	=> (int)$params['rewrite'],
			'redirect_type'	=> $params['type'],
			'date'			=> gmdate('Y-m-d H:i:s'),
			'ip'			=> $ip,
			'query_string'	=> $_SERVER['QUERY_STRING'],
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
			'referer'		=> $_SERVER['HTTP_REFERER'],
			'object'		=> $_GET['object'],
			'action'		=> $_GET['action'],
			'user_id'		=> MAIN_TYPE_ADMIN ? main()->ADMIN_ID : main()->USER_ID,
			'user_group'	=> MAIN_TYPE_ADMIN ? main()->ADMIN_GROUP : main()->USER_GROUP,
			'site_id'		=> (int)main()->SITE_ID,
			'server_id'		=> (int)main()->SERVER_ID,
			'locale'		=> conf('language'),
			'is_admin'		=> MAIN_TYPE_ADMIN ? 1 : 0,
			'rewrite_mode'	=> (int)tpl()->REWRITE_MODE,
			'debug_mode'	=> DEBUG_MODE ? 1 : 0,
			'exec_time'		=> str_replace(',', '.', round(microtime(true) - main()->_time_start, 4)),
			'trace'			=> $trace,
		]);
	}
}
