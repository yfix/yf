<?php
/**
 * Implementation strpos() function for UTF-8 encoding string
 *
 * @param	string		   $haystack   The entire string
 * @param	string		   $needle	 The searched substring
 * @param	int			  $offset	 The optional offset parameter specifies the position from which the search should be performed
 * @return   mixed(int/false)			 Returns the numeric position of the first occurrence of needle in haystack.
 *										If needle is not found, utf8_strpos() will return FALSE.
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_strpos($haystack, $needle, $offset = null)
{
	if ($offset === null or $offset < 0) $offset = 0;
	if (function_exists('mb_strpos')) return mb_strpos($haystack, $needle, $offset, 'utf-8');
	if (function_exists('iconv_strpos')) return iconv_strpos($haystack, $needle, $offset, 'utf-8');
	if (! function_exists('utf8_strlen')) include_once 'utf8_strlen.php';
	$byte_pos = $offset;
	do if (($byte_pos = strpos($haystack, $needle, $byte_pos)) === false) return false;
	while (($char_pos = utf8_strlen(substr($haystack, 0, $byte_pos++))) < $offset);
	return $char_pos;
}
?>