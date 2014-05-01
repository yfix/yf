<?php

///////////////////////////////////
// Aliases for often used methods
///////////////////////////////////

// Required to catch missing methods of the shortcut functions objects
// Only one class from functions listed below
if (!class_exists('my_missing_method_handler')) {
	class my_missing_method_handler {
		function __construct($o_name, $silent = false, $c_name = '') {
			$this->_o_name = $o_name;
			$this->_c_name = $c_name;
			$this->_silent = $silent;
		}
		function __call($name, $arguments) {
			if (!$this->_silent) {
				trigger_error($this->_o_name.'(): missing object method: '.$name. ($this->_c_name ? ' for class: '.$this->_c_name : ''), E_USER_WARNING);
				return false;
			}
		}
	}
}
// example: _class('i18n')->load_lang();
if (!function_exists('_class')) {
	function _class($class_name, $custom_path = '', $params = '', $silent = false) {
		return main()->init_class($class_name, $custom_path ?: 'classes/', $params) ?: new my_missing_method_handler(__FUNCTION__, $silent, $class_name);
	}
}
// Alias to _class() with $silent = true
// example: _class_safe('not_existing_module')->not_existing_method();
if (!function_exists('_class_safe')) {
	function _class_safe($class_name, $custom_path = '', $params = '') {
		return main()->init_class($class_name, $custom_path ?: 'classes/', $params) ?: new my_missing_method_handler(__FUNCTION__, $silent = true, $class_name);
	}
}
// example: module('test')->test_stpls();
if (!function_exists('module')) {
	function module($class_name, $params = '', $silent = false) {
		return main()->init_class($class_name, '', $params) ?: new my_missing_method_handler(__FUNCTION__, $silent, $class_name);
	}
}
// Alias to module() with $silent = true
// example: module_safe('not_existing_module')->not_existing_method();
if (!function_exists('module_safe')) {
	function module_safe($class_name, $params = '') {
		return main()->init_class($class_name, '', $params) ?: new my_missing_method_handler(__FUNCTION__, $silent = true, $class_name);
	}
}
// example: load('home_page', 'framework')
if (!function_exists('load')) {
	function load($class_name, $force_storage = '', $custom_path = '') { return main()->load_class_file($class_name, $custom_path, $force_storage); }
}
// example: main()->init_class('test')
if (!function_exists('main')) {
	function main($silent = false) { return $GLOBALS['main'] ?: new my_missing_method_handler(__FUNCTION__, $silent); }
}
// example: tpl()->parse('example', array())
if (!function_exists('tpl')) {
	function tpl($silent = false) { return $GLOBALS['tpl'] ?: new my_missing_method_handler(__FUNCTION__, $silent); }
}
// example: common()->send_mail()
if (!function_exists('common')) {
	function common($silent = false) { return $GLOBALS['common'] ?: new my_missing_method_handler(__FUNCTION__, $silent); }
}
// example: cache()->put()
if (!function_exists('cache')) {
	function cache($silent = false) { return $GLOBALS['sys_cache'] ?: new my_missing_method_handler(__FUNCTION__, $silent); }
}
if (!function_exists('cache_set')) {
	function cache_set($name, $data, $ttl = 3600, $level = '') { return cache()->set($name, $data, $ttl); }
}
if (!function_exists('cache_get')) {
	function cache_get($name, $level = '') { return cache()->get($name); }
}
if (!function_exists('cache_del')) {
	function cache_del($name, $level = '') { return cache()->del($name); }
}
// example: db()->query()
// exampleof getting real table name: db('user') should return DB_PREFIX.'user' value;
if (!function_exists('db')) {
	function db($tbl_name = '', $silent = false) {
		if (!is_object($GLOBALS['db'])) {
			return $tbl_name ?: new my_missing_method_handler(__FUNCTION__, $silent);
		}
		return $tbl_name ? $GLOBALS['db']->_real_name($tbl_name) : $GLOBALS['db'];
	}
}
if (!function_exists('db_master')) {
	function db_master($tbl_name = '') { return db($tbl_name); }
}
if (!function_exists('db_slave')) {
	function db_slave($tbl_name = '') { return db($tbl_name); }
}
if (!function_exists('t')) {
	function t($string, $args = 0, $lang = '') { return _class('i18n')->translate_string($string, $args, $lang); }
}
if (!function_exists('url')) {
	function url($params = array(), $host = '', $url_str = '') { return module('rewrite')->_force_get_url($params, $host, $url_str); }
}
if (!function_exists('_force_get_url')) {
	function _force_get_url($params = array(), $host = '', $url_str = '') { return module('rewrite')->_force_get_url($params, $host, $url_str); }
}
if (!function_exists('_generate_url')) {
	function _generate_url($params = array(), $host = '') { return module('rewrite')->_generate_url($params, $host); }
}
if (!function_exists('form')) {
	function form($replace = array(), $params = array()) { $form = clone _class('form2'); return $form->chained_wrapper($replace, $params); }
}
if (!function_exists('form_item')) {
	function form_item($replace = array(), $params = array()) {	$form = clone _class('form2'); return $form->chained_wrapper($replace, array('no_form' => 1, 'only_content' => 1, 'no_chained_mode' => 1) + (array)$params); }
}
if (!function_exists('form2')) {
	function form2($replace = array(), $params = array()) { $form = clone _class('form2'); return $form->chained_wrapper($replace, $params); }
}
if (!function_exists('table')) {
	function table($data = array(), $params = array()) { $table = clone _class('table2'); return $table->chained_wrapper($data, $params); }
}
if (!function_exists('table2')) {
	function table2($data = array(), $params = array()) { $table = clone _class('table2'); return $table->chained_wrapper($data, $params); }
}
if (!function_exists('js')) {
	function js($content, $type = 'auto', $params = array()) { return _class('core_js')->add($content, $type, $params); }
}
if (!function_exists('require_js')) {
	function require_js($content, $type = 'auto', $params = array()) { return _class('core_js')->add($content, $type, $params); }
}
if (!function_exists('css')) {
	function css($content, $type = 'auto', $params = array()) { return _class('core_css')->add($content, $type, $params); }
}
if (!function_exists('require_css')) {
	function require_css($content, $type = 'auto', $params = array()) { return _class('core_css')->add($content, $type, $params); }
}
if (!function_exists('getmicrotime')) {
	function getmicrotime() { return microtime(true); }
}
// Redirect using JS
if (!function_exists('js_redirect')) {
	function js_redirect ($location, $rewrite = true, $text = '', $ttl = 0) { return common()->redirect($location, $rewrite, 'js', $text, $ttl); }
}
// Redirect using Meta tags
if (!function_exists('redirect')) {
	function redirect ($location, $rewrite = true, $text = '', $ttl = 3) { return common()->redirect($location, $rewrite, 'html', $text, $ttl); }
}
if (!function_exists('_e')) {
	function _e($text = '', $clear_error = true) { return common()->_show_error_message($text, $clear_error); }
}
if (!function_exists('_re')) {
	function _re($text = '', $error_key = '') { return common()->_raise_error($text, $error_key); }
}
if (!function_exists('_ee')) {
	function _ee($error_key = '') { return common()->_error_exists($error_key); }
}
if (!function_exists('user')) {
	function user($user_id, $fields = 'full', $params = '', $return_sql = false) { $_common = common(); return is_object($_common) && method_exists($_common, 'user') ? $_common->user($user_id, $fields, $params, $return_sql) : false; }
}
if (!function_exists('_truncate')) {
	function _truncate($string, $len, $wordsafe = false, $dots = false) { return _class('utf8')->truncate_utf8($string, $len, $wordsafe, $dots); }
}
if (!function_exists('_strlen')) {
	function _strlen($text) { return _class('utf8')->strlen($text); }
}
if (!function_exists('_strtoupper')) {
	function _strtoupper($text) { return _class('utf8')->strtoupper($text); }
}
if (!function_exists('_strtolower')) {
	function _strtolower($text) { return _class('utf8')->strtolower($text); }
}
if (!function_exists('_ucfirst')) {
	function _ucfirst($text) { return _class('utf8')->ucfirst($text); }
}
if (!function_exists('_ucwords')) {
	function _ucwords($text) { return _class('utf8')->ucwords($text); }
}
if (!function_exists('_substr')) {
	function _substr($text, $start, $length = NULL) { return _class('utf8')->substr($text, $start, $length); }
}
if (!function_exists('_wordwrap')) {
	function _wordwrap($string, $length = 75, $break = '\n', $cut = false) { return _class('utf8')->wordwrap($string, $length, $break, $cut); }
}
if (!function_exists('_check_rights')) {
	function _check_rights ($methods) { return method_exists($GLOBALS['main'], '_check_rights') ? $GLOBALS['main']->_check_rights($methods) : true; }
}
// Execute command on remote server using SSH
if (!function_exists('_ssh_exec')) {
	function _ssh_exec ($server_info = array(), $cmd = '') { return _class('ssh')->exec($server_info, $cmd); }
}
if (!function_exists('_add_get')) {
	function _add_get ($add_skip = array()) { return common()->add_get_vars($add_skip); }
}
// Localize current piece of data
if (!function_exists('l')) {
	function l($name = '', $data = '', $lang = '') { return common()->l($name, $data, $lang); }
}
// shortcut for $db_driver_real_escape_string()
if (!function_exists('_es')) {
	function _es($text = '') { return db()->es($text); }
}
if (!function_exists('db_query')) {
	function &db_query($sql = '') { return db()->query($sql); }
}
if (!function_exists('db_fetch')) {
	function db_fetch($resource = null, $use_cache = true) { return db()->fetch_assoc($resource, $use_cache); }
}
if (!function_exists('db_get')) {
	function db_get($sql = '', $use_cache = true) { return db()->query_fetch($sql, $use_cache); }
}
if (!function_exists('db_get_all')) {
	function db_get_all($sql = '', $key_name = null, $use_cache = true) { return db()->query_fetch_all($sql, $key_name, $use_cache); }
}
if (!function_exists('db_get_one')) {
	function db_get_one($sql = '', $use_cache = true) { return db()->get_one($sql, $use_cache); }
}
// current GMT time
if (!function_exists('gmtime')) {
	function gmtime () { return common()->gmtime(); }
}
if (!function_exists('sphinx_query')) {
	function sphinx_query ($sql, $need_meta = false) { return _class('sphinxsearch')->query($sql, $need_meta); }
}
if (!function_exists('sphinx_escape_string')) {
	function sphinx_escape_string ($string) { return _class('sphinxsearch')->escape_string($string); }
}
if (!function_exists('html')) {
	function html ($params) { return _class('html')->chained_wrapper($params); }
}
if (!function_exists('validate')) {
	function validate($input = '', $rules = array()) { return _class('validate')->_input_is_valid($input, $rules); }
}
// Check user banned or not
if (!function_exists('_check_user_ban')) {
	function _check_user_ban ($info = array(), $user_info = array()) { return common()->check_user_ban($info, $user_info); }
}
// FirePHP shortcut in case if not exists
if (!function_exists('fb')) {
	function fb() { return false; }
}
