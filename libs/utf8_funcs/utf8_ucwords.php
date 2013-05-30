<?php
/**
 * Implementation ucwords() function for UTF-8 encoding string.
 * Преобразует в верхний регистр первый символ каждого слова в строке в кодировке UTF-8,
 * остальные символы каждого слова преобразуются в нижний регистр.
 * Эта функция считает словами последовательности символов, разделенных пробелом, переводом строки, возвратом каретки, горизонтальной табуляцией, неразрывным пробелом.
 *
 * @param   string    $s
 * @return  string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.1
 */
function utf8_ucwords($s)
{
    if (! function_exists('utf8_ucfirst')) include_once 'utf8_ucfirst.php';
    $words = preg_split('/([\x20\r\n\t]++|\xc2\xa0)/sS', $s, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    foreach ($words as $k => $word) $words[$k] = utf8_ucfirst($word);
    return implode('', $words);
}
?>