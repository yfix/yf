<?php
/*
	This file is designed for use "fast_init" mode
	In this mode we try to load task as fast as we can
	So if we catch task needed to init fast and dirty
	we load very samll piece of code, usually only config files	and main class.
	Execution time is 10-50 times faster than process usual full framework init process.

	Remeber: called function must do "return true" if success
*/

// Protection from direct call
if (!defined('YF_PATH')) {
	die();
}
require_once YF_PATH."share/functions/yf_aliases.php";

// Load and run fast init function code
function _call_fast_func ($f_name) {
	// Currently admin is allowed to call only dynamic->php_func
	if (MAIN_TYPE_ADMIN && !in_array($f_name, array("php_func"))) {
		return false;
	}
	$f_name = "_fast_".$f_name;
	$fwork_fast_path = YF_PATH."share/fast_init/";
	$path = $fwork_fast_path."func_".$f_name.".php";
	include ($path);
	return $f_name();
}

// Switch between fast actions (place your custom code below):
//----------------------------
// Fast display tooltip contents
if ($_GET["object"] == "help" && $_GET["action"] == "show_tip") {

	$done = _call_fast_func("tooltip");

// Fast php function (specially for threaded exec) ONLY FOR ADMIN!
} elseif ($_GET["object"] == "dynamic" && $_GET['action'] == "php_func" && MAIN_TYPE_ADMIN) {

	$done = _call_fast_func("php_func");

// Fast dynamic image
} elseif ($_GET["object"] == "dynamic" && $_GET['action'] == "image") {

	$done = _call_fast_func("dynamic_image");

// Fast low forum
} elseif ($_GET["object"] == "forum" && $_GET['action'] == "low") {

	$done = _call_fast_func("forum_low");

// Fast process search autocomplete
} elseif ($_GET["object"] == "search" && $_GET['action'] == "autocomplete") {

	$done = _call_fast_func("search_autocomplete");

// Fast process output cache
} elseif (main()->OUTPUT_CACHING && empty($_COOKIE["member_id"])) {

	$done = _call_fast_func("output_cache");

// Fast process RSS export
} elseif ($_GET["object"] == "category" && in_array($_GET['action'], array("rss_for_cat", "rss_for_city"))) {

	$done = _call_fast_func("rss_export");
}
//----------------------------
// Finish fast init
if ($done) {
	if (module_conf('tpl', 'LOG_EXEC_INFO')) {
		_call_fast_func("log_exec");
	}
	if (DEBUG_MODE && !main()->_no_fast_init_debug) {
		$body .= "<hr>DEBUG INFO:\r\n";
		$body .= "<br />exec time: <b>". round(microtime(true) - main()->_time_start, 5)."</b> sec";
		echo $body;
	}
	die(); // Required if success to stop execution
}
