<?php

/**
 * Implementation substr() function for UTF-8 encoding string.
 *
 * @param    string  $str
 * @param    int     $offset
 * @param    int     $length
 * @return   string
 * @link     http://www.w3.org/International/questions/qa-forms-utf-8.html
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.5
 */
function utf8_substr($str, $offset, $length = null)
{
    #в начале пробуем найти стандартные функции
    if (function_exists('mb_substr')) return mb_substr($str, $offset, $length, 'utf-8'); #(PHP 4 >= 4.0.6, PHP 5)
    if (function_exists('iconv_substr')) return iconv_substr($str, $offset, $length, 'utf-8'); #(PHP 5)
    if (! function_exists('utf8_str_split')) include_once 'utf8_str_split.php';
    if (! is_array($a = utf8_str_split($str))) return false;
    if ($length !== null) $a = array_slice($a, $offset, $length);
    else                  $a = array_slice($a, $offset);
    return implode('', $a);
}
?>