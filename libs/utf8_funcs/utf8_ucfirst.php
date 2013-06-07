<?php
/**
 * Implementation ucfirst() function for UTF-8 encoding string.
 * Преобразует первый символ строки в кодировке UTF-8 в верхний регистр.
 *
 * @param   string	$s
 * @parm	bool	  $is_other_to_lowercase  остальные символы преобразуются в нижний регистр?
 * @return  string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  3.0.0
 */
function utf8_ucfirst($s, $is_other_to_lowercase = true)
{
	if ($s === '' or ! is_string($s)) return $s;
	if (preg_match('/^(.)(.*)$/us', $s, $m) === false) return false;
	if (! function_exists('utf8_convert_case')) include_once 'utf8_convert_case.php';
	return utf8_uppercase($m[1]) . ($is_other_to_lowercase ? utf8_lowercase($m[2]) : $m[2]);
}
?>