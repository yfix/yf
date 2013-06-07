<?php
/**
 * Implementation strcasecmp() function for UTF-8 encoding string.
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_casecmp($s1, $s2)
{
	if (! function_exists('utf8_convert_case')) include_once 'utf8_convert_case.php';
	return strcmp(utf8_lowercase($s1), utf8_lowercase($s2));
}

