<?php
/**
 * Implementation chunk_split() function for UTF-8 encoding string.
 *
 * @created  2008-12-15
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_chunk_split(/*string*/ $string, /*int*/ $length = null, /*string*/ $glue = null)
{
    if (! is_string($string)) trigger_error('A string type expected in first parameter, ' . gettype($string) . ' given!', E_USER_ERROR);
    $length = intval($length);
    $glue   = strval($glue);
    if ($length < 1) $length = 76;
    if ($glue === '') $glue = "\r\n";
    if (! function_exists('utf8_str_split')) include_once 'utf8_str_split.php';
    if (! is_array($a = utf8_str_split($string, $length))) return false;
    return implode($glue, $a);
}
?>