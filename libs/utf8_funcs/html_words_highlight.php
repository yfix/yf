<?php

/**
 * "Подсветка" найденных слов для результатов поисковых систем.
 * Ищет все вхождения цифр или целых слов в html коде и обрамляет их заданными тагами.
 * Текст должен быть в кодировке UTF-8.
 * Поддерживаются английский, русский, татарский, турецкий языки.
 *
 * @param  string	 $s			   текст, в котором искать
 * @param  array	  $words		   массив поисковых слов
 * @param  bool	   $is_match_case   искать с учётом от регистра?
 * @param  string	 $tpl			 шаблон для замены
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  3.0.11
 */
function html_words_highlight($s, array $words = null, $is_match_case = false, $tpl = '<span class="highlight">%s</span>')
{
	#оптимизация для пустых значений
	if (! strlen($s) || ! $words) return $s;

	#оптимизация "Ту  134" = "Ту 134"
	#{{{
	if (! function_exists('utf8_convert_case')) include_once 'utf8_convert_case.php';  #оптимизация скорости include_once
	$s2 = utf8_convert_case($s, CASE_LOWER);
	foreach ($words as $k => $word)
	{
		$word = utf8_convert_case(trim($word, "\x20\r\n\t*"), CASE_LOWER);
		if ($word == '' || strpos($s2, $word) === false) unset($words[$k]);
	}
	if (! $words) return $s;
	#}}}

	#d($words);
	#кеширование построения рег. выражения для "подсвечивания" слов в функции при повторных вызовах
	static $func_cache = array();
	$cache_id = md5(serialize(array($words, $is_match_case)));
	if (! array_key_exists($cache_id, $func_cache))
	{
		#буквы в кодировке UTF-8 для разных языков:
		static $re_utf8_letter = '#английский алфавит:
								  [a-zA-Z]
								  #русский алфавит (A-я):
								  | \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]
								  #+ татарские буквы из кириллицы:
								  | \xd2[\x96\x97\xa2\xa3\xae\xaf\xba\xbb]|\xd3[\x98\x99\xa8\xa9]
								  #+ турецкие буквы из латиницы (татарский латиница):
								  | \xc3[\x84\xa4\x87\xa7\x91\xb1\x96\xb6\x9c\xbc]|\xc4[\x9e\x9f\xb0\xb1]|\xc5[\x9e\x9f]
								  ';
		#регулярное выражение для атрибутов тагов
		#корректно обрабатывает грязный и битый HTML в однобайтовой или UTF-8 кодировке!
		static $re_attrs_fast_safe =  '(?> (?>[\x20\r\n\t]+|\xc2\xa0)+  #пробельные символы (д.б. обязательно)
										   (?>
											 #правильные атрибуты
																			[^>"\']+
											 | (?<=[\=\x20\r\n\t]|\xc2\xa0) "[^"]*"
											 | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*\'
											 #разбитые атрибуты
											 |							  [^>]+
										   )*
									   )?';

		$re_words = array();
		foreach ($words as $word)
		{
			if ($is_mask = (substr($word, -1) === '*')) $word = rtrim($word, '*');

			$is_digit = ctype_digit($word);

			#рег. выражение для поиска слова с учётом регистра или цифр:
			$re_word = preg_quote($word, '/');

			#рег. выражение для поиска слова НЕЗАВИСИМО от регистра:
			if (! $is_match_case && ! $is_digit)
			{
				#для латинских букв
				if (preg_match('/^[a-zA-Z]+$/', $word)) $re_word = '(?i:' . $re_word . ')';
				#для русских и др. букв
				else
				{
					if (! function_exists('utf8_ucfirst')) include_once 'utf8_ucfirst.php';  #оптимизация скорости include_once
					$re_word_cases = array(
						'lowercase' => utf8_convert_case($re_word, CASE_LOWER),  #word
						'ucfirst'   => utf8_ucfirst($re_word),				   #Word
						'uppercase' => utf8_convert_case($re_word, CASE_UPPER),  #WORD
					);
					$re_word = '(?>' . implode('|', $re_word_cases) . ')';
				}
			}

			#d($re_word);
			if ($is_digit) $append = $is_mask ? '(?>\d*)' : '(?!\d)';
			else $append = $is_mask ? '(?>' . $re_utf8_letter . ')*' : '(?! ' . $re_utf8_letter . ')';
			$re_words[$is_digit ? 'digits' : 'words'][] = $re_word . $append;
		}#foreach
		#d($re_words);

		if (! empty($re_words['words']))
		{
			#поиск вхождения слова:
			$re_words['words'] = '(?<!' . $re_utf8_letter . ')  #просмотр назад
								  (' . implode("\r\n|\r\n", $re_words['words']) . ')   #=$m[3]
								  ';
		}
		if (! empty($re_words['digits']))
		{
			#поиск вхождения цифры:
			$re_words['digits'] = '(?<!\d)  #просмотр назад
								   (' . implode("\r\n|\r\n", $re_words['digits']) . ')   #=$m[4]
								   ';
		}
		#d($re_words);

		$func_cache[$cache_id] = '/#встроенный PHP, Perl, ASP код:
								   <([\?\%]) .*? \\1>

								   #блоки CDATA:
								   | <\!\[CDATA\[ .*? \]\]>

								   #MS Word таги типа "<![if! vml]>...<![endif]>",
								   #условное выполнение кода для IE типа "<!--[if lt IE 7]>...<![endif]-->":
								   | <\! (?>--)?
										 \[
										 (?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
										 \]
										 (?>--)?
									 >

								   #комментарии:
								   | <\!-- .*? -->

								   #парные таги вместе с содержимым:
								   | <((?i:noindex|script|style|comment|button|map|iframe|frameset|object|applet))' . $re_attrs_fast_safe . '>.*?<\/(?i:\\2)>  #=$m[2]

								   #парные и непарные таги:
								   | <[\/\!]?[a-zA-Z][a-zA-Z\d]*+' . $re_attrs_fast_safe . '\/?>

								   #html сущности:
								   | &(?> [a-zA-Z][a-zA-Z\d]++
										| \#(?> \d{1,4}
											  | x[\da-fA-F]{2,4}
											)
									  );
								   | ' . implode("\r\n|\r\n", $re_words) . '  #3 or 4
								  /sxS';
		#d($func_cache[$cache_id]);
	}
	$GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'] = $tpl;
	$s = preg_replace_callback($func_cache[$cache_id], '_html_words_highlight_callback', $s);
	unset($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL']);
	return $s;
}

function _html_words_highlight_callback(array $m)
{
	foreach (array(3, 4) as $i)
	{
		if (array_key_exists($i, $m) && strlen($m[$i]) > 0)
		{
			//d($m);
			return sprintf($GLOBALS['HTML_WORDS_HIGHLIGHT_TPL'], $m[$i]);
		}
	}#foreach

	#пропускаем таги
	return $m[0];
}

?>
