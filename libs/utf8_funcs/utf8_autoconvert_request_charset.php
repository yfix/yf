<?php

/**
 * Перекодирует значения элементов массивов $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES из кодировки cp1251 в UTF-8, если необходимо.
 * Побочным положительным эффектом является защита от XSS атаки с непечатаемыми символами на уязвимые PHP функции.
 * Алгоритм работы:
 * 1) Функция проверяет массивы $_GET, $_POST, $_COOKIE, $_REQUEST, $_FILES
 *	на корректность значений элементов кодировке UTF-8.
 * 2) Значения не в UTF-8 принимаются как cp1251 и конвертируется в UTF-8,
 *	при этом байты от 0x00 до 0x7F (ASCII) сохраняются как есть.
 * 3) Сконвертированные значения снова проверяются.
 *	Если данные опять не в кодировке UTF-8, то они считаются разбитыми и функция возвращает FALSE.
 * Т.о. веб-формы можно посылать на сервер в 2-х кодировках: cp1251 и UTF-8.
 * Функция должна вызываться после utf8_unescape_request()!
 * Параметры для тестирования: ?тест[тест]=тест (можно просто дописать в адресную строку браузера IE >= 5.x)
 *
 * @param	bool   $is_hex2bin  Декодировать HEX-данные?
 *							   Пример: 0xd09ec2a0d0bad0bed0bcd0bfd0b0d0bdd0b8d0b8 => О компании
 *							   Параметры в URL адресах иногда бывает удобно кодировать не функцией rawurlencode(),
 *							   а использовать следующий механизм (к тому же кодирующий данные более компактно):
 *							   '0x' . bin2hex($string)
 * @return   bool				Возвращает TRUE, если все значения элементов массивов
 *							   в кодировке UTF-8 и FALSE + E_USER_WARNING в противном случае.
 * @see	  utf8_unescape_request()
 * @depends  is_utf8(), cp1259_to_utf8()
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.1.2
 */
function utf8_autoconvert_request_charset($is_hex2bin = false)
{
	$is_converted = false;
	$is_utf8 = true;
	if (! function_exists('is_utf8')) include_once 'is_utf8.php';
	foreach (array('_GET', '_POST', '_COOKIE', '_FILES') as $k => $v)
	{
		if (! array_key_exists($v, $GLOBALS)) continue;
		$is_broken = false;
		#использовать array_walk_recursive() не предоставляется возможным,
		#т.к. его callback функция не поддерживает передачу ключа по ссылке
		$GLOBALS[$v] = _utf8_autoconvert_request_charset_recursive($GLOBALS[$v], $is_converted, $is_broken, $is_hex2bin);
		if ($is_broken)
		{
			$is_utf8 = false;
			trigger_error('Array $' . $v . ' does not have keys/values in UTF-8 charset!', E_USER_WARNING);
		}
	}
	if ($is_converted && ! $is_broken)
	{
		$_REQUEST =
			(isset($_COOKIE) ? $_COOKIE : array()) +
			(isset($_POST) ? $_POST : array()) +
			(isset($_GET) ? $_GET : array());
	}
	return $is_utf8;
}

function _utf8_autoconvert_request_charset_recursive(&$data, &$is_converted, &$is_broken, &$is_hex2bin)
{
	if (is_array($data))
	{
		$d = array();
		foreach ($data as $k => &$v)
		{
			$k = _utf8_autoconvert_request_charset($k, $is_converted, $is_broken, $is_hex2bin);
			$d[$k] = _utf8_autoconvert_request_charset_recursive($v, $is_converted, $is_broken, $is_hex2bin);
		}
		return $d;
	}
	return _utf8_autoconvert_request_charset($data, $is_converted, $is_broken, $is_hex2bin);
}

function _utf8_autoconvert_request_charset(&$s, &$is_converted, &$is_broken, &$is_hex2bin)
{
	#используем strpos() для оптимизации скорости медленных рег. выражений
	if ($is_hex2bin && strpos($s, '0x') === 0 && preg_match('/^0x((?:[\da-fA-F]{2})+)$/sS', $s, $m))
	{
		$s = pack('H' . strlen($m[1]), $m[1]); #hex2bin()
		$is_converted = true;
	}
	if (! is_utf8($s))
	{
		$is_converted = true;
		if (! function_exists('cp1259_to_utf8')) include_once 'cp1259_to_utf8.php';
		$s = cp1259_to_utf8($s);
		if (! $is_broken && ! is_utf8($s)) $is_broken = true;
	}
	return $s;
}

?>