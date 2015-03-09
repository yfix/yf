<?php

/**
*/
class yf_logs_db_queries {

	function go () {
		if (!db()->QUERY_LOG) {
			return false;
		}
		$logs_dir = INCLUDE_PATH.'logs/';
		if (!file_exists($logs_dir)) {
			_mkdir_m($logs_dir);
		}

		$IP = is_object(common()) ? common()->get_ip() : false;
		if (!$IP) {
			$IP = $_SERVER['REMOTE_ADDR'];
		}
		$log_header = 
			'## '.date('Y-m-d H:i:s').'; '
			.'SITE_ID: '.conf('SITE_ID').'; '
			.'IP = '.$IP.'; '
			.'QUERY_STRING = '.WEB_PATH.'?'.$_SERVER['QUERY_STRING'].'; '
			.(!empty($_SERVER['REQUEST_URI']) ? 'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'; ' : '')
			.(!empty($_SERVER['HTTP_REFERER']) ? 'REFERER = '.$_SERVER['HTTP_REFERER'].'; ' : '')
			."##\r\n";

		if (db()->LOG_ALL_QUERIES && !empty(db()->FILE_NAME_LOG_ALL)) {
			$c = 0;
			$h = fopen($logs_dir.db()->FILE_NAME_LOG_ALL, 'a');
			fwrite($h, $log_header);
			foreach ((array)db()->QUERY_LOG as $id => $text) {
				if (substr($text, 0, strlen('EXPLAIN')) == 'EXPLAIN' || substr($text, 0, strlen('SHOW SESSION STATUS')) == 'SHOW SESSION STATUS') {
					continue;
				}
				$log_entry = 
					++$c.') '
					.common()->_format_time_value(db()->QUERY_EXEC_TIME[$id]).";\t"
					.$text.'; '
					.(isset(db()->QUERY_AFFECTED_ROWS[$text]) ? ' # affected_rows: '.intval(db()->QUERY_AFFECTED_ROWS[$text]).';' : '')
					."\r\n";
				fwrite($h, $log_entry);
			}
			fwrite($h, "####\r\n");
			fclose($h);
		}
		// Slow queries
		if (db()->LOG_SLOW_QUERIES && !empty(db()->FILE_NAME_LOG_SLOW)) {
			$c = 0;
			foreach ((array)db()->QUERY_LOG as $id => $text) {
				if (db()->QUERY_EXEC_TIME[$id] < (float)db()->SLOW_QUERIES_TIME_LIMIT) {
					continue;
				}
				// Get explain info about queries
				$_explain_result = array();
				if (substr(db()->DB_TYPE, 0, 5) == 'mysql' && preg_match('/^[\(]*select/ims', $text)) {
					$_explain_result = db()->query_fetch_all('EXPLAIN '.$text);
				}
				$_cur_trace		= db()->QUERY_BACKTRACE_LOG[$id];
				$add_text = ''
					.(isset(db()->QUERY_AFFECTED_ROWS[$text]) ? ' # affected_rows: '.intval(db()->QUERY_AFFECTED_ROWS[$text]).'; ' : '')
					.(!empty($_cur_trace) ? '# '.$_cur_trace['file'].' on line '.$_cur_trace['line'].' (db->'.$_cur_trace['function'].(!empty($_cur_trace['inside_method']) ? ' inside '.$_cur_trace['inside_method'] : '').'; ' : '')
					.(!empty($_explain_result) ? $this->_format_db_explain_result($_explain_result) : '');
				$slow_queries[] = 
					++$c.') '
					.common()->_format_time_value(db()->QUERY_EXEC_TIME[$id]).";\t"
					.$text.'; '
					.($add_text ? "\r\n".$add_text : '')
					."\r\n";
			}
			if (!empty($slow_queries)) {
				$h = fopen($logs_dir. db()->FILE_NAME_LOG_SLOW, 'a');
				fwrite($h, $log_header);
				foreach ((array)$slow_queries as $text) {
					fwrite($h, $text);
				}
				fwrite($h, "####\r\n");
				fclose($h);
			}
		}
	}

	/**
	* Format result returned by db query 'EXPLAIN ...'
	* 
	* @access	private
	* @return	string
	*/
	function _format_db_explain_result($explain_result = array()) {
		if (empty($explain_result)) {
			return false;
		}
		// Get max lengths for all rows
		foreach ((array)$explain_result as $_num => $_data) {
			foreach ((array)$_data as $k => $v) {
				if (strlen($v) > $max_row_lengths[$k]) {
					$max_row_lengths[$k] = strlen($v);
				}
				if (strlen($k) > $max_row_lengths[$k]) {
					$max_row_lengths[$k] = strlen($k);
				}
			}
		}
		$body .= "\r\n";
		// Header
		$body .= '|';
		foreach ((array)$explain_result[0] as $k => $v) {
			$body .= $k. str_repeat(' ', $max_row_lengths[$k] - strlen($k) + 1).'|';
		}
		$body .= "\r\n";
		$body .= '|'.str_repeat('-', array_sum($max_row_lengths) + count($max_row_lengths) * 2 - 1)."|\r\n";
		// Data
		foreach ((array)$explain_result as $_num => $_data) {
			$body .= '|';
			foreach ((array)$_data as $k => $v) {
				$body .= $v. str_repeat(' ', $max_row_lengths[$k] - strlen($v) + 1).'|';
			}
			$body .= "\r\n";
		}
		// Cut last NL
		if (substr($body, -2) == "\r\n") {
			$body = substr($body, 0, -2);
		}
		return $body;
	}
}
