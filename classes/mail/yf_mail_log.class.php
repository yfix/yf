<?php

class yf_mail_log {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->PARENT = _class('send_mail');
	}

	/**
	* Save email log info
	*/
	function save(array $params = []) {
		// Try to get user error message source
		$backtrace = debug_backtrace();
		$cur_trace	= $backtrace[2];
		// Prepare other options
		$other_options = '';
		if (!empty($params['attaches'])) {
			$other_options .= 'attaches:'.implode(',', $params['attaches']). PHP_EOL;
		}
		if (!empty($params['charset'])) {
			$other_options .= 'charset:'.$params['charset']. PHP_EOL;
		}
		$smtp = $params['smtp_options'];
		if (!empty($smtp)) {
			$other_options .=
				'smtp_host:'.$smtp['smtp_host']
				.', smtp_user: '.$smtp['smtp_user_name']
				.', smtp_port: '.$smtp['smtp_port']
				.', smtp_secure: '.$smtp['smtp_secure']
				.PHP_EOL;
		}
		return db()->insert_safe('log_emails', [
			'email_from'	=> $params['email_from'],
			'name_from'		=> $params['name_from'],
			'email_to'		=> $params['email_to'],
			'name_to'		=> $params['name_to'],
			'subject'		=> $params['subject'],
			'text'			=> $params['text'],
			'source_file'	=> $cur_trace['file'],
			'source_line'	=> intval($cur_trace['line']),
			'date'			=> time(),
			'site_id'		=> (int)conf('SITE_ID'),
			'user_id'		=> intval($_SESSION[MAIN_TYPE_ADMIN ? 'admin_id' : 'user_id']),
			'user_group'	=> intval($_SESSION[MAIN_TYPE_ADMIN ? 'admin_group' : 'user_group']),
			'is_admin'		=> MAIN_TYPE_ADMIN ? 1 : 0,
			'ip'			=> common()->get_ip(),
			'query_string'	=> WEB_PATH.'?'.$_SERVER['QUERY_STRING'],
			'user_agent'	=> $_SERVER['HTTP_USER_AGENT'],
			'referer'		=> $_SERVER['HTTP_REFERER'],
			'request_uri'	=> $_SERVER['REQUEST_URI'],
			'env_data'		=> $this->PARENT->DB_LOG_ENV ? serialize(['_GET' => $_GET,'_POST' => $_POST]) : '',
			'object'		=> $_GET['object'],
			'action'		=> $_GET['action'],
			'success'		=> intval((bool)$params['send_success']),
			'error_text'	=> $params['error_message'],
			'send_time'		=> floatval(common()->_format_time_value(microtime(true) - (float)$params['time_start'])),
			'mail_debug'	=> intval((bool)$params['mail_debug']),
			'used_mailer'	=> $params['used_mailer'],
			'other_options'	=> $other_options,
		]);
	}
}
