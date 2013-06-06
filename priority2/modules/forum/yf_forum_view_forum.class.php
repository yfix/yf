<?php

/**
* Show forum contents
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_view_forum {

	/** @var string Session array name where filter vars are stored */
	var $_filter_name = "topics_filter";
	/** @var bool Filter on/off */
	var $USE_FILTER = true;

	/**
	* Constructor
	*/
	function _init () {
		// Apply moderator rights here
		if (FORUM_IS_MODERATOR) {
			module('forum')->_apply_moderator_rights();
		}
		// Get available anounces from db
		if (module('forum')->SETTINGS["ALLOW_ANNOUNCES"]) {
			$this->_forum_announces = main()->get_data("forum_announces");
		}
		// Filter data
		if (isset(module('forum')->SETTINGS["ALLOW_TOPICS_FILTER"])) {
			$this->USE_FILTER = &module('forum')->SETTINGS["ALLOW_TOPICS_FILTER"];
		}
		if ($this->USE_FILTER) {
			$this->_filter_sort_by = array(
				"last_post_date"	=> "Order: Last Post",
				"last_poster_name"	=> "Order: Last Poster",
				"name"				=> "Order: Topic Title",
				"user_name"			=> "Order: Topic Starter",
				"created"			=> "Order: Topic Started",
				"num_posts"			=> "Order: Replies",
				"num_views"			=> "Order: Views",
			);
			$this->_filter_sort_orders = array(
				"Z-A"	=> "Z-A",
				"A-Z"	=> "A-Z",
			);
			$prune_string = "5,7,10,15,20,25,30,60,90,180,365,1000";
			$prune_array = explode(",", trim($prune_string));
			if (is_array($prune_array)) {
				$this->_filter_prune_days[1] = "From: Today";
				foreach ((array)$prune_array as $v) {
					$this->_filter_prune_days[$v] = (count($prune_array) == ++$i) ? "Show All" : "From: ".intval($v)." days";
				}
			}
			$this->_filter_topics_flags = array(
				"all"		=> "Topics: All",
				"open"		=> "Topics: Open",
				"closed"	=> "Topics: Closed",
				"hot"		=> "Topics: Hot",
				"locked"	=> "Topics: Locked",
				"moved"		=> "Topics: Moved",
			);
			// Only for logged in users (not for guests)
			if (FORUM_USER_ID) $this->_filter_topics_flags = array_merge($this->_filter_topics_flags, array(
				"istarted"	=> "Topics: I Started",
				"ireplied"	=> "Topics: I Replied",
			));
			$this->_boxes = array(
				"sort_by"		=> 'select_box("sort_by",		$this->_filter_sort_by,		$selected, 0, 2, "", false)',
				"sort_order"	=> 'select_box("sort_order",	$this->_filter_sort_orders,	$selected, 0, 2, "", false)',
				"prune_day"		=> 'select_box("prune_day",		$this->_filter_prune_days,	$selected, 0, 2, "", false)',
				"topics_flag"	=> 'select_box("topics_flag",	$this->_filter_topics_flags,$selected, 0, 2, "", false)',
			);
			$this->_fields_in_filter = array_keys($this->_boxes);
		}
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		$_GET["id"] = preg_replace("/[^a-z0-9\_\-]/ims", "", $_GET["id"]);
		// Try to find forum by name
		if (!empty($_GET["id"]) && !is_numeric($_GET["id"]) && module('forum')->SETTINGS["USE_SEO_LINKS"]) {
			foreach ((array)module('forum')->_forums_array as $_forum_id => $_forum_info) {
				if (str_replace(" ", "_", strtolower($_forum_info["name"])) == $_GET["id"]) {
					$_GET["id"] = $_forum_id;
					break;
				}
			}
		}
		$_GET["id"] = intval($_GET["id"]);
		// Check if such forum exists
		if (empty(module('forum')->_forums_array[$_GET["id"]])) {
			return module('forum')->_show_error("No such forum found!");
		}
		// Reference to the forums array
		$this->_forum_info	= &module('forum')->_forums_array[$_GET["id"]];
		// Reference to the categories array
		$this->_cat_info	= &module('forum')->_forum_cats_array[$this->_forum_info["category"]];
		// Skip non-active forums and categories
		if ($this->_forum_info["status"] != "a" || $this->_cat_info["status"] != "a") {
			return module('forum')->_show_error("Forum is inactive!");
		}
		// Check user group access rights to the current forum
		$only_for_groups = $this->_forum_info["user_groups"] ? explode(",", $this->_forum_info["user_groups"]) : "";
		if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
			return module('forum')->_show_error("Private Forum!");
		}
		// Check if filter need to be saved
		if (!empty($_POST["act"])) {
			if ($_POST["act"] == "save_filter")	{
				return $this->_save_filter();
			} elseif ($_POST["act"] == "clear_filter") {
				return $this->_clear_filter();
			}
		}
		$topics_per_page = !empty(module('forum')->USER_SETTINGS["TOPICS_PER_PAGE"]) ? module('forum')->USER_SETTINGS["TOPICS_PER_PAGE"] : module('forum')->SETTINGS["NUM_TOPICS_ON_PAGE"];
		// Prepare SQL query
		$sql = "SELECT * FROM `".db('forum_topics')."` WHERE `forum`=".intval($this->_forum_info["id"])." ";
		// For user hide unapproved topics
		$sql .= !FORUM_IS_ADMIN ? " AND `approved`=1 " : "";
		$pinned_sql = $sql. " AND `pinned`=1 ";
		$sql .= " AND `pinned`=0 ";
		// Add filter SQL
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? $filter_sql : " ORDER BY `last_post_date` DESC ";
		// Prepare path for the pages
		$path = module('forum')->_link_to_forum($this->_forum_info["id"]);
		// Count number of rows
		// Optimized vesion for MySQL >= 4.1.x
		if (module('forum')->SETTINGS["USE_OPTIMIZED_SQL"] 
			&& false !== strpos(db()->DB_TYPE, "mysql") 
		) {
			$first_record = intval(($_GET["page"] ? $_GET["page"] - 1 : 0) * $topics_per_page);
			if ($first_record < 0) {
				$first_record = 0;
			}
			$sql .= " LIMIT ".intval($first_record).",".intval($topics_per_page);
			// Get topics ids
			$Q = db()->query(str_replace("SELECT *", "SELECT SQL_CALC_FOUND_ROWS `id`", $sql));
			while ($A = db()->fetch_assoc($Q)) {
				$topics_array[$A["id"]] = $A["id"];
			}
			// Fill topics infos
			if (!empty($topics_array)) {
				// Prepare pages
				list($forum_num_posts) = db()->query_fetch("SELECT FOUND_ROWS() AS `0`", false);
				$forum_num_posts = intval($forum_num_posts);
				list(, $forum_pages, ) = common()->divide_pages(null, $path, null, $topics_per_page, $forum_num_posts, FORUM_CLASS_NAME."/pages_1/");
				if (!empty($forum_num_posts)) {
					$Q = db()->query("SELECT * FROM `".db('forum_topics')."` WHERE `id` IN(".implode(",", array_keys($topics_array)).")");
					while ($A = db()->fetch_assoc($Q)) {
						$topics_array[$A["id"]] = $A;
					}
				}
			}
		// Common version
		} else {
			list($add_sql, $forum_pages, $forum_num_posts) = common()->divide_pages(str_replace("SELECT *","SELECT `id`",$sql), $path, null, $topics_per_page, null, FORUM_CLASS_NAME."/pages_1/");
			// Get data from db
			$Q = db()->query($sql. $order_by. $add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$topics_array[$A["id"]] = $A;
			}
		}
		// Get pinned items
		$Q = db()->query($pinned_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$topics_array[$A["id"]] = $A;
		}
		// Try to find last posts in the current forum topics
		if (!empty($topics_array)) {
			list($this->_last_posts, $this->_topic_pages) = $this->_get_topics_last_posts_and_pages($topics_array);
		}
		// Init topic item object
		if (!empty($topics_array)) {
			$TOPIC_ITEM_OBJ = main()->init_class("forum_topic_item", FORUM_MODULES_DIR);
		}
		// Process posts
		if (is_object($TOPIC_ITEM_OBJ)) {
			foreach ((array)$topics_array as $topic_info) {
				$topic_is_moved	= intval(!empty($topic_info["moved_to"]));
				$moved_id		= $topic_is_moved ? array_pop(explode(",", $topic_info["moved_to"])) : 0;
				$topic_is_read	= FORUM_USER_ID ? !empty($this->_read_topics_array[$topic_info["id"]]) : 0;
				$topic_item		= $TOPIC_ITEM_OBJ->_show_topic_item($topic_info, $topic_is_read, $this->_last_posts[$moved_id ? $moved_id : $topic_info["id"]], $this->_topic_pages[$topic_info["id"]], "/view_forum_topic_item");
				// Assign current item to the correct string
				if ($topic_info["pinned"] == 1) {
					$pinned_items .= $topic_item;
				} else {
					$forum_topics .= $topic_item;
				}
			}
		}
		$forum_is_closed	= $this->_forum_info["options"] == "2" ? 1 : 0;
		$allow_new_topic	= !$forum_is_closed && module('forum')->USER_RIGHTS["post_new_topics"];
		$allow_new_poll		= $allow_new_topic && module('forum')->SETTINGS["ALLOW_POLLS"] && module('forum')->USER_RIGHTS["make_polls"];
		// Deny guests posting (if needed)
		if (!FORUM_USER_ID && !module('forum')->SETTINGS["ALLOW_GUESTS_POSTS"]) {
			$allow_new_topic	= 0;
		}
		// Get stats
		$STATS_OBJ = main()->init_class("forum_stats", FORUM_MODULES_DIR);
		$announce_items = module('forum')->SETTINGS["ALLOW_ANNOUNCES"] ? $this->_show_announce_items() : "";
		// Prepare sub forums
		$sub_forums_ids = module('forum')->_get_sub_forums_ids($this->_forum_info["id"], 1);
		if (!empty($sub_forums_ids)) {
			$sub_forums_items = $this->_show_sub_forums($sub_forums_ids);
		}
		// Process template
		$replace = array(
			"is_admin"				=> intval(FORUM_IS_ADMIN),
			"is_moderator"			=> intval(FORUM_IS_ADMIN || (FORUM_IS_MODERATOR && module('forum')->_moderate_forum_allowed($this->_forum_info["id"]))),
			"sub_forums"			=> $this->_show_sub_forums(),
			"cat_name"				=> $this->_cat_info["name"],
			"cat_link"				=> "./?object=".FORUM_CLASS_NAME._add_get(array("page")),
			"add_topic_link"		=> $allow_new_topic ? "./?object=".FORUM_CLASS_NAME."&action=new_topic&id=".$this->_forum_info["id"]._add_get(array("page")) : "",
			"new_poll_link"			=> $allow_new_poll ? "./?object=".FORUM_CLASS_NAME."&action=new_poll&id=".$this->_forum_info["id"]._add_get(array("page")) : "",
			"forum_name"			=> _prepare_html($this->_forum_info["name"]),
			"forum_num_posts"		=> $forum_num_posts > 0 ? $forum_num_posts - 1 : $forum_num_posts,
			"forum_pages"			=> $forum_pages,
			"forum_topics"			=> !empty($forum_topics) ? $forum_topics : tpl()->parse(FORUM_CLASS_NAME."/view_forum_no_topics"),
			"forum_online"			=> is_object($STATS_OBJ) ? $STATS_OBJ->_show_forum_stats() : "",
			"forum_filter"			=> $this->USE_FILTER ? $this->_show_filter() : "",
			"board_fast_nav"		=> !$forum_is_closed && module('forum')->SETTINGS["ALLOW_FAST_JUMP_BOX"] ? module('forum')->_board_fast_nav_box() : "",
			"anounce_items"			=> $announce_items,
			"pinned_items"			=> $pinned_items,
			"show_sub_header"		=> !empty($announce_items) || !empty($pinned_items),
			"mark_forum_read_link"	=> FORUM_USER_ID && module('forum')->SETTINGS["USE_READ_MESSAGES"] ? "./?object=".FORUM_CLASS_NAME."&action=mark_read&id=".$this->_forum_info["id"]._add_get(array("page")) : "",
			"subscribe_forum_link"	=> FORUM_USER_ID && module('forum')->SETTINGS["ALLOW_TRACK_FORUM"] ? "./?object=".FORUM_CLASS_NAME."&action=subscribe_forum&id=".$this->_forum_info["id"]._add_get(array("page")) : "",
			"search_form_action"	=> module('forum')->SETTINGS["ALLOW_SEARCH"] && module('forum')->USER_RIGHTS["use_search"] ? "./?object=".FORUM_CLASS_NAME."&action=search". _add_get() : "",
			"forum_closed"			=> intval($forum_is_closed),
			"t_act_box"				=> FORUM_IS_ADMIN || FORUM_IS_MODERATOR ? $this->_t_act_box() : "",
			"rss_forum_button"		=> module('forum')->_show_rss_link("./?object=".FORUM_CLASS_NAME."&action=rss_forum&id=".$this->_forum_info["id"], "RSS feed for forum: ".$this->_forum_info["name"]),
			"sub_forums_items"		=> $sub_forums_items,
		);
		// Administration methods
		if (FORUM_IS_ADMIN || FORUM_IS_MODERATOR) {
			$replace = array_merge($replace, array(
				"admin_action_link"		=> "./?object=".FORUM_CLASS_NAME."&action=admin&id=".$this->_forum_info["id"]._add_get(array("page")),
				"inv_topics_link"		=> "./?object=".FORUM_CLASS_NAME."&action=show_inv_topics&id=".$this->_forum_info["id"]._add_get(array("page")),
				"inv_posts_link"		=> "./?object=".FORUM_CLASS_NAME."&action=show_inv_posts&id=".$this->_forum_info["id"]._add_get(array("page")),
				"resync_forum_link"		=> "./?object=".FORUM_CLASS_NAME."&action=sync_forum&id=".$this->_forum_info["id"]._add_get(array("page")),
				"prune_link"			=> "./?object=".FORUM_CLASS_NAME."&action=prune&id=".$this->_forum_info["id"]._add_get(array("page")),
			));
		}
		return module('forum')->_show_main_tpl(tpl()->parse(FORUM_CLASS_NAME."/view_forum_main", $replace));
	}

	/**
	* Get Topics Last Posts And Pages
	*/
	function _get_topics_last_posts_and_pages($topics_array = array()) {
		if (!is_array($topics_array)) {
			return false;
		}
		$last_posts_ids		= array();
		$topic_pages_ids	= array();
		foreach ((array)$topics_array as $topic_info) {
			// Skip empty topics
			if (empty($topic_info["last_post_id"])) {
				continue;
			}
			$last_posts_ids[$topic_info["last_post_id"]] = $topic_info["last_post_id"];
			// Skip next action if topics pages not needed
			if (!module('forum')->SETTINGS["SHOW_TOPIC_PAGES"]) {
				continue;
			}
			// Check if need to process topic pages
			if ($topic_info["num_posts"] > module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"]) {
				$topic_pages_ids[$topic_info["id"]] = $topic_info["num_posts"];
			}
		}
		$posts_per_page = !empty(module('forum')->USER_SETTINGS["POSTS_PER_PAGE"]) ? module('forum')->USER_SETTINGS["POSTS_PER_PAGE"] : module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"];
		// Process topic pages
		if (module('forum')->SETTINGS["SHOW_TOPIC_PAGES"] && !empty($topic_pages_ids)) {
			$topic_pages = array();
			foreach ((array)$topic_pages_ids as $topic_id => $topic_num_posts) {
				list(,$topic_pages[$topic_id],,,$_total_pages[$topic_id]) = common()->divide_pages("", "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$topic_id, null, module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"], $topic_num_posts + 1, FORUM_CLASS_NAME."/pages_2/");
			}
		}
		// Process last posts records
		if (!empty($last_posts_ids)) {
			$last_posts = array();
			$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `id` IN(".implode(",",$last_posts_ids).")");
			while ($post_info = db()->fetch_assoc($Q)) {

				$subject = strlen($post_info["subject"]) ? $post_info["subject"] : $post_info["text"];
				$subject = module('forum')->_cut_subject_for_last_post($subject);

				$replace3 = array(
					"last_post_author_name"	=> !empty($post_info["user_name"]) ? _prepare_html($post_info["user_name"]) : t("Anonymous"),
					"last_post_author_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
					"last_post_subject"		=> _prepare_html($subject),
					"last_post_date"		=> module('forum')->_show_date($post_info["created"], "last_post_date"),
					"last_post_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$post_info["topic"].($_total_pages[$post_info["topic"]] > 1 ? "&page=".$_total_pages[$post_info["topic"]] : "")."#last_post",
					"last_post_id"			=> $post_info["id"],
					"user_id"				=> $post_info["user_id"],
					"fast_text_preview"		=> (int)module('forum')->SETTINGS["FAST_TEXT_PREVIEW"],
				);
				$last_posts[$post_info["topic"]] = tpl()->parse(FORUM_CLASS_NAME."/view_forum_last_posts", $replace3);
			}
		}
		return array($last_posts, $topic_pages);
	}
	
	/**
	* Show Sub Forums
	*/
	function _show_sub_forums($sub_forums_ids = array()) {
		if (empty($sub_forums_ids)) {
			return false;
		}
		$FORUM_VIEW_HOME_OBJ = main()->init_class("forum_view_home", FORUM_MODULES_DIR);
		// Collect last posts ids
		foreach ((array)$sub_forums_ids as $_sub_id) {
			$_forum_info = module('forum')->_forums_array[$_sub_id];
			$last_posts_ids[$_forum_info["last_post_id"]] = $_forum_info["last_post_id"];
		}
		// Process last posts records
		if (!empty($last_posts_ids)) {
			$last_posts_array = main()->get_data("forum_home_page_posts");
			$forums_last_posts = array();
			// Process last posts records
			foreach ((array)$last_posts_array as $post_info) {
				// Do not remove! (need while using cache)
				if (!in_array($post_info["id"], $last_posts_ids)) {
					continue;
				}

				$subject = strlen($post_info["subject"]) ? $post_info["subject"] : $post_info["text"];
				$subject = module('forum')->_cut_subject_for_last_post($subject);

				$replace3 = array(
					"last_post_author_name"	=> !empty($post_info["user_name"]) ? _prepare_html($post_info["user_name"]) : t("Anonymous"),
					"last_post_author_link"	=> $post_info["user_id"] ? module('forum')->_user_profile_link($post_info["user_id"]) : "",
					"last_post_subject"		=> _prepare_html($subject),
					"last_post_date"		=> module('forum')->_show_date($post_info["created"], "last_post_date"),
					"last_post_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$post_info["topic"]._add_get(),
					"user_id"				=> intval($post_info["user_id"]),
				);
				$forums_last_posts[$post_info["forum"]] = tpl()->parse(FORUM_CLASS_NAME."/view_home_last_posts", $replace3);
			}
			$FORUM_VIEW_HOME_OBJ->_last_posts = $forums_last_posts;
			unset($forums_last_posts);
		}
		// Get moderators for the forums
// TODO: check carefully
//		$FORUM_VIEW_HOME_OBJ->_moderators_by_forums = $FORUM_VIEW_HOME_OBJ->_get_moderators();
		// Prepare sub-forums
		foreach ((array)$sub_forums_ids as $_sub_id) {
			$sub_forums_items .= $FORUM_VIEW_HOME_OBJ->_show_forum_item(module('forum')->_forums_array[$_sub_id]);
		}
		return $sub_forums_items;
	}
	
	/**
	* Show Announce Items
	*/
	function _show_announce_items() {
		foreach ((array)$this->_forum_announces as $info) {
			// Try to filter anounces not for the current forum
			if ($info["forum"] != "*" && !in_array($_GET["id"], explode(",",$info["forum"]))) {
				continue;
			}
			// Check if anounce is expired or not need to show yet
			if ($info["start_time"] != 0 && time() < $info["start_time"]) {
				continue;
			}
			if ($info["end_time"] != 0 && time() > $info["end_time"]) {
				continue;
			}
			// Add announce to the array
			$info["post"] = "";
			$announces[$info["id"]] = $info;
		}
		// Process filtered announces
		if (is_array($announces)) {
			// Get author's names
			foreach ((array)$announces as $info) {
				$users_ids[$info["author_id"]] = $info["author_id"];
			}
			if (is_array($users_ids)) {
				$users_array = module('forum')->_get_users_infos($users_ids);
			}
			foreach ((array)$users_array as $user_info) {
				$users_names[$user_info["id"]] = $user_info["name"];
			}
			foreach ((array)$announces as $info) {
				// Show anounce item
				$replace = array(
					"is_admin"				=> intval(FORUM_IS_ADMIN),
					"announce_link"			=> "./?object=".FORUM_CLASS_NAME."&action=view_announce&id=".$info["id"]._add_get(array("page")),
					"announce_title"		=> _prepare_html($info["title"]),
					"announce_author_link"	=> module('forum')->_user_profile_link($info["author_id"]),
					"announce_author_name"	=> _prepare_html($users_names[$info["author_id"]]),
					"announce_num_views"	=> intval($info["title"]),
				);
				$announce_items .= tpl()->parse(FORUM_CLASS_NAME."/view_forum_announce_item", $replace);
			}
		}
		return $announce_items;
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$F = &$_SESSION[$this->_filter_name];
		foreach ((array)$F as $k => $v) {
			$F[$k] = trim($v);
		}
		// Default values
		if (!isset($F["prune_day"])) {
			$F["prune_day"] = array_pop(array_keys($this->_filter_prune_days));
		}
		// Process prune days
		if (isset($this->_filter_prune_days[$F["prune_day"]])) {
			$sql .= " AND `created` > ".(time() - $F["prune_day"] * 3600 * 24)." \r\n";
		}
		// Process topics flag
		if (isset($this->_filter_topics_flags[$F["topics_flag"]])) {
			if ($F["topics_flag"] == "all") {
				$sql .= "";
			} elseif ($F["topics_flag"] == "open") {
				$sql .= " AND `status` = 'a' \r\n";
			} elseif ($F["topics_flag"] == "closed") {
				$sql .= " AND `status` = 'c' \r\n";
			} elseif ($F["topics_flag"] == "hot") {
				$sql .= " AND `num_posts` >= ".intval(module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"])." \r\n";
			} elseif ($F["topics_flag"] == "locked") {
				$sql .= " AND `pinned` = 1 \r\n";
			} elseif ($F["topics_flag"] == "moved")	{
				$sql .= " AND `moved_to` != '' \r\n";
			// Only for logged in users
			} elseif ($F["topics_flag"] == "istarted" && FORUM_USER_ID) {
				$sql .= " AND `user_id`=".intval(FORUM_USER_ID)." \r\n";
			}
		}
		// Sorting here
		if (isset($this->_filter_sort_by[$F["sort_by"]])) {
			$sql .= " ORDER BY `".$F["sort_by"]."` ".($F["sort_order"] == "Z-A" ? "DESC" : "ASC")." \r\n";
		} else $sql .= " ORDER BY `last_post_date` DESC \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Session - based members filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get(array("page")),
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse(FORUM_CLASS_NAME."/view_forum_filter", $replace);
	}

	/**
	* Filter save method
	*/
	function _save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) {
				$_SESSION[$this->_filter_name][$name] = $_POST[$name];
			}
		}
		if (!$silent) {
			js_redirect("./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get());
		}
	}

	/**
	* Clear filter
	*/
	function _clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) {
				unset($_SESSION[$this->_filter_name]);
			}
		}
		if (!$silent) {
			js_redirect("./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get());
		}
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Topic Act Box
	*/
	function _t_act_box ($name = "t_act") {
		if (module('forum')->USER_RIGHTS["close_topics"])			$t_actions["close"]		= t('Close Topics');
		if (module('forum')->USER_RIGHTS["open_topics"])			$t_actions["open"]		= t('Open Topics');
		if (module('forum')->USER_RIGHTS["pin_topics"])				$t_actions["pin"]		= t('Pin Topics');
		if (module('forum')->USER_RIGHTS["unpin_topics"])			$t_actions["unpin"]		= t('Unpin Topics');
		if (module('forum')->USER_RIGHTS["move_topics"])			$t_actions["move"]		= t('Move Topics');
		if (module('forum')->USER_RIGHTS["split_merge"])			$t_actions["merge"] 	= t('Merge Topics');
		if (module('forum')->USER_RIGHTS["delete_other_topics"])	$t_actions["delete"]	= t('Delete Topics');
		if (module('forum')->USER_RIGHTS["approve_topics"])			$t_actions["approve"]	= t('Set Visible')." (".t('Approve').")";
		if (module('forum')->USER_RIGHTS["unapprove_topics"])		$t_actions["unapprove"]	= t('Set Invisible')." (".t('Unapprove').")";
		return !empty($t_actions) ? common()->select_box($name, $t_actions, "", 0, 2, "", false) : "";
	}
}
