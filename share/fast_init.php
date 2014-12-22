<?php
/*
	This file is designed for use 'fast_init' mode
	In this mode we try to load task as fast as we can
	So if we catch task needed to init fast and dirty
	we load very samll piece of code, usually only config files	and main class.
	Execution time is 10-50 times faster than process usual full framework init process.

	Remeber: called function must do 'return true' if success
*/

// Protection from direct call
if (!defined('YF_PATH')) {
	die();
}
require_once YF_PATH.'share/functions/yf_aliases.php';

// Load and run fast init function code
function _call_fast_func ($f_name) {
	// Currently admin is allowed to call only dynamic->php_func
	if (MAIN_TYPE_ADMIN && !in_array($f_name, array('php_func'))) {
		return false;
	}
	$dir = 'share/fast_init/';
	$suffix = '.php';
	$pattern = $dir. $f_name. $suffix;
	$globs = array(
		'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $pattern,
		'project_app'			=> APP_PATH. $pattern,
		'yf_plugins'			=> YF_PATH. 'plugins/*/'. $pattern,
		'yf_main'				=> YF_PATH. $pattern,
	);
	foreach($globs as $gname => $glob) {
		foreach(glob($glob) as $path) {
			$func = include $path;
			return $func();
		}
	}
	return false;
}

$fname = '';
// Switch between fast actions (place your custom code below):
$route = ($_GET['object'] && $_GET['action']) ? '/'.$_GET['object'].'/'.$_GET['action'] : $_SERVER['REQUEST_URI'];
if (strpos($route, '/dynamic/placeholder') === 0) {
	$fname = 'placeholder';
} elseif (strpos($route, '/help/show_tip') === 0) {
	$fname = 'tooltip';
} elseif (strpos($route, '/dynamic/php_func') === 0 && MAIN_TYPE_ADMIN) {
	$fname = 'php_func';
} elseif (strpos($route, '/dynamic/image') === 0) {
	$fname = 'dynamic_image';
} elseif (strpos($route, '/dynamic/captcha_image') === 0) {
	$fname = 'captcha_image';
} elseif (strpos($route, '/forum/low') === 0) {
	$fname = 'forum_low';
} elseif (strpos($route, '/search/autocomplete') === 0) {
	$fname = 'search_autocomplete';
} elseif (main()->OUTPUT_CACHING && empty($_COOKIE['member_id'])) {
	$fname = 'output_cache';
} elseif (strpos($route, '/category/rss_for_cat') === 0 || strpos($route, '/category/rss_for_city') === 0) {
	$fname = 'rss_export';
}
if ($fname) {
	$done = _call_fast_func($fname);
}
// Finish fast init
if ($done) {
	if (module_conf('main', 'LOG_EXEC')) {
		_call_fast_func('log_exec');
	}
	if (DEBUG_MODE && !main()->_no_fast_init_debug) {
		$body .= '<hr>DEBUG INFO:'.PHP_EOL;
		$body .= '<br />exec time: <b>'. round(microtime(true) - main()->_time_start, 5).'</b> sec';
		echo $body;
	}
	die(); // Required if success to stop execution
}
