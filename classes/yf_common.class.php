<?php

/**
* Class where most common used functions are stored
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_common {

	/** @var bool Defines use or not templates for the boxes */
	public $BOXES_USE_STPL			= true;
	/** @var string Path to the boxes templates */
	public $BOXES_STPL_BASE		= 'system/common/';
	/** @var bool Store user-level errors */
	public $TRACK_USER_ERRORS		= false;
	/** @var bool Display debug info for the empty page */
	public $EMPTY_PAGE_DEBUG_INFO	= true;
	/** @var string 
	*	Default value. Cloud creates in alphabetic text order
	*	available values - 'text' or 'num'
	*	(For cloud creaion)
	*/
	public $CLOUD_ORDER = 'text';
	/** @var int Maximum fontsize for cloud (in 'em') */
	public $CLOUD_MAX_FSIZE = 2;
	/** @var int Minimum fontsize for cloud (in 'em') */
	public $CLOUD_MIN_FSIZE = 0.9;
	/** @var string Translit from encoding */
	public $TRANSLIT_FROM	= 'cp1251';
	/** @var string Required for the compatibility with old main class */
	public $MEDIA_PATH		= '';
	/** @var string Sphinx empty results logging path. Keep empty to disable. Example: /tmp/count_sphinx_empty.log */
	public $SPHINX_EMPTY_LOG_PATH = '';	

	/**
	* Constructor
	*/
	function __construct () {
		define('COMMON_LIB', 'classes/common/');
		$this->MEDIA_PATH = WEB_PATH;
		if (defined('MEDIA_PATH')) {
			$this->MEDIA_PATH = MEDIA_PATH;
		}
	}

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Form2 chained wrapper
	*/
	function form2($replace = array(), $params = array()) {
		$form = clone _class('form2');
		return $form->chained_wrapper($replace, $params);
	}

	/**
	* Table2 chained wrapper
	*/
	function table2($data = array(), $params = array()) {
		$table = clone _class('table2');
		return $table->chained_wrapper($data, $params);
	}
	
	/**
	*/
	function bs_get_avail_themes() {
		return array('amelia','cerulean','cosmo','cyborg','flatly','journal','readable','simplex','slate','spacelab','spruce','superhero','united','bootstrap');
	}

	/**
	*/
	function bs_current_theme() {
		if (MAIN_TYPE_USER) {
			$theme = 'spacelab'; // Default
		} elseif (MAIN_TYPE_ADMIN) {
			$theme = 'slate'; // Default
		}
		$conf_theme = conf('DEF_BOOTSTRAP_THEME');
		if ($conf_theme) {
			$theme = $conf_theme;
		}
		$avail_themes = $this->bs_get_avail_themes();
		if ($_COOKIE['yf_theme'] && in_array($_COOKIE['yf_theme'], $avail_themes)) {
			$theme = $_COOKIE['yf_theme'];
		}
		return $theme;
	}

	/**
	*/
	function bs_theme_html() {
		$theme = $this->bs_current_theme();
		return tpl()->parse('bs_theme_html', array('cur_theme' => $this->bs_current_theme()));
	}
	
	/**
	*/
	function bs_theme_changer() {
		return tpl()->parse('bs_theme_changer', array(
			'cur_theme' => $this->bs_current_theme(),
			'themes'	=> $this->bs_get_avail_themes(),
		));
	}
	
	/**
	* Secondary database connection
	*/
	function connect_db2() {
		if (!defined('DB_HOST2')) {
			return false;
		}
		if (!is_object($GLOBALS['db2'])) {
			$db_class_name = main()->load_class_file('db', 'classes/');
			if ($db_class_name && class_exists($db_class_name)) {
				$GLOBALS['db2'] = new $db_class_name('mysql5', 1, DB_PREFIX2);
				$GLOBALS['db2']->connect(DB_HOST2, DB_USER2, DB_PSWD2, DB_NAME2, true, defined('DB_SSL2') ? DB_SSL2 : false, defined('DB_PORT2') ? DB_PORT2 : '', defined('DB_SOCKET2') ? DB_SOCKET2 : '', defined('DB_CHARSET2') ? DB_CHARSET2 : '');
			}
		}
		return $GLOBALS['db2'];
	}

	/**
	* Secondary database connection
	*/
	function db2_connect() {
		return $this->connect_db2();
	}

	/**
	* This function generate dividing table contents per pages
	*/
	function divide_pages ($input_data = '', $path = '', $type = 'blocks', $records_on_page = 0, $num_records = 0, $TPLS_PATH = '', $add_get_vars = 1) {
		// Override default method for input array
		$method = is_array($input_data) ? 'go_with_array' : 'go';
		return _class('divide_pages', COMMON_LIB)->$method($input_data, $path, $type, $records_on_page, $num_records, $TPLS_PATH, $add_get_vars);
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function send_mail ($email_from, $name_from = '', $email_to = '', $name_to = '', $subject = '', $text = '', $html = '', $attaches = array(), $charset = '', $pear_mailer_backend = 'smtp', $force_mta_opts = array(), $priority = 3) {
		return _class('send_mail', COMMON_LIB)->send($email_from, $name_from, $email_to, $name_to, $subject, $text, $html, $attaches, $charset, $pear_mailer_backend, $force_mta_opts, $priority);
	} 

	/**
	* Quick send mail (From admin info)
	*/
	function quick_send_mail ($email_to, $subject, $html) {
		return $this->send_mail (SITE_ADMIN_EMAIL, defined('SITE_ADMIN_NAME') ? SITE_ADMIN_NAME : 'Site admin', $email_to, '', $subject, strip_tags($html), $html);
	}

	/**
	* Quick email notification to the admin (from the system)
	*/
	function send_notify_mail_to_admin ($subject, $html) {
		return $this->send_mail (SITE_ADMIN_EMAIL, SITE_NAME.' system notification', SITE_ADMIN_EMAIL, '', $subject, strip_tags($html), $html);
	}

	/**
	* This function generate select box with tree hierarhy inside
	*/
	function select_box ($name, $values = array(), $selected = '', $show_text = true, $type = 2, $add_str = '', $translate = 0, $level = 0) {
		return _class('html_controls')->select_box($name, $values, $selected, $show_text, $type, $add_str, $translate, $level);
	}

	/**
	* Generate multi-select box
	*/
	function multi_select ($name, $values = array(), $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false) {
		return _class('html_controls')->multi_select($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled);
	}

	/**
	* Alias for the multi_select
	*/
	function multi_select_box ($name, $values = array(), $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false) {
		return $this->multi_select($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled);
	}

	/**
	* Processing radio buttons
	*/
	function radio_box ($box_name, $values = array(), $selected = '', $flow_vertical = false, $type = 2, $add_str = '', $translate = 0) {
		return _class('html_controls')->radio_box($box_name, $values, $selected, $flow_vertical, $type, $add_str, $translate);
	}

	/**
	* Simple check box
	*/
	function check_box ($box_name, $values = array(), $selected = '', $add_str = '') {
		return _class('html_controls')->check_box($box_name, $values, $selected, $add_str);
	}

	/**
	* Processing many checkboxes at one time
	*/
	function multi_check_box ($box_name, $values = array(), $selected = array(), $flow_vertical = false, $type = 2, $add_str = '', $translate = 0, $name_as_array = false) {
		return _class('html_controls')->multi_check_box($box_name, $values, $selected, $flow_vertical, $type, $add_str, $translate, $name_as_array);
	}
	
	/**
	*/
	function date_box ($selected_date = '', $years = '', $name_postfix = '', $add_str = '', $order = 'ymd', $show_text = 1, $translate = 1) {
		return _class('html_controls')->date_box($selected_date, $years, $name_postfix, $add_str, $order, $show_text, $translate);
	}

	/**
	*/
	function time_box ($selected_time = '', $name_postfix = '', $add_str = '', $show_text = 1, $translate = 1) {
		return _class('html_controls')->time_box($selected_time, $name_postfix, $add_str, $show_text, $translate);
	}
	
	/**
	*/
	function date_box2 ($name = '', $selected = '', $range = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		return _class('html_controls')->date_box2($name, $selected, $range, $add_str, $show_what, $show_text, $translate);
	}

	/**
	*/
	function datetime_box2 ($name = '', $selected = '', $range = '', $add_str = '', $show_what = 'ymdhis', $show_text = 1, $translate = 1) {
		return _class('html_controls')->datetime_box2($name, $selected, $range, $add_str, $show_what, $show_text, $translate);
	}

	/**
	*/
	function time_box2 ($name = '', $selected = '', $add_str = '', $show_text = 1, $translate = 1) {
		return _class('html_controls')->time_box2($name, $selected, $add_str, $show_text, $translate);
	}

	/**
	* Format file size
	*/
	function format_file_size ($fs = 0, $digits_count = 0) {
		if ($digits_count == 0) {
			return ($fs < 1024*1024) ? round($fs/1024, 3).' Kb' : (($fs < 1024*1024*1024) ? round($fs/(1024*1024), 3).' Mb' : round($fs/(1024*1024*1024), 3).' Gb');
		} else {
			return ($fs < 1048576) ? round($fs/1024, $digits_count-strlen(round($fs/1024,0))).' Kb' : (($fs < 1073741824) ? round($fs/1048576, $digits_count-strlen(round($fs/1048576,0))).' Mb' : round($fs/1073741824, $digits_count-strlen(round($fs/1073741824,0))).' Gb');
		}
	}

	/**
	* Format time
	*/
	function format_time($timestamp, $accuracy = 'second'){
		$timestamp = intval($timestamp);
		if ($timestamp == 0){
			return 0;
		}
		$periods = array('year','month','day','hour','minute','second');
 		list($length_accur) = array_keys($periods, $accuracy);
		$lengths = array(31536000, 2592000, 86400, 3600, 60, 1);
		for ($i = 0; $timestamp > $lengths[$length_accur]; $i++) {
			$value = intval($timestamp / $lengths[$i]);
			if($value != 0) {
				$result_string .= $value.' '. $periods[$i] .($value > 1 ? 's ' : ' ');
			}
			$r = fmod($timestamp, $lengths[$i]);
			if ($r == 0) {
				break;
			}
			$timestamp = $r;			
		}
		return $result_string;
	}


	/**
	* Return file size formatted
	*/
	function get_formatted_file_size ($file_name = '', $digits_count = 0) {
		return $this->format_file_size(file_exists($file_name) ? filesize($file_name) : 0, $digits_count);
	}

	/**
	* Return file extension
	*/
	function get_file_ext ($file_path = '') {
		$_tmp = pathinfo($file_path);
		return $_tmp['extension'];
	}

	/**
	* Simple random password creator with specified length (max 32 symbols) //
	*/
	function rand_name($Length = 8) {
		return substr(md5(microtime(true).rand()), 0, $Length);
	}

	/**
	* Email verifying function
	*/
	function email_verify ($email = '', $check_mx = false, $check_by_smtp = false, $check_blacklists = false) {
		return _class('remote_files', COMMON_LIB)->_email_verify($email, $check_mx, $check_by_smtp, $check_blacklists);
	}

	/**
	* Verify url
	*/
	function url_verify ($url = '', $absolute = false) {
		return preg_match('/^(http|https):\/\/(www\.){0,1}[a-z0-9\-]+\.[a-z]{2,5}[^\s]*$/i', $url);
	}

	/**
	* Verify url using remote call
	*/
	function _validate_url_by_http($url) {
		return _class('remote_files', COMMON_LIB)->_validate_url_by_http($url);
	}

	/**
	* Add variables that came from $_GET array
	*/
	function add_get_vars($add_skip = array()) {
		// Cache it
		if (isset($this->_get_vars_cache)) {
			return $this->_get_vars_cache;
		}
		$string = '';
		$skip = array_merge(
			array('task','object','action','section','id','post_id','language'),
			(array)$add_skip
		);
		foreach ((array)$_GET as $name => $value) {
			// Skip some vars
			if (in_array($name, $skip)) {
				continue;
			}
			// Process array
			if (is_array($value)) {
				foreach ((array)$value as $k2 => $v2) {
					if (is_array($v2)) continue;
					$string .= '&'.$name.'['.$k2.']='.urlencode($v2);
				}
			} else {
				$string .= '&'.$name.'='.urlencode($value);
			}
		}
		$this->_get_vars_cache = $string;
		return $string;
	}

	/**
	* Make thumbnail using best available method
	*/
	function make_thumb($source_file_path = '', $dest_file_path = '', $LIMIT_X = -1, $LIMIT_Y = -1) {
		return _class('make_thumb', COMMON_LIB)->go($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y);
	} 

	/**
	* Do upload image
	*/
	function upload_image($new_file_path, $name_in_form = 'image', $max_image_size = 0, $is_local = false) {
		return _class('upload_image', COMMON_LIB)->go($new_file_path, $name_in_form, $max_image_size, $is_local);
	}
	
	/**
	* Do multi upload image
	*/
	function multi_upload_image($new_file_path, $k , $name_in_form = 'image', $max_image_size = 0, $is_local = false) {
		return _class('multi_upload_image', COMMON_LIB)->go($new_file_path, $k, $name_in_form, $max_image_size, $is_local);
	} 

	/**
	* Create simple table with debug info
	*/
	function show_debug_info() {
		return _class('debug_info', COMMON_LIB)->go();
	}

	/**
	* Show execution time info
	*/
	function _show_execution_time () {
		main()->_time_end = $this->_format_time_value(microtime(true) - main()->_time_start);
		return '<br><div align="center">'._ucfirst(t('page_generated_in')).' '.main()->_time_end.' '.t('seconds').', &nbsp;&nbsp;'.t('number_of_queries').' = '.intval(db()->NUM_QUERIES).'</div>'.PHP_EOL;
	}

	/**
	* Get user IP address
	*/
	function get_ip () {
		return _class('client_utils', COMMON_LIB)->_get_ip();
	}

	/**
	* Show print version of the given page
	*/
	function print_page ($text = '') {
		return _class('print_page', COMMON_LIB)->go($text);
	}

	/**
	* Send given text to a desired email address
	*/
	function email_page ($text = '') {
		return _class('email_page', COMMON_LIB)->go($text);
	}

	/**
	* Create PDF 'on the fly' from the given content
	*/
	function pdf_page ($text = '', $name = '') {
		return _class('pdf_page', COMMON_LIB)->go($text, $name);
	}

	/**
	* Raise user error
	*/
	function _raise_error ($text = '', $error_key = '') {
		$text		= t((string)$text);
		$error_key	= (string)$error_key;
		if (!$text) {
			return false;
		}
		if (!isset($this->USER_ERRORS)) {
			$this->USER_ERRORS = array();
		}
		if ($error_key) {
			$this->USER_ERRORS[$error_key] = $text;
		} else {
			$this->USER_ERRORS[] = $text;
		}
		return true;
	}

	/**
	* Chec if user error exists
	*/
	function _error_exists () {
		return isset($this->USER_ERRORS) && count($this->USER_ERRORS) ? true : false;
	}

	/**
	* Show formatted contents of user errors
	*/
	function _get_error_messages ($key = '') {
		if (!$this->USER_ERRORS) {
			return false;
		}
		return $key ? $this->USER_ERRORS[$key] : $this->USER_ERRORS;
	}

	/**
	* Show formatted contents of user errors
	*/
	function _show_error_message ($cur_error_msg = '', $clear_error = true) {
		// Prevent recursive display
		if (strlen($cur_error_msg) && false !== strpos($cur_error_msg, '<!--YF_ERROR_MESSAGE_START-->')) {
			return t($cur_error_msg);
		}
		if (!isset($this->USER_ERRORS)) {
			$this->USER_ERRORS = array();
		}
		if (strlen($cur_error_msg)) {
			$this->USER_ERRORS[] = $cur_error_msg;
		}
		foreach ((array)$this->USER_ERRORS as $error_key => $value) {
			if (empty($value)) {
				continue;
			}
			$items[$error_key] = $value;
		}
		// Try to save errors log
		if ($this->TRACK_USER_ERRORS && !empty($this->USER_ERRORS)) {
			_class('user_errors', COMMON_LIB)->_track_error(implode(PHP_EOL, (array)$this->USER_ERRORS));
		}
		// Set default value
		if ($clear_error) {
			$this->USER_ERRORS = array();
		}
		// Do not display error messages to spiders
		if (conf('IS_SPIDER')) {
			return false;
		}
		// Do not show error messages on front
		if (IS_FRONT == 1) {
//			return false;
		}
		if (empty($items)) {
			return '';
		}
		$replace = array(
			'items' => $items,
			'error' => 'error', // DO NOT REMOVE THIS!!! NEEDED TO AVOID INFINITE LOOP INSIDE TPL CLASS
		);
		return tpl()->parse('system/error_message', $replace);
	}

	/**
	* Show formatted single error message
	*/
	function _show_error_inline ($error_key = '') {
		if (empty($error_key)) {
			return false;
		}
		// Try to get error message
		$error_msg = '';
		if (isset($this->USER_ERRORS[$error_key])) {
			$error_msg = $this->USER_ERRORS[$error_key];
// TODO: need to decide if we need to do this
//			$this->USER_ERRORS[$error_key] = '';
		}
		// Last check
		if (empty($error_msg)) {
			return false;
		}
		// Prepare template
		$replace = array(
			'text'	=> $error_msg,
			'key'	=> $error_key,
		);
		return tpl()->parse('system/error_inline', $replace);
	}

	/**
	* Create Alphabet search criteria.Make alphabet html and query limit for selected chars
	*/
	function make_alphabet($url, &$chars, $get_var_name = 'id', $q_var = 'id') {
		return _class('make_alphabet', COMMON_LIB)->go($url, $chars, $get_var_name, $q_var);
	}

	/**
	* Log script execution params
	*/
	function log_exec () {
		return _class(MAIN_TYPE_ADMIN ? 'log_admin_exec' : 'log_exec', COMMON_LIB)->go();
	}

	/**
	* Create RSS 'on the fly' from the given content
	*/
	function rss_page ($data = '', $params = array()) {
		return _class('rss_data', COMMON_LIB)->show_rss_page($data, $params);
	}

	/**
	* Get data from RSS feeds and return it as array
	*/
	function fetch_rss ($params = array()) {
		return _class('rss_data', COMMON_LIB)->fetch_data($params);
	}

	/**
	* Show empty page (useful for popup windows, etc)
	*/
	function show_empty_page ($text = '', $params = array()) {
		return _class('empty_page', COMMON_LIB)->_show($text, $params);
	}

	/**
	* Try to add activity points
	*/
	function _add_activity_points ($user_id = 0, $task_name = '', $action_value = '', $record_id = 0) {
		return module('activity')->_auto_add_points($user_id, $task_name, $action_value, $record_id);
	}

	/**
	* Try to remove activity points
	*/
	function _remove_activity_points ($user_id = 0, $task_name = '', $record_id = 0) {
		return module('activity')->_auto_remove_points($user_id, $task_name, $record_id);
	}

	/**
	* Upload given file
	*/
	function upload_file ($path_tmp = '', $new_dir = '', $new_file = '') {
		return _class('remote_files', COMMON_LIB)->do_upload($path_tmp, $new_dir, $new_file);
	}

	/**
	* Delete uploaded file
	*/
	function delete_uploaded_file ($path_to = '') {
		return _class('remote_files', COMMON_LIB)->do_delete($path_to);
	}

	/**
	* Remote file last-modification time
	*/
	function filemtime_remote ($path_to = '') {
		return _class('remote_files', COMMON_LIB)->filemtime_remote($path_to);
	}

	/**
	* Check if file exists
	*/
	function file_is_exists ($path_to = '') {
		return _class('remote_files', COMMON_LIB)->file_is_exists($path_to);
	}

	/**
	* Get remote file using CURL extension
	*/
	function remote_file_size($page_url = '') {
		return _class('remote_files', COMMON_LIB)->remote_file_size($page_url);
	}

	/**
	* Get remote file using CURL extension
	*/
	function get_remote_page($page_url = '', $cache_ttl = -1, $options = array()) {
		return _class('remote_files', COMMON_LIB)->get_remote_page($page_url, $cache_ttl, $options);
	}

	/**
	* Get several remote files at one time
	*/
	function multi_request($page_urls = array(), $options = array()) {
		return _class('remote_files', COMMON_LIB)->_multi_request($page_urls, $options);
	}

	/**
	* 'Safe' multi_request, which splits inpu array into smaller chunks to prevent server breaking
	*/
	function multi_request_safe($page_urls = array(), $options = array(), $chunk_size = 50) {
		return _class('remote_files', COMMON_LIB)->multi_request_safe($page_urls, $options, $chunk_size);
	}

	/**
	* Get several remote files sizes
	*/
	function multi_file_size($page_urls, $options = array(), $max_threads = 50) {
		return _class('remote_files', COMMON_LIB)->multi_file_size($page_urls, $options, $max_threads);
	}

	/**
	* Check if user is banned
	*/
	function check_user_ban ($info = array(), $user_info = array()) {
		return _class('user_ban', COMMON_LIB)->_check($info, $user_info);
	}

	/**
	* Check if user is banned
	*/
	function get_browser_info () {
		return _class('client_utils', COMMON_LIB)->_get_browser_info();
	}

	/**
	* Format execution time
	*/
	function _format_time_value($value = '', $round_to = 4) {
		if (empty($value)) {
			$value = 0.001;
		}
		return substr(round((float)$value, $round_to), 0, $round_to + 2);
	}

	/**
	* Revert html special chars
	*/
	function unhtmlspecialchars($text = '') {
		$trans_tbl = get_html_translation_table (HTML_SPECIALCHARS);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string ,$trans_tbl);
	}

	/**
	* Do redirect user to the specified location
	*/
	function redirect($location, $rewrite = true, $redirect_type = 'js', $text = '', $ttl = 3) {
		return _class('redirect', COMMON_LIB)->_go($location, $rewrite, $redirect_type, $text, $ttl);
	}

	/**
	* Encode given address to prevent spam-bots harvesting
	*/
	function encode_email($addr = '', $as_html_link = false) {
		return _class('utils')->encode_email($addr, $as_html_link);
	}

	/**
	* Display message if server is overloaded
	* 
	*/
	function server_is_busy($msg = '') {
		$replace = array(
			'msg'	=> $msg,
		);
		return tpl()->parse('system/server_is_busy', $replace);
	}

	/**
	* Search template for the string that caused an error
	*/
	function _search_stpl_line ($class_name, $method_name, $method_params = '', $tpl_name) {
		// Search in site
		$stpl_file	= SITE_PATH. tpl()->TPL_PATH. $tpl_name;
		// Search in project
		if (!file_exists($stpl_file)) {
			$stpl_file = PROJECT_PATH. tpl()->TPL_PATH. $tpl_name;
		}
		// Search in framework
		if (!file_exists($stpl_file)) {
			$stpl_file = YF_PATH. tpl()->TPL_PATH. $tpl_name;
		}
		if (file_exists($stpl_file)) {
			$line_search = preg_grep("/\{execute\([\"']*".$class_name.','.$method_name.(!empty($method_params) ? ','.$method_params : '')."[\"']*\)\}/i", @file($stpl_file));
			return ' on line '.intval(array_shift(array_keys($line_search)) + 1);
		}
	}

	/**
	* Wrapper for translation method (for call from templates or other)
	*/
	function _translate_for_stpl ($string = '', $args_from_tpl = '', $lang = '') {
		$args = array();
		// Try to convert args
		if (is_string($args_from_tpl) && strlen($args_from_tpl)) {
			$args_from_tpl = stripslashes($args_from_tpl);
			$tmp_array = explode(';', $args_from_tpl);
			// Convert string into array
			foreach ((array)$tmp_array as $v) {
				$attrib_name = '';
				$attrib_value = '';
				if (false !== strpos($v, '=')) {
					list($attrib_name, $attrib_value) = explode('=', trim($v));
				}
				$attrib_name	= trim(str_replace(array("'",'"'), '', $attrib_name));
				$attrib_value	= trim(str_replace(array("'",'"'), '', $attrib_value));
				$args[$attrib_name] = $attrib_value;
			}
		}
		return translate($string, $args, $lang);
	}

	/**
	* Replace paths to images
	*/
	function _replace_images_paths ($string = '') {
		$web_path		= (MAIN_TYPE_USER ? $this->MEDIA_PATH : ADMIN_WEB_PATH);
		$images_path	= $web_path. tpl()->TPL_PATH. tpl()->_IMAGES_PATH;
		// Array of pairs 'match->replace' for str_replace
		$to_replace = array(
			'"images/'		=> '"'.$images_path,
			"'images/"		=> "'".$images_path,
			'"uploads/'		=> '"'.$this->MEDIA_PATH. tpl()->_UPLOADS_PATH,
			"'uploads/"		=> "'".$this->MEDIA_PATH. tpl()->_UPLOADS_PATH,
			'src="uploads/'	=> 'src="'.$web_path. tpl()->_UPLOADS_PATH,
		);
		return str_replace(array_keys($to_replace), array_values($to_replace), $string);
	}

	/**
	* Get file using HTTP request (grabbed from drupal 5.1)
	*/
	function http_request ($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
		return _class('remote_files', COMMON_LIB)->http_request($url, $headers, $method, $data, $retry);
	}

	/**
	* Get file using HTTP request (grabbed from drupal 5.1)
	*/
	function get_whois_info ($url, $server = '') {
		return _class('other_common', COMMON_LIB)->get_whois_info ($url, $server);
	}

	/**
	* Encode JSON string
	*/
	function json_encode ($string = '') {
		if (empty($string)) {
			return false;
		}
		// Use fastest PHP5+ built-in function if available
		if (function_exists('json_encode')) {
			return json_encode($string);
		}
		// Else try pure PHP implementation
		include_once YF_PATH.'libs/phpxmlrpc/lib/xmlrpc.inc';
		include_once YF_PATH.'libs/phpxmlrpc/extras/jsonrpc/jsonrpc.inc';
		if (function_exists('php_jsonrpc_encode')) {
			$value =& php_jsonrpc_encode($string);
			return $value->serialize();
		}
		return false;
	}

	/**
	* Decode JSON string
	*/
	function json_decode ($string = '') {
		if (empty($string)) {
			return false;
		}
		// Use fastest PHP5+ built-in function if available
		if (function_exists('json_decode')) {
			return json_decode($string);
		}
		// Else try pure PHP implementation
		include_once YF_PATH.'libs/phpxmlrpc/lib/xmlrpc.inc';
		include_once YF_PATH.'libs/phpxmlrpc/extras/jsonrpc/jsonrpc.inc';
		if (function_exists('php_jsonrpc_decode')) {
			$value =& php_jsonrpc_decode_json($string);
			if ($value) {
				return php_jsonrpc_decode($value);
			}
		}
		return false;
	}

	/**
	* Get geo info by IP from db
	*/
	function _get_geo_data_from_db ($cur_ip = '') {
		return _class('other_common', COMMON_LIB)->_get_geo_data_from_db ($cur_ip);
	}

	/**
	* Get geo info by IP from db
	*/
	function _is_ip_to_skip ($cur_ip = '') {
		return _class('other_common', COMMON_LIB)->_is_ip_to_skip ($cur_ip);
	}

	/**
	* Check if given IP matches given CIDR
	*/
	function _is_ip_in_cidr($iptocheck, $CIDR) {
		return _class('other_common', COMMON_LIB)->_is_ip_in_cidr($iptocheck, $CIDR);
	}

	/**
	* Check if given IP is banned
	*/
	function _ip_is_banned ($CUR_IP = '') {
		if (!$CUR_IP) {
			$CUR_IP = common()->get_ip();
		}
		if (!$CUR_IP) {
			return false;
		}
		// We allow wildcards here (example: banned_ip: 192.168.*)
		$_banned_ips_array = main()->get_data('banned_ips');
		foreach ((array)$_banned_ips_array as $_ip => $_info) {
			$IP_MATCHED = false;
			$_ip = preg_replace('/[^0-9\.\*]/', '', $_ip);
			// Check as subnetwork with wildcard
			if (false != strpos($_ip, '*')) {
				$IP_MATCHED = preg_match('#'.str_replace(array('.', '*'), array('\\.', '.*'), $_ip).'#', $CUR_IP);
			// Check as subnetwork in CIDR format
			} elseif (false != strpos($_ip, '/')) {
				$IP_MATCHED = common()->_is_ip_in_cidr($CUR_IP, $_ip);
			} else {
				$IP_MATCHED = ($CUR_IP == $_ip);
			}
			if ($IP_MATCHED) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check if given IP matches given CIDR
	*/
	function _convert_charset($text = '', $charset_from = ''/*ISO-8859-1*/, $charset_into = 'utf-8') {
		if (!strlen($text)) {
			return false;
		}
		return _class('convert_charset')->go($text, $charset_from, $charset_into);
	}

	/**
	* Check multi-accounts
	*/
	function _check_multi_accounts($target_user_id = 0, $source_user_id = 0) {
		return _class('check_multi_accounts', COMMON_LIB)->_check($target_user_id, $source_user_id);
	}

	/**
	* Adaptively split large text into smaller parts by token with part size limit
	*/
	function _my_split ($text = '', $split_token = '', $split_length = 0) {
		return _class('other_common', COMMON_LIB)->_my_split ($text, $split_token, $split_length);
	}

	/**
	* Get user info(s) by id(s)
	*/
	function user($user_id, $fields = 'full', $params = '', $return_sql = false) {
		return _class('user_data', COMMON_LIB)->_user($user_id, $fields, $params, $return_sql);
	}

	/**
	* Update given user info by id
	*/
	function update_user($user_id, $data = array(), $params = '', $return_sql = false) {
		return _class('user_data', COMMON_LIB)->_update_user($user_id, $data, $params, $return_sql);
	}

	/**
	* Search user(s) info by params
	*/
	function search_user($params = array(), $fields = array(), $return_sql = false) {
		return _class('user_data', COMMON_LIB)->_search_user($params, $fields, $return_sql);
	}

	/**
	* Check if user is ignored by second one
	*/
	function _is_ignored($target_user_id, $owner_id) {
		if (empty($target_user_id) || empty($owner_id) || $target_user_id == $owner_id) {
			return false;
		}
		return (bool)db()->query_num_rows(
			'SELECT * FROM '.db('ignore_list').' WHERE user_id='.intval($owner_id).' AND target_user_id='.intval($target_user_id)
		);
	}

	/**
	* Remove accents from symbols
	*/
	function _unaccent($text = '', $case = 0) {
		if (is_array($text)) {
			foreach ((array)$text as $k => $v) {
				$text[$k] = $this->_unaccent($v);
			}
			return $text;
		}
		if (!strlen($text)) {
			return $text;
		}
		return _class('utf8_clean', COMMON_LIB)->_unaccent($text, $case);
	}

	/**
	* Create translit from Russian or Ukrainian text
	*/
	function make_translit($string, $from_encoding = '') {
		if (empty($from_encoding)) {
			$from_encoding = $this->TRANSLIT_FROM;
		}
		return _class('translit', COMMON_LIB)->make($string);
	}

	/**
	* Cut BB Codes from the given text
	*/
	function _cut_bb_codes ($body = '') {
		return preg_replace('/\[[^\]]+\]/ims', '', $body);
	}

	/**
	* Show formatted contents of notices for user
	*/
	function show_notices ($keep = false, $force_text = '') {
		if ($force_text) {
			$this->set_notice($force_text);
		}
		$name_in_session = '_user_notices';
		$items = $_SESSION[$name_in_session];
		if (!$keep) {
			$_SESSION[$name_in_session] = array();
			unset($_SESSION[$name_in_session]);
		}
		return $items ? tpl()->parse('system/user_notices', array('items' => $items)) : '';
	}

	/**
	* Set notice to display on next page (usually after redirect)
	*/
	function set_notice ($text = '') {
		$_SESSION['_user_notices'][crc32($text)] = $text;
	}

	/**
	* Log user actions for stats
	*/
	function _log_user_action ($action_name, $member_id, $object_name = '', $object_id = 0) {
		return _class('logs')->_log_user_action($action_name, $member_id, $object_name, $object_id);
	}

	/**
	* Creates tags cloud
	* //$cloud_data - array like (key => array(text, num))
	* $cloud_data - array like (text => num)
	*/
	function _create_cloud($cloud_data = array(), $params = array()) {
		return _class('other_common', COMMON_LIB)->_create_cloud($cloud_data, $params);
	}	

	/**
	* Makes thumb of remote web page 
	* parameters: page url, filename(without extension)
	*/
	function _make_thumb_remote($url, $filename) {
		$command = '/usr/src/webthumb-1.01/webthumb "'.$url.'" | pnmcrop -black | pamcut -top 95 -right -16 -bottom -40 | pnmtojpeg > '.$filename;
		exec($command);
	}

	/**
	* Create account name of fixed length with given prefix
	*/
	function gen_account_name($id = 0, $prefix = 'u', $length = 5, $padding_char = '0') {
		if (!$id) {
			return false;
		}
		if ($length < strlen($id)){
			$length = strlen($id);
		}
		return $prefix.str_pad($id, $length, $padding_char, STR_PAD_LEFT);
	}

	/**
	* Try to detect intrusions (XSS and other hack stuff)
	*/
	function intrusion_detection() {
		return _class('intrusion_detection', COMMON_LIB)->check();
	}

	/**
	* Parse text using jevix
	*/
	function jevix_parse($text = '', $params = array()) {
		return _class('other_common', COMMON_LIB)->jevix_parse($text, $params);
	}

	/**
	* Parse text using jevix
	*/
	function text_typos($text = '', $params = array()) {
		return _class('text_typos', 'classes/')->process($text, $params);
	}

	/**
	* Search related content based on params
	*/
	function related_content($params = array()) {
		return _class('related_content', COMMON_LIB)->_process($params);
	}

	/**
	* Convert name into URL-friendly string
	*/
	function _propose_url_from_name ($name = '', $from_encoding = '') {
		if (empty($name)) {
			return '';
		}
		if (empty($from_encoding)) {
			$from_encoding = $this->TRANSLIT_FROM;
		}
		$url = str_replace(array(';',',','.',':',' ','/'), '_', $name);
		$url = str_replace('__', '_', $url);
		// Use translit
		$url = common()->make_translit($url, $from_encoding);
		// Cut spaces
		$url = strtolower(preg_replace('/\W/i', '', $url));
		return $url;
	}

	/**
	* Simple trace without dumping whole objects
	*/
	function trace() {
		$trace = array();
		foreach (debug_backtrace() as $k => $v) {
			if (!$k) {
				continue;
			}
			$v['object'] = is_object($v['object']) ? get_class($v['object']) : null;
			$trace[$k - 1] = $v;
		}
		return $trace;
	}

	/**
	* Print nice 
	*/
	function trace_string() {
		$e = new Exception();
		return implode(PHP_EOL, array_slice(explode(PHP_EOL, $e->getTraceAsString()), 1, -1));
	}

	/**
	* Convert URL to absolute form
	*/
	function url_to_absolute($base_url, $relative_url) {
		return _class('url_to_absolute', COMMON_LIB)->url_to_absolute($base_url, $relative_url);
	}
	
	/**
	* is_utf8
	*/
	function is_utf8 ($content) {
		if(!$this->_set_include_path){
			set_include_path (YF_PATH.'libs/utf8_funcs/'. PATH_SEPARATOR. get_include_path()); 
		}
		$this->_set_include_path = true;
		
		include_once 'is_utf8.php';
		return is_utf8($content);
	}
	
	/**
	* utf8_html_entity_decode
	*/
	function utf8_html_entity_decode ($content) {
		if(!$this->_set_include_path){
			set_include_path (YF_PATH.'libs/utf8_funcs/'. PATH_SEPARATOR. get_include_path()); 
		}
		$this->_set_include_path = true;
		
		include_once 'utf8_html_entity_decode.php';
		return utf8_html_entity_decode($content, true);
	}
	
	/**
	* strip_tags_smart
	*/
	function strip_tags_smart ($content) {
		if(!$this->_set_include_path){
			set_include_path (YF_PATH.'libs/utf8_funcs/'. PATH_SEPARATOR. get_include_path()); 
		}
		$this->_set_include_path = true;
		
		include_once 'strip_tags_smart.php';
		return strip_tags_smart($content);
	}
	
	
	/**
	* strip_tags_smart
	*/
	function utf8_clean ($text = '', $params = array()) {
		return _class('utf8_clean', COMMON_LIB)->_do($text, $params);
	}
	
	/**
	* current GMT time
	*/
	function gmtime () {
		return strtotime('now GMT');
	}
	
	/**
	* 
	*/
	function graph ($data, $params = '') {
		return _class('graph', COMMON_LIB)->graph($data, $params);
	}
	
	/**
	* 
	*/
	function graph_bar ($data, $params = '') {
		return _class('graph', COMMON_LIB)->graph_bar($data, $params);
	}
	
	/**
	* 
	*/
	function graph_pie ($data, $params = '') {
		return _class('graph', COMMON_LIB)->graph_pie($data, $params);
	}

	/**
	* Localize current piece of data
	*/
	function l($name = '', $data = '', $lang = '') {
		if (!isset($this->L10N)) {
			$this->L10N = main()->init_class('l10n', 'classes/');
		}
		if (!is_object($this->L10N)) {
			return '';
		}
		if (method_exists($this->L10N, $name)) {
			return $this->L10N->$name($data, $lang);
		} else {
			return $this->L10N->_get_var($name, $lang);
		}
	}

	/**
	* new method checking for spider by ip address (database from http://www.iplists.com/)
	*/
	function _is_spider ($ip = '', $ua = '') {
		return _class('spider_detection', COMMON_LIB)->_is_spider ($ip, $ua);
	}

	/**
	* Searches given URL for known search engines hosts
	* @return string name of the found search engine
	*/
	function is_search_engine_url ($url = '') {
		return _class('spider_detection', COMMON_LIB)->is_search_engine_url ($url);
	}

	/**
	* Return SQL part for detecting search engine ips
	*/
	function get_spiders_ips_sql ($field_name = 'ip') {
		return _class('spider_detection', COMMON_LIB)->get_spiders_ips_sql ($field_name);
	}

	/**
	* Get country by IP address using maxmind API (http://geolite.maxmind.com/download/geoip/api/php/)
	* @return 2-byte $country_code (uppercased) or false if something wrong
	*/
	function _get_country_by_ip ($ip = '') {
		return _class('other_common', COMMON_LIB)->_get_country_by_ip ($ip);
	}

	/**
	* Converter between well-known currencies
	*/
	function _currency_convert ($number = 0, $c_from = '', $c_to = '') {
		return _class('other_common', COMMON_LIB)->_currency_convert ($number, $c_from, $c_to);
	}

	/**
	* Threaded execution of the given object/action
	* @example: 
	*	$data_for_threads = array(
	*		array('id' => 1), 
	*		array('id' => 2),
	* 	);
	* @example: 
	*	for ($i = 0; $i < 10; $i++) {
	*		$threads[] = array('id' => $i);
	*	}
	*	print_r(common()->threaded_exec($_GET['object'], 'console', $threads), 1);
	* @example: 
	*	function console () {
	*		main()->NO_GRAPHICS = true;
	*		session_write_close();
	*		if (!main()->CONSOLE_MODE) {
	*			exit('No direct access to method allowed');
	*		}
	*		sleep(3);
	*   	$params = common()->get_console_params();
	*		echo $params['id'];
	*		exit();
	*	}
	*/
	function threaded_exec($object, $action = 'show', $threads_params = array(), $max_threads = 10) {
		$results = array();

		$threads = main()->init_class('threads', 'classes/');

		// Limit max number of parallel threads
		foreach (array_chunk($threads_params, $max_threads, true) as $chunk) {

			$ids_to_params = array();
			foreach ((array)$chunk as $param_id => $_params) {
				$thread_id = $threads->new_framework_thread($object, $action, $_params);
				$ids_to_params[$thread_id] = $param_id;
			}
			while (false !== ($result = $threads->iteration())) {
				if (!empty($result)) {
					$thread_id	= $result[0];
					$param_id	= $ids_to_params[$thread_id];
					$results[$param_id] = $result[1];
				}
			}

		}

		return $results;
	}

	/**
	* Helper to get params from command line
	*/
	function get_console_params() {
		foreach ((array)$_SERVER['argv'] as $key => $argv) {
			if ($argv == '--params' && isset($_SERVER['argv'][$key + 1])) {
				return unserialize($_SERVER['argv'][$key + 1]);
			}
		}
		return false;
	}

	/**
	* Sphinx QL query wrapper
	*/
	function sphinx_query ($sql, $need_meta = false) {
		if (empty($sql)) {
			return false;
		}
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		$time =  microtime(true);

		ini_set('mysql.connect_timeout', 2);

		$CACHE_NAME = 'SPHINX_'.md5($sql);
		$data = cache_get($CACHE_NAME);
		if ($data) {
			list($data, $meta, $warnings, $query_error) = $data;
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> $sql,
					'time'	=> microtime(true) - $time,
					'trace'	=> $trace,
					'count'	=> count($data),
					'meta'	=> $meta,
					'warnings'	=> $warnings,
					'error'	=> $query_error,
					'cached'=> 1,
				));
			}
			$GLOBALS['_SPHINX_META'] = $meta;
			return $data;
		}

	 	$host = SPHINX_HOST.':'.SPHINX_PORT;

		if (!isset($this->sphinx_connect)) {
			$time =  microtime(true);
			$this->sphinx_connect = mysql_connect($host, DB_USER, DB_PSWD, true);

			// Try to reconnect
			if(!$this->sphinx_connect){
				usleep(1000000);	// wait for 1 second
				$this->sphinx_connect = mysql_connect($host, DB_USER, DB_PSWD, true);
			}
			if(!$this->sphinx_connect){
				$query_error = mysql_error($this->sphinx_connect);
				$q_error_num = mysql_errno($this->sphinx_connect);
				conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
				trigger_error('No connection to sphinx', E_USER_WARNING);
			}
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> 'sphinx connect',
					'time'	=> microtime(true) - $time,
					'count'	=> '',
					'trace'	=> $trace,
					'meta'	=> '',
					'error'	=> $query_error,
				));
			}
		}
		$meta		= array();
		$warnings	= array();
		$results	= array();
		$query_error= '';
		$q_error_num= '';
		if ($this->sphinx_connect) {
			$Q = mysql_query($sql, $this->sphinx_connect);
			if (!$Q) {
				$query_error = mysql_error($this->sphinx_connect);
				$q_error_num = mysql_errno($this->sphinx_connect);
				// Try to execute query again in case of these errors returned:
				// 2003 - Can't connect to MySQL server on
				// 2006 - MySQL server has gone away
				// 2013 - Lost connection to MySQL server during query
				// 2020 - Got packet bigger than 'max_allowed_packet' bytes
				if (in_array($q_error_num, array(2003,2006,2013,2020))) {
					usleep(1000000);	// wait for 1 second
					$Q = mysql_query($sql, $this->sphinx_connect);
					if (!$Q) {
						$query_error = mysql_error($this->sphinx_connect);
						$q_error_num = mysql_errno($this->sphinx_connect);
					}
				}
				if ($query_error) {
					trigger_error('Sphinx error: '.$sql, E_USER_WARNING);
					conf('http_headers::X-Details', conf('http_headers::X-Details').';SE=('.$q_error_num.') '.$query_error.';');
				}
			} else {
				while ($A = mysql_fetch_assoc($Q)) {
					$results[] = $A;
				}
				// log empty results
				if (count($results) == 0 && $this->SPHINX_EMPTY_LOG_PATH != '') {
					$out = array(
						date('YmdH'),
						conf('CUR_DOMAIN_SHORT'),
						$this->_db_escape($_SERVER['HTTP_REFERER']),
						$this->_db_escape($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']),
						$this->_db_escape($_SERVER['HTTP_USER_AGENT']),			
						$this->_db_escape($sql),
					);
					file_put_contents($this->SPHINX_EMPTY_LOG_PATH, implode('#|#',$out).PHP_EOL, FILE_APPEND);
				}
			}
			if (DEBUG_MODE || $need_meta) {
				// Get query internal details
				$Q = mysql_query('SHOW META', $this->sphinx_connect);
				if (!is_bool($Q)) {
					while ($A = mysql_fetch_row($Q)) {
						$meta[$A[0]] = $A[1];
					}
				}
				$GLOBALS['_SPHINX_META'] = $meta;

				// Get query warnings
				$Q = mysql_query('SHOW WARNINGS', $this->sphinx_connect);
				if (!is_bool($Q)) {
					while ($A = mysql_fetch_row($Q)) {
						$warnings[$A[0]] = $A[1];
					}
				}
				$GLOBALS['_SPHINX_WARNINGS'] = $warnings;
			}
			if (DEBUG_MODE) {
				$debug_index = count(debug('sphinx'));
				debug('sphinx::'.$debug_index, array(
					'query'	=> $sql,
					'time'	=> microtime(true) - $time,
					'count'	=> intval(count($results)),
					'trace'	=> $trace,
					'meta'	=> $meta,
					'error'	=> $query_error,
					'warnings' => $warnings,
				));
			}
		}
		if (empty($query_error) && $this->sphinx_connect) {
			cache_set($CACHE_NAME, array($results, $meta, $warnings, $query_error), 300);
		}
		return $results;
	}
	
	/**
	*/
	function show_left_filter(){
		$obj = module($_GET['object']);
		$method = '_show_filter';
		if (method_exists($obj, $method) && !(isset($obj->USE_FILTER) && !$obj->USE_FILTER)) {
			return $obj->$method();
		}	
	}
	
	/**
	*/
	function show_side_column_hooked(){
		$obj = module($_GET['object']);
		$method = '_hook_side_column';
		if (method_exists($obj, $method)) {
			return $obj->$method();
		}	
	}

	/**
	*/
	function admin_wall_add($data = array()) {
# TODO: check this and enable
#		if (!is_array($data)) {
#			$data = func_get_args();
#		}
		return db()->insert('admin_walls', db()->es(array(
			'message'	=> isset($data['message']) ? $data['message'] : (isset($data[0]) ? $data[0] : ''),
			'object_id'	=> isset($data['object_id']) ? $data['object_id'] : (isset($data[1]) ? $data[1] : ''),
			'user_id'	=> isset($data['user_id']) ? $data['user_id'] : (isset($data[2]) ? $data[2] : main()->ADMIN_ID),
			'object'	=> isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
			'action'	=> isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
			'important'	=> isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
			'old_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
			'new_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[7]) ? $data[7] : '')),
			'add_date'	=> date('Y-m-d H:i:s'),
			'server_id'	=> (int)main()->SERVER_ID,
			'site_id'	=> (int)main()->SITE_ID,
		)));
	}

	/**
	*/
	function user_wall_add($data = array()) {
# TODO: check this and enable
#		if (!is_array($data)) {
#			$data = func_get_args();
#		}
		return db()->insert('user_walls', db()->es(array(
			'message'	=> isset($data['message']) ? $data['message'] : (isset($data[0]) ? $data[0] : ''),
			'user_id'	=> isset($data['user_id']) ? $data['user_id'] : (isset($data[1]) ? $data[1] : ''),
			'object_id'	=> isset($data['object_id']) ? $data['object_id'] : (isset($data[2]) ? $data[2] : ''),
			'object'	=> isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
			'action'	=> isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
			'important'	=> isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
			'old_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
			'new_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[7]) ? $data[7] : '')),
			'add_date'	=> date('Y-m-d H:i:s'),
			'server_id'	=> (int)main()->SERVER_ID,
			'site_id'	=> (int)main()->SITE_ID,
			'read'		=> isset($data['read']) ? $data['read'] : 0,
			'type'      => isset($data['type']) ? $data['type'] : '',
		)));
	}

	/**
	* Sphinx-related
	*/
	function sphinx_escape_string ( $string ){
		$from = array ( "\\", '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=' );
		$to   = array ( "\\\\", '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=' );
		return str_replace ( $from, $to, $string );
	}

	/**
	*
	*/
	function date_picker ($name, $cur_date = '') {
		$content = '';
		if (empty($this->date_picker_count)) {
			$content .= '
				<script src="'.WEB_PATH.'js/jquery/ui/jquery.ui.core.js"></script>
				<script src="'.WEB_PATH.'js/jquery/ui/jquery.ui.datepicker.js"></script>
				<link rel="stylesheet" href="'.WEB_PATH.'js/jquery/ui/jquery.ui.datepicker.css">
				<link rel="stylesheet" href="'.WEB_PATH.'js/jquery/ui/jquery.ui.all.css">
				<script>
					$(function() {
						$( ".datepicker" ).datepicker(
							{ dateFormat: "yy-mm-dd" }
						);
					});
				</script>
			';
		}
		$content .= '<input type="text" name="'.$name.'" class="datepicker" value="'.$cur_date.'" style="width:80px" readonly="true" />';
		$this->date_picker_count++;
		return $content;
	}
}
