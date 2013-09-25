<?php

// Fast log exec info
function _fast_log_exec () {
	$log_file_path	= INCLUDE_PATH. 'logs/log_exec/'. gmdate('Y-m-d').'.log';
	$log_dir_path	= dirname($log_file_path);
	if (!file_exists($log_dir_path)) {
		mkdir($log_dir_path, 0777, true); // PHP5-only 3-rd 'recursive' param
	}
	$t = '';
	$t .= '#@#0'; // user_id
	$t .= '#@#0'; // user_group
	$t .= '#@#'.time();
	$t .= '#@#'.$_SERVER['REMOTE_ADDR'];
	$t .= '#@#'.$_SERVER['HTTP_USER_AGENT'];
	$t .= '#@#'.$_SERVER['HTTP_REFERER'];
	$t .= '#@#'.$_SERVER['QUERY_STRING'];
	$t .= '#@#'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$t .= '#@#'.round(microtime(true) - main()->_time_start, 5);
	$t .= '#@#0'; // db queries
	$t .= '#@#0'; // html size
	$t .= '#@#'.(int)conf('SITE_ID');
	$t .= '#@#1'; // mean: exec from output cache
	$t .= PHP_EOL;
	@file_put_contents($log_file_path, $t, FILE_APPEND); // PHP5-only native function

#	return true; // Means success
}
