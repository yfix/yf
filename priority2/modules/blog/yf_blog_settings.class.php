<?php

/**
* Blog settings handler
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_blog_settings {

	/**
	* Constructor
	*/
	function yf_blog_settings () {
		// Reference to the parent object
		$this->BLOG_OBJ		= module(BLOG_CLASS_NAME);
		// Tagging
		if (!is_object($this->TAG_OBJ && $this->BLOG_OBJ->ALLOW_TAGGING)) {
			$this->TAG_OBJ = module("tags");
		}
	}

	/**
	* Change user blog settings
	*/
	function _start_blog () {
		// Check if user is member
		if (empty($this->BLOG_OBJ->_user_info)) {
			return _error_need_login();
		}
		if ($_ban_error = $this->BLOG_OBJ->_ban_check()) {
			return $_ban_error;
		}
		// Try to get user settings (also start them if not done yet)
		$BLOG_SETTINGS = $this->BLOG_OBJ->_get_user_blog_settings($this->BLOG_OBJ->USER_ID);
		// Save data
		if (!empty($_POST)) {
			// Prepare posted blog links
			$_posted_links = array();
			for ($i = 0; $i < $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM; $i++) {
				if (empty($_POST["blog_links_titles"][$i]) || empty($_POST["blog_links_urls"][$i])) {
					continue;
				}
				$_posted_links[] = $_POST["blog_links_titles"][$i]." ## ".$_POST["blog_links_urls"][$i];
			}
			if (empty($_POST["blog_links"]) && !empty($_posted_links)) {
				$_POST["blog_links"] = implode("\n", $_posted_links);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				$sql = array(
					"custom_cats"	=> _es($_POST["custom_cats"]),
					"blog_links"	=> _es($_POST["blog_links"]),
					"privacy"		=> _es($_POST["privacy"]),
					"allow_comments"=> _es($_POST["allow_comments"]),
					"allow_tagging"	=> _es($_POST["allowed_group"]),
				);
				if (isset($_POST["blog_title"])) {
					// Check fields
					$_POST["blog_title"] = _filter_text($_POST["blog_title"]);

					$sql["blog_title"]	= _es($_POST["blog_title"]);
				}
				// Generate SQL
				db()->UPDATE("blog_settings", $sql, "`user_id`=".intval($this->BLOG_OBJ->USER_ID));

				$this->BLOG_OBJ->_callback_on_update(array("page_header" => $_POST["blog_title"]));

				return js_redirect("./?object=".BLOG_CLASS_NAME."&action=add_post");
			}
		}
		// Prepare links for display
		$blog_links_array = array();
		$_links_array = $this->BLOG_OBJ->_blog_links_into_array($BLOG_SETTINGS["blog_links"]);
		for ($i = 0; $i < $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM; $i++) {
			$blog_links_array[$i] = array(
				"title"	=> $_links_array[$i]["title"],
				"url"	=> $_links_array[$i]["url"],
			);
		}
		// Prepare tempalte
		$replace = array(
			"form_action"		=> "./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"],
			"error_message"		=> _e(),
			"blog_title"		=> $_POST["blog_title"],
			"blog_links"		=> _prepare_html($_POST["blog_links"]),
			"blog_links_array"	=> $blog_links_array,
			"custom_cats"		=> _prepare_html($_POST["custom_cats"]),
			"max_blog_title"	=> intval($this->BLOG_OBJ->MAX_BLOG_TITLE_LENGTH),
			"max_blog_links"	=> intval($this->BLOG_OBJ->MAX_CUSTOM_CATS_NUM),
			"max_custom_cats"	=> intval($this->BLOG_OBJ->MAX_BLOG_LINKS_NUM),
			"blog_privacy_box"	=> $this->BLOG_OBJ->_box("privacy", $_POST["privacy"]),
			"blog_comments_box"	=> $this->BLOG_OBJ->_box("allow_comments", $_POST["allow_comments"]),
			"blog_tagging_box"	=> $this->BLOG_OBJ->ALLOW_TAGGING ? $this->TAG_OBJ->_mod_spec_settings(array("module"=>"blog", "object_id"=>$this->USER_ID)) : "",
		);
		return tpl()->parse(BLOG_CLASS_NAME."/start_blog", $replace);
	}

	/**
	* Change user blog settings
	*/
	function _change () {
		// Check if user is member
		if (empty($this->BLOG_OBJ->_user_info)) {
			return _error_need_login();
		}
		if ($_ban_error = $this->BLOG_OBJ->_ban_check()) {
			return $_ban_error;
		}
		// Try to get user settings
		$BLOG_SETTINGS = $this->BLOG_OBJ->_get_user_blog_settings($this->BLOG_OBJ->USER_ID);
		// Check posted data and save
		if (!empty($_POST["go"])) {
			// Prepare posted blog links
			$_posted_links = array();
			for ($i = 0; $i < $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM; $i++) {
				if (empty($_POST["blog_links_titles"][$i]) || empty($_POST["blog_links_urls"][$i])) {
					continue;
				}
				$_posted_links[] = $_POST["blog_links_titles"][$i]." ## ".$_POST["blog_links_urls"][$i];
			}
			if (empty($_POST["blog_links"]) && !empty($_posted_links)) {
				$_POST["blog_links"] = implode("\n", $_posted_links);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Generate SQL
				$sql = array(
					"blog_desc"		=> _es($_POST["blog_desc"]),
					"custom_cats"	=> _es($_POST["custom_cats"]),
					"blog_links"	=> _es($_POST["blog_links"]),
					"privacy"		=> _es($_POST["privacy"]),
					"allow_comments"=> _es($_POST["allow_comments"]),
					"allow_tagging"	=> _es($_POST["allowed_group"]),
				);
				if (isset($_POST["blog_title"])) {
					// Check fields
					if (!empty($this->BLOG_OBJ->MAX_BLOG_TITLE_LENGTH)) {
						$_POST["blog_title"] = substr($_POST["blog_title"], 0, $this->BLOG_OBJ->MAX_BLOG_TITLE_LENGTH);
					}
					$_POST["blog_title"]	= _filter_text($_POST["blog_title"]);

					$sql["blog_title"]	= _es($_POST["blog_title"]);
				}
				db()->UPDATE("blog_settings", $sql, "`user_id`=".intval($this->BLOG_OBJ->USER_ID));
				// Synchronize blog title with site menu
				$this->BLOG_OBJ->_callback_on_update(array("page_header" => $_POST["blog_title"]));
				// Synchronize all blogs stats
				$this->BLOG_OBJ->_update_all_stats();
				// Return user back
				return js_redirect("./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")));
			} else {
				$error_message = _e();
			}
		} else {
			$_POST["blog_title"]	= $BLOG_SETTINGS["blog_title"];
			$_POST["blog_desc"]		= $BLOG_SETTINGS["blog_desc"];
			$_POST["blog_links"]	= $BLOG_SETTINGS["blog_links"];
			$_POST["custom_cats"]	= $BLOG_SETTINGS["custom_cats"];
			$_POST["privacy"]		= $BLOG_SETTINGS["privacy"];
			$_POST["allow_comments"]= $BLOG_SETTINGS["allow_comments"];
		}
		// Prepare links for display
		$blog_links_array = array();
		$_links_array = $this->BLOG_OBJ->_blog_links_into_array($BLOG_SETTINGS["blog_links"]);
		for ($i = 0; $i < $this->BLOG_OBJ->MAX_BLOG_LINKS_NUM; $i++) {
			$blog_links_array[$i] = array(
				"title"	=> $_links_array[$i]["title"],
				"url"	=> $_links_array[$i]["url"],
			);
		}
		// Show form
		if (empty($_POST["go"]) || !empty($error_message)) {
			$replace = array(
				"form_action"		=> "./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
				"error_message"		=> $error_message,
				"blog_title"		=> _prepare_html($_POST["blog_title"]),
				"blog_desc"			=> _prepare_html($_POST["blog_desc"]),
				"blog_links"		=> _prepare_html($_POST["blog_links"]),
				"blog_links_array"	=> $blog_links_array,
				"custom_cats"		=> _prepare_html($_POST["custom_cats"]),
				"max_blog_title"	=> intval($this->BLOG_OBJ->MAX_BLOG_TITLE_LENGTH),
				"max_blog_links"	=> intval($this->BLOG_OBJ->MAX_BLOG_LINKS_NUM),
				"max_custom_cats"	=> intval($this->BLOG_OBJ->MAX_CUSTOM_CATS_NUM),
				"blog_privacy_box"	=> $this->BLOG_OBJ->_box("privacy", $_POST["privacy"]),
				"blog_comments_box"	=> $this->BLOG_OBJ->_box("allow_comments", $_POST["allow_comments"]),
				"manage_link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_posts"._add_get(array("page")),
				"blog_tagging_box"	=> $this->BLOG_OBJ->ALLOW_TAGGING ? $this->TAG_OBJ->_mod_spec_settings(array("module"=>"blog", "object_id"=>$this->USER_ID)) : "",
			);
			$body = tpl()->parse(BLOG_CLASS_NAME."/edit_blog_settings", $replace);
		}
		return $body;
	}

	/**
	* Create default blog settings fro the given user ID
	*/
	function _start_blog_settings ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		$ACCOUNT_EXISTS = db()->query_num_rows("SELECT `user_id` FROM `".db('blog_settings')."` WHERE `user_id`=".intval($user_id));
		if ($ACCOUNT_EXISTS) {
			return false;
		}
		// Set global tags settings as defaults
		$default_tags_settings = $this->TAG_OBJ->_mod_spec_settings(array("module"=>"blog", "object_id"=>$this->USER_ID), $this->TAG_OBJ->ALLOWED_GROUP);
		// Process SQL
		$sql = "INSERT INTO `".db('blog_settings')."` (
				`user_id`,
				`allow_tagging`
			) VALUES ( 
				".intval($user_id).",
				".intval($default_tags_settings)."
			)";
		db()->query($sql);
	}

	/**
	* Convert blog links string into array
	*/
	function _blog_links_into_array ($raw_blog_links = "") {
		$links_array = array();
		if (empty($raw_blog_links)) {
			return $links_array;
		}
		// Try to find correct links
		$links_array = explode("\n", trim(str_replace(array("\r\n","\r","\t"), array("\n","",""), $raw_blog_links)));
		foreach ((array)$links_array as $k => $v) {
			$title = $url = "";
			$tmp = explode("##", $v);
			if (count($tmp) == 1 && !empty($tmp[0])) {
				$title	= trim($tmp[0]);
				$url	= trim($tmp[0]);
			} elseif (count($tmp) == 2) {
				$title	= trim($tmp[0]);
				$url	= trim($tmp[1]);
			}
			if (empty($title) || empty($v)) {
				unset($links_array[$k]);
			} else {
				$links_array[$k] = array(
					"title"	=> _prepare_html($title),
					"url"	=> _prepare_html($url),
				);
			}
		}
		return $links_array;
	}

	/**
	* Convert custom categories string into array
	*/
	function _custom_cats_into_array ($raw_custom_cats = "") {
		$cats_array = array();
		if (empty($raw_custom_cats)) {
			return $cats_array;
		}
		// Try to find correct categories
		$cats_array = explode("\n", trim(str_replace(array("\r\n","\r","\t"), array("\n","",""), $raw_custom_cats)));
		foreach ((array)$cats_array as $k => $cat_name) {
			$cat_name = trim($cat_name);
			if (empty($cat_name)) {
				unset($cats_array[k]);
				continue;
			}
			$_id_for_link = "";
			if (!$this->BLOG_OBJ->HIDE_TOTAL_ID) {
				$_id_for_link .= intval($GLOBALS['user_info']["id"]). "-";
			}
			$_id_for_link .= $this->BLOG_OBJ->CUSTOM_CATS_LINKS_TEXTS ? urlencode(str_replace(" ", "_", strtolower($cat_name))) : ($k + 1);
			$cats_array[$k] = array(
				"name"	=> _prepare_html($cat_name),
				"link"	=> "./?object=".BLOG_CLASS_NAME."&action=custom_category&id=".$_id_for_link,
			);
		}
		return $cats_array;
	}

	/**
	* Update all blogs stats
	*/
	function _update_all_stats () {
		// Set blog stats to initial values
		db()->query(
			"UPDATE `".db('blog_settings')."` 
			SET `num_posts` = 0,
				`num_views` = 0,
				`num_comments` = 0"
		);
		// We need to create blog settings tables for all blog posts
		db()->query(
			"REPLACE INTO `".db('blog_settings')."`
					(`user_id`,`user_nick`) 
				SELECT `u`.`id`,`u`.`nick` 
				FROM `".db('user')."` AS `u`,
					`".db('blog_posts')."` AS `b`
				WHERE `b`.`user_id` = `u`.`id` 
					AND `u`.`id` NOT IN ( 
						SELECT `s`.`user_id` FROM `".db('blog_settings')."` AS `s`
					)
/*			ON DUPLICATE KEY UPDATE `user_nick` = VALUES(`user_nick`)*/"
		);
		// Update user_nick in stats
		db()->query(
			"UPDATE `".db('blog_settings')."` AS `s`,
					`".db('user')."` AS `u`
			SET `s`.`user_nick` = `u`.`nick`
			WHERE `s`.`user_id` = `u`.`id`"
		);
		// Create temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`user_id`		int(10) unsigned NOT NULL, 
				`num_posts`		int(10) unsigned NOT NULL, 
				`num_views`		int(10) unsigned NOT NULL, 
				`num_comments`	int(10) unsigned NOT NULL, 
				PRIMARY KEY (`user_id`)
			)"
		);
		// Update blog num posts and views
		db()->query(
			"INSERT INTO `".$tmp_table_name."` 
				(`user_id`,`num_posts`,`num_views`) 
				SELECT `user_id`, 
					COUNT(`id`) AS `num_posts`, 
					SUM(`num_reads`) AS `num_views` 
				FROM `".db('blog_posts')."` 
				WHERE `active`=1 
				GROUP BY `user_id`"
		);
		// Create temporary table for num comments
		$tmp_table_name2 = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name2."` ( 
				`user_id`		int(10) unsigned NOT NULL, 
				`num_comments`	int(10) unsigned NOT NULL, 
				PRIMARY KEY (`user_id`)
			)"
		);
		// Update num_comments for blog posts
		db()->query(
			"INSERT INTO `".$tmp_table_name2."` 
				(`user_id`,`num_comments`) 
				SELECT 
					`b`.`user_id` AS `blog_user_id`,
					COUNT(`c`.`id`) AS `num_comments`
				FROM `".db('comments')."` AS `c`,
					`".db('blog_posts')."` AS `b` 
				WHERE `c`.`object_name`='"._es(BLOG_CLASS_NAME)."' 
					AND `b`.`id`=`c`.`object_id` 
					AND `b`.`active`=1 
					AND `b`.`privacy` NOT IN(9)
					AND `b`.`allow_comments` NOT IN(9)
				GROUP BY `b`.`user_id`"
		);
		// Syncronize temporary tables
		db()->query(
			"UPDATE `".$tmp_table_name."` AS `tmp1`,
					`".$tmp_table_name2."` AS `tmp2`
				SET `tmp1`.`num_comments` = `tmp2`.`num_comments` 
			WHERE `tmp1`.`user_id` = `tmp2`.`user_id`"
		);
		// Update stats table
		db()->query(
			"UPDATE `".db('blog_settings')."` AS `s`,
					`".$tmp_table_name."` AS `tmp`
			SET `s`.`num_posts` = `tmp`.`num_posts`,
				`s`.`num_views` = `tmp`.`num_views`,
				`s`.`num_comments` = `tmp`.`num_comments`
			WHERE `s`.`user_id` = `tmp`.`user_id`"
		);
		// Cleanup temp
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name2."`");
	}
	
	/**
	* Fix second id (used for HIDE_TOTAL_ID)
	*/
	function _fix_id2($user_id = 0) {
		if (empty($user_id)) {
			$user_id = $this->BLOG_OBJ->USER_ID;
		}
		if (empty($user_id) || !$this->BLOG_OBJ->HIDE_TOTAL_ID) {
			return false;
		}
		// Prepare curent max id
		list($_max_id2) = db()->query_fetch(
			"SELECT MAX(`id2`) AS `0` FROM `".db('blog_posts')."` WHERE `user_id`=".intval($user_id)
		);
// TODO: fix that number 1 could be assigned, currently not
		$_max_id2 = intval($_max_id2);
		if (!$_max_id2) {
			$_max_id2 = 1;
		}
		$posts_to_update	= array();
		// Get all user posts without assigned id2
		$Q = db()->query(
			"SELECT `id`,`id2` 
			FROM `".db('blog_posts')."` 
			WHERE `user_id`=".intval($user_id)." 
				AND `id2`='0' 
			ORDER BY `id` ASC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$posts_to_update[$A["id"]] = $A["id2"];
		}
		// Check duplicates or empty ids
		$Q = db()->query(
			"SELECT `id`,`id2`,
				COUNT(`id2`) AS `num` 
			FROM `".db('blog_posts')."` 
			WHERE `user_id`=".intval($user_id)." 
				AND `id2` != '0' 
			GROUP BY `id2` 
			HAVING `num` > 1"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$posts_to_update[$A["id"]] = $A["id2"];
		}
		foreach ((array)$posts_to_update as $_post_id => $_info) {
			$_max_id2++;

			db()->UPDATE("blog_posts", array(
				"id2" => intval($_max_id2)
			), "`id`=".intval($_post_id));
		}
		return $_max_id2;
	}
}
