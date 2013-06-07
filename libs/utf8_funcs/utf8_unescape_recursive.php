<?php

/**
 * Рекурсивный вариант utf8_unescape()
 *
 * @param	mixed(string/array) $data
 * @return   mixed
 * @see	  utf8_unescape()
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.4
 */
function utf8_unescape_recursive($data, $is_rawurlencode = false)
{
	if (! function_exists('utf8_unescape')) include_once 'utf8_unescape.php'; #оптимизация скорости include_once
	if (is_array($data))
	{
		$d = array();
		foreach ($data as $k => &$v) $d[utf8_unescape($k, $is_rawurlencode)] = call_user_func(__FUNCTION__, $v, $is_rawurlencode);
		return $d;
	}
	else return utf8_unescape($data, $is_rawurlencode);
}

?>