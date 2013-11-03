<?php

// User stats refresh methods
class yf_user_stats_refresh {

	
	// Updated given user stats
	function _update_user_stats ($user_id = 0, $force_user_info = array()) {
		// Check for user id
		if (empty($user_id)) {
			return false;
		}
		// Prepare user info
		if (!empty($force_user_info)) {
			$user_info = $force_user_info;
			unset($force_user_info);
		} else {
			$user_info = user($user_id);
		}
		// Check if user exists
		if (empty($user_info["id"])) {
			return false;
		}
		//
		// GO gathering user stats
		//
		$totals = array();
		// Get unified items stats
		$sql_array = _class('user_stats')->_sql_array;
		$_sql_keys = array_keys($sql_array);
		// Add item names (auto) to show in query result (useful for debug)
		foreach ((array)$sql_array as $_k => $_v) {
			$sql_array[$_k] = str_replace("SELECT ", "SELECT '".$_k."', ", str_replace("{_USER_ID_}", intval($user_id), $_v));
		}
		// Get and assign unified data
		foreach ((array)db()->query_fetch_all("(".implode(") UNION ALL (", $sql_array).")") as $_counter => $_value) {
			$totals[$_sql_keys[$_counter]] = $_value[0];
		}
		// Friends
		$totals["friends"] = count((array)module("friends")->_get_user_friends_ids($user_id));
		// Interests
		$totals["interests"] = count((array)module("interests")->_get_for_user_id($user_id));
		// Do update stats
		$STATS_ARRAY = array(
			"user_id"		=> intval($user_info["id"]),
			"group_id"		=> intval($user_info["group"]),
			"ads"			=> intval($totals["ads"]),
			"reviews"		=> intval($totals["reviews"]),
			"gallery_photos"=> intval($totals["gallery_photos"]),
			"blog_posts"	=> intval($totals["blog_posts"]),
			"que_answers"	=> intval($totals["que_answers"]),
			"articles"		=> intval($totals["articles"]),
			"interests"		=> intval($totals["interests"]),
			"forum_posts"	=> intval($totals["forum_posts"]),
			"comments"		=> intval($totals["comments"]),
			"favorite_users"=> intval($totals["favorite_users"]),
			"ignored_users"	=> intval($totals["ignored_users"]),
			"paid_orders"	=> intval($totals["paid_orders"]),
			"friend_of"		=> intval($totals["friend_of"]),
			"friends"		=> intval($totals["friends"]),
			"nick"			=> _es($user_info["nick"]),
			"profile_url"	=> _es($user_info["profile_url"]),
		);
		db()->_add_shutdown_query(db()->REPLACE("user_stats", $STATS_ARRAY, 1));
		return $STATS_ARRAY;
	}

	
	// Refresh all stats
	function _refresh_all_stats () {
		// Delete all cached user stats first
		db()->query("TRUNCATE TABLE ".db('user_stats')."");
		// First we need to prepare stats for all users
		db()->query("REPLACE INTO ".db('user_stats')." (user_id,group_id,nick,profile_url) SELECT id,group,nick,profile_url FROM ".db('user')."");
		// Create temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE ".$tmp_table_name." ( 
				user_id	int(10) unsigned NOT NULL, 
				num_items	int(10) unsigned NOT NULL, 
				PRIMARY KEY (user_id),
				KEY (num_items)
			)"
		);
		// ############
		// Posted ads
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.ad_id) 
				FROM ".db('ads')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.ads = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Reviews for this user
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT escort_id, COUNT(t2.id) 
				FROM ".db('reviews')." AS t2 
				GROUP BY t2.escort_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.reviews = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Gallery photos
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('gallery_photos')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.gallery_photos = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Blog posts
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('blog_posts')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.blog_posts = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Que answers
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('que_answers')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.que_answers = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Forum posts
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('forum_posts')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.forum_posts = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Comments
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('comments')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.comments = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Favorite users
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('favorites')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.favorite_users = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Ignored users
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.target_user_id) 
				FROM ".db('ignore_list')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.ignored_users = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Paid orders
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('adv_orders')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.paid_orders = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Articles
		// ############
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.id) 
				FROM ".db('articles_texts')." AS t2 
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.articles = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
		// ############
		// Interests
		// ############
// TODO
		// ############
		// Friends
		// ############
// TODO
		// ############
		// Friend of
		// ############
// TODO:
/*
		db()->query(
			"INSERT INTO ".$tmp_table_name." (user_id,num_items) 
				SELECT user_id, COUNT(t2.*) 
				FROM ".db('friends')." AS t2 
				WHERE t2.friends_list LIKE '%,".intval($user_id).",%'
				GROUP BY t2.user_id"
		);
		db()->query(
			"UPDATE ".db('user_stats')."AS s, ".$tmp_table_name." AS tmp 
				SET s.friend_of = tmp.num_items 
				WHERE tmp.user_id=s.user_id"
		);
		db()->query("TRUNCATE TABLE ".$tmp_table_name."");
//		db()->query("UPDATE ".db('user_stats')."AS s SET friend_of = (SELECT COUNT(t2.*) FROM ".db('friends')." AS t2 WHERE t2.friends_list LIKE '%,".intval($user_id).",%'");
*/
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE ".$tmp_table_name."");
	}
}
