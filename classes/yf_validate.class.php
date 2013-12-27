<?php

/**
* Validation common methods, part of this was inspired by codeigniter 2.1 form_validate
*/
class yf_validate {

	/** @var int Minimal nick length */
	public $MIN_NICK_LENGTH		= 2;
	/** @var array Allowed nick symbols (display for user) */
	public $NICK_ALLOWED_SYMBOLS	= array('a-z','0-9','_','\-','@','#',' ');
	/** @var array Reserved words for the profile url (default) */
	public $reserved_words = array(
		'login',
		'logout',
		'admin',
		'admin_modules',
		'classes',
		'modules',
		'functions',
		'uploads',
		'fonts',
		'pages_cache',
		'core_cache',
		'templates'
	);

	/***/
	function _init() {
		$this->MB_ENABLED = _class('utf8')->MULTIBYTE;
	}

	/***/
	function _prepare_reserved_words() {
		if ($this->_reserved_words_prepared) {
			return $this->reserved_words;
		}
		$user_modules = main()->get_data('user_modules');
		// Merge them with default ones
		if (is_array($user_modules)) {
			$this->reserved_words = array_merge($this->reserved_words, $user_modules);
		}
		$this->_reserved_words_prepared = true;
		return $this->reserved_words;
	}

	/**
	*/
	function _process_text($text, $validate_rules = array()) {
		$rules = array();
		$global_rules = isset($this->_params['validate']) ? $this->_params['validate'] : $this->_replace['validate'];
		foreach ((array)$global_rules as $name => $rules) {
			$rules[$name] = $rules;
		}
		foreach ((array)$validate_rules as $name => $rules) {
			$rules[$name] = $rules;
		}
		$rules = $this->_validate_rules_cleanup($rules);
		return $this->_do_process_text($rules, $text);
	}

	/**
	*/
	function _do_process_text($validate_rules = array(), &$data) {
/*
		$validate_ok = true;
		foreach ((array)$validate_rules as $name => $rules) {
			$is_required = false;
			foreach ((array)$rules as $rule) {
				if ($rule[0] == 'required') {
					$is_required = true;
					break;
				}
			}
			foreach ((array)$rules as $rule) {
				$is_ok = true;
				$error_msg = '';
				$func = $rule[0];
				$param = $rule[1];
				// PHP pure function, from core or user
				if (is_string($func) && function_exists($func)) {
					$data[$name] = $func($data[$name]);
				} elseif (is_callable($func)) {
					$is_ok = $func($data[$name], null, $data);
				} else {
					$is_ok = _class('validate')->$func($data[$name], array('param' => $param), $data, $error_msg);
					if (!$is_ok && empty($error_msg)) {
						$error_msg = t('form_validate_'.$func, array('%field' => $name, '%param' => $param));
					}
				}
				// In this case we do not track error if field is empty and not required
				if (!$is_ok && !$is_required && !strlen($data[$name])) {
					$is_ok = true;
					$error_msg = '';
				}
				if (!$is_ok) {
					$validate_ok = false;
					if (!$error_msg) {
						$error_msg = 'Wrong field '.$name;
					}
					_re($error_msg, $name);
					// In case when we see any validation rule is not OK - we stop checking further for this field
					continue 2;
				}
			}
		}
		return $validate_ok;
*/
	}

	/**
	* Examples of validate rules setting:
	* 	'name1' => 'trim|required',
	* 	'name2' => array('trim', 'required'),
	* 	'name3' => array('trim|required', 'other_rule|other_rule2|other_rule3'),
	* 	'name4' => array('trim|required', function() { return true; } ),
	* 	'name5' => array('trim', 'required', function() { return true; } ),
	* 	'__before__' => 'trim',
	* 	'__after__' => 'some_method2|some_method3',
	*/
	function _validate_rules_cleanup($validate_rules = array()) {
		// Add these rules to all validation rules, before them
		$_name = '__before__';
		$all_before = array();
		if (isset($validate_rules[$_name])) {
			$all_before = (array)$this->_validate_rules_array_from_raw($validate_rules[$_name]);
			unset($validate_rules[$_name]);
		}

		// Add these rules to all validation rules, after them
		$_name = '__after__';
		$all_after = array();
		if (isset($validate_rules[$_name])) {
			$all_after = (array)$this->_validate_rules_array_from_raw($validate_rules[$_name]);
			unset($validate_rules[$_name]);
		}
		unset($_name);

		$out = array();
		foreach ((array)$validate_rules as $name => $raw) {
			$rules = (array)$this->_validate_rules_array_from_raw($raw);
			if ($all_before) {
				$tmp = $all_before;
				foreach((array)$rules as $_item) {
					$tmp[] = $_item;
				}
				$rules = $tmp;
				unset($tmp);
			}
			if ($all_after) {
				$tmp = $rules;
				foreach((array)$all_after as $_item) {
					$tmp[] = $_item;
				}
				$rules = $tmp;
				unset($tmp);
			}
			// Here we do last parse of the rules params like 'matches[user.email]' into rule item array second element
			foreach ((array)$rules as $k => $rule) {
				if (!is_string($rule[0])) {
					continue;
				}
				$val = trim($rule[0]);
				$param = null;
				// Parsing these: min_length[6], matches[form_item], is_unique[table.field]
				$pos = strpos($val, '[');
				if ($pos !== false) {
					$param = trim(trim(substr($val, $pos), ']['));
					$val = trim(substr($val, 0, $pos));
				}
				if (!is_callable($val) && empty($val)) {
					unset($rules[$k]);
					continue;
				}
				$rules[$k] = array(
					0	=> $val,
					1	=> $param,
				);
			}
			if ($rules) {
				$out[$name] = array_values($rules); // array_values needed here to make array keys straight, unit tests will pass fine
			}
		}
		return $out;
	}

	/***/
	function password_update(&$in) {
		if (!strlen($in)) {
			$in = null; // Somehow unset($in) not working here...
		} else {
			$in = md5($in);
		}
		return true;
	}

	/**
	*/
	function _validate_rules_array_from_raw($raw = '') {
		$rules = array();
		// At first, we merging all rules sets variants into one array
		if (is_string($raw)) {
			foreach((array)explode('|', $raw) as $_item) {
				$rules[] = array($_item, null);
			}
		} elseif (is_array($raw)) {
			foreach((array)$raw as $_raw) {
				if (is_string($_raw)) {
					foreach((array)explode('|', $_raw) as $_item) {
						$rules[] = array($_item, null);
					}
				} elseif (is_callable($_raw)) {
					$rules[] = array($_raw, null);
				}
			}
		} elseif (is_callable($raw)) {
			$rules[] = array($raw, null);
		}
		return $rules;
	}

	/***/
	function md5_not_empty(&$in) {
		if (strlen($in)) {
			$in = md5($in);
		}
		return true;
	}

	/***/
	function required($in) {
		return is_array($in) ? (bool) count($in) : (trim($in) !== '');
	}

	/**
	* Returns true when _ANY_ of passed fields will be non-empty
	* Examples: required_any[duration_*] or required_any[duration_day,duration_week,duration_month]
	*/
	function required_any($in, $params = array(), $fields = array()) {
		$param = trim($params['param']);
		// Example: duration_*
		if (false !== strpos($param, '*')) {
			$strpos = str_replace('*', '', $param);
		// Example: duration_day,duration_week,duration_month
		} elseif (false !== strpos($param, ',')) {
			$field_names = explode(',', $param);
		}
		foreach((array)$fields as $k => $v) {
			$skip = true;
			if ($strpos && false !== strpos($k, $strpos)) {
				$skip = false;
			} elseif ($field_names && in_array($v, $field_names)) {
				$skip = false;
			}
			if ($skip) {
				continue;
			}
			if (is_array($v) ? (bool) count($v) : (trim($v) !== '')) {
				return true;
			}
		}
		return false;
	}

	/***/
	function matches($in, $params = array(), $fields = array()) {
		$field = $params['param'];
		return isset($fields[$field], $_POST[$field]) ? ($in === $_POST[$field]) : false;
	}

	/***/
	function is_unique($in, $params = array()) {
		if (!$in) {
			return true;
		}
		$param = $params['param'];
		if ($param) {
			list($check_table, $check_field) = explode('.', $param);
		}
		if ($check_table && $check_field && $in) {
			$exists = db()->get_one('SELECT `'.db()->es($check_field).'` FROM '.db($check_table).' WHERE `'.db()->es($check_field).'`="'.db()->es($in).'"');
			if ($exists == $in) {
				return false;
			}
		}
		return true;
	}

	/***/
	function is_unique_without($in, $params = array()) {
		if (!$in) {
			return true;
		}
		$param = $params['param'];
		if ($param) {
			list($check_table, $check_field, $check_id) = explode('.', $param);
		}
		if ($check_table && $check_field && $check_id && $in) {
			$exists = db()->get_one('SELECT `'.db()->es($check_field).'` FROM '.db($check_table).' WHERE `'.db()->es($check_field).'`="'.db()->es($in).'" AND id != "'.db()->es($check_id).'"');
			if ($exists == $in) {
				return false;
			}
		}
		return true;
	}

	/***/
	function exists($in, $params = array()) {
		if (!$in) {
			return false;
		}
		$param = $params['param'];
		if ($param) {
			list($check_table, $check_field) = explode('.', $param);
		}
		if ($check_table && $check_field && $in) {
			$exists = db()->get_one('SELECT `'.db()->es($check_field).'` FROM '.db($check_table).' WHERE `'.db()->es($check_field).'`="'.db()->es($in).'"');
			if ($exists == $in) {
				return true;
			}
		}
		return false;
	}

	/***/
	function regex_match($in, $params = array()) {
		$regex = $params['param'];
		return (bool) preg_match($regex, $in);
	}

	/***/
	function differs($in, $params = array(), $fields = array()) {
		$field = $params['param'];
		return ! (isset($fields[$field]) && $_POST[$field] === $in);
	}

	/***/
	function valid_url($in, $params = array()) {
		if (empty($in)) {
			return FALSE;
		} elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $in, $matches)) {
			if (empty($matches[2])) {
				return FALSE;
			} elseif ( ! in_array($matches[1], array('http', 'https'), TRUE)) {
				return FALSE;
			}
			$in = $matches[2];
		}
		$in = 'http://'.$in;
		return (filter_var($in, FILTER_VALIDATE_URL) !== FALSE);
	}
	
	/***/
	function min_length($in, $params = array()) {
		$val = $params['param'];
		if ( ! is_numeric($val)) {
			return FALSE;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === TRUE)
			? ($val <= mb_strlen($in))
			: ($val <= strlen($in));
	}

	/***/
	function max_length($in, $params = array()) {
		$val = $params['param'];
		if ( ! is_numeric($val)) {
			return FALSE;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === TRUE)
			? ($val >= mb_strlen($in))
			: ($val >= strlen($in));
	}

	/***/
	function exact_length($in, $params = array()) {
		$val = $params['param'];
		if ( ! is_numeric($val)) {
			return FALSE;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === TRUE)
			? (mb_strlen($in) === $val)
			: (strlen($in) === $val);
	}

	/***/
	function greater_than($in, $params = array()) {
		$min = $params['param'];
		return is_numeric($in) ? ($in > $min) : FALSE;
	}

	/***/
	function less_than($in, $params = array()) {
		$max = $params['param'];
		return is_numeric($in) ? ($in < $max) : FALSE;
	}

	/***/
	function greater_than_equal_to($in, $params = array()) {
		$min = $params['param'];
		return is_numeric($in) ? ($in >= $min) : FALSE;
	}

	/***/
	function less_than_equal_to($in, $params = array()) {
		$max = $params['param'];
		return is_numeric($in) ? ($in <= $max) : FALSE;
	}

	/***/
	function alpha($in) {
		return ctype_alpha($in);
	}

	/***/
	function alpha_numeric($in) {
		return ctype_alnum((string) $in);
	}

	/***/
	function alpha_numeric_spaces($in) {
		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $in);
	}

	/***/
	function alpha_dash($in) {
		return (bool) preg_match('/^[a-z0-9_-]+$/i', $in);
	}

	/***/
	function numeric($in) {
		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $in);
	}

	/***/
	function integer($in) {
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $in);
	}

	/***/
	function decimal($in) {
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $in);
	}

	/***/
	function is_natural($in) {
		return ctype_digit((string) $in);
	}

	/***/
	function is_natural_no_zero($in) {
		return ($in != 0 && ctype_digit((string) $in));
	}

	/***/
	function valid_email($in) {
		return (bool) filter_var($in, FILTER_VALIDATE_EMAIL);
	}

	/***/
	function valid_emails($in) {
		if (strpos($in, ',') === FALSE) {
			return $this->valid_email(trim($in));
		}
		foreach (explode(',', $in) as $email) {
			if (trim($email) !== '' && $this->valid_email(trim($email)) === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/***/
	function valid_base64($in) {
		return (base64_encode(base64_decode($in)) === $in);
	}

	/***/
	function prep_url($in) {
		if ($in === 'http://' OR $in === '') {
			return '';
		}
		if (strpos($in, 'http://') !== 0 && strpos($in, 'https://') !== 0) {
			return 'http://'.$in;
		}
		return $in;
	}

	/***/
	function encode_php_tags($in) {
		return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $in);
	}

	/***/
	function valid_ip($in, $params = array()) {
		$which = $params['param'];
		return $this->_valid_ip($in, $which);
	}

	/***/
	function captcha($in, $params = array(), $fields = array()) {
		return _class('captcha')->check('captcha');
	}

	/***/
	function xss_clean($in) {
# TODO: write unit tests and only then enable
#		return _class('security')->xss_clean($in);
		return true;
	}

	/***/
	function strip_image_tags($in) {
# TODO: write unit tests and only then enable
		return true;
#		return _class('security')->strip_image_tags($in);
	}

	/***/
	public function _valid_ip($ip, $ip_version = 'ipv4') {
		$ip_version = strtolower($ip_version);
		if (!$ip_version) {
			$ip_version = 'ipv4';
		}
		if ($ip_version == 'ipv6') {
			$filter_flag = FILTER_FLAG_IPV6;
		} else {
			$filter_flag = FILTER_FLAG_IPV4;
		}
		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $filter_flag);
	}

	/**
	* Check user nick
	*/
	function _check_user_nick ($CUR_VALUE = '', $force_value_to_check = null, $name_in_form = 'nick') {
// TODO: rewrite me
		$TEXT_TO_CHECK = $_POST[$name_in_form];
		// Override value to check
		if (!is_null($force_value_to_check)) {
			$TEXT_TO_CHECK = $force_value_to_check;
			$OVERRIDE_MODE = true;
		}
		// Do check
		$_nick_pattern = implode('', $this->NICK_ALLOWED_SYMBOLS);
		if (empty($TEXT_TO_CHECK) || (strlen($TEXT_TO_CHECK) < $this->MIN_NICK_LENGTH)) {
			_re(t('Nick must have at least @num symbols', array('@num' => $this->MIN_NICK_LENGTH)));
		} elseif (!preg_match('/^['.$_nick_pattern.']+$/iu', $TEXT_TO_CHECK)) {
			_re(t("Nick can contain only these characters: \"@text1\"", array("@text1" => _prepare_html(stripslashes(implode("\" , \"", $this->NICK_ALLOWED_SYMBOLS))))));
			if (!$OVERRIDE_MODE) {
				$_POST[$name_in_form] = preg_replace("/[^".$_nick_pattern."]+/iu", "", $_POST[$name_in_form]);
			}
		} elseif ($TEXT_TO_CHECK != $CUR_VALUE) {
			$NICK_ALREADY_EXISTS = (db()->query_num_rows("SELECT id FROM ".db('user')." WHERE nick='"._es($TEXT_TO_CHECK)."'") >= 1);
			if ($NICK_ALREADY_EXISTS) {
				_re(t("Nick (\"@name\") is already reserved. Please try another one.", array("@name" => $TEXT_TO_CHECK)));
			}
		}
	}

	/**
	* Check user profile url
	*/
	function _check_profile_url ($CUR_VALUE = "", $force_value_to_check = null, $name_in_form = "profile_url") {
// TODO: rewrite me
		$TEXT_TO_CHECK = $_POST[$name_in_form];
		// Override value to check
		if (!is_null($force_value_to_check)) {
			$TEXT_TO_CHECK = $force_value_to_check;
			$OVERRIDE_MODE = true;
		}
		// Ignore empty values
		if (empty($TEXT_TO_CHECK)) {
			return false;
		}
		$this->_prepare_reserved_words();
		// Do check profile url
		if (!empty($CUR_VALUE)) {
			_re(t("You have already chosen your profile url. You are not allowed to change it!"));
		} elseif (!preg_match("/^[a-z0-9]{0,64}$/ims", $TEXT_TO_CHECK)) {
			_re(t("Wrong Profile url format! (Letters or numbers only with no Spaces)"));
		} elseif (in_array($TEXT_TO_CHECK, $this->reserved_words)) {
			_re("This profile url (\"".$TEXT_TO_CHECK."\") is our site reserved name. Please try another one.");
		} elseif (db()->query_num_rows("SELECT id FROM ".db('user')." WHERE profile_url='"._es($TEXT_TO_CHECK)."'") >= 1) {
			_re("This profile url (\"".$TEXT_TO_CHECK."\") has already been registered with us! Please try another one.");
		}
	}

	/**
	* 
	*/
	function _check_login () {
// TODO: rewrite me
		if ($_POST["login"] == "") {
			_re(t('Login required'));
		} elseif (db()->query_num_rows("SELECT id FROM ".db('user')." WHERE login='"._es($_POST['login'])."'") >= 1) {
			_re("This login (".$_POST["login"].") has already been registered with us!");
		}
	}

	/**
	* Check selected location (country, region, city)
	*/
	function _check_location ($cur_country = '', $cur_region = '', $cur_city = '') {
// TODO: rewrite me
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST['country']) && substr($_POST['country'], 0, 2) == 'f_') {
			$_POST['country'] = substr($_POST['country'], 2);
		}
		// verify country
		if (!empty($_POST['country'])) {
			if (!isset($GLOBALS['countries'])) {
				$GLOBALS['countries'] = main()->get_data('countries');
			}
			// Check for correct country
			if (!isset($GLOBALS['countries'][$_POST['country']])) {
				$_POST['country']	= '';
				$_POST['region']	= '';
				$_POST['state']		= '';
				$_POST['city']		= '';
			} else {
				$GLOBALS['_country_name'] = $GLOBALS['countries'][$_POST['country']];
			}
		}
		// Verify region
		if (!empty($_POST['region'])) {
			$region_info = db()->query_fetch('SELECT * FROM '.db('geo_regions').' WHERE country = "'._es($_POST['country']).'" AND code="'._es($_POST['region']).'"');
			if (empty($region_info)) {
				$_POST['region']	= '';
				$_POST['state']		= '';
				$_POST['city']		= '';
			} else {
				$GLOBALS['_region_name'] = $region_info['name'];
			}
		}
		// Verify city
		if (!empty($_POST['city'])) {
			$city_info = db()->query_fetch('SELECT * FROM '.db('geo_city_location')." WHERE region = '"._es($_POST["region"])."' AND country = '"._es($_POST["country"])."' AND city='"._es($_POST["city"])."'");
			if (empty($city_info)) {
				$_POST['city']		= '';
			}
		}
	}

	/**
	* Check user birth date
	*/
	function _check_birth_date ($CUR_VALUE = '') {
// TODO: rewrite me
		// Validate birth date
		$_POST['birth_date']	= $CUR_VALUE;

		$_POST['year_birth']	= intval($_POST['year_birth']);
		$_POST['month_birth']	= intval($_POST['month_birth']);
		$_POST['day_birth']		= intval($_POST['day_birth']);
		if ($_POST['year_birth'] >= 1915 && $_POST['year_birth'] <= (date('Y') - 17)
			&& $_POST['month_birth'] >= 1 && $_POST['month_birth'] <= 12
			&& $_POST['day_birth'] >= 1 && $_POST['day_birth'] <= 31
		) {
			if ($_POST['month_birth'] < 10) {
				$_POST['month_birth'] = '0'.$_POST['month_birth'];
			}
			if ($_POST['day_birth'] < 10) {
				$_POST['day_birth'] = '0'.$_POST['day_birth'];
			}
			$_POST['birth_date'] = $_POST['year_birth'].'-'.$_POST['month_birth'].'-'.$_POST['day_birth'];
		}
		if (!empty($_POST['birth_date'])) {
			$_POST['age'] = _get_age_from_birth($_POST['birth_date']);
		}
	}

	/**
	*/
#	function email_verify ($email = '', $check_mx = false, $check_by_smtp = false, $check_blacklists = false) {
#		return _class('remote_files', 'classes/common/')->_email_verify($email, $check_mx, $check_by_smtp, $check_blacklists);
#	}

	/**
	*/
#	function url_verify ($url = '', $absolute = false) {
#		return preg_match('/^(http|https):\/\/(www\.){0,1}[a-z0-9\-]+\.[a-z]{2,5}[^\s]*$/i', $url);
#	}

	/**
	*/
#	function _validate_url_by_http($url) {
#		return _class('remote_files', 'classes/common/')->_validate_url_by_http($url);
#	}
}
