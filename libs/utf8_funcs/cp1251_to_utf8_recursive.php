<?php
/**
 * Функция для перекодировки данных произвольной структуры из кодировки cp1251 в кодировку UTF-8.
 * Функция может работать без использования библиотеки iconv.
 *
 * @param   mixed  $data
 * @return  mixed
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.1
 */
function cp1251_to_utf8_recursive(/*mixed*/ $data)
{
    if (is_array($data))
    {
        $d = array();
        foreach ($data as $k => &$v) $d[cp1251_to_utf8_recursive($k)] = cp1251_to_utf8_recursive($v);
        return $d;
    }
    if (is_string($data))
    {
        if (function_exists('iconv')) return iconv('cp1251', 'utf-8//IGNORE//TRANSLIT', $data);
        if (! function_exists('cp1259_to_utf8')) include_once 'cp1259_to_utf8.php';
        return cp1259_to_utf8($data);
    }
    if (is_scalar($data) or is_null($data)) return $data;
    #throw warning, if the $data is resource or object:
    trigger_error('An array, scalar or null type expected, ' . gettype($data) . ' given!', E_USER_WARNING);
    return $data;
}
?>