<?php
/**
 * Вычисляет высоту области редактирования текста (<textarea>) по значению и ширине.
 *
 * В большинстве случаев будет корректно работать для моноширинных шрифтов.
 * Т.к. браузер переносит последнее слово, которое не умещается на строке,
 * на следующую строку, высота м.б. меньше ожидаемой.
 * Этот алгоритм явл. простым (и быстрым) и не отслеживает переносы слов.
 *
 * @param	string  $value	   текст в однобайтовой кодировке
 * @param	int	 $cols		ширина области редактирования (колонок)
 * @param	int	 $min_rows	минимальное кол-во строк
 * @param	int	 $max_rows	максимальное кол-во строк
 * @return   int
 *
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.3
 */
function textarea_rows($value, $cols, $min_rows = 3, $max_rows = 32)
{
	if (strlen($value) == 0) return $min_rows;
	$rows = 0;
	foreach (preg_split('/\r\n|[\r\n]/sS', $value) as $s)
	{
		$rows += ceil((strlen($s) + 1) / $cols);
		if ($rows > $max_rows) return $max_rows;
	}#foreach
	return ($rows < $min_rows) ? $min_rows : $rows;
}

function utf8_textarea_rows($value, $cols, $min_rows = 3, $max_rows = 32)
{
	#utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
	return textarea_rows(utf8_decode($value), $cols, $min_rows, $max_rows);
}
?>