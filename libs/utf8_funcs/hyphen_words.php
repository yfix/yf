<?php

/**
 * Расстановка "мягких" переносов в словах.
 * Браузеры, которые показывают их: IE 6.0.x, Opera 7.54u2, Safari 3.1.1, Firefox 3.0.0
 * Поддерживается текст для русского (UTF-8) и английского языков (ANSI).
 *
 * TODO?  ftp://scon155.phys.msu.su/pub/russian/hyphen/
 *
 * @link	http://www.chebykin.ru/tutorials/hyphenation/
 * @link	http://shy.dklab.ru/newest/
 * @link	http://gramota.ru/
 *
 * @param   string   $s		текст
 * @param   string   $is_html  если TRUE, то html таги, комментарии и сущности не обрабатываются
 * @return  string
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.1.1
 */
function hyphen_words($s, $is_html = false)
{
	if (! $is_html)
	{
		$m = array($s);
		$m[3] =& $m[0];
		return _hyphen_words($m);
	}

	#регулярное выражение для атрибутов тагов
	#корректно обрабатывает грязный и битый HTML в однобайтовой или UTF-8 кодировке!
	$re_attrs_fast_safe =  '(?> (?>[\x20\r\n\t]+|\xc2\xa0)+  #пробельные символы (д.б. обязательно)
								(?>
								  #правильные атрибуты
																 [^>"\']+
								  | (?<=[\=\x20\r\n\t]|\xc2\xa0) "[^"]*"
								  | (?<=[\=\x20\r\n\t]|\xc2\xa0) \'[^\']*\'
								  #разбитые атрибуты
								  |							  [^>]+
								)*
							)?';
	$regexp = '/(?: #встроенный PHP, Perl, ASP код
					<([\?\%]) .*? \\1>  #1

					#блоки CDATA
				  | <\!\[CDATA\[ .*? \]\]>

					#MS Word таги типа "<![if! vml]>...<![endif]>",
					#условное выполнение кода для IE типа "<!--[if lt IE 7]>...<![endif]-->"
				  | <\! (?>--)?
						\[
						(?> [^\]"\']+ | "[^"]*" | \'[^\']*\' )*
						\]
						(?>--)?
					>

					#комментарии
				  | <\!-- .*? -->

					#парные таги вместе с содержимым
				  | <((?i:noindex|script|style|comment|button|map|iframe|frameset|object|applet))' . $re_attrs_fast_safe . '> .*? <\/(?i:\\2)>  #2

					#парные и непарные таги
				  | <[\/\!]?[a-zA-Z][a-zA-Z\d]*+' . $re_attrs_fast_safe . '\/?>

					#html сущности (&lt; &gt; &amp;) (+ корректно обрабатываем код типа &amp;amp;nbsp;)
				  | &(?>
						(?> [a-zA-Z][a-zA-Z\d]+
						  | \#(?> \d{1,4}
								| x[\da-fA-F]{2,4}
							  )
						);
					 )+

					#не html таги и не сущности
				  | ([^<&]{2,}+)  #3
				)
			   /sxS';
	return preg_replace_callback($regexp, '_hyphen_words', $s);
}

function _hyphen_words(array &$m)
{
	if (! array_key_exists(3, $m)) return $m[0];
	$s =& $m[0];

	#буква (letter)
	$l = '(?: \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]  #А-я (все)
			| [a-zA-Z]
		  )';

	#буква (letter)
	$l_en = '[a-zA-Z]';
	#буква (letter)
	$l_ru = '(?: \xd0[\x90-\xbf\x81]|\xd1[\x80-\x8f\x91]  #А-я (все)
			 )';

	#гласная (vowel)
	$v = '(?: \xd0[\xb0\xb5\xb8\xbe]|\xd1[\x83\x8b\x8d\x8e\x8f\x91]  #аеиоуыэюяё (гласные)
			| \xd0[\x90\x95\x98\x9e\xa3\xab\xad\xae\xaf\x81]		 #АЕИОУЫЭЮЯЁ (гласные)
			| (?i:[aeiouy])
		  )';

	#согласная (consonant)
	$c = '(?: \xd0[\xb1-\xb4\xb6\xb7\xba-\xbd\xbf]|\xd1[\x80\x81\x82\x84-\x89]  #бвгджзклмнпрстфхцчшщ (согласные)
			| \xd0[\x91-\x94\x96\x97\x9a-\x9d\x9f-\xa2\xa4-\xa9]				#БВГДЖЗКЛМНПРСТФХЦЧШЩ (согласные)
			| (?i:sh|ch|qu|[bcdfghjklmnpqrstvwxz])
		  )';

	#специальные
	$x = '(?:\xd0[\x99\xaa\xac\xb9]|\xd1[\x8a\x8c])';   #ЙЪЬйъь (специальные)

	if (0)
	{
		#алгоритм П.Христова в модификации Дымченко и Варсанофьева
		$rules = array(
			# $1	   $2
			"/($x)	 ($l$l)/sxS",
			"/($v)	 ($v$l)/sxS",
			"/($v$c)   ($c$v)/sxS",
			"/($c$v)   ($c$v)/sxS",
			"/($v$c)   ($c$c$v)/sxS",
			"/($v$c$c) ($c$c$v)/sx"
		);

		#improved rules by Dmitry Koteroff
		$rules = array(
			# $1					  $2
			"/($x)					($l (?>\xcc\x81)? $l)/sxS",
			"/($v (?>\xcc\x81)? $c$c) ($c$c$v)/sxS",
			"/($v (?>\xcc\x81)? $c$c) ($c$v)/sxS",
			"/($v (?>\xcc\x81)? $c)   ($c$c$v)/sxS",
			"/($c$v (?>\xcc\x81)? )   ($c$v)/sxS",
			"/($v (?>\xcc\x81)? $c)   ($c$v)/sxS",
			"/($c$v (?>\xcc\x81)? )   ($v (?>\xcc\x81)? $l)/sxS",
		);
	}

	#improved rules by Dmitry Koteroff and Rinat Nasibullin
	$rules = array(
		# $1					  $2
		"/($x)					($c (?>\xcc\x81)? $l)/sxS",
		"/($v (?>\xcc\x81)? $c$c) ($c$c$v)/sxS",
		"/($v (?>\xcc\x81)? $c$c) ($c$v)/sxS",
		"/($v (?>\xcc\x81)? $c)   ($c$c$v)/sxS",
		"/($c$v (?>\xcc\x81)? )   ($c$v)/sxS",
		"/($v (?>\xcc\x81)? $c)   ($c$v)/sxS",
		"/($c$v (?>\xcc\x81)? )   ($v (?>\xcc\x81)? $l)/sxS",
	);
	#\xc2\xad = &shy;  U+00AD SOFT HYPHEN
	return preg_replace($rules, "$1\xc2\xad$2", $s);
}

?>