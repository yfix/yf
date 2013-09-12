<?php

/**
*/
class yf_form_validate {

	/***/
	function _init() {
		$this->MB_ENABLED = function_exists('mb_strlen');
	}

	/***/
	function required($in, $params = array(), $fields = array()) {
		return is_array($in) ? (bool) count($in) : (trim($in) !== '');
	}

	/***/
	function matches($in, $params = array(), $fields = array()) {
		$field = $params['param'];
		return isset($fields[$field], $_POST[$field])
			? ($in === $_POST[$field])
			: FALSE;
	}

	/***/
	function is_unique($in, $params = array(), $fields = array()) {
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
	function exists($in, $params = array(), $fields = array()) {
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
	function regex_match($in, $params = array(), $fields = array()) {
		$regex = $params['param'];
		return (bool) preg_match($regex, $in);
	}

	/***/
	function differs($in, $params = array(), $fields = array()) {
		$field = $params['param'];
		return ! (isset($fields[$field]) && $_POST[$field] === $in);
	}

	/***/
	function valid_url($in, $params = array(), $fields = array()) {
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
	function min_length($in, $params = array(), $fields = array()) {
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
	function max_length($in, $params = array(), $fields = array()) {
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
	function exact_length($in, $params = array(), $fields = array()) {
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
	function greater_than($in, $params = array(), $fields = array()) {
		$min = $params['param'];
		return is_numeric($in) ? ($in > $min) : FALSE;
	}

	/***/
	function less_than($in, $params = array(), $fields = array()) {
		$max = $params['param'];
		return is_numeric($in) ? ($in < $max) : FALSE;
	}

	/***/
	function greater_than_equal_to($in, $params = array(), $fields = array()) {
		$min = $params['param'];
		return is_numeric($in) ? ($in >= $min) : FALSE;
	}

	/***/
	function less_than_equal_to($in, $params = array(), $fields = array()) {
		$max = $params['param'];
		return is_numeric($in) ? ($in <= $max) : FALSE;
	}

	/***/
	function alpha($in, $params = array(), $fields = array()) {
		return ctype_alpha($in);
	}

	/***/
	function alpha_numeric($in, $params = array(), $fields = array()) {
		return ctype_alnum((string) $in);
	}

	/***/
	function alpha_numeric_spaces($in, $params = array(), $fields = array()) {
		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $in);
	}

	/***/
	function alpha_dash($in, $params = array(), $fields = array()) {
		return (bool) preg_match('/^[a-z0-9_-]+$/i', $in);
	}

	/***/
	function numeric($in, $params = array(), $fields = array()) {
		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $in);
	}

	/***/
	function integer($in, $params = array(), $fields = array()) {
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $in);
	}

	/***/
	function decimal($in, $params = array(), $fields = array()) {
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $in);
	}

	/***/
	function is_natural($in, $params = array(), $fields = array()) {
		return ctype_digit((string) $in);
	}

	/***/
	function is_natural_no_zero($in, $params = array(), $fields = array()) {
		return ($in != 0 && ctype_digit((string) $in));
	}

	/***/
	function valid_email($in, $params = array(), $fields = array()) {
		return (bool) filter_var($in, FILTER_VALIDATE_EMAIL);
	}

	/***/
	function valid_emails($in, $params = array(), $fields = array()) {
		if (strpos($in, ',') === FALSE) {
			return $this->valid_email(trim($in), $params, $fields);
		}
		foreach (explode(',', $in) as $email) {
			if (trim($email) !== '' && $this->valid_email(trim($email), $params, $fields) === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/***/
	function valid_base64($in, $params = array(), $fields = array()) {
		return (base64_encode(base64_decode($in)) === $in);
	}

	/***/
	function prep_for_form($in, $params = array(), $fields = array()) {
		if ($this->_safe_form_data === FALSE OR empty($data)) {
			return $data;
		}
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = $this->prep_for_form($val, $params, $fields);
			}
			return $data;
		}
		return str_replace(array("'", '"', '<', '>'), array('&#39;', '&quot;', '&lt;', '&gt;'), stripslashes($data));
	}

	/***/
	function prep_url($in, $params = array(), $fields = array()) {
		if ($in === 'http://' OR $in === '') {
			return '';
		}
		if (strpos($in, 'http://') !== 0 && strpos($in, 'https://') !== 0) {
			return 'http://'.$in;
		}
		return $in;
	}

	/***/
	function encode_php_tags($in, $params = array(), $fields = array()) {
		return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $in);
	}

	/***/
	function valid_ip($in, $params = array(), $fields = array()) {
// TODO
//		return $this->CI->input->valid_ip($ip, $which);
		return true;
	}

	/***/
	function xss_clean($in, $params = array(), $fields = array()) {
// TODO
//		return $this->CI->security->xss_clean($in);
	}

	/***/
	function strip_image_tags($in, $params = array(), $fields = array()) {
// TODO
//		return $this->CI->security->strip_image_tags($in);
		return true;
	}

	/***/
	function captcha($in, $params = array(), $fields = array()) {
// TODO
		return true;
	}
}
