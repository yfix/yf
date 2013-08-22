<?php

///////////////////////////////////
// Aliases for often used methods
///////////////////////////////////

if (!function_exists('_class')) {
	function _class($class_name, $custom_path = "classes/", $params = "") { if (isset($GLOBALS['modules'][$class_name])) { return $GLOBALS['modules'][$class_name];	}; return main()->init_class($class_name, $custom_path, $params); }
}
// example: module("test")->test_stpls();
if (!function_exists('module')) {
	function module($class_name, $params = "") { if (isset($GLOBALS['modules'][$class_name])) { return $GLOBALS['modules'][$class_name]; }; return main()->init_class($class_name, "", $params); }
}
// Required to catch missing methods of the shortcut functions objects
// Only one class from functions listed below
if (!class_exists('my_missing_method_handler')) {
	class my_missing_method_handler {
		function __construct($o_name) { $this->_o_name = $o_name; }
		function __call($name, $arguments) { trigger_error($this->_o_name.'(): missing object method: '.$name, E_USER_WARNING); return false; }
	}
}
// example: main()->init_class("test")
if (!function_exists('main')) {
	function main() { return is_object($GLOBALS['main']) ? $GLOBALS['main'] : new my_missing_method_handler(__FUNCTION__); }
}
// example: tpl()->parse("example", array())
if (!function_exists('tpl')) {
	function tpl() { return is_object($GLOBALS['tpl']) ? $GLOBALS['tpl'] : new my_missing_method_handler(__FUNCTION__); }
}
// example: common()->send_mail()
if (!function_exists('common')) {
	function common() { return is_object($GLOBALS['common']) ? $GLOBALS['common'] : new my_missing_method_handler(__FUNCTION__); }
}
// example: cache()->put()
if (!function_exists('cache')) {
	function cache() { return is_object($GLOBALS['sys_cache']) ? $GLOBALS['sys_cache'] : new my_missing_method_handler(__FUNCTION__); }
}
// example: db()->query()
// exampleof getting real table name: db("user") should return DB_PREFIX."user" value;
if (!function_exists('db')) {
	function db($tbl_name = "") {
		if (!is_object($GLOBALS['db'])) { return $tbl_name ? $tbl_name : new my_missing_method_handler(__FUNCTION__); }
		return $tbl_name ? $GLOBALS['db']->_real_name($tbl_name) : $GLOBALS['db'];
	}
}
if (!function_exists('db_master')) {
	function db_master($tbl_name = "") { return db($tbl_name); }
}
if (!function_exists('db_slave')) {
	function db_slave($tbl_name = "") { return db($tbl_name); }
}
// example: load("home_page", "framework")
if (!function_exists('load')) {
	function load($class_name, $force_storage = "", $custom_path = "") { return main()->load_class_file($class_name, $custom_path, $force_storage); }
}
if (!function_exists("_force_get_url")) {
	function _force_get_url($params = array(), $host = "", $url_str = "") { return module("rewrite")->_force_get_url($params, $host, $url_str); }
}
if (!function_exists("_generate_url")) {
	function _generate_url($params = array(), $host = "") { return module("rewrite")->_generate_url($params, $host); }
}
if (!function_exists("form")) {
	function form($replace = array(), $params = array()) { $form = clone _class("form2"); return $form->chained_wrapper($replace, $params); }
}
if (!function_exists("form2")) {
	function form2($replace = array(), $params = array()) { $form = clone _class("form2"); return $form->chained_wrapper($replace, $params); }
}
if (!function_exists("table")) {
	function table($data = array(), $params = array()) { $table = clone _class("table2"); return $table->chained_wrapper($data, $params); }
}
if (!function_exists("table2")) {
	function table2($data = array(), $params = array()) { $table = clone _class("table2"); return $table->chained_wrapper($data, $params); }
}
if (!function_exists("getmicrotime")) {
	function getmicrotime() { return microtime(true); }
}
// Alias for "t()"
if (!function_exists("translate")) {
	function translate ($string, $args = 0, $lang = "") { return t($string, $args, $lang); }
}
// Alias for "t()"
if (!function_exists("i18n")) {
	function i18n ($string, $args = 0, $lang = "") { return t($string, $args, $lang); }
}
// Redirect using JS
if (!function_exists("js_redirect")) {
	function js_redirect ($location, $rewrite = true, $text = "", $ttl = 0) { return common()->redirect($location, $rewrite, "js", $text, $ttl); }
}
// Redirect using Meta tags
if (!function_exists("redirect")) {
	function redirect ($location, $rewrite = false, $text = "", $ttl = 3) {	return common()->redirect($location, $rewrite, "html", $text, $ttl); }
}
if (!function_exists('_e')) {
	function _e($text = "", $clear_error = true) { return common()->_show_error_message($text, $clear_error); }
}
if (!function_exists('_re')) {
	function _re($text = "", $error_key = "") { return common()->_raise_error($text, $error_key); }
}
if (!function_exists('_ee')) {
	function _ee() { return common()->_error_exists(); }
}
if (!function_exists("user")) {
	function user($user_id, $fields = "full", $params = "", $return_sql = false) { $_common = common(); return is_object($_common) && method_exists($_common, "user") ? $_common->user($user_id, $fields, $params, $return_sql) : false; }
}
if (!function_exists("update_user")) {
	function update_user($user_id, $data = array(), $params = "", $return_sql = false) { $_common = common(); return is_object($_common) && method_exists($_common, "update_user") ? $_common->update_user($user_id, $data, $params, $return_sql) : false; }
}
if (!function_exists("search_user")) {
	function search_user($params = array(), $fields = array(), $return_sql = false) { $_common = common(); return is_object($_common) && method_exists($_common, "user") ? $_common->search_user($params, $fields, $return_sql) : false; }
}
if (!function_exists('_truncate')) {
	function _truncate($string, $len, $wordsafe = false, $dots = false) { return _class("unicode_funcs", "classes/")->truncate_utf8($string, $len, $wordsafe, $dots); }
}
if (!function_exists('_strlen')) {
	function _strlen($text) { return _class("unicode_funcs", "classes/")->strlen($text); }
}
if (!function_exists('_strtoupper')) {
	function _strtoupper($text) { return _class("unicode_funcs", "classes/")->strtoupper($text); }
}
if (!function_exists('_strtolower')) {
	function _strtolower($text) { return _class("unicode_funcs", "classes/")->strtolower($text); }
}
if (!function_exists('_ucfirst')) {
	function _ucfirst($text) { return _class("unicode_funcs", "classes/")->ucfirst($text); }
}
if (!function_exists('_ucwords')) {
	function _ucwords($text) { return _class("unicode_funcs", "classes/")->ucwords($text); }
}
if (!function_exists('_substr')) {
	function _substr($text, $start, $length = NULL) { return _class("unicode_funcs", "classes/")->substr($text, $start, $length); }
}
if (!function_exists('_wordwrap')) {
	function _wordwrap($string, $length = 75, $break = "\n", $cut = false) { return _class("unicode_funcs", "classes/")->wordwrap($string, $length, $break, $cut); }
}
if (!function_exists('_check_rights')) {
	function _check_rights ($methods) { return method_exists($GLOBALS["main"], "_check_rights") ? $GLOBALS["main"]->_check_rights($methods) : true; }
}
// Execute command on remote server using SSH
if (!function_exists('_ssh_exec')) {
	function _ssh_exec ($server_info = array(), $cmd = "") { return _class("ssh", "classes/")->exec($server_info, $cmd); }
}
if (!function_exists('_add_get')) {
	function _add_get ($add_skip = array()) { return common()->add_get_vars($add_skip); }
}
// Localize current piece of data
if (!function_exists('l')) {
	function l($name = "", $data = "", $lang = "") { return common()->l($name, $data, $lang); }
}
// shortcut for $db_driver_real_escape_string()
if (!function_exists('_es')) {
	function _es($text = "") { return db()->es($text); }
}
if (!function_exists('db_query')) {
	function &db_query($sql = "") { return db()->query($sql); }
}
if (!function_exists('db_fetch')) {
	function db_fetch($resource = null, $use_cache = true) { return db()->fetch_assoc($resource, $use_cache); }
}
if (!function_exists('db_get')) {
	function db_get($sql = "", $use_cache = true) { return db()->query_fetch($sql, $use_cache); }
}
if (!function_exists('db_get_all')) {
	function db_get_all($sql = "", $key_name = null, $use_cache = true) { return db()->query_fetch_all($sql, $key_name, $use_cache); }
}
// current GMT time
if (!function_exists('gmtime')) {
	function gmtime () { return common()->gmtime(); }
}
if (!function_exists('sphinx_query')) {
	function sphinx_query ($sql, $need_meta = false) { return common()->sphinx_query($sql, $need_meta); }
}
if (!function_exists('sphinx_escape_string')) {
	function sphinx_escape_string ($string) { return common()->sphinx_escape_string($string); }
}
// Short alias for the "_prepare_html"
if (!function_exists("html")) {
	function html ($text = "", $need_strip_slashes = 1, $use_smart_function = 1) { return _prepare_html ($text, $need_strip_slashes, $use_smart_function); }
}
// Short name for "_process_url"
if (!function_exists("url")) {
	function url($url = "", $force_rewrite = false, $for_site_id = false) { return process_url($url, $force_rewrite, $for_site_id);	}
}
// Check user banned or not
if (!function_exists('_check_user_ban')) {
	function _check_user_ban ($info = array(), $user_info = array()) { return common()->check_user_ban($info, $user_info); }
}
// FirePHP shortcut in case if not exists
if (!function_exists('fb')) {
	function fb() { return false; }
}
