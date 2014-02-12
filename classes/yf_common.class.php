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
		$themes = array('amelia','cerulean','cosmo','cyborg','flatly','journal','readable','simplex','slate','spacelab','spruce','superhero','united');
		if (conf('css_framework') == 'bs3') {
			$themes[] = 'yeti';
		}
		$themes[] = 'bootstrap';
		return $themes;
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
	function divide_pages ($input_data = '', $path = '', $type = 'blocks', $records_on_page = 0, $num_records = 0, $TPLS_PATH = '', $add_get_vars = 1, $extra = array()) {
		// Override default method for input array
		$method = is_array($input_data) ? 'go_with_array' : 'go';
		return _class('divide_pages', 'classes/common/')->$method($input_data, $path, $type, $records_on_page, $num_records, $TPLS_PATH, $add_get_vars, $extra);
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function send_mail ($email_from, $name_from = '', $email_to = '', $name_to = '', $subject = '', $text = '', $html = '', $attaches = array(), $charset = '', $pear_mailer_backend = 'smtp', $force_mta_opts = array(), $priority = 3) {
		return _class('send_mail', 'classes/common/')->send($email_from, $name_from, $email_to, $name_to, $subject, $text, $html, $attaches, $charset, $pear_mailer_backend, $force_mta_opts, $priority);
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
		return pathinfo($file_path, PATHINFO_EXTENSION);
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
		return _class('validate')->_email_verify($email, $check_mx, $check_by_smtp, $check_blacklists);
	}

	/**
	* Verify url
	*/
	function url_verify ($url = '', $absolute = false) {
		return _class('validate')->_url_verify($url, $absolute);
	}

	/**
	* Verify url using remote call
	*/
	function _validate_url_by_http($url) {
		return _class('validate')->_validate_url_by_http($url);
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
					if (is_array($v2)) {
						continue;
					}
					$string .= '&'.urlencode($name).'['.urlencode($k2).']='.urlencode($v2);
				}
			} else {
				$string .= '&'.urlencode($name).'='.urlencode($value);
			}
		}
		$this->_get_vars_cache = $string;
		return $string;
	}

	/**
	* Make thumbnail using best available method
	*/
	function make_thumb($source_file_path = '', $dest_file_path = '', $LIMIT_X = -1, $LIMIT_Y = -1, $watermark_path = '', $ext = '') {
		return _class('make_thumb', 'classes/common/')->go($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $watermark_path, $ext);
	} 

	/**
	* Do upload image
	*/
	function upload_image($new_file_path, $name_in_form = 'image', $max_image_size = 0, $is_local = false) {
		return _class('upload_image', 'classes/common/')->go($new_file_path, $name_in_form, $max_image_size, $is_local);
	}
	
	/**
	* Do multi upload image
	*/
	function multi_upload_image($new_file_path, $k , $name_in_form = 'image', $max_image_size = 0, $is_local = false) {
		return _class('multi_upload_image', 'classes/common/')->go($new_file_path, $k, $name_in_form, $max_image_size, $is_local);
	} 
	
	/**
	* Do crop image
	*/
	function crop_image($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top) {
		return _class('image_manip', 'classes/common/')->crop($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $pos_left, $pos_top);
	} 

	/**
	* Do upload archive file (zip, rar, tar accepted)
	*/
	function upload_archive($new_file_path, $name_in_form = 'archive') {
		return _class('upload_archive', 'classes/common/')->go($new_file_path, $name_in_form);
	}

	/**
	* Create simple table with debug info
	*/
	function show_debug_info() {
		return _class('debug_info', 'classes/common/')->go();
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
	function get_ip ($check_type = 'force') {
		return _class('client_utils', 'classes/common/')->_get_ip($check_type);
	}

	/**
	* Show print version of the given page
	*/
	function print_page ($text = '') {
		return _class('print_page', 'classes/common/')->go($text);
	}

	/**
	* Send given text to a desired email address
	*/
	function email_page ($text = '') {
		return _class('email_page', 'classes/common/')->go($text);
	}

	/**
	* Create PDF 'on the fly' from the given content
	*/
	function pdf_page ($text = '', $name = '', $destination = 'I') {
		return _class('pdf_page', 'classes/common/')->go($text, $name, $destination);
	}

	/**
	* Create Alphabet search criteria.Make alphabet html and query limit for selected chars
	*/
	function make_alphabet($url, &$chars, $get_var_name = 'id', $q_var = 'id') {
		return _class('make_alphabet', 'classes/common/')->go($url, $chars, $get_var_name, $q_var);
	}

	/**
	* Alias
	*/
	function log_exec () {
		return _class('logs')->log_exec();
	}

	/**
	* Create RSS 'on the fly' from the given content
	*/
	function rss_page ($data = '', $params = array()) {
		return _class('rss_data', 'classes/common/')->show_rss_page($data, $params);
	}

	/**
	* Get data from RSS feeds and return it as array
	*/
	function fetch_rss ($params = array()) {
		return _class('rss_data', 'classes/common/')->fetch_data($params);
	}

	/**
	* Show empty page (useful for popup windows, etc)
	*/
	function show_empty_page ($text = '', $params = array()) {
		return _class('empty_page', 'classes/common/')->_show($text, $params);
	}

	/**
	* Try to add activity points
	*/
	function _add_activity_points ($user_id = 0, $task_name = '', $action_value = '', $record_id = 0) {
		return module_safe('activity')->_auto_add_points($user_id, $task_name, $action_value, $record_id);
	}

	/**
	* Try to remove activity points
	*/
	function _remove_activity_points ($user_id = 0, $task_name = '', $record_id = 0) {
		return module_safe('activity')->_auto_remove_points($user_id, $task_name, $record_id);
	}

	/**
	* Upload given file to remote server from this server
	*/
	function upload_file ($path_tmp = '', $new_dir = '', $new_file = '') {
		return _class('remote_files', 'classes/common/')->do_upload($path_tmp, $new_dir, $new_file);
	}

	/**
	* Delete uploaded file
	*/
	function delete_uploaded_file ($path_to = '') {
		return _class('remote_files', 'classes/common/')->do_delete($path_to);
	}

	/**
	* Remote file last-modification time
	*/
	function filemtime_remote ($path_to = '') {
		return _class('remote_files', 'classes/common/')->filemtime_remote($path_to);
	}

	/**
	* Check if file exists
	*/
	function file_is_exists ($path_to = '') {
		return _class('remote_files', 'classes/common/')->file_is_exists($path_to);
	}

	/**
	* Get remote file using CURL extension
	*/
	function remote_file_size($page_url = '') {
		return _class('remote_files', 'classes/common/')->remote_file_size($page_url);
	}

	/**
	* Get remote file using CURL extension
	*/
	function get_remote_page($page_url = '', $cache_ttl = -1, $options = array(), &$requests_info = array()) {
		return _class('remote_files', 'classes/common/')->get_remote_page($page_url, $cache_ttl, $options, $requests_info);
	}

	/**
	* Get several remote files at one time
	*/
	function multi_request($page_urls = array(), $options = array(), &$requests_info = array()) {
		return _class('remote_files', 'classes/common/')->_multi_request($page_urls, $options, $requests_info);
	}

	/**
	* 'Safe' multi_request, which splits inpu array into smaller chunks to prevent server breaking
	*/
	function multi_request_safe($page_urls = array(), $options = array(), $chunk_size = 50) {
		return _class('remote_files', 'classes/common/')->multi_request_safe($page_urls, $options, $chunk_size);
	}

	/**
	* Get several remote files sizes
	*/
	function multi_file_size($page_urls, $options = array(), $max_threads = 50) {
		return _class('remote_files', 'classes/common/')->multi_file_size($page_urls, $options, $max_threads);
	}

	/**
	* Check if user is banned
	*/
	function check_user_ban ($info = array(), $user_info = array()) {
		return _class('user_ban', 'classes/common/')->_check($info, $user_info);
	}

	/**
	* Check if user is banned
	*/
	function get_browser_info () {
		return _class('client_utils', 'classes/common/')->_get_browser_info();
	}

	/**
	* Format execution time
	*/
	function _format_time_value($value = '', $round_to = 4) {
		if (empty($value)) {
			$value = 0.0001;
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
		return _class('redirect', 'classes/common/')->_go($location, $rewrite, $redirect_type, $text, $ttl);
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
	* Get file using HTTP request (grabbed from drupal 5.1)
	*/
	function http_request ($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
		return _class('remote_files', 'classes/common/')->http_request($url, $headers, $method, $data, $retry);
	}

	/**
	* Get file using HTTP request (grabbed from drupal 5.1)
	*/
	function get_whois_info ($url, $server = '') {
		return _class('other_common', 'classes/common/')->get_whois_info ($url, $server);
	}

	/**
	* Get geo info by IP from db
	*/
	function _get_geo_data_from_db ($cur_ip = '') {
		return _class('other_common', 'classes/common/')->_get_geo_data_from_db ($cur_ip);
	}

	/**
	* Get geo info by IP from db
	*/
	function _is_ip_to_skip ($cur_ip = '') {
		return _class('other_common', 'classes/common/')->_is_ip_to_skip ($cur_ip);
	}

	/**
	* Check if given IP matches given CIDR
	*/
	function _is_ip_in_cidr($iptocheck, $CIDR) {
		return _class('other_common', 'classes/common/')->_is_ip_in_cidr($iptocheck, $CIDR);
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
		return _class('check_multi_accounts', 'classes/common/')->_check($target_user_id, $source_user_id);
	}

	/**
	* Adaptively split large text into smaller parts by token with part size limit
	*/
	function _my_split ($text = '', $split_token = '', $split_length = 0) {
		return _class('other_common', 'classes/common/')->_my_split ($text, $split_token, $split_length);
	}

	/**
	* Get user info(s) by id(s)
	*/
	function user($user_id, $fields = 'full', $params = '', $return_sql = false) {
		return _class('user_data', 'classes/common/')->_user($user_id, $fields, $params, $return_sql);
	}

	/**
	* Update given user info by id
	*/
	function update_user($user_id, $data = array(), $params = '', $return_sql = false) {
		return _class('user_data', 'classes/common/')->_update_user($user_id, $data, $params, $return_sql);
	}

	/**
	* Search user(s) info by params
	*/
	function search_user($params = array(), $fields = array(), $return_sql = false) {
		return _class('user_data', 'classes/common/')->_search_user($params, $fields, $return_sql);
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
		return _class('utf8_clean', 'classes/common/')->_unaccent($text, $case);
	}

	/**
	* Create translit from Russian or Ukrainian text
	*/
	function make_translit($string, $from_encoding = '') {
		if (empty($from_encoding)) {
			$from_encoding = $this->TRANSLIT_FROM;
		}
		return _class('translit', 'classes/common/')->make($string);
	}

	/**
	* Cut BB Codes from the given text
	*/
	function _cut_bb_codes ($body = '') {
		return preg_replace('/\[[^\]]+\]/ims', '', $body);
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
		return _class('other_common', 'classes/common/')->_create_cloud($cloud_data, $params);
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
		return _class('intrusion_detection', 'classes/common/')->check();
	}

	/**
	* Parse text using jevix
	*/
	function jevix_parse($text = '', $params = array()) {
		return _class('other_common', 'classes/common/')->jevix_parse($text, $params);
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
		return _class('related_content', 'classes/common/')->_process($params);
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
		$url = preg_replace('/[_]{2,}/', '_', $url);
		$url = trim(trim(trim($url), '_'));

		$url = common()->make_translit($url, $from_encoding);

		$url = preg_replace('/[_]{2,}/', '_', $url);
		$url = strtolower(preg_replace('/[^a-z0-9_-]+/i', '', $url));
		$url = trim(trim(trim($url), '_'));
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
		return _class('url_to_absolute', 'classes/common/')->url_to_absolute($base_url, $relative_url);
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
		return _class('utf8_clean', 'classes/common/')->_do($text, $params);
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
		return _class('graph', 'classes/common/')->graph($data, $params);
	}
	
	/**
	* 
	*/
	function graph_bar ($data, $params = '') {
		return _class('graph', 'classes/common/')->graph_bar($data, $params);
	}
	
	/**
	* 
	*/
	function graph_pie ($data, $params = '') {
		return _class('graph', 'classes/common/')->graph_pie($data, $params);
	}

	/**
	* Localize current piece of data
	*/
	function l($name = '', $data = '', $lang = '') {
		return _class('l10n')->$name($data, $lang);
	}

	/**
	* new method checking for spider by ip address (database from http://www.iplists.com/)
	*/
	function _is_spider ($ip = '', $ua = '') {
		return _class('spider_detection', 'classes/common/')->_is_spider ($ip, $ua);
	}

	/**
	* Searches given URL for known search engines hosts
	* @return string name of the found search engine
	*/
	function is_search_engine_url ($url = '') {
		return _class('spider_detection', 'classes/common/')->is_search_engine_url ($url);
	}

	/**
	* Return SQL part for detecting search engine ips
	*/
	function get_spiders_ips_sql ($field_name = 'ip') {
		return _class('spider_detection', 'classes/common/')->get_spiders_ips_sql ($field_name);
	}

	/**
	* Get country by IP address using maxmind API (http://geolite.maxmind.com/download/geoip/api/php/)
	* @return 2-byte $country_code (uppercased) or false if something wrong
	*/
	function _get_country_by_ip ($ip = '') {
		return _class('other_common', 'classes/common/')->_get_country_by_ip ($ip);
	}

	/**
	* Converter between well-known currencies
	*/
	function _currency_convert ($number = 0, $c_from = '', $c_to = '') {
		return _class('other_common', 'classes/common/')->_currency_convert ($number, $c_from, $c_to);
	}

	/**
	* Threaded execution of the given object/action
	*/
	function threaded_exec($object, $action = 'show', $threads_params = array(), $max_threads = 10) {
		return _class('threads')->threaded_exec($object, $action, $threads_params, $max_threads);
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
		return _class('sphinxsearch')->query($sql, $need_meta);
	}

	/**
	*/
	function sphinx_escape_string ($string) {
		return _class('sphinxsearch')->escape_string($string);
	}

	/**
	*/
	function show_left_filter(){
		$obj = module_safe($_GET['object']);
		$method = '_show_filter';
		if (method_exists($obj, $method) && !(isset($obj->USE_FILTER) && !$obj->USE_FILTER)) {
			return $obj->$method();
		}	
	}
	
	/**
	*/
	function show_side_column_hooked(){
		$obj = module_safe($_GET['object']);
		$method = '_hook_side_column';
		if (method_exists($obj, $method)) {
			return $obj->$method();
		}	
	}

	/**
	*/
	function admin_wall_add($data = array()) {
		return _class('common_admin')->admin_wall_add($data);
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
			'object_id'	=> isset($data['object_id']) ? $data['object_id'] : (isset($data[2]) ? $data[2] : (isset($_GET['id']) ? $_GET['id'] : '')),
			'object'	=> isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
			'action'	=> isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
			'important'	=> isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
			'old_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
			'new_data'	=> json_encode(isset($data['new_data']) ? $data['new_data'] : (isset($data[7]) ? $data[7] : '')),
			'add_date'	=> date('Y-m-d H:i:s'),
			'server_id'	=> (int)main()->SERVER_ID,
			'site_id'	=> (int)main()->SITE_ID,
			'read'		=> isset($data['read']) ? $data['read'] : 0,
			'type'      => isset($data['type']) ? $data['type'] : '',
		)));
	}

	/**
	*/
	function date_picker($name, $cur_date = '') {
		return _class('html_controls')->date_picker($name, $cur_date);
	}

	/**
	*/
	function shop_get_images($product_id) {
		return module('shop')->_get_images($product_id);
	}

	/**
	*/
	function shop_generate_image_name($product_id, $image_id, $media = false){
		return module('shop')->_generate_image_name($product_id, $image_id, $media);
	}

	/**
	*/
	function rar_extract($archive_name, $EXTRACT_PATH){
		$rar = rar_open($archive_name);
		$list = rar_list($rar);
		foreach($list as $file) {
			$file = explode("\"",$file); 
		    $entry = rar_entry_get($rar, $file[1]);
		    $entry->extract($EXTRACT_PATH); 
		}
		rar_close($rar);
	}

	/**
	*/
	function zip_extract($archive_name, $EXTRACT_PATH){
		$zip = new ZipArchive;
		$res = $zip->open($archive_name);
		if ($res === TRUE) {
		    $zip->extractTo($EXTRACT_PATH);
			$zip->close();
		}
	}

	/**
	 * Returns the sum in words (for money)
	 */
	function num2str($num) {
	    $nul='ноль';
    	$ten=array(
        	array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
	        array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
    	);
	    $a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
    	$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
	    $hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
    	$unit=array( // Units
	        array('копейка' ,'копейки' ,'копеек',	 1),
    	    array('гривна'   ,'гривни'   ,'гривен'    ,0),
        	array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
	        array('миллион' ,'миллиона','миллионов' ,0),
    	    array('миллиард','милиарда','миллиардов',0),
    	);
    	list($rub,$kop) = explode(',',sprintf("%015.2f", floatval($num)));
	    $out = array();
	    if (intval($rub)>0) {
    	    foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
	            if (!intval($v)) continue;
    	        $uk = sizeof($unit)-$uk-1; // unit key
        	    $gender = $unit[$uk][3];
            	list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
	            // mega-logic
    	        $out[] = $hundred[$i1]; # 1xx-9xx
        	    if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
            	else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
	            // units without rub & kop
    	        if ($uk>1) $out[]= $this->morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
        	} //foreach
	    }
    	else $out[] = $nul;
	    $out[] = $this->morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
    	$out[] = $kop.' '.$this->morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
	    return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
	}

	/**
	 * Bow word form
	 */
	function morph($n, $f1, $f2, $f5) {
    	$n = abs(intval($n)) % 100;
	    if ($n>10 && $n<20) return $f5;
    	$n = $n % 10;
	    if ($n>1 && $n<5) return $f2;
    	if ($n==1) return $f1;
	    return $f5;
	}

	/**
	*/
	function message_success($text = '') {
		return $this->add_message($text, 'success', $key);
	}

	/**
	*/
	function message_info($text = '') {
		return $this->add_message($text, 'info', $key);
	}

	/**
	*/
	function message_warning($text = '') {
		return $this->add_message($text, 'warning', $key);
	}

	/**
	*/
	function message_error($text = '') {
		return $this->add_message($text, 'error', $key);
	}

	/**
	*/
	function add_message($text = '', $level = 'info', $key = '') {
		if (!strlen($text)) {
			return false;
		}
		if ($key) {
			$_SESSION['permanent'][$level][$key] = $text;
		} else {
			$_SESSION['permanent'][$level][] = $text;
		}
		return true;
	}

	/**
	*/
	function show_messages() {
		if (empty($_SESSION['permanent'])) {
			return false;
		}
		$body = array();
		$level_to_style = array(
			'info'		=> 'alert alert-info',
			'success'	=> 'alert alert-success',
			'warning'	=> 'alert alert-warning',
			'error'		=> 'alert alert-error alert-danger',
		);
		foreach ((array)$level_to_style as $level => $css_style) {
			$messages = $_SESSION['permanent'][$level];
			if (!is_array($messages) || !count($messages)) {
				continue;
			}
			$body[] = '<div class="'.$css_style.'"><button type="button" class="close" data-dismiss="alert">×</button>'.implode('<br />'.PHP_EOL, t($messages)).'</div>';
		}
		unset($_SESSION['permanent']);
		return implode(PHP_EOL, $body);
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
	function _error_exists ($error_key = '') {
		if (!empty($error_key)) {
			return (bool)$this->USER_ERRORS[$error_key];
		}
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
			_class('user_errors', 'classes/common/')->_track_error(implode(PHP_EOL, (array)$this->USER_ERRORS));
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
}
