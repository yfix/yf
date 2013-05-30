<?php
/**
 * Implementation strrev() function for UTF-8 encoding string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
*/
function utf8_strrev($string)
{
    if (! function_exists('utf8_str_split')) include_once 'utf8_str_split.php';
    if (! is_array($a = utf8_str_split($string))) return false;
    return implode('', array_reverse($a));
}
?>
