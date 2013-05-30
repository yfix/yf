<?
/**
 * Convert UTF-16 / UCS-2 encoding string to UTF-8.
 * Surrogates UTF-16 are supported!
 *
 * Преобразует строку из кодировки UTF-16 / UCS-2 в UTF-8.
 * Суррогаты UTF-16 поддерживаются!
 *
 * @param    string        $s
 * @param    string        $type      'BE' -- big endian byte order
 *                                    'LE' -- little endian byte order
 * @param    bool          $to_array  returns array chars instead whole string?
 * @return   string/array/false       UTF-8 string, array chars or FALSE if error occured
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat  <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function utf16_to_utf8($s, $type = 'BE', $to_array = false)
{
    static $types = array(
        'BE' => 'n',  #unsigned short (always 16 bit, big endian byte order)
        'LE' => 'v',  #unsigned short (always 16 bit, little endian byte order)
    );
    if (! array_key_exists($type, $types))
    {
        trigger_error('Unexpected value in second parameter, "' . $type . '" given!', E_USER_WARNING);
        return false;
    }
    #the fastest way:
    if (! $to_array && function_exists('iconv')) return iconv('UTF-16' . $type, 'UTF-8', $s);

    /*
    http://en.wikipedia.org/wiki/UTF-16

    The improvement that UTF-16 made over UCS-2 is its ability to encode 
    characters in planes 1–16, not just those in plane 0 (BMP). 

    UTF-16 represents non-BMP characters (those from U+10000 through U+10FFFF)
    using a pair of 16-bit words, known as a surrogate pair.
    First 1000016 is subtracted from the code point to give a 20-bit value. 
    This is then split into two separate 10-bit values each of which is represented 
    as a surrogate with the most significant half placed in the first surrogate. 
    To allow safe use of simple word-oriented string processing, separate ranges 
    of values are used for the two surrogates: 0xD800–0xDBFF for the first, most 
    significant surrogate and 0xDC00-0xDFFF for the second, least significant surrogate.

    For example, the character at code point U+10000 becomes the code unit sequence 0xD800 0xDC00, 
    and the character at U+10FFFD, the upper limit of Unicode, becomes the sequence 0xDBFF 0xDFFD. 
    Unicode and ISO/IEC 10646 do not, and will never, assign characters to any of the code points 
    in the U+D800–U+DFFF range, so an individual code value from a surrogate pair does not ever 
    represent a character.

    http://www.russellcottrell.com/greek/utilities/SurrogatePairCalculator.htm
    http://www.russellcottrell.com/greek/utilities/UnicodeRanges.htm

    Conversion of a Unicode scalar value S to a surrogate pair <H, L>:
      H = Math.floor((S - 0x10000) / 0x400) + 0xD800;
      L = ((S - 0x10000) % 0x400) + 0xDC00;
    The conversion of a surrogate pair <H, L> to a scalar value:
      N = ((H - 0xD800) * 0x400) + (L - 0xDC00) + 0x10000;
    */
    if (! function_exists('utf8_chr')) include_once 'utf8_chr.php';
    $a = array();
    $hi = false;
    foreach (unpack($types[$type] . '*', $s) as $codepoint)
    {
        #surrogate process
        if ($hi !== false)
        {
            $lo = $codepoint;
            if ($lo < 0xDC00 || $lo > 0xDFFF) $a[] = "\xEF\xBF\xBD"; #U+FFFD REPLACEMENT CHARACTER (for broken char)
            else
            {
                $codepoint = (($hi - 0xD800) * 0x400) + ($lo - 0xDC00) + 0x10000;
                $a[] = utf8_chr($codepoint);
            }
            $hi = false;
        }
        elseif ($codepoint < 0xD800 || $codepoint > 0xDBFF) $a[] = utf8_chr($codepoint); #not surrogate
        else $hi = $codepoint; #surrogate was found
    }
    return $to_array ? $a : implode('', $a);
}
?>