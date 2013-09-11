<?php

/**
*/
class yf_form_validate {
	function required($in) {
		return is_array($in) ? (bool) count($in) : (trim($in) !== '');
	}
	function matches($in) {
// TODO
	}
	function is_unique($in) {
// TODO
	}
	function min_length($in) {
// TODO
	}
	function max_length($in) {
// TODO
	}
	function exact_length($in) {
// TODO
	}
	function greater_than($in) {
// TODO
	}
	function less_than($in) {
// TODO
	}
	function alpha($in) {
// TODO
	}
	function alpha_numeric($in) {
// TODO
	}
	function alpha_dash($in) {
// TODO
	}
	function numeric($in) {
// TODO
	}
	function integer($in) {
// TODO
	}
	function decimal($in) {
// TODO
	}
	function is_natural($in) {
// TODO
	}
	function is_natural_no_zero($in) {
// TODO
	}
	function valid_email($in) {
// TODO
	}
	function valid_emails($in) {
// TODO
	}
	function valid_ip($in) {
// TODO
	}
	function valid_base64($in) {
// TODO
	}
	function xss_clean($in) {
// TODO
	}
	function prep_for_form($in) {
// TODO
	}
	function prep_url($in) {
// TODO
	}
	function strip_image_tags($in) {
// TODO
	}
	function encode_php_tags($in) {
// TODO
	}
/*
	public function regex_match($in, $regex)
	{
		return (bool) preg_match($regex, $in);
	}
	public function matches($in, $field)
	{
		return isset($this->_field_data[$field], $this->_field_data[$field]['postdata'])
			? ($in === $this->_field_data[$field]['postdata'])
			: FALSE;
	}
	public function differs($in, $field)
	{
		return ! (isset($this->_field_data[$field]) && $this->_field_data[$field]['postdata'] === $in);
	}
	public function is_unique($in, $field)
	{
		sscanf($field, '%[^.].%[^.]', $table, $field);
		if (isset($this->CI->db))
		{
			$query = $this->CI->db->limit(1)->get_where($table, array($field => $in));
			return $query->num_rows() === 0;
		}
		return FALSE;
	}
	public function min_length($in, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
		else
		{
			$val = (int) $val;
		}

		return (MB_ENABLED === TRUE)
			? ($val <= mb_strlen($in))
			: ($val <= strlen($in));
	}
	public function max_length($in, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
		else
		{
			$val = (int) $val;
		}

		return (MB_ENABLED === TRUE)
			? ($val >= mb_strlen($in))
			: ($val >= strlen($in));
	}
	public function exact_length($in, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}
		else
		{
			$val = (int) $val;
		}

		return (MB_ENABLED === TRUE)
			? (mb_strlen($in) === $val)
			: (strlen($in) === $val);
	}
	public function valid_url($in)
	{
		if (empty($in))
		{
			return FALSE;
		}
		elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $in, $matches))
		{
			if (empty($matches[2]))
			{
				return FALSE;
			}
			elseif ( ! in_array($matches[1], array('http', 'https'), TRUE))
			{
				return FALSE;
			}

			$in = $matches[2];
		}

		$in = 'http://'.$in;

		// There's a bug affecting PHP 5.2.13, 5.3.2 that considers the
		// underscore to be a valid hostname character instead of a dash.
		// Reference: https://bugs.php.net/bug.php?id=51192
		if (version_compare(PHP_VERSION, '5.2.13', '==') === 0 OR version_compare(PHP_VERSION, '5.3.2', '==') === 0)
		{
			sscanf($in, 'http://%[^/]', $host);
			$in = substr_replace($in, strtr($host, array('_' => '-', '-' => '_')), 7, strlen($host));
		}

		return (filter_var($in, FILTER_VALIDATE_URL) !== FALSE);
	}
	public function valid_email($in)
	{
		return (bool) filter_var($in, FILTER_VALIDATE_EMAIL);
	}
	public function valid_emails($in)
	{
		if (strpos($in, ',') === FALSE)
		{
			return $this->valid_email(trim($in));
		}

		foreach (explode(',', $in) as $email)
		{
			if (trim($email) !== '' && $this->valid_email(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	public function valid_ip($ip, $which = '')
	{
		return $this->CI->input->valid_ip($ip, $which);
	}
	public function alpha($in)
	{
		return ctype_alpha($in);
	}
	public function alpha_numeric($in)
	{
		return ctype_alnum((string) $in);
	}
	public function alpha_numeric_spaces($in)
	{
		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $in);
	}
	public function alpha_dash($in)
	{
		return (bool) preg_match('/^[a-z0-9_-]+$/i', $in);
	}
	public function numeric($in)
	{
		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $in);

	}
	public function integer($in)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $in);
	}
	public function decimal($in)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $in);
	}
	public function greater_than($in, $min)
	{
		return is_numeric($in) ? ($in > $min) : FALSE;
	}
	public function greater_than_equal_to($in, $min)
	{
		return is_numeric($in) ? ($in >= $min) : FALSE;
	}
	public function less_than($in, $max)
	{
		return is_numeric($in) ? ($in < $max) : FALSE;
	}
	public function less_than_equal_to($in, $max)
	{
		return is_numeric($in) ? ($in <= $max) : FALSE;
	}
	public function is_natural($in)
	{
		return ctype_digit((string) $in);
	}
	public function is_natural_no_zero($in)
	{
		return ($in != 0 && ctype_digit((string) $in));
	}
	public function valid_base64($in)
	{
		return (base64_encode(base64_decode($in)) === $in);
	}
	public function prep_for_form($data = '')
	{
		if ($this->_safe_form_data === FALSE OR empty($data))
		{
			return $data;
		}

		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $this->prep_for_form($val);
			}

			return $data;
		}

		return str_replace(array("'", '"', '<', '>'), array('&#39;', '&quot;', '&lt;', '&gt;'), stripslashes($data));
	}
	public function prep_url($in = '')
	{
		if ($in === 'http://' OR $in === '')
		{
			return '';
		}

		if (strpos($in, 'http://') !== 0 && strpos($in, 'https://') !== 0)
		{
			return 'http://'.$in;
		}

		return $in;
	}
	public function strip_image_tags($in)
	{
		return $this->CI->security->strip_image_tags($in);
	}
	public function xss_clean($in)
	{
		return $this->CI->security->xss_clean($in);
	}
	public function encode_php_tags($in)
	{
		return str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $in);
	}
*/
}
