<?php

/**
* Forum synchronization methods
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_sync {

	/** @var bool Delete or turn off wrong linked records */
	var $DELETE_WRONG_RECORDS = 1;
	
	/**
	* Synchronize total board
	*/
	function _sync_board($INNER_CALL = false) {
		if (!FORUM_IS_ADMIN && !$INNER_CALL) {
			return false;
		}
		$this->_cleanup_board();
		$this->_update_topics_last_posts();
		$this->_update_topics_num_replies();
		$this->_update_forums_num_topics_and_replies();
		$this->_update_forums_last_posts();
		$this->_update_all_users();
		$this->_fix_subforums();
		// Refresh caches
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("forum_categories");
			cache()->refresh("forum_forums");
			cache()->refresh("forum_user_ranks");
			cache()->refresh("forum_home_page_posts");
			cache()->refresh("forum_announces");
			cache()->refresh("user_skins");
			cache()->refresh("smilies");
			if (module('forum')->SETTINGS["SEO_KEYWORDS"]) {
				cache()->refresh("search_engines");
			}
			$this->_refresh_board_totals();
		}
		return !$INNER_CALL ? js_redirect($_SERVER["HTTP_REFERER"]) : true;
	}
	
	/**
	* Synchronize selected forum
	*/
	function _sync_forum($forum_id = 0, $INNER_CALL = false) {
		if (!FORUM_IS_ADMIN && !$INNER_CALL) {
			return false;
		}
		if (empty($forum_id)) {
			$forum_id = intval($_GET["id"]);
		}
		if (empty($forum_id)) {
			return module('forum')->_show_error("No ID!");
		}
		$this->_cleanup_board();
		$this->_update_topics_num_replies($forum_id);
		$this->_update_topics_last_posts($forum_id);
		$this->_update_forums_num_topics_and_replies($forum_id);
		$this->_fix_subforums();
		// Refresh caches
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("forum_forums");
			cache()->refresh("forum_home_page_posts");
			$this->_refresh_board_totals();
		}
		return !$INNER_CALL ? js_redirect($_SERVER["HTTP_REFERER"]) : false;
	}

	/**
	* Refresh forum totals correctly
	*/
	function _refresh_board_totals() {
		if (!main()->USE_SYSTEM_CACHE) {
			return false;
		}
		list($total_posts) = db()->query_fetch(
			"SELECT COUNT(*) AS `0` FROM `".db('forum_posts')."` WHERE `status`='a'"
		);
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			list($total_users) = db()->query_fetch(
				"SELECT COUNT(*) AS `0` FROM `".db('user')."` WHERE `active`='1'"
			);
			list($last_user_id, $last_user_login) = db()->query_fetch(
				"SELECT `id` AS `0`,`nick` AS `1` FROM `".db('user')."` WHERE `id`=( 
					SELECT MAX(`id`) FROM `".db('user')."` WHERE `active`='1'
				) LIMIT 1"
			);
		} else {
			list($total_users) = db()->query_fetch(
				"SELECT COUNT(`id`) AS `0` FROM `".db('forum_users')."` WHERE `status`='a'"
			);
			list($last_user_id, $last_user_login) = db()->query_fetch(
				"SELECT `id` AS `0`,`name` AS `1` 
				FROM `".db('forum_users')."` 
				WHERE `status`='a' 
				ORDER BY `id` DESC 
				LIMIT 1"
			);
		}
		$forum_totals = array(
			"total_posts"		=> $total_posts,
			"total_users"		=> $total_users,
			"last_user_id"		=> $last_user_id,
			"last_user_login"	=> $last_user_login,
		);
		cache()->put("forum_totals", $forum_totals);
	}

	/**
	* Update number of topics and replies for all forums
	*/
	function _update_forums_num_topics_and_replies ($forum_id = 0) {
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` (
				`forum_id` int(10) unsigned NOT NULL, 
				`num_items` int(10) unsigned NOT NULL, 
				PRIMARY KEY (`forum_id`)
			)"
		);
		// Clenup first
		db()->query(
			"UPDATE `".db('forum_forums')."` AS `f` 
			SET `num_topics` = 0, `num_posts` = 0"
		);
		// Count number of topics
		db()->query(
			"INSERT INTO `".$tmp_table_name."` 
				(`forum_id`, `num_items`) 
			SELECT t.forum, COUNT(t.id) 
			FROM `".db('forum_topics')."` AS t 
			WHERE t.approved = 1 
			GROUP BY t.forum"
		);
		db()->query(
			"UPDATE `".db('forum_forums')."` AS f 
				, `".$tmp_table_name."` AS `tmp` 
			SET f.`num_topics`	= `tmp`.`num_items` 
			WHERE f.`id` = `tmp`.`forum_id`"
		);
		// Cleanup temp
		db()->query("TRUNCATE TABLE `".$tmp_table_name."`");
		// Count number of posts
		db()->query(
			"INSERT INTO `".$tmp_table_name."` 
				(`forum_id`, `num_items`) 
			SELECT p.forum, COUNT(p.id) - 1 
			FROM `".db('forum_posts')."` AS p
			WHERE p.status='a'
			GROUP BY p.forum"
		);
		db()->query(
			"UPDATE `".db('forum_forums')."` AS f 
				, `".$tmp_table_name."` AS `tmp` 
			SET f.`num_posts`	= `tmp`.`num_items` 
			WHERE f.`id` = `tmp`.`forum_id`"
		);
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
	}

	/**
	* Update last posts for all forums
	*/
	function _update_forums_last_posts () {
		db()->query(
			"UPDATE `".db('forum_forums')."` 
			SET `last_post_id`=0 
				, `last_post_date`=0"
		);
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` (
				`forum_id` int(10) unsigned NOT NULL, 
				`last_post_id` int(10) unsigned NOT NULL, 
				`last_post_date` int(10) unsigned NOT NULL, 
				PRIMARY KEY (`forum_id`)
			)"
		);
		db()->query(
			"INSERT IGNORE INTO `".$tmp_table_name."` ( 
				`forum_id`
				, `last_post_id`
				, `last_post_date` 
			) 
			SELECT `forum` 
				, `last_post_id` 
				, `last_post_date` 
			FROM `".db('forum_topics')."`
			WHERE `approved`=1 
			ORDER BY `last_post_date` DESC 
			"
		);
		db()->query(
			"UPDATE `".db('forum_forums')."` AS f 
				, `".$tmp_table_name."` AS `tmp` 
			SET f.`last_post_id`	= `tmp`.`last_post_id`, 
				f.`last_post_date`	= `tmp`.`last_post_date` 
			WHERE f.`id` = `tmp`.`forum_id`"
		);
	}

	/**
	* Update number of replies for all topics inside given forum
	*/
	function _update_topics_num_replies ($forum_id = 0) {
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`id` int(10) unsigned NOT NULL, 
				`num_posts` int(10) unsigned NOT NULL, 
				PRIMARY KEY (`id`)
			)"
		);
		db()->query(
			"REPLACE INTO `".$tmp_table_name."` (
				`id`
				,`num_posts`
			) 
			SELECT t.`id` AS `topic_id`, 
				COUNT(p.`id`) - 1 AS `num_replies`  
			FROM `".db('forum_topics')."` AS t, 
				`".db('forum_posts')."` AS p 
			WHERE p.`topic` = t.`id` 
				AND t.`approved` = 1 
				AND p.`status`='a' 
			".($forum_id ? " AND t.`forum`=".intval($forum_id) : "")."
			GROUP BY t.`id`
		");
		db()->query(
			"UPDATE `".db('forum_topics')."` AS `t`
				,`".$tmp_table_name."` AS `tmp` 
			SET `t`.`num_posts`=`tmp`.`num_posts` 
			WHERE `t`.`id`=`tmp`.`id`
		");
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
	}

	/**
	* Update last posts for the topics inside given forum
	*/
	function _update_topics_last_posts ($forum_id = 0) {
		// First, we cleanup all topics with last post data
		db()->query(
			"UPDATE `".db('forum_topics')."` SET 
				`last_post_id`		= 0, 
				`last_post_date`	= 0, 
				`last_poster_id`	= 0, 
				`last_poster_name`	= '' 
			".($forum_id ? " WHERE `forum`=".intval($forum_id)." " : "")
		);
		// Use temp to avoid long locks on the main tables
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` (
				`id` int(10) unsigned NOT NULL, 
				`post_id` int(10) unsigned NOT NULL, 
				`post_date` int(10) unsigned NOT NULL, 
				`poster_id` int(10) unsigned NOT NULL, 
				`poster_name` varchar(255), 
				PRIMARY KEY (`id`)
			)"
		);
		// Here is cool trick: we greatly speed-up by using "INSERT IGNORE"
		// So, sorting data to insert by last post date we have quick and correct data
		// about last posts groupped by topics correct way
		db()->query(
			"INSERT IGNORE INTO `".$tmp_table_name."`
			SELECT `topic`
				,`id`
				,`created`
				,`user_id`
				,`user_name`
			FROM `".db('forum_posts')."`
				".($forum_id ? " WHERE `forum`=".intval($forum_id)." " : "")."
			ORDER BY `created` DESC"
		);
		db()->query(
			"UPDATE `".db('forum_topics')."` AS `t`
				,`".$tmp_table_name."` AS `tmp` 
			SET 
				`t`.`last_post_id`=`tmp`.`post_id`, 
				`t`.`last_post_date`=`tmp`.`post_date`, 
				`t`.`last_poster_id`=`tmp`.`poster_id`, 
				`t`.`last_poster_name`=`tmp`.`poster_name` 
			WHERE `t`.`id`=`tmp`.`id`"
		);
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
	}

	/**
	* Update single topic
	*/
	function _update_topic_record ($topic_id = 0, $cache_refresh = 1) {
		if (empty($topic_id)) {
			return false;
		}
		// Prepare data for the topic record
		$sql = "SELECT COUNT(`id`) AS `0` 
				FROM `".db('forum_posts')."` 
				WHERE `topic`=".intval($topic_id)." 
					AND `status`='a'";
		list($topic_num_posts) = db()->query_fetch($sql);
		$sql = "SELECT `id`,`user_id`,`user_name`,`created` 
				FROM `".db('forum_posts')."` 
				WHERE `topic`=".intval($topic_id)." 
					AND `status`='a' 
				ORDER BY `created` DESC 
				LIMIT 1";
		$A = db()->query_fetch($sql);
		// Update record
		$sql = "UPDATE `".db('forum_topics')."` SET 
				`num_posts`			= ".(!empty($topic_num_posts) ? intval($topic_num_posts - 1) : 0).",
				`last_post_id`		= ".intval($A["id"]).",
				`last_post_date`	= ".intval($A["created"]).",
				`last_poster_id`	= ".intval($A["user_id"]).",
				`last_poster_name`	= '"._es($A["user_name"])."'
			WHERE `id`=".intval($topic_id);
		db()->query($sql);
		// Refresh caches
		if (main()->USE_SYSTEM_CACHE && $cache_refresh) {
			cache()->refresh("forum_home_page_posts");
			$this->_refresh_board_totals();
		}
	}

	/**
	* Update single forum
	*/
	function _update_forum_record ($forum_id = 0, $cache_refresh = 1) {
		if (empty($forum_id)) {
			return false;
		}
		// Prepare data
		list($forum_num_topics)	= db()->query_fetch(
			"SELECT COUNT(`id`) AS `0` 
			FROM `".db('forum_topics')."` 
			WHERE `forum`=".intval($forum_id)." 
				AND `approved`=1"
		);
		list($forum_num_posts)	= db()->query_fetch(
			"SELECT COUNT(`id`) AS `0` 
			FROM `".db('forum_posts')."` 
			WHERE `forum`=".intval($forum_id)." 
				AND `status`='a' 
				AND `new_topic`=0"
		);
		$A = db()->query_fetch(
			"SELECT `id`,`user_id`,`user_name`,`created` 
			FROM `".db('forum_posts')."` 
			WHERE `forum`=".intval($forum_id)." 
				AND `status`='a' 
			ORDER BY `created` DESC 
			LIMIT 1"
		);
		// Update record
		$sql = "UPDATE `".db('forum_forums')."` SET 
				`num_topics`		= ".intval($forum_num_topics).",
				`num_posts`			= ".intval($forum_num_posts + $forum_num_topics).",
				`last_post_id`		= ".intval($A["id"]).",
				`last_post_date`	= ".intval($A["created"])."
			WHERE `id`=".intval($forum_id);
		db()->query($sql);
		// Refresh caches
		if (main()->USE_SYSTEM_CACHE && $cache_refresh) {
			cache()->refresh("forum_forums");
			cache()->refresh("forum_home_page_posts");
			$this->_refresh_board_totals();
		}
	}

	/**
	* Update all users records
	*/
	function _update_all_users () {
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` (
				`id` int(10) unsigned NOT NULL, 
				`num_posts` int(10) unsigned NOT NULL, 
				PRIMARY KEY (`id`)
			)"
		);
		db()->query(
			"REPLACE INTO `".$tmp_table_name."` ( 
				`id`
				,`num_posts`
			) 
			SELECT `p`.`user_id`, 
				COUNT(`p`.`id`)
			FROM `".db('forum_posts')."` AS `p`
			WHERE `p`.`user_id` !=0 
				AND `p`.`status` = 'a' 
			GROUP BY `p`.`user_id`"
		);
		db()->query(
			"UPDATE `".db('forum_users')."` AS `u`
				,`".$tmp_table_name."` AS `tmp` 
			SET 
				`u`.`user_posts`=`tmp`.`num_posts` 
			WHERE `u`.`id`=`tmp`.`id`"
		);
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
	}

	/**
	* Update single user
	*/
	function _update_user_record ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		// Count number of users posts
		list($user_num_posts) = db()->query_fetch(
			"SELECT COUNT(`id`) AS `0` 
			FROM `".db('forum_posts')."` 
			WHERE `user_id`=".intval($user_id)." 
				AND `status` = 'a'"
		);
		// Update users records
		$sql2 = "UPDATE `".db('forum_users')."` SET
				`user_posts` = ".intval($user_num_posts)."
			WHERE `id`=".intval($user_id);
		db()->query($sql2);
	}

	/**
	* Cleanup board
	*/
	function _cleanup_board () {
		if ($this->DELETE_WRONG_RECORDS) {
			$SQL_POSTS	= "DELETE FROM `".db('forum_posts')."`";
			$SQL_TOPICS	= "DELETE FROM `".db('forum_topics')."`";
		} else {
			$SQL_POSTS	= "UPDATE `".db('forum_posts')."` SET `status`='u'";
			$SQL_TOPICS	= "UPDATE `".db('forum_topics')."` SET `approved`=0";
		}
		// Delete unassigned posts
		db()->query($SQL_POSTS." WHERE `forum`=0 OR `topic`=0");
		// Delete unassigned or created wrong topics
		db()->query($SQL_TOPICS." WHERE `forum`=0 OR `first_post_id`=0");
		// Delete topics without posts
		// Tmp table needed because we cant specify table to UPDATE that is in WHERE clause
		$tmp_table_name = $this->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` (
				`id` int(10) unsigned NOT NULL, 
				PRIMARY KEY (`id`)
			)"
		);
		db()->query(
			"INSERT INTO `".$tmp_table_name."` (
				`id`
			) 
			SELECT `t`.`id` 
			FROM `".db('forum_topics')."` AS `t`, 
				`".db('forum_posts')."` AS `p` 
			WHERE `t`.`id` = `p`.`topic` 
			GROUP BY `p`.`topic` 
			ORDER BY `t`.`id` ASC
		");
		db()->query(
			$SQL_TOPICS." 
			WHERE `id` NOT IN (
				SELECT `id` FROM `".$tmp_table_name."`
			) 
		");
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
		// Delete topics without forums
		db()->query(
			$SQL_TOPICS." 
			WHERE `forum` NOT IN (
				SELECT `id` FROM `".db('forum_forums')."`
			) 
		");
		// Delete posts without topics
		db()->query(
			$SQL_POSTS." 
			WHERE `topic` NOT IN (
				SELECT `id` FROM `".db('forum_topics')."`
			) 
		");
		// Delete topics with non-existed first posts
		db()->query(
			$SQL_TOPICS." 
			WHERE `first_post_id` NOT IN (
				SELECT `id` FROM `".db('forum_posts')."`
			)
		");
		// Fix posts with wrong forum id
		db()->query(
			"UPDATE `".db('forum_posts')."` AS `p`
				, `".db('forum_topics')."`AS `t`
			SET `p`.`forum` = `t`.`forum`
			WHERE `p`.`topic` = `t`.`id`
		");
	}

	/**
	* Create unique temporary table name
	*/
	function _get_unique_tmp_table_name () {
		return DB_PREFIX."tmp__".substr(md5(rand().microtime(true)), 0, 8);
	}

	/**
	* Fix subforums last posts
	*/
	function _fix_subforums () {
		// Prevent double execution
		if (isset($GLOBALS['_subforums_fixed'])) {
			return false;
		}
		// Forum table fields needed to be sum with sub-forums (children)
		$fields_to_sum = array(
			"num_views",
			"num_topics",
			"num_posts",
		);
		// First prepare parents with childs
		$Q = db()->query("SELECT * FROM `".db('forum_forums')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_forums_to_process[$A["id"]] = $A;
			$this->_parents_map[$A["id"]] = $A["parent"];
			$this->_forum_children[$A["parent"]][$A["id"]] = $A["parent"];
		}
		// Create bubbled infos for any forums having childs
		foreach ((array)$this->_forums_to_process as $_forum_id => $_info) {
			// Skip last childs
			if (!in_array($_forum_id, $this->_parents_map)) {
				continue;
			}
			// Save old info to compare it with new one later
			$new_info = $_info;
			// Get last post info for the current forum
			$last_info_id = $this->_last_posts_subforums_recursive($_forum_id);
			if ($last_info_id != $_forum_id) {
				$_last_info = $this->_forums_to_process[$last_info_id];
				$new_info["last_post_id"]	= $_last_info["last_post_id"];
				$new_info["last_post_date"] = $_last_info["last_post_date"];
			}
			// Generate sum by given fields
			foreach ((array)$fields_to_sum as $_field_name) {
				$new_info[$_field_name] = $this->_sum_subforums_recursive($_field_name, $_forum_id);
			}
			// Update database table if something had changed
			if ($_info != $new_info) {
				db()->UPDATE("forum_forums", $new_info, "`id`=".intval($_forum_id));
			}
		}
		// Prevent double execution
		$GLOBALS['_subforums_fixed'] = true;
	}

	/**
	* Get forum id with latest posts for the given parent
	*/
	function _last_posts_subforums_recursive ($parent_id = 0, $last_info_id = 0) {
		if (!$last_info_id) {
			$last_info_id = $parent_id;
		}
		$f = __FUNCTION__;
		$last_info		= $this->_forums_to_process[$last_info_id];
		// Try to find latest post inside subforums (children)
		foreach ((array)$this->_forum_children[$parent_id] as $_forum_id => $_parent_id) {
			$_cur_info = $this->_forums_to_process[$_forum_id];
			// Found more latest post
			if ($_cur_info["last_post_date"] > $last_info["last_post_date"]) {
				$last_info_id	= $_forum_id;
				$last_info		= $_cur_info;
			}
			// Try in children of current one
			$last_info_id = $this->$f($_forum_id, $last_info_id);
		}
		return $last_info_id;
	}

	/**
	* Sub different stats using sub-forums of the given one
	*/
	function _sum_subforums_recursive ($count_what = "", $parent_id = 0) {
		if (empty($count_what)) {
			return 0;
		}
		$f = __FUNCTION__;
		// Try to find latest post inside subforums (children)
		foreach ((array)$this->_forum_children[$parent_id] as $_forum_id => $_parent_id) {
			$_cur_info = $this->_forums_to_process[$_forum_id];
			// Add current number to total
			$sum += (int)$_cur_info[$count_what];
			// Try in children of current one
			$sum += (int)$this->$f($count_what, $_forum_id);
		}
		return $sum;
	}
}
