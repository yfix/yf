<?php

list($total_posts) = db()->query_fetch('SELECT COUNT(id) AS `0` FROM '.db('forum_posts').' WHERE status="a"');
if (module('forum')->SETTINGS['USE_GLOBAL_USERS']) {
	list($total_users) = db()->query_fetch('SELECT COUNT(id) AS `0` FROM '.db('user').' WHERE active="1"');
	list($last_user_id, $last_user_login) = db()->query_fetch('SELECT id AS `0`,nick AS 1 FROM '.db('user').' WHERE active="1" ORDER BY add_date DESC LIMIT 1');
} else {
	list($total_users) = db()->query_fetch('SELECT COUNT(id) AS `0` FROM '.db('forum_users').' WHERE status="a"');
	list($last_user_id, $last_user_login) = db()->query_fetch('SELECT id AS `0`,name AS 1 FROM '.db('forum_users').' WHERE status="a" ORDER BY user_regdate DESC LIMIT 1');
}
$data = array(
	'total_posts'		=> $total_posts,
	'total_users'		=> $total_users,
	'last_user_id'		=> $last_user_id,
	'last_user_login'	=> $last_user_login,
);
