<?php
/**
 * Implementation strlen() function for UTF-8 encoding string.
 *
 * @param	string  $str
 * @return   int
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   <chernyshevsky at hotmail dot com>
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.3
 */
function utf8_strlen($str)
{
	if (function_exists('mb_strlen')) return mb_strlen($str, 'utf-8');

	/*
	utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
	It's much faster than iconv_strlen()
	Note: this function does not count bad UTF-8 bytes in the string - these are simply ignored
	*/
	return strlen(utf8_decode($str));

	/*
	DEPRECATED below
	if (function_exists('iconv_strlen')) return iconv_strlen($str, 'utf-8');

	#Do not count UTF-8 continuation bytes.
	#return strlen(preg_replace('/[\x80-\xBF]/sS', '', $str));
	*/
}

/*
#:NOTE:
#Тесты показали, что этот способ работает медленнее, чем хак через utf8_decode()
function utf8_strlen($str)
{
	preg_match_all('~[\x09\x0A\x0D\x20-\x7E]			 # ASCII
					 | [\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
					 |  \xE0[\xA0-\xBF][\x80-\xBF]	   # excluding overlongs
					 | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
					 |  \xED[\x80-\x9F][\x80-\xBF]	   # excluding surrogates
					 |  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
					 | [\xF1-\xF3][\x80-\xBF]{3}		 # planes 4-15
					 |  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
					~xs', $str, $m);
	return count($m[0]);
}

#:NOTE:
#Тесты показали, что этот способ работает медленнее, чем через регулярное выражение!
function utf8_strlen($str)
{
	$n = 0;
	for ($i = 0, $len = strlen($str); $i < $len; $i++)
	{
		$c = ord(substr($str, $i, 1));
		if ($c < 0x80) $n++;			  #single-byte (0xxxxxx)
		elseif (($c & 0xC0) == 0xC0) $n++;   #multi-byte starting byte (11xxxxxx)
	}
	return $n;
}
*/

?>
