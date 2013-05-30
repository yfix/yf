<?php
/**
 * Check the text in UTF-8 charset on given ranges of the standard UNICODE.
 * The suitable alternative to regular expressions.
 *
 * Проверят текст в кодировке UTF-8 на заданные диапазоны стандарта UNICODE.
 * Удобная альтернатива регулярным выражениям.
 *
 * http://www.unicode.org/charts/
 *
 * Examples:
 *   #A simple check the standard named ranges:
 *   unicode_blocks_check('поисковые системы Google и Yandex', array('Basic Latin', 'Cyrillic'));
 *   #You can check the named, direct ranges or codepoints together:
 *   unicode_blocks_check('поисковые системы Google и Yandex', array(array(0x20, 0x7E),     #[\x20-\x7E]
 *                                                                   array(0x0410, 0x044F), #[A-Яa-я]
 *                                                                   0x0401, #Ё
 *                                                                   0x0451, #ё
 *                                                                   'Arrows',
 *                                                                  ));
 * Возвращает TRUE, если все символы из текста принадлежат указанным диапазонам
 * и FALSE в противном случае или для разбитого UTF-8.
 *
 * @created  2008-12-22
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.0
 */
function unicode_blocks_check(/*string*/ $s, array $blocks)
{
    if ($s === '' || $s === null) return true; #speed improve
    if (! is_string($s)) trigger_error('A string/null type expected in first parameter, ' . gettype($s) . ' type given!', E_USER_ERROR);

    static $unicode_blocks;
    if (! $unicode_blocks) require 'unicode_blocks.php';

    if (! function_exists('utf8_str_split')) include_once 'utf8_str_split.php';
    if (! function_exists('utf8_ord'))       include_once 'utf8_ord.php';

    $chars = utf8_str_split($s);
    if ($chars === false) return false; #broken UTF-8
    unset($s); #memory free
    $skip = array(); #кэшируем уже проверенные символы
    foreach ($chars as $i => $char)
    {
        if (array_key_exists($char, $skip)) continue; #speed improve
        $codepoint = utf8_ord($char);
        if ($codepoint === false) return false; #broken UTF-8
        $is_valid = false;
        foreach ($blocks as $j => $block)
        {
            if (is_string($block))
            {
                if (! array_key_exists($block, $unicode_blocks))
                {
                    trigger_error('Unknown block "' . $block . '"!', E_USER_WARNING);
                    return false;
                }
                list ($min, $max) = $unicode_blocks[$block];
            }
            elseif (is_array($block)) list ($min, $max) = $block;
            elseif (is_int($block)) $min = $max = $block;
            else trigger_error('A string/array/int type expected for block[' . $j . ']!', E_USER_ERROR);
            if ($codepoint >= $min && $codepoint <= $max)
            {
                $is_valid = true;
                break;
            }
        }#foreach
        if (! $is_valid) return false;
        $skip[$char] = true;
    }#foreach
    return true;
}
?>
