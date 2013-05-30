<?php
/**
 * Converts a UTF-8 character to a UNICODE codepoint
 *
 * @param   string  $char  UTF-8 character
 * @return  int            Unicode codepoint
 *                         Returns FALSE if $char broken (not UTF-8)
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf8_ord($char) # = utf8_to_unicode()
{
    static $cache = array();
    if (array_key_exists($char, $cache)) return $cache[$char]; #speed improve

    switch (strlen($char))
    {
        case 1 : return $cache[$char] = ord($char);
        case 2 : return $cache[$char] = (ord($char{1}) & 63) |
                                        ((ord($char{0}) & 31) << 6);
        case 3 : return $cache[$char] = (ord($char{2}) & 63) |
                                        ((ord($char{1}) & 63) << 6) |
                                        ((ord($char{0}) & 15) << 12);
        case 4 : return $cache[$char] = (ord($char{3}) & 63) |
                                        ((ord($char{2}) & 63) << 6) |
                                        ((ord($char{1}) & 63) << 12) |
                                        ((ord($char{0}) & 7)  << 18);
        default :
            trigger_error('Character is not UTF-8!', E_USER_WARNING);
            return false;
    }#switch
}
?>
