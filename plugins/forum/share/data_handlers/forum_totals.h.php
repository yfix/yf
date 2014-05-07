<?php

$last_user = db()->get('SELECT id, nick FROM '.db('user').' WHERE active="1" ORDER BY add_date DESC LIMIT 1');
$data = array(
	'total_posts'		=> (int)db()->get_one('SELECT COUNT(id) FROM '.db('forum_posts').' WHERE active="1"'),
	'total_users'		=> (int)db()->get_one('SELECT COUNT(id) FROM '.db('user').' WHERE active="1"'),
	'last_user_id'		=> (int)$last_user['id'],
	'last_user_login'	=> $last_user['login'],
);
