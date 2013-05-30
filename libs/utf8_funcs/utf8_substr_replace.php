<?php
/**
 * Implementation substr_replace() function for UTF-8 encoding string.
 *
 * @created  2008-12-25
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_substr_replace(/*string*/ $string,
                             /*string*/ $replacement,
                             /*int*/    $start,
                             /*int*/    $length = null)
{
    if (! function_exists('utf8_str_split')) include_once 'utf8_str_split.php';
    if (! is_array($a = utf8_str_split($string))) return false;
    array_splice($a, $start, $length, $replacement);
    return implode('', $a);
}
?>

