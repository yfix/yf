<?php

$lang = conf('language');
if ($lang == 'ru') {
	$data = array(
		1	=> 'Public (могут видеть все посетители)',
		2	=> 'Members (только для зарегистрированных пользователей)',
		3	=> 'Friends (пользователь должен добавить Вас другом)',
		4	=> 'My Friends (пользователь должен быть в списке ваших друзей)',
		5	=> 'Mutual Friends (пользователь должен быть у Вас в друзьях а Вы у него)',
		9	=> 'Diary (только для себя)',
	);
} elseif ($lang == 'uk') {
	$data = array(
		1	=> 'Public (можуть побачити усі відвідувачі)',
		2	=> 'Members (тільки для зареєстрованих користувачів)',
		3	=> 'Friends (користувач повинен додати Вас у друзі)',
		4	=> 'My Friends (користувач повинен бути серед ваших друзів)',
		5	=> 'Mutual Friends (користувач повинен бути серед Ваших друзів а Ви серед його)',
		9	=> 'Diary (тільки для себе)',
	);
} else {
	$data = array(
		1	=> 'Public (everyone can view)',
		2	=> 'Members (only for registered users)',
		3	=> 'Friends (user must add you to friends list)',
		4	=> 'My Friends (user must be in your friends list)',
		5	=> 'Mutual Friends (friends to each other)',
		9	=> 'Diary (only for me)',
	);
}
return $data;