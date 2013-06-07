<?php

/**
 * 1) Корректирует глобальные массивы $_GET, $_POST, $_COOKIE, $_REQUEST
 *	декодируя значения в юникоде, закодированные через функцию javascript escape() ~ "%uXXXX"
 *	Cтандартный PHP 5.2.x этого делать не умеет.
 * 2) Если в HTTP_COOKIE есть параметры с одинаковым именем, то берётся последнее значение,
 *	а не первое (так обрабатывается QUERY_STRING).
 * 3) Создаёт массив $_POST для нестандартных Content-Type, например, "Content-Type: application/octet-stream".
 *	Стандартный PHP 5.2.x создаёт массив только для "Content-Type: application/x-www-form-urlencoded"
 *	и "Content-Type: multipart/form-data".
 *
 * @return   void
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.3.0
 */
function utf8_unescape_request()  #старое название функции - fixRequestUnicode()
{
	$fixed = false;
	/*
	ATTENTION!
	HTTP_RAW_POST_DATA is only accessible when Content-Type of POST request is NOT default "application/x-www-form-urlencoded"!
	*/
	$HTTP_RAW_POST_DATA = strcasecmp(@$_SERVER['REQUEST_METHOD'], 'POST') == 0 ? (isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : @file_get_contents('php://input')) : null;
	if (ini_get('always_populate_raw_post_data')) $GLOBALS['HTTP_RAW_POST_DATA'] = $HTTP_RAW_POST_DATA;
	foreach (array('_GET'	=> @$_SERVER['QUERY_STRING'],
				   '_POST'   => $HTTP_RAW_POST_DATA,
				   '_COOKIE' => @$_SERVER['HTTP_COOKIE']) as $k => $v)
	{
		if (! is_string($v)) continue;
		if ($k === '_COOKIE')
		{
			/*
			ЗАМЕЧАНИЕ
			PHP не правильно (?) обрабатывает заголовок HTTP_COOKIE, если там встречается параметры с одинаковым именем, но разными значениями.
			Пример HTTP-заголовка: "Cookie: sid=chpgs2fiak-330mzqza; sid=cmz5tnp5zz-xlbbgqp"
			В этом случае он берёт первое значение, а не последнее.
			Хотя если в QUERY_STRING есть такая ситуация, всегда берётся последний параметр, это правильно.
			В HTTP_COOKIE два параметра с одинаковым именем могут появиться, если отправить клиенту следующие HTTP-заголовки:
			"Set-Cookie: sid=chpgs2fiak-330mzqza; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=domain.com"
			"Set-Cookie: sid=cmz6uqorzv-1bn35110; expires=Thu, 15 Oct 2009 14:23:42 GMT; path=/; domain=.domain.com"
			См. так же: RFC 2965 - HTTP State Management Mechanism <http://tools.ietf.org/html/rfc2965>
			*/
			$v = preg_replace('/; *+/sS', '&', $v);
			unset($_COOKIE); #будем парсить HTTP_COOKIE сами
		}
		if (strpos($v, '%u') !== false)
		{
			if (! function_exists('utf8_unescape')) include_once 'utf8_unescape.php';
			parse_str(utf8_unescape($v, $is_rawurlencode = true), $GLOBALS[$k]);
			$fixed = true;
			continue;
		}
		if (@$GLOBALS[$k]) continue;
		parse_str($v, $GLOBALS[$k]);
		$fixed = true;
	}#foreach
	if ($fixed)
	{
		$_REQUEST =
			(isset($_COOKIE) ? $_COOKIE : array()) +
			(isset($_POST) ? $_POST : array()) +
			(isset($_GET) ? $_GET : array());
	}
}

?>