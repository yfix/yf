<?php

/**
 * Returns true if $data is valid UTF-8 and false otherwise.
 * Для значений null, integer, float, boolean возвращает TRUE.
 *
 * Массивы обходятся рекурсивно, если в хотябы одном элементе массива его значение
 * не в кодировке UTF-8, возвращается FALSE.
 *
 * @param	mixed(array/scalar/null)  $data
 * @param	bool					  $is_strict	строгая проверка диапазона ASCII?
 * @return   bool
 *
 * @link	 http://www.w3.org/International/questions/qa-forms-utf-8.html
 * @link	 http://ru3.php.net/mb_detect_encoding
 * @link	 http://webtest.philigon.ru/articles/utf8/
 * @link	 http://unicode.coeurlumiere.com/
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  4.0.2
 */
function is_utf8(&$data, $is_strict = true)
{
	if (is_array($data))
	{
		foreach ($data as $k => &$v) if (! is_utf8($v, $is_strict)) return false;
		return true;
	}
	elseif (is_string($data))
	{
		#the fastest variant:
		if (function_exists('iconv'))
		{
			$distance = strlen($data) - strlen(iconv('UTF-8', 'UTF-8//IGNORE', $data));
			if ($distance > 0) return false;
			if ($is_strict && preg_match('/[^\x09\x0A\x0D\x20-\xFF]/sS', $data)) return false;
			return true;
		}

		/*
		Рег. выражения имеют внутренние ограничения на длину повторов шаблонов поиска *, +, {x,y}
		равное 65536, поэтому используем preg_replace() вместо preg_match()
		*/
		$result = $is_strict ?
				  preg_replace('/(?>[\x09\x0A\x0D\x20-\x7E]		   # ASCII
								  | [\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
								  |  \xE0[\xA0-\xBF][\x80-\xBF]	   # excluding overlongs
								  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
								  |  \xED[\x80-\x9F][\x80-\xBF]	   # excluding surrogates
								  |  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
								  | [\xF1-\xF3][\x80-\xBF]{3}		 # planes 4-15
								  |  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
								  #| (.)							   # catch bad bytes
								 )*
								/sxS', '', $data) :
				  #The current check allows only values in the range U+0 to U+10FFFF, excluding U+D800 to U+DFFF.
				  preg_replace('/^\X*$/su', '', $data); #\X is equivalent to \P{M}\p{M}*+
		if (function_exists('preg_last_error'))
		{
			if (preg_last_error() === PREG_NO_ERROR) return $result === '';
			if (preg_last_error() === PREG_BAD_UTF8_ERROR) return false;
		}
		elseif (is_string($result)) return $result === '';

		#в этом месте произошла ошибка выполнения регулярного выражения
		#проверяем еще одним, но более медленным способом:
		if (! function_exists('utf8_check')) include_once 'utf8_check.php';
		return utf8_check($data, $is_strict);
	}
	elseif (is_scalar($data) || is_null($data)) return true;  #~ null, integer, float, boolean
	#~ object or resource
	trigger_error('Scalar, null or array type expected, ' . gettype($data) . ' given ', E_USER_WARNING);
	return false;
}
?>