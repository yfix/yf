<?php
/**
 * Implementation str_split() function for UTF-8 encoding string.
 *
 * @created  2008-12-15
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.1
 */
function utf8_str_split(/*string*/ $string, /*int*/ $length = null)
{
	if (! is_string($string)) trigger_error('A string type expected in first parameter, ' . gettype($string) . ' given!', E_USER_ERROR);
	$length = ($length === null) ? 1 : intval($length);
	if ($length < 1) return false;
	#there are limits in regexp for {min,max}!
	if ($length < 100)
	{
		preg_match_all('/(?>[\x09\x0A\x0D\x20-\x7E]		   # ASCII
						  | [\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
						  |  \xE0[\xA0-\xBF][\x80-\xBF]	   # excluding overlongs
						  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
						  |  \xED[\x80-\x9F][\x80-\xBF]	   # excluding surrogates
						  |  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
						  | [\xF1-\xF3][\x80-\xBF]{3}		 # planes 4-15
						  |  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
						  #| (.)							   # catch bad bytes
						 ){1,' . $length . '}
						/xsS', $string, $m);
		$a =& $m[0];
	}
	else
	{
		preg_match_all('/(?>[\x09\x0A\x0D\x20-\x7E]		   # ASCII
						  | [\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
						  |  \xE0[\xA0-\xBF][\x80-\xBF]	   # excluding overlongs
						  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
						  |  \xED[\x80-\x9F][\x80-\xBF]	   # excluding surrogates
						  |  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
						  | [\xF1-\xF3][\x80-\xBF]{3}		 # planes 4-15
						  |  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
						  #| (.)							   # catch bad bytes
						 )
						/xsS', $string, $m);
		$a = array();
		for ($i = 0, $c = count($m[0]); $i < $c; $i += $length) $a[] = implode('', array_slice($m[0], $i, $length));
	}
	#check UTF-8 data
	$distance = strlen($string) - strlen(implode('', $a));
	if ($distance > 0)
	{
		trigger_error('Charset is not UTF-8, total ' . $distance . ' unknown bytes found!', E_USER_WARNING);
		return false;
	}
	return $a;
}
?>