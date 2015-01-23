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

function _route( $table ) {
	$request = ($_GET['object'] && $_GET['action']) ? '/'.$_GET['object'].'/'.$_GET['action'] : $_SERVER['REQUEST_URI'];
	foreach( $table as $uri => $action ) {
		if( strpos( $request, $uri ) === 0 ) { return( $action ); }
	}
	return( null );
}

$_route_table = array(
	'/dynamic/placeholder'   => 'placeholder',
	'/help/show_tip'         => 'tooltip',
	'/dynamic/php_func'      => 'php_func',
	'/dynamic/image'         => 'dynamic_image',
	'/dynamic/captcha_image' => 'captcha_image',
	'/forum/low'             => 'forum_low',
	'/search/autocomplete'   => 'search_autocomplete',
	'/category/rss_for_cat'  => 'rss_export',
	'/payment_test/'         => 'payment_test',
);

$fname = _route( $_route_table );

// cache
if (!$fname && main()->OUTPUT_CACHING && empty($_COOKIE['member_id'])) {
	$fname = 'output_cache';
}
// try
if ($fname) {
	$done = _call_fast_func($fname);
}
// log
if ($done) {
	if (module_conf('main', 'LOG_EXEC')) {
		_call_fast_func('log_exec');
	}
	if (DEBUG_MODE && !main()->_no_fast_init_debug) {
		$body .= '<hr>DEBUG INFO:'.PHP_EOL;
		$body .= '<br />exec time: <b>'. round(microtime(true) - main()->_time_start, 5).'</b> sec';
		echo $body;
	}
	exit;
}

