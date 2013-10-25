<?php

/**
* Manage future posts
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_manage_future {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var int Next auto-date lower limit (in seconds) */
	public $NEXT_DATE_MIN	= 3600;
	/** @var int Next auto-date higher limit (in seconds) */
	public $NEXT_DATE_MAX	= 7200;

	/**
	* Framework constructor
	*/
	function _init() {
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* List of future posts
	*/
	function _show_future_posts() {
		if (!in_array($_SESSION["admin_group"], array(1, 6))) {
			return "Access denied";
		}
		// Get forum posters
		$Q = db()->query("SELECT * FROM ".db('admin')." /*WHERE `group`=6*/ ORDER BY first_name ASC");
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A["id"]] = $A;
		// Connect pager
		$sql = "SELECT * FROM ".db('forum_future_posts')." ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql("posts") : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date ASC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get records
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$future_posts[$A["id"]]		= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
			if ($A["topic_id"]) {
				$topics_ids[$A["topic_id"]]	= $A["topic_id"];
			}
		}
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A["id"]] = $A;
		}
		if (!empty($topics_ids)) {
			$Q = db()->query("SELECT * FROM ".db('forum_topics')." WHERE id IN(".implode(",", $topics_ids).")");
			while ($A = db()->fetch_assoc($Q)) $topics_infos[$A["id"]] = $A;
		}
		// Process records
		foreach ((array)$future_posts as $A) {
			$poster_info	= $forum_posters[$A["poster_id"]];
			$user_info		= $users_infos[$A["user_id"]];
			$_forum_info	= module("forum")->_forums_array[$A["forum_id"]];
			$_topic_info	= $A["topic_id"] ? $topics_infos[$A["topic_id"]] : false;
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"poster_name"	=> _prepare_html($poster_info["first_name"]." ".$poster_info["last_name"]),
				"by_poster_link"=> "./?object=".$_GET["object"]."&action=show_posters&id=".intval($A["poster_id"]),
				"user_name"		=> _prepare_html(_display_name($user_info)),
				"profile_link"	=> _profile_link($A["user_id"]),
				"date"			=> _format_date($A["date"], "long"),
				"type"			=> $A["new_topic"] ? "topic" : "post",
				"subject"		=> _prepare_html($A["subject"]),
				"forum_link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".intval($A["forum_id"]),
				"forum_name"	=> _prepare_html($_forum_info["name"]),
				"topic_link"	=> $_topic_info ? "./?object=".$_GET["object"]."&action=view_topic&id=".intval($A["topic_id"]) : "",
				"topic_name"	=> $_topic_info ? _prepare_html($_topic_info["name"]) : "",
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_future_post&id=".intval($A["id"])._add_get(array("id")),
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_future_post&id=".intval($A["id"])._add_get(array("id")),
			);
			$items .= tpl()->parse("forum/admin/future_posts_item", $replace2);
		}
		// Prepare template
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"filter"			=> $this->USE_FILTER ? $this->_show_filter("posts") : "",
			"future_topic_link"	=> "./?object=".$_GET["object"]."&action=add_future_topic&id=".$_GET['id']._add_get(array("id")),
			"mass_delete_action"=> "./?object=".$_GET["object"]."&action=delete_future_post&id=".$_GET['id']._add_get(array("id")),
		);
		return tpl()->parse("forum/admin/future_posts_main", $replace);
	}

	/**
	* Add new future topic
	*/
	function _add_topic() {
		if (!in_array($_SESSION["admin_group"], array(1, 6))) {
			return "Access denied";
		}
		$FORUM_ID = $_GET["id"];
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 3600);
		$_users_array = $all_posters_users[$_SESSION["admin_id"]];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e(t("No user accounts specified for you."));
		}
		// Save data
		if (!empty($_POST)) {
			$_POST["user_id"] = intval($_POST["user_id"]);
			if (empty($_POST["user_id"]) || !isset($_users_array[$_POST["user_id"]])) {
				_re(t("User id required"));
			}
			if (empty($_POST["name"])) {
				_re(t("Topic name required"));
			}
			if (empty($_POST["text"])) {
				_re(t("Topic text required"));
			}
			if (!common()->_error_exists()) {
				db()->INSERT("forum_future_posts", array(
					"poster_id"			=> intval($_SESSION["admin_id"]),
					"forum_id"			=> intval($_POST["forum"]),
					"topic_id"			=> 0,
					"future_topic_id"	=> 0,
					"user_id"			=> intval($_POST["user_id"]),
					"user_name"			=> _es($_users_array[$_POST["user_id"]]),
					"date"				=> strtotime($_POST["date"]),
					"subject"			=> _es($_POST["desc"]),
					"text"				=> _es($_POST["text"]),
					"new_topic"			=> 1,
					"topic_title"		=> _es($_POST["name"]),
					"active"			=> 1,
				));
				return js_redirect("./?object=".$_GET["object"]."&action=view_forum&id=".$_POST["forum"]);
			}
		}
		if (empty($_POST["date"])) {
			$_POST["date"] = time() + rand($this->NEXT_DATE_MIN, $this->NEXT_DATE_MAX);
		}
		$_parents_array = module("forum")->_prepare_parents_for_select();
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(),
			"error_message"	=> _e(),
			"name"			=> _prepare_html($_POST["name"]),
			"desc"			=> _prepare_html($_POST["desc"]),
			"text"			=> _prepare_html($_POST["text"]),
			"forum_box"		=> common()->select_box("forum", $_parents_array, $_GET["id"], false),
			"users_box"		=> common()->select_box("user_id", $_users_array, $_POST["user_id"], false),
			"date"			=> date("Y-m-d H:i:s", !is_numeric($_POST["date"]) ? strtotime($_POST["date"]) : $_POST["date"]),
			"back"			=> back("./?object=".$_GET["object"]."&action=view_forum&id=".$_GET["id"]),
			"next_date_min"	=> intval($this->NEXT_DATE_MIN),
			"next_date_max"	=> intval($this->NEXT_DATE_MAX),
		);
		return tpl()->parse("forum/admin/future_topic_form", $replace);
	}

	/**
	* Add new future post
	*/
	function _add_post() {
		if (!in_array($_SESSION["admin_group"], array(1, 6))) {
			return "Access denied";
		}
		$_GET["id"] = intval($_GET["id"]);
		$TOPIC_ID = $_GET["id"];
		if (!empty($_GET["id"])) {
			$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$_GET['id']." LIMIT 1");
		}
		if (empty($topic_info['id'])) {
			return module("forum")->_show_error("No such topic");
		}
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 3600);
		$_users_array = $all_posters_users[$_SESSION["admin_id"]];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e(t("No user accounts specified for you."));
		}
		$parent_forum_id = module("forum")->_forums_array[$topic_info['forum']]["parent"];
		$forum_name = module("forum")->_forums_array[$topic_info["forum"]]["name"];
		$topic_name = $topic_info["name"];
		$cat_name	= $topic_info["category"] ? module("forum")->_forum_cats_array[$topic_info["category"]]["name"] : module("forum")->_forum_cats_array[module("forum")->_forums_array[$topic_info["forum"]]["category"]]["name"];
		// Save data
		if (!empty($_POST)) {
			// Process multi-add
			foreach ((array)$_POST["text"] as $_item_id => $_tmp) {
				$DATA = array(
					"user_id"	=> $_POST["user_id"][$_item_id],
					"date"		=> $_POST["date"][$_item_id],
					"text"		=> $_POST["text"][$_item_id],
					"subject"	=> $_POST["subject"][$_item_id],
				);
				$DATA["user_id"] = intval($DATA["user_id"]);
				if (empty($DATA["user_id"]) || !isset($_users_array[$DATA["user_id"]])) {
					continue;
				}
				if (empty($DATA["text"])) {
					continue;
				}
				db()->INSERT("forum_future_posts", array(
					"poster_id"			=> intval($_SESSION["admin_id"]),
					"forum_id"			=> intval($topic_info["forum"]),
					"topic_id"			=> intval($_GET["id"]),
					"future_topic_id"	=> 0,
					"user_id"			=> intval($DATA["user_id"]),
					"user_name"			=> _es($_users_array[$DATA["user_id"]]),
					"date"				=> strtotime($DATA["date"]),
					"subject"			=> _es($DATA["subject"]),
					"text"				=> _es($DATA["text"]),
					"new_topic"			=> 0,
					"topic_title"		=> "",
					"active"			=> 1,
				));
			}
			return js_redirect("./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id")));
		}
		if (empty($_POST["date"])) {
			$_POST["date"] = time() + rand($this->NEXT_DATE_MIN, $this->NEXT_DATE_MAX);
		}
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(),
			"error_message"	=> _e(),
			"cat_name"		=> _prepare_html($cat_name),
			"forum_name"	=> _prepare_html($forum_name),
			"topic_name"	=> _prepare_html($topic_name),
			"cat_link"		=> "./?object=".$_GET["object"]._add_get(array("id")),
			"forum_link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$topic_info["forum"]._add_get(array("id")),
			"topic_link"	=> "./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id")),
			"subject"		=> "Re:"._prepare_html($topic_info["name"]),
			"text"			=> _prepare_html($_POST["text"]),
			"users_box"		=> common()->select_box("user_id[]", $_users_array, $_POST["user_id"], false),
			"date"			=> date("Y-m-d H:i:s", !is_numeric($_POST["date"]) ? strtotime($_POST["date"]) : $_POST["date"]),
			"time"			=> (!is_numeric($_POST["date"]) ? strtotime($_POST["date"]) : $_POST["date"]),
			"back"			=> back("./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id"))),
			"next_date_min"	=> intval($this->NEXT_DATE_MIN),
			"next_date_max"	=> intval($this->NEXT_DATE_MAX),
		);
		return tpl()->parse("forum/admin/future_post_form", $replace);
	}

	/**
	* Edit future post
	*/
	function _edit_future_post() {
		if (!in_array($_SESSION["admin_group"], array(1, 6))) {
			return "Access denied";
		}
// TODO: add checking for owner
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$post_info = db()->query_fetch("SELECT * FROM ".db('forum_future_posts')." WHERE id='".intval($_GET["id"])."'");
		}
		if (empty($post_info)) {
			return "No such post";
		}
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 3600);
		$_users_array = $all_posters_users[$_SESSION["admin_id"]];
		unset($all_posters_users);
		if (empty($_users_array)) {
			return _e(t("No user accounts specified for you."));
		}
		$is_new_topic = $post_info["new_topic"] ? 1 : 0;
		$_forum_info = module("forum")->_forums_array[$post_info["forum_id"]];
		$forum_name = $_forum_info["name"];
		$cat_name	= module("forum")->_forum_cats_array[$_forum_info["category"]]["name"];
		if (!$is_new_topic) {
			$topic_info = db()->query_fetch("SELECT * FROM ".db('forum_topics')." WHERE id=".$post_info['topic_id']." LIMIT 1");
			$topic_name = $topic_info["name"];
		}
		// Get forum posters
		$Q = db()->query("SELECT * FROM ".db('admin')." ORDER BY first_name ASC");
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A["id"]] = $A;
		// Do save data
		if (!empty($_POST)) {
			db()->UPDATE("forum_future_posts", array(
				"forum_id"			=> intval($_POST["forum"] ? $_POST["forum"] : $post_info["forum_id"]),
				"user_id"			=> intval($_POST["user_id"]),
				"user_name"			=> _es($_users_array[$_POST["user_id"]]),
				"date"				=> strtotime($_POST["date"]),
				"subject"			=> _es($_POST["subject"]),
				"text"				=> _es($_POST["text"]),
				"topic_title"		=> _es($_POST["name"] ? $_POST["name"] : $post_info["topic_title"]),
			), "id=".intval($_GET["id"]));
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=show_future_posts");
		}
		$_parents_array = module("forum")->_prepare_parents_for_select();
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(),
			"is_new_topic"	=> $is_new_topic,
			"error_message"	=> _e(),
			"cat_name"		=> _prepare_html($cat_name),
			"forum_name"	=> _prepare_html($forum_name),
			"topic_name"	=> _prepare_html($topic_name),
			"cat_link"		=> "./?object=".$_GET["object"]._add_get(array("id")),
			"forum_link"	=> "./?object=".$_GET["object"]."&action=view_forum&id=".$topic_info["forum"]._add_get(array("id")),
			"topic_link"	=> !$is_new_topic ? "./?object=".$_GET["object"]."&action=view_topic&id=".$_GET['id']._add_get(array("id")) : "",
			"name"			=> _prepare_html($post_info["topic_title"]),
			"subject"		=> _prepare_html($post_info["subject"]),
			"text"			=> _prepare_html($post_info["text"]),
			"forum_box"		=> common()->select_box("forum", $_parents_array, $post_info["forum_id"], false),
			"users_box"		=> common()->select_box("user_id", $_users_array, $post_info["user_id"], false),
			"date"			=> date("Y-m-d H:i:s", !is_numeric($post_info["date"]) ? strtotime($post_info["date"]) : $post_info["date"]),
			"back"			=> back("./?object=".$_GET["object"]."&action=show_future_posts"._add_get(array("id"))),
		);
		return tpl()->parse($_GET["object"]."/admin/future_edit_post", $replace);
	}

	/**
	* Delete future post
	*/
	function _delete_future_post() {
		if ($_SESSION["admin_group"] != 1) {
			return "Access denied";
		}
// TODO: add checking for owner
		$_GET["id"] = intval($_GET["id"]);
		// Mass delete
		if (isset($_POST["items"])) {
			$ids_to_delete = array();
			// Prepare ids to delete
			foreach ((array)$_POST["items"] as $_cur_id) {
				if (empty($_cur_id)) {
					continue;
				}
				$ids_to_delete[$_cur_id] = $_cur_id;
			}
			// Do delete ids
			if (!empty($ids_to_delete)) {
				db()->query("DELETE FROM ".db('forum_future_posts')." WHERE id IN(".implode(",",$ids_to_delete).")");
			}
		// Single delete
		} else {
			if (!empty($_GET["id"])) {
				$post_info = db()->query_fetch("SELECT * FROM ".db('forum_future_posts')." WHERE id='".intval($_GET["id"])."'");
			}
			if (!empty($post_info)) {
				db()->query("DELETE FROM ".db('forum_future_posts')." WHERE id=".intval($_GET["id"]));
			}
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Display posters list
	*/
	function _show_posters() {
		// Only for the super-admin
		if ($_SESSION["admin_group"] != 1) {
			return "Access denied";
		}
		$POSTER_ID = intval($_GET["id"]);
		// Get forum posters
		$Q = db()->query("SELECT * FROM ".db('admin')." WHERE `group`=6 ORDER BY first_name ASC");
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A["id"]] = $A;
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 3600);
		// Process records
		foreach ((array)$forum_posters as $A) {
			$users_array = array();
			foreach ((array)$all_posters_users[$A["id"]] as $_user_id => $_user_name) {
				$users_array[] = array(
					"name"	=> _prepare_html($_user_name),
					"link"	=> _profile_link($_user_id),
				);
			}
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"user_id"		=> intval($A["id"]),
				"user_name"		=> _prepare_html($A["first_name"]." ".$A["last_name"]),
				"users_array"	=> $users_array,
				"start_date"	=> _format_date($A["add_date"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_forum_poster&id=".intval($A["id"])._add_get(array("id")),
				"stats_link"	=> "./?object=".$_GET["object"]."&action=show_poster_stats&id=".intval($A["id"])._add_get(array("id")),
				"delete_link"	=> "./?object=admin",
			);
			$items .= tpl()->parse("forum/admin/forum_posters_item", $replace2);
		}
		// Prepare template
		$replace = array(
			"add_link"	=> "./?object=admin&action=add",
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"filter"	=> $this->USE_FILTER ? $this->_show_filter("stats") : "",
		);
		return tpl()->parse("forum/admin/forum_posters_main", $replace);
	}

	/**
	* Display poster stats
	*/
	function _show_poster_stats() {
		// Only for the super-admin
		if ($_SESSION["admin_group"] != 1) {
			return "Access denied";
		}
		$_GET["id"] = intval($_GET["id"]);
		$POSTER_ID = $_GET["id"];
		// Get forum posters
		$Q = db()->query("SELECT * FROM ".db('admin')." /*WHERE `group`=6*/ ORDER BY first_name ASC");
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A["id"]] = $A;
		// Check if such admin exists
		$poster_info = $forum_posters[$POSTER_ID];
		if (!isset($poster_info)) {
			return "No such poster";
		}
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 3600);
		$users_ids = array();
		foreach ((array)$all_posters_users[$POSTER_ID] as $_user_id => $_user_name) {
			$users_ids[$_user_id] = $_user_id;
		}
		ksort($users_ids);
// TODO: connect filter here
		$START_DATE	= $poster_info["add_date"];
		$WORK_DAYS	= floor((time() - $START_DATE) / 86400);
		// Get number of posts and themes created by this poster
		list($themes_total)	= db()->query_fetch(
			"SELECT COUNT(*) AS `0` FROM ".db('forum_topics')." WHERE auto_poster_id=".intval($POSTER_ID)
		);
		list($posts_total)	= db()->query_fetch(
			"SELECT COUNT(*) AS `0` FROM ".db('forum_posts')." WHERE new_topic != 1 AND auto_poster_id=".intval($POSTER_ID)
		);
		// Count number of words and symbols (without quotes)
		$words_total	= 0;
		$symbols_total	= 0;
		// Get data from db
		$Q = db()->query(
			"SELECT text FROM ".db('forum_posts')." WHERE auto_poster_id=".intval($POSTER_ID)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$cur_text = $this->_cleanup_text($A["text"]);
			$_cur_length = strlen($cur_text);
			if (!$_cur_length) {
				continue;
			}
			$symbols_total += $_cur_length;
			$_num_words = strlen(preg_replace("/[^\s]/ims", "", $cur_text)) + 1;
			$words_total += $_num_words;
		}
		// Get number of responses by normal users
		list($total_responses)	= db()->query_fetch(
			"SELECT COUNT(*) AS `0` 
			FROM ".db('forum_posts')." 
			WHERE new_topic != 1 
				AND auto_poster_id=0 
				AND topic IN ( 
					SELECT id FROM ".db('forum_topics')." WHERE auto_poster_id = ".intval($POSTER_ID)."
				)"
		);
		// Get posts inside other themes
		$Q = db()->query(
			"SELECT text FROM ".db('forum_posts')." ".db('forum_posts')." 
			WHERE new_topic != 1 
				AND auto_poster_id = ".intval($POSTER_ID)."
				AND topic NOT IN ( 
					SELECT id FROM ".db('forum_topics')." WHERE auto_poster_id = ".intval($POSTER_ID)."
				)"
		);
		$others_themes_posts	= 0;
		$others_themes_length	= 0;
		while ($A = db()->fetch_assoc($Q)) {
			$others_themes_posts++;
			$cur_text = $this->_cleanup_text($A["text"]);
			$_cur_length = strlen($cur_text);
			if (!$_cur_length) {
				continue;
			}
			$others_themes_length += $_cur_length;
		}
		// Gather stats
		$stats = array(
			"themes_total"			=> intval($themes_total),
			"themes_per_month"		=> round($WORK_DAYS ? ($themes_total / $WORK_DAYS * 30) : 0, 2),
			"themes_per_day"		=> round($WORK_DAYS ? ($themes_total / $WORK_DAYS) : 0, 2),
			"posts_total"			=> intval($posts_total),
			"posts_per_month"		=> round($WORK_DAYS ? ($posts_total / $WORK_DAYS * 30) : 0, 2),
			"posts_per_day"			=> round($WORK_DAYS ? ($posts_total / $WORK_DAYS) : 0, 2),
			"words_total"			=> intval($words_total),
			"words_per_month"		=> round($WORK_DAYS ? ($words_total / $WORK_DAYS * 30) : 0, 2),
			"words_per_day"			=> round($WORK_DAYS ? ($words_total / $WORK_DAYS) : 0, 2),
			"symbols_total"			=> intval($symbols_total),
			"symbols_per_month"		=> round($WORK_DAYS ? ($symbols_total / $WORK_DAYS * 30) : 0, 2),
			"symbols_per_day"		=> round($WORK_DAYS ? ($symbols_total / $WORK_DAYS) : 0, 2),
			"total_responses"		=> intval($total_responses),
			"responses_per_topic"	=> round($total_responses / $themes_total, 2),
			"others_themes_posts"	=> round($posts_total ? $others_themes_posts / $posts_total : 0, 2),
			"others_themes_length"	=> round($symbols_total ? $others_themes_length / $symbols_total : 0, 2),
		);
		// Prepare template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(),
			"users_ids"				=> implode(",", $users_ids),
			"poster_id"				=> intval($poster_info["id"]),
			"poster_name"			=> _prepare_html($poster_info["first_name"]." ".$poster_info["last_name"]),
			"filter"				=> $this->USE_FILTER ? $this->_show_filter("stats") : "",
			"start_date"			=> _format_date($START_DATE),
			"work_days"				=> intval($WORK_DAYS),
			"back"					=> back("./?object=".$_GET["object"]."&action=show_future_posts"._add_get(array("id"))),
		);
		foreach ((array)$stats as $k => $v) {
			$replace[$k] = $v;
		}
		return tpl()->parse("forum/admin/forum_poster_stats", $replace);
	}

	/**
	* 
	*/
	function _cleanup_text($cur_text = "") {
		if (!strlen($cur_text)) {
			return "";
		}
		$cur_text = str_replace(array("\r", "\n", "\t"), array("\n", " ", " "), $cur_text);
		$cur_text = preg_replace("/\[quote(=[^\]]+){0,1}\].*\[\/quote\]/ims", "", $cur_text);
		$cur_text = preg_replace("/[\s]{2,}/ims", " ", trim($cur_text));
		return $cur_text;
	}

	/**
	* Edit current poster
	*/
	function _edit_poster() {
		// Only for the super-admin
		if ($_SESSION["admin_group"] != 1) {
			return "Access denied";
		}
		$_GET["id"] = intval($_GET["id"]);
		$POSTER_ID = $_GET["id"];
		// Get forum posters
		$Q = db()->query("SELECT * FROM ".db('admin')." /*WHERE `group`=6*/ ORDER BY first_name ASC");
		while ($A = db()->fetch_assoc($Q)) $forum_posters[$A["id"]] = $A;
		// Check if such admin exists
		$poster_info = $forum_posters[$POSTER_ID];
		if (!isset($poster_info)) {
			return "No such poster";
		}
		// Get child accouts for the current poster
		$all_posters_users = main()->get_data("forum_posters_users", 5/* !Do not touch! */);
		$users_ids = array();
		foreach ((array)$all_posters_users[$POSTER_ID] as $_user_id => $_user_name) {
			$users_ids[$_user_id] = $_user_id;
		}
		ksort($users_ids);
		// Save data
		if (!empty($_POST)) {
			// Cleanup posted ids
			$new_users_ids = array();
			foreach (explode(",", $_POST["users_ids"]) as $_user_id) {
				$_user_id = intval($_user_id);
				if (empty($_user_id)) {
					continue;
				}
				$new_users_ids[$_user_id] = $_user_id;
			}
			// Get ids to delete poster_id
			if (!empty($users_ids)) {
				$ids_to_delete = array();
				foreach ((array)$users_ids as $_user_id) {
					$_user_id = intval($_user_id);
					if (empty($_user_id)) {
						continue;
					}
					if (!isset($new_users_ids[$_user_id])) {
						$ids_to_delete[$_user_id] = $_user_id;
					}
				}
				// Do remove this poster from old records
				if (!empty($ids_to_delete)) {
					db()->UPDATE("user", array("poster_id" => 0), "id IN(".implode(",", $ids_to_delete).")");
				}
			}
			// Get ids to add poster_id
			if (!empty($new_users_ids)) {
				$ids_to_add = array();
				foreach ((array)$new_users_ids as $_user_id) {
					$_user_id = intval($_user_id);
					if (empty($_user_id)) {
						continue;
					}
					if (!isset($users_ids[$_user_id])) {
						$ids_to_add[$_user_id] = $_user_id;
					}
				}
				// Do add this poster to the selected accounts
				if (!empty($ids_to_add)) {
					db()->UPDATE("user", array("poster_id" => $POSTER_ID), "id IN(".implode(",", $ids_to_add).")");
				}
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("forum_posters_users");
			}
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=show_forum_posters");
		}
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET['id']._add_get(),
			"users_ids"		=> implode(",", $users_ids),
			"poster_id"		=> intval($poster_info["id"]),
			"poster_name"	=> _prepare_html($poster_info["first_name"]." ".$poster_info["last_name"]),
			"back"			=> back("./?object=".$_GET["object"]."&action=show_forum_posters"._add_get(array("id"))),
		);
		return tpl()->parse("forum/admin/edit_forum_poster", $replace);
	}

	/**
	* Do cron job for future posts
	*/
	function _do_cron_job() {
		$ids_to_delete = array();
		// Get future records to be inserted now
		$Q = db()->query(
			"SELECT * 
			FROM ".db('forum_future_posts')." 
			WHERE date < ".time()." 
				AND active='1' 
			ORDER BY date DESC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$NEW_POST_ID	= 0;
			$NEW_TOPIC_ID	= 0;
			// Create topic
			if ($A["new_topic"]) {

				// Pre-create topic
				db()->INSERT("forum_topics", array(
					"forum"				=> $A["forum_id"],
					"status"			=> "c",
				));
				$NEW_TOPIC_ID = db()->INSERT_ID();
				if (empty($NEW_TOPIC_ID)) {
					continue;
				}
				// Create new post
				db()->INSERT("forum_posts", array(
					"forum"			=> intval($A["forum_id"]),
					"topic"			=> intval($NEW_TOPIC_ID),
					"auto_poster_id"=> intval($A["poster_id"]),
					"user_id"		=> intval($A["user_id"]),
					"user_name"		=> _es($A["user_name"]),
					"created"		=> intval($A["date"]),
					"subject"		=> _prepare_html($A["subject"]),
					"text"			=> _prepare_html($A["text"]),
					"new_topic"		=> 1,
					"status"		=> "a",
				));
				$NEW_POST_ID = db()->INSERT_ID();
				if (empty($NEW_POST_ID)) {
					// Cleanup if failed
					db()->query("DELETE FROM ".db('forum_topics')." WHERE id=".intval($NEW_TOPIC_ID));
					continue;
				}
				// Update all other info in the created topic
				db()->UPDATE("forum_topics", array(
					"forum"				=> intval($A["forum_id"]),
					"auto_poster_id"	=> intval($A["poster_id"]),
					"user_id"			=> intval($A["user_id"]),
					"user_name"			=> _es($A["user_name"]),
					"created"			=> intval($A["date"]),
					"name"				=> _prepare_html($A["topic_title"]),
					"desc"				=> _prepare_html($A["subject"]),
					"first_post_id"		=> intval($NEW_POST_ID),
					"last_post_id"		=> intval($NEW_POST_ID),
					"last_poster_id"	=> intval($A["user_id"]),
					"last_poster_name"	=> _es($A["user_name"]),
					"status"			=> "a",
					"approved"			=> 1,
				), "id=".intval($NEW_TOPIC_ID));

			// Create new post
			} else {

				db()->INSERT("forum_posts", array(
					"forum"			=> intval($A["forum_id"]),
					"topic"			=> intval($A["topic_id"]),
					"auto_poster_id"=> intval($A["poster_id"]),
					"user_id"		=> intval($A["user_id"]),
					"user_name"		=> _es($A["user_name"]),
					"created"		=> intval($A["date"]),
					"subject"		=> _prepare_html($A["subject"]),
					"text"			=> _prepare_html($A["text"]),
					"new_topic"		=> 1,
					"status"		=> "a",
				));
				$NEW_POST_ID = db()->INSERT_ID();
				if (empty($NEW_POST_ID)) {
					continue;
				}

			}
			// Store id to delete later
			$ids_to_delete[$A["id"]] = $A["id"];
		}
		// Delete future post records
		if (!empty($ids_to_delete)) {
			db()->query(
				"DELETE FROM ".db('forum_future_posts')." WHERE id IN(".implode(",", $ids_to_delete).")"
			);
			_class("forum_sync", USER_MODULES_DIR."forum/")->_sync_board(true);
		}
	}

	/**
	* Prepare required data for filter
	*/
	function _prepare_filter_data () {
		if (in_array($_GET["action"], array("show_future_posts"))) {
			$filter_for = "posts";
		} elseif (in_array($_GET["action"], array("show_forum_posters"))) {
			$filter_for = "posters";
		}
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,		$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
			"post_type"		=> 'select_box("post_type",		$this->_post_types,			$selected, 0, 2, "", false)',
			"user_id"		=> 'select_box("user_id",		$this->_users_ids2,			$selected, 0, 2, "", false)',
			"poster_id"		=> 'select_box("poster_id",		$this->_forum_posters2,		$selected, 0, 2, "", false)',
		));
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"poster_id",
			"date_min",
			"date_max",
			"post_type",
			"sort_by",
			"sort_order",
		);
		// Switch between filter types
		if ($filter_for == "posts") {
			// Post types
			$this->_post_types = array(
				" "		=> t("-- All --"),
				"post"	=> "Post",
				"topic"	=> "Topic",
			);
			// Get forum posters
			$this->_forum_posters2[" "]	= t("-- All --");
			$Q = db()->query("SELECT * FROM ".db('admin')." WHERE `group`=6 ORDER BY first_name ASC");
			while ($A = db()->fetch_assoc($Q)) $this->_forum_posters2[$A["id"]] = $A["first_name"]." ".$A["last_name"];
			// Get child accouts for the current poster
			$all_posters_users = main()->get_data("forum_posters_users", 3600);
			$this->_users_ids2[" "]	= t("-- All --");
			foreach ((array)$all_posters_users as $_poster_id => $_users) {
				$_poster_name = $this->_forum_posters2[$_poster_id];
				foreach ((array)$_users as $_user_id => $_user_name) {
					$this->_users_ids2/*[$_poster_name]*/[$_user_id] = $_user_name;
				}
			}
			// Sort fields
			$this->_sort_by = array(
				"",
				"date",
				"user_id",
				"poster_id",
				"forum_id",
				"topic_id",
				"new_topic",
			);
		} elseif ($filter_for == "posters") {
			// Sort fields
			$this->_sort_by = array(
				"",
				"date",
			);
// TODO
		}
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql ($filter_for = "posts") {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($filter_for == "posts") {
			if ($SF["date_min"]) 	$sql .= " AND date >= ".strtotime($SF["date_min"])." \r\n";
			if ($SF["date_max"])	$sql .= " AND date <= ".strtotime($SF["date_max"])." \r\n";
			if ($SF["user_id"])		$sql .= " AND user_id = ".intval($SF["user_id"])." \r\n";
			if ($SF["poster_id"])	$sql .= " AND poster_id = ".intval($SF["poster_id"])." \r\n";
			if ($SF["post_type"])	$sql .= " AND new_topic ".($SF["post_type"] == "post" ? "=0 " : "!=0")." \r\n";
		} elseif ($filter_for == "posters") {
			if ($SF["date_min"]) 	$posts_sql .= " AND date >= ".strtotime($SF["date_min"])." \r\n";
			if ($SF["date_max"])	$posts_sql .= " AND date <= ".strtotime($SF["date_max"])." \r\n";
// TODO
		}
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	/**
	* Session - based filter
	*/
	function _show_filter ($filter_for = "posts") {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=future_save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=future_clear_filter"._add_get(),
			"filter_for"	=> $filter_for,
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = _prepare_html($_SESSION[$this->_filter_name][$name]);
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/admin/".($filter_for == "posts" ? "future_filter_posts" : "future_filter_posters"), $replace);
	}

	/**
	* Filter save method
	*/
	function _save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Clear filter
	*/
	function _clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			if (!empty($_REQUEST["go_home"])) {
				return js_redirect("./?object=".$_GET["object"]);
			}
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
