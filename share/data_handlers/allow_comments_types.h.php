<?php

$lang = conf('language');
if ($lang == 'ru') {
	$data = array(
		1	=> 'Public (все могут комментировать)',
		2	=> 'Members (только для зарегистрированных пользователей)',
		3	=> 'Friends (пользователь должен добавить Вас другом)',
		4	=> 'My Friends (пользователь должен быть в списке ваших друзей)',
		5	=> 'Mutual Friends (пользователь должен быть у Вас в друзьях а Вы у него)',
		9	=> 'Disabled (комментирование выключено)',
	);
} elseif ($lang == 'uk') {
	$data = array(
		1	=> 'Public (усі можуть комментувати)',
		2	=> 'Members (комментувати можуть тільки зареєстровані користувачі)',
		3	=> 'Friends (користувач повинен додати Вас у друзі)',
		4	=> 'My Friends (користувач повинен бути серед ваших друзів)',
		5	=> 'Mutual Friends (користувач повинен бути серед Ваших друзів а Ви серед його)',
		9	=> 'Disabled (коментування вимкнено)',
	);
} else {
	$data = array(
		1	=> 'Public (anyone can post comments)',
		2	=> 'Members (only members can post comments)',
		3	=> 'Friends (user must add you to friends list)',
		4	=> 'My Friends (user must be in your friends list)',
		5	=> 'Mutual Friends (friends to each other)',
		9	=> 'Disabled (No one can post comments)',
	);
}
return $data;