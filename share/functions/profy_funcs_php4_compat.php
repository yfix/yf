<?php

/**
* Put string contents to the file
*/
if (!function_exists("file_put_contents")) {
	function file_put_contents ($filename, $data) {
		if (!$fp = @fopen($filename, "w")) return false;
//		flock ($fp, LOCK_EX);
		$res = fwrite($fp, $data, strlen($data));
//		flock ($fp, LOCK_UN);
		fclose ($fp);
		return $res;
	}
}

/**
* Put data array as CSV line to a file pointer
* 
* @access	public
* @param	$fp			= the file resource to write to
* @param	$data		= the data to write out (1-dimensional array)
* @param	$delimeter	= the field separator
* @param	$enclosure
* @return	mixed	int|false	// Compilance with PHP5's function
*/
if (!function_exists('fputcsv')) {
	function fputcsv($fp, $data, $delimiter = ",", $enclosure = "\"") {
		$string = "";
		// No leading delimiter
		$write_delim = false;
		// Process elements
		foreach ((array)$data as $data_element) {
			// Replaces a double quote with two double quotes
			$data_element = str_replace("\"", "\"\"", $data_element);
			// Adds a delimiter before each field (except the first)
			if ($write_delim) {
				$string .= $delimiter;
			}
			// Encloses each field with $enclosure and adds it to the string
			$string .= $enclosure . $data_element . $enclosure;
			// Delimiters are used every time except the first.
			$write_delim = true;
		}
		// Append new line
		$string .= "\n";
		// Write the string to the file
		return fwrite($fp, $string);
	}
}

/**
* Get array of received headers for given URL (PHP4 compat)
*/
if (!function_exists('get_headers')) {
	function get_headers($url, $format = 0, $httpn = 0) {
		$fp = fsockopen($url, 80, $errno, $errstr, 30);
		if (!$fp) {
			return false;
		}
		$out = "GET / HTTP/1.1\r\n";
		$out .= "Host: $url\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite($fp, $out);
		while (!feof($fp)) {
			$var .= fgets($fp, 1280);
		}
		$var = explode("<", $var);
		$var = $var[0];
		$var = explode("\n", $var);
		fclose($fp);
		return $var;
	}
}

/**
* Convert a string to an array
*/
if (!function_exists('str_split')) {
	function str_split($string,$string_length=1) {
		if(strlen($string)>$string_length || !$string_length) {
			do {
				$c = strlen($string);
				$parts[] = substr($string,0,$string_length);
				$string = substr($string,$string_length);
			} while($string !== false);
		} else {
			$parts = array($string);
		}
		return $parts;
	}
}

if (!function_exists('array_intersect_key')) {
	function array_intersect_key($isec, $keys){
		$argc = func_num_args();
		if ($argc > 2) {
			for ($i = 1; !empty($isec) && $i < $argc; $i++)	{
				$arr = func_get_arg($i);
				foreach (array_keys($isec) as $key)	{
					if (!isset($arr[$key]))	{
						unset($isec[$key]);
					}
				}
			}
			return $isec;
		} else {
			$res = array();
			foreach (array_keys($isec) as $key)	{
				if (isset($keys[$key]))	{
					$res[$key] = $isec[$key];
				}
			}
			return $res;
		}
	}
}

if (!function_exists('http_build_query')) { 
	function http_build_query($data, $prefix='', $sep='', $key='') { 
		$ret = array(); 
		foreach ((array)$data as $k => $v) { 
			if (is_int($k) && $prefix != null) { 
				$k = urlencode($prefix . $k); 
			} 
			if ((!empty($key)) || ($key === 0))  $k = $key.'['.urlencode($k).']'; 
			if (is_array($v) || is_object($v)) { 
				array_push($ret, http_build_query($v, '', $sep, $k)); 
			} else { 
				array_push($ret, $k.'='.urlencode($v)); 
			} 
		} 
		if (empty($sep)) $sep = ini_get('arg_separator.output'); 
		return implode($sep, $ret); 
	}
}

if (!function_exists('array_combine')) {
	function array_combine($arr1, $arr2) {
		$out = array();
	
		$arr1 = array_values($arr1);
		$arr2 = array_values($arr2);
	
		foreach ((array)$arr1 as $key1 => $value1) {
			$out[(string)$value1] = $arr2[$key1];
		}
	
		return $out;
	}
}

if (!function_exists('array_walk_recursive')) {
	function array_walk_recursive(&$input, $funcname, $userdata = "") {
		if (!is_callable($funcname)) {
			return false;
		}
		if (!is_array($input)) {
			return false;
		}
		foreach ((array)$input AS $key => $value) {
			if (is_array($input[$key])) {
				array_walk_recursive($input[$key], $funcname, $userdata);
			} else {
				$saved_value = $value;
				if (!empty($userdata)) {
					$funcname($value, $key, $userdata);
				} else {
					$funcname($value, $key);
				}
				if ($value != $saved_value)	{
					$input[$key] = $value;
				}
			}
		}
		return true;
	}
}

/**
* Get values from the multi-dimensional array
*/
if (!function_exists('array_values_recursive')) {
	function array_values_recursive($ary) {
		$lst = array();
		foreach (array_keys($ary) as $k) {
			$v = $ary[$k];
			if (is_scalar($v)) {
				$lst[] = $v;
			} elseif (is_array($v)) {
				$lst = array_merge($lst, array_values_recursive($v));
			}
		}
		return $lst;
	}
}