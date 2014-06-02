<?php

$lang = conf('language');
if ($lang == 'ru') {
	$data = array(
		1	=> 'Слушаю (Музыку)',
		2	=> 'Читаю (Книги)',
		3	=> 'Смотрю (DVD/Видео)',
		4	=> 'Играю (Видео игры)',
	);
} elseif ($lang == 'uk') {
	$data = array(
		1	=> 'Слухаю (Музику)',
		2	=> 'Читаю (Книги)',
		3	=> 'Дивлюсь (DVD/Відео)',
		4	=> 'Граю (Відео ігри)',
	);
} else {
	$data = array(
		1	=> 'Playing (Music)',
		2	=> 'Reading (Books)',
		3	=> 'Watching (DVD/Video)',
		4	=> 'Playing (Video Games)',
	);
}
return $data;