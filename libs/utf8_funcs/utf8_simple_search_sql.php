<?php

/**
 * Создает условия для простого поискового SQL запроса, основанного на LIKE и REGEXP.
 * Поисковая фраза и данные в таблице БД д.б. в кодировке UTF-8!
 *
 * LIKE используется для оптимизации скорости (и стоит в условии первым!),
 * REGEXP -- для точного поиска целых вхождений слов и чисел.
 *
 * Синтаксис и примеры поискового запроса:
 *   "квантов* механик*", "сервер 3.4GHz 1024Mb", "смысл* жизни" -- ищутся вхождения всех слов (логическое "И")
 *   "квантов* | механик*" -- ищутся вхождения любого найденного (логическое "ИЛИ")
 *   Необязательный символ "*" заменяет ноль или более букв и может стоять только в конце слова!
 *   В начале или в середине слова звёздочка стоять не может!
 *   Регистр слов при поиске не учитывается!
 *
 * @param    string               $s                поисковая фраза
 * @param    array                &$words           массив поисковых слов из запроса (слова и цифры)
 *                                                  может понадобится, например, при "подсвечивании" найденных слов в тексте
 * @param    mixed(string/array)  $field            поле или массив полей, по которым будет производиться поиск
 * @param    bool                 $is_result_array  возвращать результат в виде массива?
 *                                                  (ключами явл. имена полей, значениями SQL условия)
 * @return   mixed(string/array/false)              строка или массив с SQL кодом для условия WHERE
 *                                                  имя поля обозначается через {field}
 *                                                  возвращает FALSE в случае ошибки в синтаксисе поискового запроса
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.1.0
 */
function utf8_simple_search_sql($s, array &$words = null, $field = '{field}', $is_result_array = false)
{
    #хак для MySQL для поиска целых слов в кодировке UTF-8 через REGEXP
    #английский + турецкие буквы (татарский латиница)
    #русский + татарские буквы (кириллица)

    #предыдущий/последующий символ -- не буква (lowercase):
    #/*static*/ $re_utf8_no_letter_begin_lc = "([^a-z\x80-\x8f\x91\x97\x99\x9f\xa3\xa4\xa7\xa9\xaf\xb0-\xbf]|\xc3[^\xa4\xa7\xb1\xb6\xbc]|\xc4[^\x9f\xb1]|\xc5[^\x9f]|\xd0[^\xb0-\xbf]|\xd1[^\x80-\x8f\x91]|\xd2[^\x97\xa3\xaf\xbb]|\xd3[^\x99\xa9]|^)";
    #/*static*/ $re_utf8_no_letter_end_lc   = "([^a-z\xc3-\xc5\xd0-\xd3]|\xc3[^\xa4\xa7\xb1\xb6\xbc]|\xc4[^\x9f\xb1]|\xc5[^\x9f]|\xd0[^\xb0-\xbf]|\xd1[^\x80-\x8f\x91]|\xd2[^\x97\xa3\xaf\xbb]|\xd3[^\x99\xa9]|$)";

    #предыдущий/последующий символ -- не буква (all):
    /*static*/ $re_utf8_no_letter_begin = "(^".
                                          "|[^a-zA-Z\x80-\xbf]".
                                          "|\xc3[^\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]".
                                          "|\xc4[^\x9f\xb1\x9e\xb0]".
                                          "|\xc5[^\x9f\x9e]".
                                          "|\xd0[^\x90-\xbf\x81]".
                                          "|\xd1[^\x80-\x8f\x91]".
                                          "|\xd2[^\x96\x97\xa2\xa3\xae\xaf\xba\xbb]".
                                          "|\xd3[^\x98\x99\xa8\xa9]".
                                          #сущности из utf8_html_entity_decode():
                                          "|\xc2[\xa0-\xbf]".
                                          "|\xc3[\x80-\xbf]".
                                          "|\xc6\x92|\xce[\x91-\xbf]|\xcf[\x80-\x92]|\xcf\x96".
                                          "|\xe2\x80[\xa2\xa6\xb2\xb3\xbe]".
                                          "|\xe2\x81\x84".
                                          "|\xe2\x84[\x98\x91\x9c\xa2\xb5]".
                                          "|\xe2\x86[\x90-\x94\xb5]".
                                          "|\xe2\x87[\x90-\x94]".
                                          "|\xe2\x88[\x80\x82\x83\x85\x87\x88\x89\x8b\x8f\x91\x92\x97\x9a\x9d\x9e\xa0\xa7\xa8\xa9\xaa\xab\xb4\xbc]".
                                          "|\xe2\x89[\x85\x88\xa0\xa1\xa4\xa5]".
                                          "|\xe2\x8a[\x82-\x87\x95\x97\xa5]".
                                          "|\xe2\x8b\x85".
                                          "|\xe2\x8c[\x88\x89\x8a\x8b\xa9\xaa]".
                                          "|\xe2\x97\x8a".
                                          "|\xe2\x99[\xa0\xa3\xa5\xa6]".
                                          "|\xc5[\x92\x93\xa0\xa1\xb8]".
                                          "|\xcb[\x86\x9c]".
                                          "|\xe2\x80[\x82\x83\x89\x8c-\x8f\x93\x94\x98\x99\x9a\x9c\x9d\x9e\xa0\xa1\xb0\xb9\xba]".
                                          "|\xe2\x82\xac)";
    
    /*static*/ $re_utf8_no_letter_end   = "($".
                                          "|[^a-zA-Z\xc3-\xc5\xd0-\xd3]".
                                          "|\xc3[^\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]".
                                          "|\xc4[^\x9f\xb1\x9e\xb0]".
                                          "|\xc5[^\x9f\x9e]".
                                          "|\xd0[^\x90-\xbf\x81]".
                                          "|\xd1[^\x80-\x8f\x91]".
                                          "|\xd2[^\x96\x97\xa2\xa3\xae\xaf\xba\xbb]".
                                          "|\xd3[^\x98\x99\xa8\xa9])";

    #буквы алфавитов
    static $re_letters = array(
        #английский + турецкие буквы (татарский латиница)
        'tr'    => '[a-zA-Z]|\xc3[\xa4\xa7\xb1\xb6\xbc\x84\x87\x91\x96\x9c]|\xc4[\x9f\xb1\x9e\xb0]|\xc5[\x9f\x9e]', #(all)
        'tr_uc' => '[A-Z]|\xc3[\x84\x87\x91\x96\x9c]|\xc4[\x9e\xb0]|\xc5\x9e', #(uppercase)
        'tr_lc' => '[a-z]|\xc3[\xa4\xa7\xb1\xb6\xbc]|\xc4[\x9f\xb1]|\xc5\x9f', #(lowercase)

        #русский + татарские буквы (кириллица)
        'tt'    => '\xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]                          #А-я (all)
                   |\xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]',  #татарские буквы (all)
        'tt_uc' => '\xd0[\x90-\xaf\x81]                      #А-Я (uppercase)
                   |\xd2[\x96\xa2\xae\xba]|\xd3[\x98\xa8]',  #татарские буквы (uppercase)
        'tt_lc' => '\xd0[\xb0-\xbf]|\xd1[\x80-\x8f\x91]      #а-я (lowercase)
                   |\xd2[\x97\xa3\xaf\xbb]|\xd3[\x99\xa9]',  #татарские буквы (lowercase)
    );

    $trans = array(
        "\xc2\xad" => '',   #вырезаем "мягкие" переносы строк (&shy;)
        "\xcc\x81" => '',   #знак ударения  (U+0301 «combining acute accent»)
        "\xc2\xa0" => ' ',  #неразрывный пробел
        "\xe2\x88\x92" => '-',  #minus sign (&minus;)
        "\xe2\x80\x93" => '-',  #en dash (&ndash;)
        "\xe2\x80\x94" => '-',  #em dash (&mdash;)
    );
    $s = strtr($s, $trans);
    #$s = str_replace(array_keys($trans), array_values($trans), trim($s, ' |'));

    #заменяем недопустимые символы из таблицы ASCII на пробел (за исключением " ", "|", "*")
    $s = preg_replace('/(?![\x20\x7c\x2a])[\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]/sS', ' ', $s);

    if (! function_exists('utf8_convert_case')) include_once 'utf8_convert_case.php'; #оптимизация скорости include_once
    $s = utf8_convert_case($s, CASE_LOWER);

    #проверка синтаксиса поискового запроса:
    if (! preg_match('/^(?:(?:(?>' . $re_letters['tr_lc'] . ')+  #слова английские или турецкие
                            | (?>' . $re_letters['tt_lc'] . ')+  #слова русские или татарские
                            | \d+
                            )(?>\*?\x20)?
                            \x20*(?>\|\x20*)?
                         )+
                       $/sxS', $s . ' ')) return false; #неверный поисковый запрос
    #d($s);
    if (! function_exists('utf8_ucfirst')) include_once 'utf8_ucfirst.php'; #оптимизация скорости include_once
    preg_match_all('/  ((?>' . $re_letters['tr_lc'] . ')+\*?)  #1 слова английские или турецкие
                     | ((?>' . $re_letters['tt_lc'] . ')+\*?)  #2 слова русские или татарские
                     | (\d+\*?)                                #3 цифры
                    /sxS', $s, $m);
    $words = array_unique($m[0]);

    $cond = (strpos($s, '|') === false) ? 'AND' : 'OR';
    $q = array();
    foreach ($words as $i => $word)
    {
        if ($is_mask = (substr($word, -1) === '*')) $word = rtrim($word, '*');

        /*
        ЗАМЕЧАНИЕ
          опция BINARY в LIKE/REGEXP позволяет не привязываться к кодировке в БД!
          bin2hex() позволяет сделать корректную замену {field} на имя поля
        */
        if (strlen($m[3][$i]) > 0)  #для цифр
        {
            $re_word = '([^0-9]|^)' . $word . ($is_mask ? '' : '([^0-9]|$)');
            $like    = '{field} LIKE   BINARY "%' . $word    . '%"';
            $regexp  = '{field} REGEXP BINARY  "' . $re_word . '"';
        }
        else  #для букв
        {
            $word_cases = array(
                'lowercase' => $word,                                 #word
                'ucfirst'   => utf8_ucfirst($word),                   #Word
                'uppercase' => utf8_convert_case($word, CASE_UPPER),  #WORD
            );
            $like = '( {field} LIKE BINARY 0x' . bin2hex('%' . $word_cases['lowercase'] . '%') . ' OR
                       {field} LIKE BINARY 0x' . bin2hex('%' . $word_cases['ucfirst']   . '%') . ' OR
                       {field} LIKE BINARY 0x' . bin2hex('%' . $word_cases['uppercase'] . '%') . ' )';
            $re_word = $re_utf8_no_letter_begin .
                       '(' . implode('|', $word_cases) . ')' .
                       ($is_mask ? '' : $re_utf8_no_letter_end);
            $regexp = '{field} REGEXP BINARY 0x' . bin2hex($re_word);
        }
        #:DEBUG:
        #d(array('like' => $like, 'regexp' => $regexp));

        if ($cond == 'AND')
        {
            $q['like'][]   = $like;
            $q['regexp'][] = $regexp;
        }
        else $q[] = '(' . $like . ' AND ' . $regexp . ')';
    }#foreach

    $r = '(' . implode("\r\n" . $cond . ' ', ($cond == 'AND') ? array_merge($q['like'], $q['regexp']) : $q) . ')';

    $a = array();
    $fields = is_array($field) ? $field : array($field);
    foreach ($fields as $k => $v)
    {
        if (! $v) trigger_error('Empty filed name was found at ' . $k . ' key of array!', E_USER_ERROR);
        $a[$v] = str_replace('{field}', $v, $r);
    }
    if ($is_result_array) return $a;
    return '(' . implode("\r\nOR\r\n", $a) . ')';
}

?>