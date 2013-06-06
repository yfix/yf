<?php
/**
* Wrapper class for converting character sets. This is to keep a consistent
* interface even if we decide to change the actual engine.
*
* EXAMPLE: Converting text from one char set to another
* <code>
* $convert = new yf_convert_charset();
* $converted_text = $convert->go( $string, $string_char_set, $destination_char_set );
* print $converted_text;
* </code>
*
*/

/**
* Wrapper class for converting character sets
*
* Does what it says on the tin.
*
*/
class yf_convert_charset {

	/**
	* Converts a text string from its current charset to a destination charset
	* As above
	*
	* @param	string	Text string
	* @param	string	Text string char set (original)
	* @param	string	Desired character set (destination)
	* @return	string
	*/
	function go ($string, $string_char_set, $destination_char_set = 'UTF-8') {
		$string_char_set	= strtolower($string_char_set);
		$t					= $string;
		// Did we pass a destination?
		$destination_char_set = strtolower($destination_char_set);
		// Not the same?
		if ($destination_char_set == $string_char_set) {
			return $string;
		}
		// Try to detect encoding automatically
		if (!$string_char_set && function_exists('mb_detect_encoding')) {
			$string_char_set = mb_detect_encoding($string);
		}
		if (!$string_char_set) {
			return $string;
		}
		// Do the convert
		if (function_exists('mb_convert_encoding')) {
			$text = mb_convert_encoding($string, $destination_char_set, $string_char_set);
		} elseif (function_exists('recode_string')) {
			$text = recode_string($string_char_set.'..'.$destination_char_set, $string);
		} elseif (function_exists('iconv')) {
			$text = iconv($string_char_set, $destination_char_set, $string);
		} else {
			require_once (PF_PATH. "libs/convertcharset/ConvertCharset.class.php");
			$convert	=& new ConvertCharset();
			$text		= $convert->Convert($string, $string_char_set, $destination_char_set, false);
		}
		return $text ? $text : $t;
	}
}
