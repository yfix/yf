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
/*
	public $methods_info = array(
		'numeric' => array(
			'regex'		=> '^[\-+]?[0-9]*\.?[0-9]+$',
			'help'		=> 'Value must contain only numbers',
			'examples'	=> array('25', '25.05'),
		),
	);
		# $extra['title'] is used in html5 validation suggesting messages
*/

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

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
	* Method by form-less checking of any custom data for validity
	*/
	function _input_is_valid($input, $validate_rules = array()) {
		$rules = array();
		$global_rules = isset($this->_params['validate']) ? $this->_params['validate'] : $this->_replace['validate'];
		foreach ((array)$global_rules as $name => $_rules) {
			$rules[$name] = $_rules;
		}
		foreach ((array)$validate_rules as $name => $_rules) {
			$rules[$name] = $_rules;
		}
		$rules = $this->_validate_rules_cleanup($rules);
		$ok = $this->_do_check_data_is_valid($rules, $input);
		return (bool)$ok;
	}

	/**
	*/
	function _apply_existing_func($func, $data) {
		if (is_array($data)) {
			$self = __FUNCTION__;
			foreach ($data as $k => $v) {
				$data[$k] = $this->$self($func, $v);
			}
			return $data;
		}
		return $func($data);
	}

	/**
	*/
	function _do_check_data_is_valid($rules = array(), &$data) {
		$validate_ok = true;
		foreach ((array)$rules as $name => $_rules) {
			$is_required = false;
			foreach ((array)$_rules as $rule) {
				if ($rule[0] == 'required') {
					$is_required = true;
					break;
				}
			}
			foreach ((array)$_rules as $rule) {
				$is_ok = true;
				$error_msg = '';
				$func = $rule[0];
				$param = $rule[1];
				// PHP pure function, from core or user
				if (is_string($func) && function_exists($func)) {
					$data[$name] = $this->_apply_existing_func($func, $data[$name]);
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
			$is_html_array = (false !== strpos($name, '['));
			if ($is_html_array) {
				$name = str_replace(array('[',']'), array('.',''), trim($name,']['));
			}
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
					0 => $val,
					1 => $param,
				);
			}
			if ($rules) {
				$out[$name] = array_values($rules); // array_values needed here to make array keys straight, unit tests will pass fine
			}
		}
		return $out;
	}

	/**
	* This method used by validate() function to do standalone validation processing
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

	/**
	* Returns md5() from input string, or null. Usually used to update password inside admin panel or not change it if new value not passed.
	* Example: ["password" => 'trim|min_length[6]|max_length[32]|password_update']
	*/
	function password_update(&$in) {
		if (!strlen($in)) {
			$in = null; // Somehow unset($in) not working here...
		} else {
			$in = md5($in);
		}
		return true;
	}

	/**
	* Returns md5() from given input string, only if not empty. 
	* Example usage: ["password" => 'trim|min_length[6]|max_length[32]|md5_not_empty']
	*/
	function md5_not_empty(&$in) {
		if (strlen($in)) {
			$in = md5($in);
		}
		return true;
	}

	/**
	* Returns hash() from given input string, only if not empty.  Get list of available algorithms by running: php -r 'echo implode(" ", hash_algos());'.
	* Most popular are: md5 sha1 sha224 sha256 sha384 sha512 ripemd128 ripemd160 ripemd256 ripemd320 gost crc32
	* Example usage: ["password" => 'trim|min_length[6]|max_length[32]|hash_not_empty[sha256]']
	*/
	function hash_not_empty(&$in, $params = array()) {
		$hash_name = is_array($params) ? $params['param'] : $params;
		if (strlen($in) && $hash_name) {
			$in = hash($hash_name, $in);
		}
		return true;
	}

	/**
	* Returns FALSE if form field is empty.
	*/
	function required($in) {
		return is_array($in) ? (bool) count($in) : (trim($in) !== '');
	}

	/**
	* Returns true when _ANY_ of passed fields will be non-empty
	* Examples: required_any[duration_*] or required_any[duration_day,duration_week,duration_month]
	*/
	function required_any($in, $params = array(), $fields = array()) {
		$param = trim(is_array($params) ? $params['param'] : $params);
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
			} elseif ($field_names && in_array($k, $field_names)) {
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

	/**
	* Returns FALSE if field does not match field(s) in parameter. 
	* Example: matches[password_again]
	*/
	function matches($in, $params = array(), $fields = array()) {
		$field = is_array($params) ? $params['param'] : $params;
		return isset($fields[$field], $_POST[$field]) ? ($in === $_POST[$field]) : false;
	}

	/**
	* Returns FALSE if form field(s) defined in parameter are not filled in. 
	* Example: depends_on[field_name]
	*/
	function depends_on($in, $params = array(), $fields = array()) {
		$field = is_array($params) ? $params['param'] : $params;
		return isset($fields[$field], $_POST[$field]);
	}

	/**
	* The field under validation must be a valid URL according to the checkdnsrr PHP function.
	*/
	function active_url($in) {
		return checkdnsrr(str_replace(array('http://', 'https://', 'ftp://'), '', strtolower($in)));
	}

	/**
	* The field under validation must be a value after a given date. The dates will be passed into the PHP strtotime function. 
	* Examples: after_date[2012-01-01], after_date[day ago]
	*/
	function after_date($in, $params = array()) {
		$param = is_array($params) ? $params['param'] : $params;
		if (!$param) {
			return false;
		}
		if (isset($params['format'])) {
			return DateTime::createFromFormat($params['format'], $in) > DateTime::createFromFormat($params['format'], $param);
		}
		$date = strtotime($param);
		if ( ! $date) {
			return strtotime($in) > strtotime($this->getValue($param));
		} else {
			return strtotime($in) > $date;
		}
	}

	/**
	* The field under validation must be a value preceding the given date. The dates will be passed into the PHP strtotime function. 
	* Example: before_date[2020-12-31], after_date[+1 day]
	*/
	function before_date($in, $params = array()) {
		$param = is_array($params) ? $params['param'] : $params;
		if (!$param) {
			return false;
		}
		if (isset($params['format'])) {
			return DateTime::createFromFormat($params['format'], $in) < DateTime::createFromFormat($params['format'], $param);
		}
		$date = strtotime($param);
		if ( ! $date) {
			return strtotime($in) < strtotime($this->getValue($param));
		} else {
			return strtotime($in) < $date;
		}
	}

	/**
	* The field under validation must be a valid date according to the strtotime PHP function.
	*/
	function valid_date($in) {
		if ($in instanceof DateTime) {
			return true;
		}
		if (strtotime($in) === false) {
			return false;
		}
		$date = date_parse($in);
		return checkdate($date['month'], $date['day'], $date['year']);
	}

	/**
	* The field under validation must match the format defined according to the date_parse_from_format PHP function.
	*/
	function valid_date_format($in, $params = array()) {
		$param = is_array($params) ? $params['param'] : $params;
		$parsed = date_parse_from_format($param, $in);
		return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
	}

	/**
	* Returns FALSE if form field is not valid text (letters, numbers, whitespace, dashes, periods and underscores are allowed)
	*/
	function standard_text($in) {
		return (bool) preg_match('~^[a-z0-9\s\t,\._-]+$~ims', $in);
	}

	/**
	* The field under validation must have a size between the given min and max. Strings, numerics, and files are evaluated in the same fashion as the size rule. 
	* Examples: between[a,z]  between[44,99]
	*/
	function between($in, $params = array()) {
		$param = is_array($params) ? $params['param'] : $params;
		list($min, $max) = explode(',', $param);
		return $in >= $min && $in <= $max;
	}

	/**
	* Returns FALSE if field contains characters not in the parameter. 
	* Example: chars[a,b,c,d,1,2,3,4]
	*/
	function chars($in, $params = array()) {
		$param = is_array($params) ? $params['param'] : $params;
		$chars = array();
		foreach (explode(',', trim($param)) as $char) {
			$char = trim($char);
			if (strlen($char)) {
				$chars[$char] = $char;
			}
		}
		if (!count($chars)) {
			return false;
		}
		$regex = '~^['.preg_quote(implode($chars), '~').']+$~ims';
		return (bool) preg_match($regex, $in);
	}

	/**
	* Returns TRUE if given field value is unique inside given database table.field
	* Examples: is_unique[user.login]
	*/
	function is_unique($in, $params = array()) {
		if (!$in) {
			return true;
		}
		$param = is_array($params) ? $params['param'] : $params;
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

	/**
	* Returns TRUE if given field value is unique inside given database table.field.pk_value
	* Examples: is_unique_without[user.id.1]
	*/
	function is_unique_without($in, $params = array()) {
		if (!$in) {
			return true;
		}
		$param = is_array($params) ? $params['param'] : $params;
		$id_field = $params['id_field'] ?: 'id';
		if ($param) {
			list($check_table, $check_field, $check_id) = explode('.', $param);
		}
		if ($check_table && $check_field && $check_id && $in) {
			$exists = db()->get_one('SELECT `'.db()->es($check_field).'` FROM '.db($check_table).' WHERE `'.db()->es($check_field).'`="'.db()->es($in).'" AND `'.db()->es($id_field).'` != "'.db()->es($check_id).'"');
			if ($exists == $in) {
				return false;
			}
		}
		return true;
	}

	/**
	* Returns TRUE if given field value exists inside database
	* Examples: exists[user.email]
	*/
	function exists($in, $params = array()) {
		if (!$in) {
			return false;
		}
		$param = is_array($params) ? $params['param'] : $params;
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

	/**
	* Custom regex matching.
	* Example: regex_match[/^[a-z0-9]+$/]
	*/
	function regex_match($in, $params = array()) {
		$regex = is_array($params) ? $params['param'] : $params;
		return (bool) preg_match($regex, $in);
	}

	/**
	* Returns TRUE if given field value differs from compared field value
	* Example: differs[address_2]
	*/
	function differs($in, $params = array(), $fields = array()) {
		$field = is_array($params) ? $params['param'] : $params;
		return ! (isset($fields[$field]) && $_POST[$field] === $in);
	}

	/**
	* The original specification of hostnames in RFC 952, mandated that labels could not start with a digit or with a hyphen, and must not end with a hyphen. 
	* However, a subsequent specification (RFC 1123) permitted hostname labels to start with digits.
	* http://tools.ietf.org/html/rfc952, http://tools.ietf.org/html/rfc1123
	* Each label within a valid hostname may be no more than 63 octets long.
	* the total length of the hostname must not exceed 255 characters. For more information, please consult RFC-952 and RFC-1123.
	* see also: 
	*    http://stackoverflow.com/questions/106179/regular-expression-to-match-hostname-or-ip-address
	*    http://data.iana.org/TLD/tlds-alpha-by-domain.txt
	*/
	function valid_hostname ($in = '') {
		$len = strlen($in);
		if (!$len && $len > 255) {
			return false;
		}
		foreach ((array)explode('.', $in) as $v) {
			if (strlen($v) > 63) {
				return false;
			}
		}
		return (bool) preg_match('/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))*$/i', $in);
	}

	/**
	* Returns TRUE if given field contains valid url. Checking is done in combination of regexp and php built-in filter_val() to ensure most correct results
	*/
	function valid_url($in, $params = array()) {
		if (empty($in)) {
			return false;
		} elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $in, $matches)) {
			if (empty($matches[2])) {
				return false;
			} elseif ( ! in_array($matches[1], array('http', 'https'), true)) {
				return false;
			}
			$in = $matches[2];
		}
		$in = 'http://'.$in;
		return (filter_var($in, FILTER_VALIDATE_URL) !== false);
	}

	/**
	* Returns TRUE if given field contains valid email address
	*/
	function valid_email($in) {
		return (bool) filter_var($in, FILTER_VALIDATE_EMAIL);
	}

	/**
	* Returns TRUE if given field contains several valid email addresses
	*/
	function valid_emails($in) {
		if (!$in) {
			return false;
		}
		if (strpos($in, ',') === false) {
			return $this->valid_email(trim($in));
		}
		foreach (explode(',', $in) as $email) {
			if (trim($email) !== '' && $this->valid_email(trim($email)) === false) {
				return false;
			}
		}
		return true;
	}

	/**
	* Returns TRUE if given field contains correct base64-encoded string.
	*/
	function valid_base64($in) {
		return strlen($in) && (base64_encode(base64_decode($in)) === $in);
	}

	/**
	* Returns TRUE if given field contains valid IP address, ipv4 by default, ipv6 supported too
	*/
	function valid_ip($in, $params = array()) {
		$which = is_array($params) ? $params['param'] : $params;
		return $this->_valid_ip($in, $which);
	}

	/**
	* Returns TRUE if given field length is no more than specified, excluding exact length.
	* Example: min_length[10]
	*/
	function min_length($in, $params = array()) {
		$val = is_array($params) ? $params['param'] : $params;
		if ( ! is_numeric($val)) {
			return false;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === true) ? ($val <= mb_strlen($in)) : ($val <= strlen($in));
	}

	/**
	* Returns TRUE if given field length is more than specified, including exact length.
	* Example: max_length[10]
	*/
	function max_length($in, $params = array()) {
		$val = is_array($params) ? $params['param'] : $params;
		if ( ! is_numeric($val)) {
			return false;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === true) ? ($val >= mb_strlen($in)) : ($val >= strlen($in));
	}

	/**
	* Returns TRUE if given field length is more than specified, including exact length.
	* Example: exact_length[10]
	*/
	function exact_length($in, $params = array()) {
		$val = is_array($params) ? $params['param'] : $params;
		if ( ! is_numeric($val)) {
			return false;
		} else {
			$val = (int) $val;
		}
		return ($this->MB_ENABLED === true) ? (mb_strlen($in) === $val) : (strlen($in) === $val);
	}

	/**
	* Returns FALSE if the field is too long or too short. 
	* Examples: length[1,30] - between 1 and 30 characters long. length[30] - exactly 30 characters long
	*/
	function length($in, $params = array()) {
		$val = is_array($params) ? $params['param'] : $params;
		if (false === strpos($val, ',')) {
			return $this->exact_length($in, $params);
		} else {
			list($min, $max) = explode(',', $val);
			$min_check = true;
			if ($min) {
				$min_check = $this->min_length($in, $min);
			}
			$max_check = true;
			if ($max) {
				$max_check = $this->max_length($in, $max);
			}
			return ($min_check && $max_check);
		}
		return false;
	}

	/**
	* Returns TRUE if given field value is a number and greater than specified, not including exact value
	* Example: greater_than[10]
	*/
	function greater_than($in, $params = array()) {
		$min = is_array($params) ? $params['param'] : $params;
		return is_numeric($in) ? ($in > $min) : false;
	}

	/**
	* Returns TRUE if given field value is a number and less than specified, not including exact value
	* Example: less_than[10]
	*/
	function less_than($in, $params = array()) {
		$max = is_array($params) ? $params['param'] : $params;
		return is_numeric($in) ? ($in < $max) : false;
	}

	/**
	* Returns TRUE if given field value is a number and greater than specified, including exact value
	* Example: greater_than_equal_to[10]
	*/
	function greater_than_equal_to($in, $params = array()) {
		$min = is_array($params) ? $params['param'] : $params;
		return is_numeric($in) ? ($in >= $min) : false;
	}

	/**
	* Returns TRUE if given field value is a number and less than specified, including exact value
	* Example: less_than_equal_to[10]
	*/
	function less_than_equal_to($in, $params = array()) {
		$max = is_array($params) ? $params['param'] : $params;
		return is_numeric($in) ? ($in <= $max) : false;
	}

	/**
	* Returns TRUE if given field value contains only latin1 letters, lower and uppercase allowed.
	*/
	function alpha($in) {
		return ctype_alpha($in);
	}

	/**
	* Returns TRUE if given field value contains only latin1 letters, lower and uppercase allowed, and digits.
	*/
	function alpha_numeric($in) {
		return (is_array($in) || is_object($in) || is_callable($in)) ? false : ctype_alnum((string) $in);
	}

	/**
	* Returns TRUE if given field value contains only latin1 letters, lower and uppercase allowed, and spaces.
	*/
	function alpha_spaces($in) {
		return (bool) preg_match('/^[A-Z ]+$/i', $in);
	}

	/**
	* Returns TRUE if given field value contains only latin1 letters, lower and uppercase allowed, and spaces and digits.
	*/
	function alpha_numeric_spaces($in) {
		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $in);
	}

	/**
	* Returns TRUE if given field value contains only latin1 letters, lower and uppercase allowed, and dash and underscore symbols.
	*/
	function alpha_dash($in) {
		return (bool) preg_match('/^[a-z0-9_-]+$/i', $in);
	}

	/**
	* Same as alpha(), but including unicode characters too
	*/
	function unicode_alpha($in) {
		return (bool)preg_match('/^[\pL\pM]+$/u', $in);
	}

	/**
	* Same as alpha_numeric(), but including unicode characters too
	*/
	function unicode_alpha_numeric($in) {
		return (bool)preg_match('/^[\pL\pM\pN]+$/u', $in);
	}

	/**
	* Same as alpha_spaces(), but including unicode characters too
	*/
	function unicode_alpha_spaces($in) {
		return (bool)preg_match('/^[\pL\pM\s]+$/u', $in);
	}

	/**
	* Same as alpha_numeric_spaces(), but including unicode characters too
	*/
	function unicode_alpha_numeric_spaces($in) {
		return (bool)preg_match('/^[\pL\pM\pN\s]+$/u', $in);
	}

	/**
	* Same as alpha_dash(), but including unicode characters too
	*/
	function unicode_alpha_dash($in) {
		return (bool)preg_match('/^[\pL\pM\pN_-]+$/u', $in);
	}

	/**
	* Returns TRUE if given field value contains only numbers, including integers, floats and decimals.
	*/
	function numeric($in) {
		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $in);
	}

	/**
	* Returns TRUE if given field value contains only integers.
	*/
	function integer($in) {
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $in);
	}

	/**
	* Returns TRUE if given field value contains only decimals.
	*/
	function decimal($in) {
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $in);
	}

	/**
	* Returns TRUE if given field value contains only numbers that are natural.
	*/
	function is_natural($in) {
		return ctype_digit((string) $in);
	}

	/**
	* Returns TRUE if given field value contains only numbers that are natural except 0.
	*/
	function is_natural_no_zero($in) {
		return ($in != 0 && ctype_digit((string) $in));
	}

	/**
	* Do url preparation, not validates anything
	*/
	function prep_url($in) {
		if ($in === 'http://' OR $in === '') {
			return '';
		}
		if (strpos($in, 'http://') !== 0 && strpos($in, 'https://') !== 0) {
			return 'http://'.$in;
		}
		return $in;
	}

	/**
	* Returns TRUE is captcha user input value is valid
	*/
	function captcha($in) {
		return _class('captcha')->check('captcha');
	}

	/**
	* Clean string from possible XSS, using security class
	*/
	function xss_clean($in) {
		return _class('security')->xss_clean($in);
	}

	/**
	* Internal IP validity checking method
	*/
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
		if (!is_null($force_value_to_check)) {
			$TEXT_TO_CHECK = $force_value_to_check;
			$OVERRIDE_MODE = true;
		}
		$_nick_pattern = implode('', $this->NICK_ALLOWED_SYMBOLS);
		if (empty($TEXT_TO_CHECK) || (strlen($TEXT_TO_CHECK) < $this->MIN_NICK_LENGTH)) {
			_re(t('Nick must have at least @num symbols', array('@num' => $this->MIN_NICK_LENGTH)));
		} elseif (!preg_match('/^['.$_nick_pattern.']+$/iu', $TEXT_TO_CHECK)) {
			_re(t('Nick can contain only these characters: @text1', array('@text1' => _prepare_html(stripslashes(implode('" , "', $this->NICK_ALLOWED_SYMBOLS))))));
			if (!$OVERRIDE_MODE) {
				$_POST[$name_in_form] = preg_replace('/[^'.$_nick_pattern.']+/iu', '', $_POST[$name_in_form]);
			}
		} elseif ($TEXT_TO_CHECK != $CUR_VALUE) {
			$NICK_ALREADY_EXISTS = (db()->query_num_rows('SELECT id FROM '.db('user').' WHERE nick="'._es($TEXT_TO_CHECK).'"') >= 1);
			if ($NICK_ALREADY_EXISTS) {
				_re(t('Nick "@name" is already reserved. Please try another one.', array('@name' => $TEXT_TO_CHECK)));
			}
		}
	}

	/**
	* Check user profile url
	*/
	function _check_profile_url ($CUR_VALUE = '', $force_value_to_check = null, $name_in_form = 'profile_url') {
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
		if (!empty($CUR_VALUE)) {
			_re('You have already chosen your profile url. You are not allowed to change it!');
		} elseif (!preg_match('/^[a-z0-9]{0,64}$/ims', $TEXT_TO_CHECK)) {
			_re('Wrong Profile url format! Letters or numbers only with no spaces');
		} elseif (in_array($TEXT_TO_CHECK, $this->reserved_words)) {
			_re('This profile url ("'.$TEXT_TO_CHECK.'") is our site reserved name. Please try another one.');
		} elseif (db()->query_num_rows('SELECT id FROM '.db('user').' WHERE profile_url="'._es($TEXT_TO_CHECK).'"') >= 1) {
			_re('This profile url ("'.$TEXT_TO_CHECK.'") has already been registered with us! Please try another one.');
		}
	}

	/**
	* Check user login
	*/
	function _check_login () {
// TODO: rewrite me
		if ($_POST['login'] == '') {
			_re('Login required');
		} elseif (db()->query_num_rows('SELECT id FROM '.db('user').' WHERE login="'._es($_POST['login']).'"') >= 1) {
			_re('This login '.$_POST['login'].' has already been registered with us!');
		}
	}

	/**
	* Check selected location (country, region, city)
	*/
	function _check_location ($cur_country = '', $cur_region = '', $cur_city = '') {
// TODO: rewrite me
		if (FEATURED_COUNTRY_SELECT && !empty($_POST['country']) && substr($_POST['country'], 0, 2) == 'f_') {
			$_POST['country'] = substr($_POST['country'], 2);
		}
		if (!empty($_POST['country'])) {
			if (!isset($GLOBALS['countries'])) {
				$GLOBALS['countries'] = main()->get_data('countries');
			}
			if (!isset($GLOBALS['countries'][$_POST['country']])) {
				$_POST['country']	= '';
				$_POST['region']	= '';
				$_POST['state']		= '';
				$_POST['city']		= '';
			} else {
				$GLOBALS['_country_name'] = $GLOBALS['countries'][$_POST['country']];
			}
		}
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
		if (!empty($_POST['city'])) {
			$city_info = db()->query_fetch('SELECT * FROM '.db('geo_city_location').' WHERE region = "'._es($_POST['region']).'" AND country = "'._es($_POST['country']).'" AND city="'._es($_POST['city']).'"');
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
	* Internal method
	*/
	function _email_verify ($email = '', $check_mx = false, $check_by_smtp = false, $check_blacklists = false) {
		return _class('remote_files', 'classes/common/')->_email_verify($email, $check_mx, $check_by_smtp, $check_blacklists);
	}

	/**
	* Internal method
	*/
	function _validate_url_by_http($url) {
		return _class('remote_files', 'classes/common/')->_validate_url_by_http($url);
	}
	
	/**
	* Alias
	*/
	function _url_verify ($in = '') {
		return $this->valid_url($in);
	}

	/**
	* Alias
	*/
	function valid_image($in, $params = array(), $fields = array()) {
		return $this->image($in, $params, $fields);
	}

	/**
	* The file under validation must be an image (jpeg, png, bmp, or gif)
	*/
	function image($in, $params = array(), $fields = array()) {
// TODO
	}

	/**
	* The file under validation must have a MIME type corresponding to one of the listed extensions.  mime:jpeg,bmp,png
	*/
	function mime($in, $params = array(), $fields = array()) {
// TODO
	}

	/**
	* Returns FALSE if credit card is not valid. 
	* Examples: credit_card[mastercard]
	*/
	function credit_card($in, $params = array(), $fields = array()) {
// TODO
	}

	/**
	* Same as is_unique(), but tells form validator to include ajax form checking
	*/
	function ajax_is_unique($in, $params = array(), $fields = array()) {
		return $this->is_unique($in, $params, $fields);
	}

	/**
	* Same as is_unique_without(), but tells form validator to include ajax form checking
	*/
	function ajax_is_unique_without($in, $params = array(), $fields = array()) {
		return $this->is_unique_without($in, $params, $fields);
	}

	/**
	* Same as exists(), but tells form validator to include ajax form checking
	*/
	function ajax_exists($in, $params = array(), $fields = array()) {
		return $this->exists($in, $params, $fields);
	}
}
