<?php
/**
 * Converts a UNICODE codepoint to a UTF-8 character
 *
 * @param   int     $cp  Unicode codepoint
 * @return  string       UTF-8 character
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_chr($cp) # = utf8_from_unicode()
{
    static $cache = array();
    $cp = intval($cp);
    if (array_key_exists($cp, $cache)) return $cache[$cp]; #speed improve

    if ($cp <= 0x7f)     return $cache[$cp] = chr($cp);
    if ($cp <= 0x7ff)    return $cache[$cp] = chr(0xc0 | ($cp >> 6))  .
                                              chr(0x80 | ($cp & 0x3f));
    if ($cp <= 0xffff)   return $cache[$cp] = chr(0xe0 | ($cp >> 12)) .
                                              chr(0x80 | (($cp >> 6) & 0x3f)) .
                                              chr(0x80 | ($cp & 0x3f));
    if ($cp <= 0x10ffff) return $cache[$cp] = chr(0xf0 | ($cp >> 18)) .
                                              chr(0x80 | (($cp >> 12) & 0x3f)) .
                                              chr(0x80 | (($cp >> 6) & 0x3f)) .
                                              chr(0x80 | ($cp & 0x3f));
    #U+FFFD REPLACEMENT CHARACTER
    return $cache[$cp] = "\xEF\xBF\xBD";
}
?>
