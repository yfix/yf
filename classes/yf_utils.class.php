<?php

class yf_utils {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Encode given address to prevent spam-bots harvesting
	*
	*	Output: the email address as a mailto link, with each character
	*		of the address encoded as either a decimal or hex entity, in
	*		the hopes of foiling most address harvesting spam bots. E.g.:
	*
	*	  <a href='&#x6D;&#97;&#105;&#108;&#x74;&#111;:&#102;&#111;&#111;&#64;&#101;
	*		x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;'>&#102;&#111;&#111;
	*		&#64;&#101;x&#x61;&#109;&#x70;&#108;&#x65;&#x2E;&#99;&#111;&#109;</a>
	*
	* @public
	* @param	string	an email address to encode, e.g. 'foo@example.com'
	* @param	bool	switch between returning HTML link or just encode text
	* @return	string
	*/
	function encode_email($addr = '', $as_html_link = false) {
		if ($as_html_link) {
			$addr = 'mailto:' . $addr;
		}
		$length = strlen($addr);
		// leave ':' alone (to spot mailto: later)
		$addr = preg_replace_callback('/([^\:])/', function($matches) {
			$char = $matches[1];
			$r = rand(0, 100);
			// roughly 10% raw, 45% hex, 45% dec
			// '@' *must* be encoded. I insist.
			if ($r > 90 && $char != '@') {
				return $char;
			}
			if ($r < 45) {
				return '&#x'.dechex(ord($char)).';';
			}
			return '&#'.ord($char).';';
		}, $addr);
		// Convert into HTML anchor link
		if ($as_html_link) {
			$addr = '<a href="'.$addr.'">'.$addr.'</a>';
		}
		// strip the mailto: from the visible part
		$addr = preg_replace('/">.+?:/', '">', $addr);
		return $addr;
	}

	/**
	* Creates hyperlink from text
	*/
	function hyperlink(&$text) {
		// match protocol://address/path/file.extension?some=variable&another=asf%
		$text = preg_replace('/\s(([a-zA-Z]+:\/\/)([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))\s/i', ' <a href="$1">$3</a> ', $text);
		// match www.something.domain/path/file.extension?some=variable&another=asf%
		$text = preg_replace('/\s(www\.([a-z][a-z0-9_\..-]*[a-z]{2,6})([a-zA-Z0-9\/*-?&%]*))\s/i', ' <a href="http://$1">$2</a> ', $text);
		return $text;
	}

	// Show custom text message (fetch it from db)
	function show_text ($text = '') {
		$text_l = str_replace(' ', '_', strtolower($text));
		list($value) = db()->query_fetch("SELECT value AS `0` FROM ".db('texts')." WHERE name='".db()->es($text_l)."' AND active='1' AND language='".db()->es(conf('language'))."'");
		$text = strlen($value) ? stripslashes($value) : $text_l;
		return str_replace('_', ' ', $text);
	}

	// Function that formats error messages
	function error_back($where_go_back = 'javascript:history.back()', $what_to_say= 'error') {
		if (!$what_to_say == 'error' || $what_to_say == '') {
			$what_to_say = t('error');
		}
		if (!$where_go_back) {
			$where_go_back = 'javascript:history.back()';
		}
		return $Text = '<div align="center"><strong>'.$what_to_say."</strong><input type='button' class='btn btn-default' onclick=\"javascript:window.location.href='".$where_go_back."'\" value='".ucfirst(t('back'))."'></div>";
	}

	// Back link with text message
	function back($where_go_back = 'javascript:history.back()', $what_to_say = 'back') {
		if ($what_to_say == 'back' || $what_to_say == '') {
			$what_to_say = t('back');
		}
		return $Text = "<div align=\"center\"><input type='button' class='btn btn-default' onclick=\"javascript:window.location.href='".$where_go_back."'\" value='".$what_to_say."'></div>";
	}

	// Show Javascript alert
	function js_alert ($text) {
		echo "<script type='text/javascript'>alert('".str_replace(array("'", "\r", "\n"), "", $text)."')</script>";
	}

	// Simple random password creator with specified length (max 32 symbols) //
	function generate_password($Length) {
		return substr(base64_encode(md5(microtime(true))), 0, $Length);
	}

	// Process URL (making rewrite if needed)
	function process_url($url = '', $force_rewrite = false, $for_site_id = false) {
		return _class('rewrite')->_rewrite_replace_links($url, true, $force_rewrite, $for_site_id);
	}

	// Highlight given text (case-insensetive)
	function highlight($string, $search_words, $tag = 'span', $class = 's_word'){
		if(empty($string) || empty($search_words)){
			return $string;
		}
		$class = !empty($class) ? ' class="'.$class.'"' : '';
		$search_words = preg_replace('/[^\d_!?\p{L}-]/imsu', ' ', $search_words);
		$search_words = explode(' ', $search_words);
		$prepared = array();
		foreach((array)$search_words as $item){
			if (!empty($item)) {
				$prepared[strtolower($item)] = strlen($item);
			}
		}
		if (empty($prepared)) {
			return $string;
		}
		arsort($prepared);
		foreach((array)$prepared as $item => $length) {
			$replace[] = '<~>$1<~~>';
			$search[] = '/('.preg_quote($item, '/').')/iu';
		}
		$string = preg_replace($search, $replace, $string);
		$string = str_replace('<~>', '<'.$tag.$class.'>', $string);
		$string = str_replace('<~~>', '</'.$tag.'>', $string);
		return $string;
	}

	// Filter text for specified symbols
	function text_filter ($str) {
		$str = htmlspecialchars($str);
		if (defined('SITE_BAD_WORD_FILTER') && SITE_BAD_WORD_FILTER == 1) {
			$bad_words = conf('BAD_WORDS_ARRAY');
			if (is_null($bad_words)) {
				$Q = db()->query('SELECT word FROM '.db('badwords').'');
				while ($A = db()->fetch_assoc($Q)) {
					$bad_words[] = $A['word'];
				}
				conf('BAD_WORDS_ARRAY', $bad_words);
			}
			$str = str_replace($bad_words, '', $str);
		}
		return $str;
	}

	// Function to prevent creation VERY long words (without spaces inside)
	function _check_words_length ($text, $length = 0, $do_encode_email = false) {
		if (empty($length)) {
			$length = 60;
			if (SITE_MAX_WORD_LENGTH != 'SITE_MAX_WORD_LENGTH' && SITE_MAX_WORD_LENGTH != '') {
				$length = SITE_MAX_WORD_LENGTH;
			}
		}
		$source_length = strlen($text);
		if ($source_length < $length) {
			return $text;
		}
		$email_pairs = array();
		// Fast check that we do not have emails inside text
		if (false !== strpos($text, '@')) {
			// Do extract emails from text
			if (preg_match_all('/[\w-]+(\.[\w-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,5})/ims', $text, $m)) {
				foreach ((array)$m[0] as $_cur_email) {
					$cur_pair_key = '%%'.(++$_cur_number).'%%';
					$email_pairs[$_cur_email]		= ' '.$cur_pair_key.' ';
					$reverted_pairs[$cur_pair_key]	= ' '.($do_encode_email ? common()->encode_email($_cur_email) : $_cur_email).' ';
				}
				krsort($email_pairs);
				krsort($reverted_pairs);
			}
		}
		// Now we allow URLs in text
		if (preg_match_all('/(http|https|ftp|ftps):\/\/[a-z0-9%&\?_\-\=\.\/]+/ims', $text, $m)) {
			foreach ((array)$m[0] as $_cur_url) {
				$cur_pair_key = '%%'.(++$_cur_number).'%%';
				$url_pairs[$_cur_url]			= ' '.$cur_pair_key.' ';
				$reverted_pairs[$cur_pair_key]	= ' '.$_cur_url.' ';
			}
			krsort($url_pairs);
			krsort($reverted_pairs);
		}
		if (!empty($email_pairs)) {
			$text = str_replace(array_keys($email_pairs), array_values($email_pairs), $text);
		}
		if (!empty($url_pairs)) {
			$text = str_replace(array_keys($url_pairs), array_values($url_pairs), $text);
		}
		$text = wordwrap($text, intval($length), ' ', 1);
		if (!empty($reverted_pairs)) {
			$text = str_replace(array_keys($reverted_pairs), array_values($reverted_pairs), $text);
		}
		return $text;
	}

	// Prepare content to the correct output in form fields
	function _prepare_html ($text = '', $need_strip_slashes = 1, $use_smart_function = 1) {
		if (is_array($text)) {
			foreach ((array)$text as $k => $v) {
				$text[$k] = $this->_prepare_html($v);
			}
			return $text;
		}
		$have_tr = false;
		if (DEBUG_MODE && main()->INLINE_EDIT_LOCALE) {
			if (preg_match("/(<span class=['\"]{0,1}locale_tr['\"]{0,1}[^>]*?>).*?(<\/span>)/i", $text, $m)) {
				$tr_1 = $m[1];
				$tr_2 = $m[2];
				$text = substr($text, strlen($tr_1), -strlen($tr_2));
				$have_tr = true;
			}
		}
		$replace = array(
			'{'	=> '&#123;',
			'}'	=> '&#125;',
			"\\"=> '&#92;',
			'(' => '&#40;',
			')' => '&#41;',
			'?' => '&#63;',
		);
		if ($need_strip_slashes) {
			$text = stripslashes($text);
		}
		// Prepare special chars
		$text = $use_smart_function ? $this->smart_htmlspecialchars($text) : htmlspecialchars($text, ENT_QUOTES);
		if (DEBUG_MODE && $have_tr) {
			$text = $tr_1.$text.$tr_2;
		}
		return str_replace(array_keys($replace), array_values($replace), $text);
	}

	// Do filter text from unwanted sequences of symbols
	function _filter_text ($body, $length = 0) {
		return _check_words_length(preg_replace("/([^\s]+)\r\n/i", "\$1 \r\n", $body), $length);
	}

	// Get user avatar
	function _show_avatar ($user_id = 0, $user_name = '', $as_link = 0, $is_middle = 0, $only_img_src = 0, $force_link = '') {
		if (is_array($user_name)) {
			$user_info = $user_name;
			$user_name = _display_name($user_info);
		}
		$avatar_path	= _gen_dir_path($user_id, INCLUDE_PATH. SITE_AVATARS_DIR , 0, 0777). intval($user_id). ($is_middle ? '_m' : ''). '.jpg';
		$photo_src		= file_exists($avatar_path) && filesize($avatar_path) ? str_replace(INCLUDE_PATH, WEB_PATH, $avatar_path) : '';
		if ($only_img_src) {
			return !empty($photo_src) ? $photo_src : '';
		}
		$use_ajax = conf('no_ajax_here') ? 0 : 1;
		if (conf('HIGH_CPU_LOAD') == 1) {
			$use_ajax = 0;
		}
		$replace = array(
			'user_name'			=> $user_name,
			'custom_title'		=> _prepare_html(conf('avatar_custom_title')),
			'user_id'			=> $user_id,
			'photo_src'			=> $photo_src,
			'user_details_link'	=> !empty($force_link) ? process_url($force_link) : _profile_link(is_array($user_info) ? $user_info : $user_id, null, MAIN_TYPE_ADMIN ? 1 : 0),
			'as_link'			=> intval((bool) $as_link),
			'is_middle'			=> intval((bool) $is_middle),
			'no_photo_small'	=> !$is_middle && empty($photo_src),
			'no_photo_middle'	=> $is_middle && empty($photo_src),
			'use_ajax'			=> intval($use_ajax),
		);
		$body = tpl()->parse('avatar_img', $replace);
		return str_replace(array("\r","\n","\t"), '', trim($body));
	}

	// Check if user's avatar image exists
	function _avatar_exists ($user_id = 0, $is_middle = 0) {
		$avatar_path = _gen_dir_path($user_id, INCLUDE_PATH. SITE_AVATARS_DIR , 1, 0777). intval($user_id). ($is_middle ? '_m' : ''). '.jpg';
		return file_exists($avatar_path);
	}

	// Get user's age (int) from birthday date in formet 'YYYY-MM-DD'
	function _get_age_from_birth ($birth_date = '0000-00-00') {
		if (empty($birth_date) || $birth_date == '0000-00-00') {
			return false;
		}
		$tmp = explode('-', $birth_date);
		return intval(date('Y') - $tmp[0] - (strtotime(date('Y').'-'.$tmp[1].'-'.$tmp[2]) > time() ? 1 : 0));
	}

	// Display user nick name (or name before all nicks will not be transfered)
	function _display_name ($user_info = array()) {
		if (is_string($user_info)) {
			return $user_info;
		}
		return empty($user_info['display_name']) ? (empty($user_info['name']) ? $user_info['nick'] : $user_info['name']) : $user_info['display_name'];
	}

	// Display formatted date
	function _format_date ($input_date = '', $type = 'short') {
		if (!strlen($input_date)) {
			return '';
		}
		$date_short_format = conf('date_short_format');
		$date_long_format = conf('date_long_format');
		// Different date formats
		if (empty($type) || $type == 'short') {
			$date_format = !empty($date_short_format) ? $date_short_format : '%m/%d/%y';
		} elseif ($type == 'long') {
			$date_format = !empty($date_long_format) ? $date_long_format : '%m/%d/%y %H:%M';
		} elseif ($type == 'time_only') {
			$date_format = '%I:%M %p';
		} elseif ($type == 'for_profile') {
			$date_format = '%B %d, %Y';
		// Custom format. Example: %Y_%m_%d
		} elseif (!empty($type) && false !== strpos($type, '%')) {
			$date_format = $type;
		// Unknown named format fallback
		} else {
			$date_format = '%Y-%m-%d %H:%M:%S';
		}
		if (empty($input_date)) {
			return '';
#			$input_date = time();
		}
		$date_to_show = !is_numeric($input_date) ? strtotime($input_date) : intval($input_date);
		if (empty($date_to_show)) {
			return '';
		}
		$output = strftime($date_format, $date_to_show);
		// Try to catch and replace some basic dates before 1970 year
		if (empty($output) && preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $input_date, $m)) {
			if (empty($type) || in_array($type, array('short'))) {
				$r = array('%y' => $m[1], '%m'	=> $m[2], '%d' => $m[3]);
				$output = str_replace(array_keys($r), array_values($r), $date_format);
			}
		}
		return $output;
	}

	// This function adds the 'st', 'nd', 'rd' or 'th' to a given timestamp
	function _day_suffix_eng ($timestamp = 0) {
		if (empty($timestamp)) $timestamp = time();
		$day_number = gmstrftime ('%#d', $timestamp);
		$sufixes	= array ('1' => 'st', '2' => 'nd', '3' => 'rd');
		$new_suffix	= $sufixes[substr($day_number, -1)];
		return !empty($new_suffix) ? $new_suffix : 'th';
	}

	// Simple encode string
	function xsb_encode($string) {
		$temp = null;
		for ($i = 0; $i < strlen($string); $i++) {
			$temp .= chr(((ord($string[$i]) + ($i % 4)) ^ 0xFF) + ($i % 7));
		}
		return base64_encode($temp);
	}

	// Simple decode string
	function xsb_decode($string) {
		$string = base64_decode($string);
		$out = null;
		for ($i = 0; $i < strlen($string); $i++) {
			$out .= chr(((ord($string[$i]) - ($i % 7)) ^ 0xFF) - ($i % 4));
		}
		return $out;
	}

	// Add login activity
	function _add_login_activity () {
		if (MAIN_TYPE_ADMIN) {
			return false;
		}
		if (empty($_SESSION['user_id'])) {
			return false;
		}
		$RECORD_ID = conf('_log_auth_insert_id');
		// Do add activity points
		return common()->_add_activity_points($_SESSION['user_id'], 'site_login', '', $RECORD_ID);
	}

	// Prepare phone number for the internal view
	function _prepare_phone ($phone = '') {
		return preg_replace('/[^0-9]/ims', '', $phone);
	}

	/**
	* Replacement for the htmlspecialchars function
	* Performs the same function as htmlspecialchars, but leaves characters that are already escaped intact.
	*/
	function smart_htmlspecialchars($html_text = '') {
		if (!strlen($html_text)) {
			return '';
		}
		$translation_table = array(
			'"' => '&quot;',
			'&' => '&amp;',
			'\'' => '&#039;',
			'<' => '&lt;',
			'>' => '&gt;',
		);
		// Change the ampersand to translate to itself, to avoid getting &amp;
		$translation_table[ chr(38) ] = '&';
		// Perform replacements
		// Regular expression says: find an ampersand, check the text after it,
		// if the text after it is not one of the following, then replace the ampersand
		// with &amp;
		// a) any combination of up to 4 letters (upper or lower case) with at least 2 or 3 non whitespace characters, then a semicolon
		// b) a hash symbol, then between 2 and 7 digits
		// c) a hash symbol, an 'x' character, then between 2 and 7 digits
		// d) a hash symbol, an 'X' character, then between 2 and 7 digits
		return preg_replace('/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,7}|#x[0-9]{2,7}|#X[0-9]{2,7};)/', '&amp;' , strtr($html_text, $translation_table));
	}

	// Similar to array_merge_recursive but keyed-valued are always overwritten. Priority goes to the 2nd array.
	function array_replace_recursive($array_1, $array_2) {
		if (!is_array($array_1) or !is_array($array_2)) {
			return $array_2;
		}
		foreach ((array)$array_2 as $key_2 => $value_2) {
			$array_1[$key_2] = $this->array_replace_recursive(@$array_1[$key_2], $value_2);
		}
		return $array_1;
	}

	// Format given text as BB code
	function format_bbcode_text ($body = '') {
		if (empty($body)) {
			return '';
		}
		return _class('bb_codes')->_process_text($body);
	}

	// print_r for view in browser
	function printr($var, $do_not_echo = false) {
		ob_start();
		print_r($var);
		$code =  htmlentities(ob_get_clean());
		if (!$do_not_echo) {
			echo '<pre>'.$code.'</pre>';
		}
		return $code;
	}

	// Stores the specified text into debug log
	function _debug_log($text, $log_level = false) {
		if (is_array($text) || is_object($text)) {
			$text = print_r($text, 1);
		}
		$simple = false;
		if ($log_level == 'simple') {
			$simple = true;
		}
		if (!$log_level || !is_numeric($log_level)) {
			$log_level = E_NOTICE;
		}
		return _class('logs')->_save_debug_log($text, $log_level, array()/*array_shift(debug_backtrace())*/, $simple);
	}

	// fast debug function
	function d() {
		foreach( func_get_args() as $key => $value ) {
			printf( "<pre><b>variable[ %s ]</b>:\n%s</pre>", $key, var_export( $value, true ) );
		}
	}

	/**
	* Create path
	*/
	function _mkdir_m($path_to_create = '', $dir_mode = 0755, $create_index_htmls = 0, $start_folder = '') {
		if (file_exists($path_to_create)) {
			return true;
		}
		return _class('dir')->mkdir_m($path_to_create, $dir_mode, $create_index_htmls, $start_folder);
	}

	/**
	*/
	function _mklink($target, $link) {
		return _class('dir')->mklink($target, $link);
	}

	// Generate path for given id with several subfolders
	function _gen_dir_path($id, $path = '', $make = false, $dir_mode = 0755, $create_index_htmls = 1) {
		return _class('dir')->_gen_dir_path($id, $path, $make, $dir_mode, $create_index_htmls);
	}

	// Recursive function that preserves keys of merged arrays
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) {
			if (isset($a1[$k]) && is_array($a1[$k])) {
				if (is_array($a2[$k])) { 
					foreach ((array)$a2[$k] as $k2 => $v2) {
						if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) {
							$a1[$k][$k2] += $v2;
						} else {
							$a1[$k][$k2] = $v2;
						} 
					}
				} else {
					$a1[$k] += $v;
				}
			} else {
				$a1[$k] = $v;
			}
		}
		return $a1;
	}

	// Prepare text to include it inside STPL tag like {execute(...)}
	function _prepare_for_stpl_exec($source = '') {
		return preg_replace('/[^a-z0-9\-\_\s]/ims', '', $source);
	}

	// Display link to user's profile
	function _profile_link ($user_info = 0, $skip_get_array = array(), $do_add_get = true) {
		if (IS_FRONT == 1) {
			return false;
		}
		$output = '';
		if (is_array($user_info)) {
			$user_id = $user_info['id'];
		} else {
			$user_id = intval($user_info);
		}
		$output	= './?object=user_profile&action=show&id='.$user_id;
		$output .= ($do_add_get ? common()->add_get_vars(array_merge(array('page'),(array)$skip_get_array)) : '');
		$output = process_url($output);
		return $output;
	}

	// Error message for guests with propose to log into system (for user section)
	function _error_need_login($go_after_login = '') {
		// Hosting frontend
		if (IS_FRONT == 1) {
			return '';
		}
		if (empty($go_after_login)) {
			$go_after_login = './?object='.$_GET['object'].'&action='.$_GET['action'].(!empty($_GET['id']) ? '&id='.$_GET['id'] : '').common()->add_get_vars();
		}
		conf('_force_login_go_url', $go_after_login);
		if (empty(main()->USER_ID)) {
			$body .= common()->_show_error_message(t('Only for members').'!');
		}
		$body .= module('login_form')->show();
		return $body;
	}

	function _output_cache_trigger($data = array()) {
		if (!main()->OUTPUT_CACHING) {
			return false;
		}
		_class('output_cache')->_exec_trigger($data);
	}

	function _country_name ($code = '') {
		$countries = conf('countries');
		if (!$countries) {
			$countries = main()->get_data('countries');
			conf('countries', $countries);
		}
		if (FEATURED_COUNTRY_SELECT && substr($code, 0, 2) == 'f_') {
			$code = substr($code, 2);
		}
		return isset($countries[$code]) ? $countries[$code] : $code;
	}

	function _region_name ($region_code = '', $country_code = '') {
		$regions = conf('regions');
		if (!$regions) {
			$regions = main()->get_data('regions');
			conf('regions', $regions);
		}
		if (!strlen($region_code) || empty($country_code)) {
			return $region_code;
		}
		$region_name = $regions[$country_code][$region_code];
		return !empty($region_name) ? $region_name : $region_code;
	}

	// Display link to send internal email
	function _email_link ($user_id = 0, $skip_get_array = array(), $do_add_get = true) {
		$body = _prepare_members_link('./?object=email&action=send_form&id='.$user_id);
		$body .= ($do_add_get ? common()->add_get_vars(array_merge(array('page'),(array)$skip_get_array)) : '');
		return $body;
	}

	// Prepare link for members
	function _prepare_members_link ($url = '') {
		if (main()->USER_ID) {
			return $url;
		}
		$parts = array();
		parse_str(substr($url, 3), $parts);
		if (!empty($parts['object'])) {
			return './?object=login_form&go_url='.$parts['object']. (!empty($parts['action']) ? ';'.$parts['action'] : ''). (!empty($parts['id']) ? ';id='.$parts['id'] : '');
		}
	}

	function _range ($_start = 0, $_end = 10) {
		$data = array();
		for ($i = $_start; $i <= $_end; $i++) {
			$data[$i] = $i;
		}
		return $data;
	}

	function _my_strip_tags ($_text = '') {
		return strip_tags($_text, '<a><b><i><u><p><br><strike><span><div><ul><ol><li><h1><h2><h3><h4><h5><h6><table><thead><tbody><th><tr><td>');
	}

	function checkdnsrr($hostName, $recType = '') {
		if (!empty($hostName)) {
			if ($recType == '') {
				$recType = 'MX';
			}
			@exec("nslookup -type=$recType $hostName", $result);
			// check each line to find the one that starts with the host
			// name. If it exists then the function succeeded.
			foreach ((array)$result as $line) {
				if (preg_match('/^'.$hostName.'/i', $line)) {
					return true;
				}
			}
			// otherwise there was no mail handler for the domain
			return false;
		}
		return false;
	}

	// Rename function, sometimes was needed as std rename not worked fine
	function _rename($src_filename, $dest_filename) {
		if (!file_exists($src_filename)) {
			return false;
		}
		$content = file_get_contents($src_filename);
		file_put_contents($dest_filename, $content);
		unlink($src_filename);
		return true;
	}

	function _cut_bb_codes ($body = '') {
		return preg_replace('/\[[^\]]+\]/ims', '', $body);
	}

	// Get server info
	function _server_info ($server_id) {
		$cached_server_info = &main()->_cached_server_info;
		if (is_numeric($server_id)) {
			$server_id = intval($server_id);
			if (!$server_id) {
				return false;
			}
			if (!$cached_server_info[$server_id]) {
				$cached_server_info[$server_id] = db()->query_fetch('SELECT * FROM '.db('servers').' WHERE id='.$server_id);
			}
			$server_info = $cached_server_info[$server_id];
		} elseif (is_array($server_id) && !empty($server_id)) {
			foreach ((array)$server_id as $_id) {
				$_id = intval($_id);
				if (!$cached_server_info[$_id]) {
					$ids_to_get_info[] = $_id;
				}
			}
			$Q = db()->query('SELECT * FROM '.db('servers').' WHERE id IN('.implode(',', $ids_to_get_info).')');
			while($A = db()->fetch_assoc($Q)) {
				$cached_server_info[$A['id']] = $A;
			}
			foreach ((array)$server_id as $_id) {
				$server_info[$_id] = $cached_server_info[$_id];
			}
		} else {
			return false;
		}
		return $server_info;
	}

	// Get account info
	function _account_info ($account_id) {
		$cached_account_info = &main()->_cached_account_info;
		if (is_numeric($account_id)) {
			$account_id = intval($account_id);
			if (!$account_id) {
				return false;
			}
			if (!$cached_account_info[$account_id]) {
				$cached_account_info[$account_id] = db()->query_fetch('SELECT * FROM '.db('host_accounts').' WHERE id='.$account_id);
			}
			$account_info = $cached_account_info[$account_id];
		} elseif (is_array($account_id)) {
			foreach ((array)$account_id as $_id) {
				$_id = intval($_id);
				if (empty($cached_account_info[$_id])){
					$ids_to_get_info[] = $_id;
				}
			}
			$Q = db()->query('SELECT * FROM '.db('host_accounts').' WHERE id IN('.implode(',', $ids_to_get_info).')');
			while($A = db()->fetch_assoc($Q)) {
				$cached_account_info[$A['id']] = $A;
			}
			foreach ((array)$account_id as $_id) {
				$account_info[$_id] = $cached_account_info[$_id];
			}
		} else {
			return false;

		}
		return $account_info;
	}

	// Locale safe floatval
	function _floatval ($val = 0) {
		return floatval(str_replace(',', '.', $val));
	}

	// Useful explode with cleanup
	function my_explode ($string = '', $divider = "\n") {
		$result = explode("\n", trim($string));
		foreach ((array)$result as $k => $v) {
			$v = trim($v);
			if (!strlen($v)) {
				unset($result[$k]);
			}
		}
		return $result;
	}

	// Allow to easy run subprocess in background both on win32 and linux
	function _exec_in_background($cmd) {
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			pclose(popen('start /B '. $cmd, 'r'));
		} else {
			exec($cmd . ' > /dev/null &');
		}
	}

	function recursive_unset(&$array, $unwanted_key) {
	    unset($array[$unwanted_key]);
   		foreach ($array as &$value) {
	        if (is_array($value)) {
    	        $this->recursive_unset($value, $unwanted_key);
        	}
    	}
	}

}
