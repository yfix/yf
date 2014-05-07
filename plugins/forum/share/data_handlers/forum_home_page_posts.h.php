<?php

$data = array();
$forums_array = main()->get_data('forum_forums');
foreach ((array)$forums_array as $forum_info) {
	if (empty($forum_info['last_post_id'])) {
		continue;
	}
	$last_posts_ids[$forum_info['id']] = $forum_info['last_post_id'];
}
if (!empty($last_posts_ids)) {
	$Q = db()->query('SELECT id,forum,topic,subject,user_id,user_name,created FROM '.db('forum_posts').' WHERE id IN('.implode(',',$last_posts_ids).')');
	while ($A = db()->fetch_assoc($Q)) {
		$posts[$A['id']] = $A;
		$_topics_ids[$A['topic']] = $A['topic'];
	}
	foreach ((array)$last_posts_ids as $_forum_id => $_post_id) {
		$data[$_forum_id] = $posts[$_post_id];
		$data[$_forum_id]['forum'] = $_forum_id;
	}
}
if (!empty($_topics_ids)) {
	$Q = db()->query('SELECT id,num_posts FROM '.db('forum_topics').' WHERE id IN('.implode(',',$_topics_ids).')');
	while ($A = db()->fetch_assoc($Q)) {
		$topics_total_posts[$A['id']] = $A['num_posts'];
	}
	foreach ((array)$data as $_post_id => $_post_info) {
		$data[$_post_info['forum']]['total_posts'] = $topics_total_posts[$_post_info['topic']];
	}
}
