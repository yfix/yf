<?php

/**
* Forum administration class
*/
class yf_forum {

	/** @var string @conf_skip Current forum version */
	public $VERSION		= "admin 1.1.2";
	/** @var int Number of topics to show on one page */
	public $topics_on_page = 50;
	/** @var int Number of messages to show on one page */
	public $posts_on_page	= 20;
	/** @var int Number of symbols to trim comments, etc */
	public $text_trim		= 300;
	/** @var array @conf_skip CSS classes */
	public $css = array (
		"show1"	=> "forum1",
		"show2"	=> "forum2",
		"quote"	=> "forum_quote",
		"code"	=> "forum_code",
		"smile"	=> "forum_smile",
	);
	/** @var array Date/Time formats (arguments for date() function) */
	public $format = array(
		"date"	=> "d/m/Y",
		"time"	=> "H:i:s",
	);
	/** @var array Folder where avatars are storing */
	public $avatars_dir = "uploads/avatars/";
	/** @var bool Approve posts and topics creation */
	public $APPROVE			= false;
	/** @var bool Confirm registration with email */
	public $CONFIRM_REGISTER	= true;
	/** @var bool Tree mode */
	public $TREE_MODE			= false;
	/** @var bool BB Codes */
	public $BB_CODE			= true;
	/** @var bool Show smilies images or CSS based boxes (works only if smilies are allowed) */
	public $SMILIES_IMAGES		= false;
	/** @var bool Show user ranks */
	public $SHOW_USER_RANKS	= true;
	/** @var bool Show totals */
	public $SHOW_TOTALS		= false;
	/** @var bool Show direct links to pages inside forum */
	public $SHOW_TOPIC_PAGES	= true;
	/** @var bool Use global user accounts or only forum internals */
	public $USE_GLOBAL_USERS	= true;
	/** @var bool Hide some links (for using as separate discussion boards for different objects) */
	public $HIDE_USERS_INFO	= true;
	/** @var int @conf_skip user ID (if exists one) */
	public $USER_ID			= null;
	/** @var int @conf_skip group ID (if exists one) */
	public $GROUP_ID			= null;
	/** @var string @conf_skip */
	public $USER_NAME			= null;
	/** @var array @conf_skip (for internal use only) */
	public $forum_cats			= null;
	/** @var array @conf_skip (for internal use only) */
	public $forums				= null;
	/** @var array @conf_skip (for internal use only) */
	public $last_posts			= null;
	/** @var array @conf_skip (for internal use only) */
	public $topics				= null;
	/** @var array @conf_skip (for internal use only) */
	public $posts				= null;
	/** @var array @conf_skip (for internal use only) */
	public $smilies			= null;
	/** @var array @conf_skip (for internal use only) */
	public $users				= null;
	/** @var array @conf_skip (for internal use only) */
	public $user_ranks			= null;
	/** @var array @conf_skip (for internal use only) */
	public $topic_watch		= null;
	/** @var string @conf_skip Unique for each installation identifier (you can set it manually here) */
	public $salt				= "";
	/** @var int Minimal time between posts (posts from IP address with less time period are denied) */
	public $ANTISPAM_TIME		= 0;
	/** @var array */
	public $_group_triggers = array(
		"view_board"			=> "Allow to view forums",
		"view_ip"				=> "Allow to view poster's saved IP address",
		"view_member_info"		=> "Allow to view forum member info",
		"view_other_topics"		=> "Allow to view topic created by others",
		"view_post_closed"		=> "Allow to view closed posts",
		"post_new_topics"		=> "Allow to create new topics",
		"reply_own_topics"		=> "Allow to reply inside own topics",
		"reply_other_topics"	=> "Allow to reply inside others' topics",
		"delete_own_topics"		=> "Allow to delete own topics",
		"delete_other_topics"	=> "Allow to delete others' topics",
		"edit_own_topics"		=> "Allow to edit own topics",
		"edit_other_topics"		=> "Allow to edit others' topics",
		"open_topics"			=> "Allow to open (change status) topics",
		"close_topics"			=> "Allow to close (change status) topics",
		"pin_topics"			=> "Allow to pin topics",
		"unpin_topics"			=> "Allow to unpin topics",
		"move_topics"			=> "Allow to move topics",
		"approve_topics"		=> "Allow to approve topics",
		"unapprove_topics"		=> "Allow to unapprove topics",
		"open_close_posts"		=> "Allow to open/close posts",
		"delete_own_posts"		=> "Allow to delete own posts",
		"delete_other_posts"	=> "Allow to delete others' posts",
		"edit_own_posts"		=> "Allow to edit own posts",
		"edit_other_posts"		=> "Allow to edit others' posts",
		"move_posts"			=> "Allow to move posts",
		"approve_posts"			=> "Allow to approve posts",
		"unapprove_posts"		=> "Allow to unapprove posts",
		"split_merge"			=> "Allow to use split/merge topics",
		"edit_own_profile"		=> "Allow to edit own profile",
		"edit_other_profile"	=> "Allow to edit others' profile",
		"use_search"			=> "Allow to use search",
		"make_polls"			=> "Allow to create Polls",
		"vote_polls"			=> "Allow to vote in Polls",
		//"hide_from_list"		=> "Hide from online users list",
		//"avatar_upload"		=> "Allow uploading avatar",
		//"use_pm"				=> "Allow to use Private Messages",
		"is_admin"				=> "Apply all other Admin rights (IS_ADMIN)",
		"is_moderator"			=> "Apply all other Moderator rights (IS_MODERATOR)",
	);
	/** @var array */
	public $_moderator_triggers = array(
		"view_ip"				=> "Allow to view poster's saved IP address",
		"delete_own_topics"		=> "Allow to delete own topics",
		"delete_other_topics"	=> "Allow to delete others' topics",
		"edit_own_topics"		=> "Allow to edit own topics",
		"edit_other_topics"		=> "Allow to edit others' topics",
		"open_topics"			=> "Allow to open (change status) topics",
		"close_topics"			=> "Allow to close (change status) topics",
		"pin_topics"			=> "Allow to pin topics",
		"unpin_topics"			=> "Allow to unpin topics",
		"move_topics"			=> "Allow to move topics",
		"open_close_posts"		=> "Allow to open/close posts",
		"delete_own_posts"		=> "Allow to delete own posts",
		"delete_other_posts"	=> "Allow to delete others' posts",
		"edit_own_posts"		=> "Allow to edit own posts",
		"edit_other_posts"		=> "Allow to edit others' posts",
		"move_posts"			=> "Allow to move posts",
		"split_merge"			=> "Allow to use split/merge topics",
		"edit_own_profile"		=> "Allow to edit own profile",
		"edit_other_profile"	=> "Allow to edit others' profile",
		"make_polls"			=> "Allow to create Polls",
		"vote_polls"			=> "Allow to vote in Polls",
	);
	/** @var bool */
	public $ALLOW_FUTURE_POSTS		= 1;

	/**
	* Framework constructor
	*/
	function _init () {
		// Init required constants
		define('FORUM_IS_ADMIN', 1);
		// Forum class name (to allow changing only in one place)
		define("FORUM_CLASS_NAME", "forum");
		// Forum modules folder
		define("FORUM_MODULES_DIR", ADMIN_MODULES_DIR.FORUM_CLASS_NAME."/");
		// Set unique identifier (if is empty)
		if (!strlen($this->salt)) {
			$this->salt = substr(md5($_SERVER["HTTP_HOST"]."123456"), 0, 8);
		}
		// Get all forum categories
		$this->_forum_cats_array	= main()->get_data("forum_categories");
		// Get all forums
		$this->_forums_array		= main()->get_data("forum_forums");
		// Get all user groups
		$this->_forum_groups		= main()->get_data("forum_groups");
		// Get all moderators
		$this->_forum_moderators	= main()->get_data("forum_moderators");
		// Try to insert correct user ID from session
		$this->_verify_session_vars();
		// Init bb codes module
		if ($this->BB_CODE) {
			$this->BB_OBJ = main()->init_class("bb_codes", "classes/");
		}
		// Get all smilies into array
		if ($this->BB_CODE && in_array($_GET["action"], array("view_topic"))) {
			$this->smiles = main()->get_data("smilies");
		}
		// Get all ranks into array
		if ($this->SHOW_USER_RANKS && in_array($_GET["action"], array("view_topic"))) {
			$Q = db()->query("SELECT * FROM ".db('forum_ranks')."");// WHERE special=0
			while($A = db()->fetch_assoc($Q)) {
				$this->user_ranks[$A['id']] = $A;
			}
		}
		// Prepare some admin arrays
		$this->_std_trigger = array(
			"1" => "<span class='positive'>".t("YES")."</span>",
			"0" => "<span class='negative'>".t("NO")."</span>",
		);
		$this->_active_select = array(
			"a" => "<b style='color:green;'>".t("Active")."</b>",
			"c" => "<b style='color:red;'>".t("Inactive")."</b>",
		);
		$this->_postings_select = array(
			"" => "<b style='color:green;'>".t("Open")."</b>",
			"2" => "<b style='color:red;'>".t("Closed")."</b>",
		);
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
	function _module_action_handler($called_action = "") {
		if (!$this->_check_acl($called_action)) {
			return "Access denied";
		}
		$body = $this->$called_action();
		return $body;
	}

	/**
	* Show forum layout (default function)
	*/
	function show () {
		$last_posts	= $this->_create_last_posts("forum");
		$_GET['id'] = intval($_GET['id']);
		// If ID specified - then show only one category
		if ($_GET['id']) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('forum_categories')." WHERE id=".$_GET['id']." ORDER BY order");
			if ($cat_info['id']) {
				$body = $this->_show_category_contents($cat_info['id']);
			}
		// Collect categories
		} else {
			if (count($this->_forum_cats_array)) {
				foreach ((array)$this->_forum_cats_array as $_cat_info) {
					$body .= $this->_show_category_contents($_cat_info['id']);
				}
			} else {
				$body = $this->_show_error(t("no_categories"));
			}
		}
		$body .= tpl()->parse("forum/admin/button_add_category");
		return $this->_show_main_tpl($body);
	}

	/**
	* Show category contents
	*/
	function _show_category_contents ($cat_id = 0) {
		foreach ((array)$this->_forums_array as $_forum_info) {
			if ($_forum_info["category"] != $cat_id) {
				continue;
			}
			if (!empty($_forum_info["parent"])) {
				continue;
			}
			$forums .= $this->_show_forum_item($_forum_info);
		}
		// Try to find detailed info about current category
		$cat_details = $this->_forum_cats_array[$cat_id];
		// Prepare template
		$replace = array(
			"cat_name"			=> $this->_forum_cats_array[$cat_id]["name"],
			"cat_link"			=> "./?object=".$_GET["object"]."&id=".$cat_id._add_get(array("id")),
			"forums"			=> $forums,
			"activity"			=> $this->_active_select[$cat_details["status"]],
			"is_active"			=> $cat_details["status"] == "a" ? 1 : 0,
			"edit_link"			=> "./?object=".$_GET["object"]."&action=edit_category&id=".$cat_id,
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_category&id=".$cat_id,
			"add_link"			=> "./?object=".$_GET["object"]."&action=add_forum&id=".$cat_id,
			"active_link"		=> "./?object=".$_GET["object"]."&action=change_category_activity&id=".$cat_id,
			"future_topic_link"	=> module("forum")->ALLOW_FUTURE_POSTS ? "./?object=".$_GET["object"]."&action=add_future_topic&id=c_".$cat_id : "",
		);
		return tpl()->parse("forum/admin/category_main", $replace);
	}

	/**
	* 
	*/
	function _show_forum_item ($forum_info = array()) {
		$OBJ = $this->_load_sub_module("forum_manage_view");
		return is_object($OBJ) ? $OBJ->_show_forum_item($forum_info) : "";
	}

	/**
	* Main function
	*/
	function view_forum () {
		$OBJ = $this->_load_sub_module("forum_manage_view");
		return is_object($OBJ) ? $OBJ->_view_forum() : "";
	}

	/**
	* Process template
	*/
	function view_topic () {
		$OBJ = $this->_load_sub_module("forum_manage_view");
		return is_object($OBJ) ? $OBJ->_view_topic() : "";
	}

	/**
	* New topic creation form
	*/
	function new_topic () {
		$OBJ = $this->_load_sub_module("forum_manage_view");
		return is_object($OBJ) ? $OBJ->_new_topic() : "";
	}

	/**
	* Reply to the existing topic (post message)
	*/
	function reply () {
		$OBJ = $this->_load_sub_module("forum_manage_view");
		return is_object($OBJ) ? $OBJ->_reply() : "";
	}

	/**
	* Searching current forum
	*/
	function search () {
		$body .= tpl()->parse("forum/search_form");
		return $this->_show_main_tpl($body);
	}

	/**
	* Process main template
	*/
	function _show_main_tpl($items = "") {
		if ($_GET["action"] == "show") 				$type = "main";
		elseif ($_GET["action"] == "view_forum")	$type = "forum";
		elseif ($_GET["action"] == "view_topic")	$type = "topic";
		// Prepare template
		$replace = array(
			"user_name"			=> $this->USER_ID ? $this->USER_NAME : t("Guest"),
			"menu"				=> tpl()->parse("forum/menu_member"),
			"items"				=> $items,
			"version"			=> $this->VERSION,
			"totals"			=> "",
			"manage_groups_link"=> "./?object=".$_GET["object"]."&action=manage_groups"._add_get(array("id")),
			"manage_mods_link"	=> "./?object=".$_GET["object"]."&action=manage_moderators"._add_get(array("id")),
			"forum_users_link"	=> "./?object=".$_GET["object"]."&action=manage_users"._add_get(array("id")),
			"forum_posters_link"=> "./?object=".$_GET["object"]."&action=show_forum_posters"._add_get(array("id")),
			"future_posts_link"	=> "./?object=".$_GET["object"]."&action=show_future_posts"._add_get(array("id")),
			"sync_board_link"	=> FORUM_IS_ADMIN == 1 ? "./?object=".$_GET["object"]."&action=sync_board"._add_get(array("id")) : "",
		);
		return tpl()->parse("forum/main", $replace);
	}

	/**
	* Show error message
	*/
	function _show_error($text = "") {
		if (!strlen($text)) {
			$text = t("error");
		}
		return tpl()->parse("forum/error", array("text" => $text));
	}

	/**
	* Count topic view
	*/
	function _add_topic_view($topic_array = array()) {
		db()->query("UPDATE ".db('forum_topics')." SET num_views=num_views+1 WHERE id=".intval($topic_array['id']));
		db()->query("UPDATE ".db('forum_forums')." SET num_views=num_views+1 WHERE id=".intval($topic_array["forum"]));
	}

	/**
	* Create an array of last posts for forums and topics (unified function)
	*/
	function _create_last_posts ($type = "forum") {
		if ($type == "forum") {
			// Collect posts IDs
			foreach ((array)$this->_forums_array as $_forum_info) {
				// Default value (can be overriden later)
				$this->last_posts[$_forum_info['id']] = t("no_posts");
				if ($_forum_info["last_post_id"]) {
					$add_sql .= $_forum_info["last_post_id"].",";
				}
			}
		} elseif ($type == "topic") {
			// Collect posts IDs
			foreach ((array)$this->topics as $_topic_info) {
				// Default value (can be overriden later)
				$this->last_posts[$_topic_info['id']] = t("no_posts");
				if ($_topic_info["last_post_id"]) {
					$add_sql .= $_topic_info["last_post_id"].",";
				}
			}
		}
		if (strlen($add_sql)) {
			$Q = db()->query("SELECT * FROM ".db('forum_posts')." WHERE id IN(".substr($add_sql, 0, -1).")");
			while($post_info = db()->fetch_assoc($Q)) {
				$user_name = $post_info["user_id"] ? (strlen($post_info["user_name"]) ? $post_info["user_name"] : $post_info["user_id"]) : (strlen($post_info["user_name"]) ? $post_info["user_name"] : t("Anonymous"));
				$replace = array(
					"user_name"		=> $user_name,
					"profile_link"	=> $this->_user_profile_link($post_info),
					"time"			=> date($this->format["time"], $post_info["created"]),
					"date"			=> date($this->format["date"], $post_info["created"]),
					"topic"			=> "<a href=\"./?object=".$_GET["object"]."&action=view_topic&id=".$post_info["topic"]._add_get(array("id"))."\">".(_substr($post_info["subject"], 0, 33)."...")."</a>\r\n",
					"post"			=> "<a href=\"./?object=".$_GET["object"]."&action=view_topic&id=".$post_info["topic"]._add_get(array("id"))."\">".(_substr($post_info["subject"], 0, 33)."...")."</a>\r\n",
				);
				$this->last_posts[$post_info["id"]] = tpl()->parse("forum/last_post_".$type, $replace);
			}
		}
	}

	/**
	* Get unique users for the current topic
	*/
	function _get_topic_users () {
		// Create unique users
		foreach ((array)$this->posts as $k => $v) {
			$this->users[$v["user_id"]] = 1;
		}
		// Process users
		foreach ((array)$this->users as $k => $v) {
			$add_sql .= $k.",";
		}
		if (strlen($add_sql)) {
			$Q = db()->query("SELECT * FROM ".db('forum_users')." WHERE id IN(".substr($add_sql, 0, -1).")");
			while($A = db()->fetch_assoc($Q)) {
				$this->users[$A['id']] = $A;
			}
		}
	}

	/**
	* Show icon for new messages if exists some
	*/
	function _forum_new_msg ($forum_id) {
// TODO: convert into new "track new msgs" system
		return $this->USER_ID ? (is_array($this->topic_watch) && in_array($forum_id, $this->topic_watch) ? "N" : "-") : "-";
	}

	/**
	* Show icon for new messages if exists some
	*/
	function _topic_new_msg ($topic_id) {
// TODO: convert into new "track new msgs" system
		return $this->USER_ID ? (is_array($this->topic_watch) && array_key_exists($topic_id, $this->topic_watch) ? "N" : "-") : "-";
	}

	/**
	* Verify session variables
	*/
	function _verify_session_vars () {
		$this->USER_ID	= $_SESSION["admin_id"];
		$this->GROUP_ID = $_SESSION["admin_group"];

		$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($this->USER_ID));

		$this->USER_NAME = $admin_info["first_name"]." ".$admin_info["last_name"];
		$admin_groups = main()->get_data("admin_groups");
		$this->GROUP_NAME = $admin_groups[$this->GROUP_ID];
	}

	/**
	* 
	*/
	function _update_forum_record ($forum_id = 0) {
		if (!$forum_id) {
			return false;
		}
		// Count number of topics
		$num_topics = intval(db()->query_num_rows("SELECT id FROM ".db('forum_topics')." WHERE forum=".$forum_id." AND status='a'"));
		// Count number of posts
		$num_posts = intval(db()->query_num_rows("SELECT id FROM ".db('forum_posts')." WHERE forum=".$forum_id." AND status='a'"));
		// Determine last post ID
		list($last_post_id) = db()->query_fetch("SELECT id AS 0 FROM ".db('forum_posts')." WHERE forum=".$forum_id." AND status='a' ORDER BY created DESC LIMIT 1");
		// Update forum table
		db()->query("UPDATE ".db('forum_forums')." SET num_topics=".$num_topics.",num_posts=".$num_posts.",last_post_id=".intval($last_post_id)." WHERE id=".$forum_id);
	}

	/**
	* 
	*/
	function _update_topic_record ($topic_id = 0) {
		if (!$topic_id) {
			return false;
		}
		// Prepare data for the topic record
		$num_posts = intval(db()->query_num_rows("SELECT id FROM ".db('forum_posts')." WHERE topic=".$topic_id." AND status='a'"));
		list($last_post_id) = db()->query_fetch("SELECT id AS 0 FROM ".db('forum_posts')." WHERE topic=".$topic_id." AND status='a' ORDER BY created DESC LIMIT 1");
		// Update topic record
		db()->query("UPDATE ".db('forum_topics')." SET num_posts=".$num_posts.",last_post_id=".intval($topic_last_post_id)."	WHERE id=".$topic_id);
	}

	/**
	* Prepare link to user's profile
	*/
	function _user_profile_link ($user_info = "", $user_name = "") {
		if (is_array($user_info)) {
			$user_id	= intval($user_info["user_id"]);
			$user_name	= $user_info["user_name"];
		} else {
			$user_id	= intval($user_info);
		}
		return $user_id ? "<a class=\"forum_profile_link\" yf:user_id=\"".$user_id."\" href=\"./?object=".$_GET["object"]."&action=view_profile&id=".$user_id."\" target=\"_blank\">".(strlen($user_name) ? $user_name : $user_id)."</a>" : (strlen($user_name) ? $user_name : t("Anonymous"));
	}

	/**
	* 
	*/
	function _get_sub_forums_ids ($parent_id = 0, $only_first_level = false) {
		$sub_ids = array();
		if (empty($parent_id)) {
			return $sub_ids;
		}
		foreach ((array)$this->_forums_array as $_info) {
			if ($_info["parent"] != $parent_id) {
				continue;
			}
			$sub_ids[$_info["id"]] = $_info["id"];
			if (!$only_first_level) {
				$sub_ids = array_merge($sub_ids, (array)$this->_get_sub_forums_ids($_info["id"]));
			}
		}
		return $sub_ids;
	}

	/**
	* 
	*/
	function _get_parent_forums_ids ($cur_id = 0, $level = 0) {
		$forums_ids = array();
		if (empty($cur_id) || empty($this->_forums_array[$cur_id])) {
			return $forums_ids;
		}
		foreach ((array)$this->_get_parent_forums_ids($this->_forums_array[$cur_id]["parent"], $level + 1) as $_parent_id) {
			$forums_ids[$_parent_id] = $_parent_id;
		}
		if ($level > 0) {
			$forums_ids[$cur_id] = $cur_id;
		}
		return $forums_ids;
	}

	/**
	* View user's profile
	*/
	function view_profile () {
		$_GET['id'] = intval($_GET['id']);
		$user_info = db()->query_fetch("SELECT * FROM ".db('forum_users')." WHERE id=".$_GET['id']);
		if ($user_info['id'] && !$this->HIDE_USERS_INFO) {
			$replace = $user_info;
			$replace["user_regdate"] = date($this->format["date"], $replace["user_regdate"]);
			// Send Private message link
			$replace["pm_link"] = "";
			$body .= tpl()->parse("forum/view_profile", $replace);
		} else {
			$body .= $this->_show_error(t("no_such_user"));
		}
		return $this->_show_main_tpl($body);
	}

	//################# ADMINISTRATION METHODS #################//

	/**
	* Info usually for select box
	*/
	function _prepare_parents_for_select ($skip_id = 0) {
		$forums = array();
		foreach ((array)$this->_forum_cats_array as $_cat_id => $_cat_info) {
			$_cat_name = $_cat_info["name"];
			// Add category (with prefix: "c_")
			$forums["c_".$_cat_id] = "######## ". $_cat_name;
			// Get forums inside this category
			foreach ((array)$this->_prepare_forums_for_select($skip_id, $_cat_id) as $k => $v) {
				$forums[$k] = $v;
			}
		}
		return $forums;
	}

	/**
	* Recursive sub-method for "_prepare_parents_for_select"
	*/
	function _prepare_forums_for_select ($skip_id = 0, $cat_id = 0, $parent_id = 0, $level = 0) {
		$forums = array();
		$func_name = __FUNCTION__;
		// Prepare categories for select box
		foreach ((array)module("forum")->_forums_array as $_info) {
			if ($_info["id"] == $skip_id) {
				continue;
			}
			if ($_info["parent"] != $parent_id) {
				continue;
			}
			if ($cat_id && $cat_id != $_info['category']) {
				continue;
			}
			// Add current forum
			$forums[$_info['id']] = str_repeat("&nbsp;", $level * 4). "&#0124;---". $_info["name"];
			// Try to find sub-forums
			foreach ((array)$this->$func_name($skip_id, $cat_id, $_info["id"], $level + 1) as $_sub_id => $_sub_name) {
				$forums[$_sub_id] = $_sub_name;
			}
		}
		return $forums;
	}

	/**
	* Admin: edit category
	*/
	function edit_category () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_edit_category() : "";
	}

	/**
	* Admin: add category
	*/
	function add_category () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_add_category() : "";
	}

	/**
	* Delete category
	*/
	function delete_category () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_delete_category() : "";
	}

	/**
	* Admin: edit forum
	*/
	function edit_forum () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_edit_forum() : "";
	}

	/**
	* Admin: add forum
	*/
	function add_forum () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_add_forum() : "";
	}

	/**
	* Admin: delete forum
	*/
	function delete_forum () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_delete_forum() : "";
	}

	/**
	* Admin: edit topic
	*/
	function edit_topic () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_edit_topic() : "";
	}

	/**
	* Admin: delete topic
	*/
	function delete_topic () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_delete_topic() : "";
	}

	/**
	* Admin: edit post
	*/
	function edit_post () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_edit_post() : "";
	}

	/**
	* Admin: delete post
	*/
	function delete_post () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_delete_post() : "";
	}

	/**
	* Admin: manage users
	*/
	function manage_users () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_manage_users() : "";
	}

	/**
	* Admin: edit user
	*/
	function edit_user () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_edit_user() : "";
	}

	/**
	* Admin: manage group
	*/
	function manage_groups () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_manage_groups() : "";
	}

	/**
	* Admin: edit group
	*/
	function edit_group () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_edit_group() : "";
	}

	/**
	* Admin: add group
	*/
	function add_group () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_add_group() : "";
	}

	/**
	* Admin: delete group
	*/
	function delete_group () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_delete_group() : "";
	}

	/**
	* Admin: clone group
	*/
	function clone_group () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_clone_group() : "";
	}

	/**
	* Admin: manage moderators
	*/
	function manage_moderators () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_manage_moderators() : "";
	}

	/**
	* Admin: edit moderator
	*/
	function edit_moderator () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_edit_moderator() : "";
	}

	/**
	* Admin: add moderator
	*/
	function add_moderator () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_add_moderator() : "";
	}

	/**
	* Admin: delete moderator
	*/
	function delete_moderator () {
		$OBJ = $this->_load_sub_module("forum_manage_users");
		return is_object($OBJ) ? $OBJ->_delete_moderator() : "";
	}

	/**
	* Change activity status
	*/
	function change_category_activity () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_change_category_activity() : "";
	}

	/**
	* Change activity status
	*/
	function change_forum_activity () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_change_forum_activity() : "";
	}

	/**
	* Change activity status
	*/
	function change_topic_activity () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_change_topic_activity() : "";
	}

	/**
	* Change activity status
	*/
	function change_post_activity () {
		$OBJ = $this->_load_sub_module("forum_manage_main");
		return is_object($OBJ) ? $OBJ->_change_post_activity() : "";
	}

	/**
	* Admin: synchronize board
	*/
	function sync_board () {
		$SYNC_OBJ = main()->init_class("forum_sync", USER_MODULES_DIR."forum/");
		if (is_object($SYNC_OBJ)) {
			$SYNC_OBJ->_sync_board(true);
		}
	}

	/**
	* Try to load forum sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ =& main()->init_class($module_name, FORUM_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("FORUM: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}

	/**
	* Check permissions (return false if denied, true if allowed)
	*/
	function _check_acl($action = "") {
		$GID = $_SESSION["admin_group"];
		// Admin allowed to do anything
		if ($GID == 1) {
			return true;
		}
		// Forum poster allowed actions
		if ($GID == 6 && !in_array($action, array(
			"edit_post",
			"new_topic",
			"reply",
			"show",
			"view_forum",
			"view_topic",
		))) {
			return false;
		}
		return true;
	}

	/**
	* Future posts
	*/
	function show_future_posts() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_show_future_posts() : "";
	}

	/**
	* Future posts
	*/
	function add_future_topic() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_add_topic() : "";
	}

	/**
	* Future posts
	*/
	function add_future_post() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_add_post() : "";
	}

	/**
	* Future posts
	*/
	function edit_future_post() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_edit_future_post() : "";
	}

	/**
	* Future posts
	*/
	function delete_future_post() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_delete_future_post() : "";
	}

	/**
	* Future posts
	*/
	function show_forum_posters() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_show_posters() : "";
	}

	/**
	* Future posts
	*/
	function edit_forum_poster() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_edit_poster() : "";
	}

	/**
	* Future posts
	*/
	function show_poster_stats() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_show_poster_stats() : "";
	}

	/**
	* Future posts
	*/
	function future_save_filter() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_save_filter() : "";
	}

	/**
	* Future posts
	*/
	function future_clear_filter() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_clear_filter() : "";
	}

	/**
	* Do cron job for future posts
	*/
	function _future_posts_cron_job() {
		$OBJ = $this->_load_sub_module("forum_manage_future");
		return is_object($OBJ) ? $OBJ->_do_cron_job() : "";
	}

	/**
	* Placeholder for compatibility with user section
	*/
	function _for_user_profile() {
		return "";
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Manage forum groups",
				"url"	=> "./?object=".$_GET["object"]."&action=manage_groups",
			),
			array(
				"name"	=> "Manage moderators",
				"url"	=> "./?object=".$_GET["object"]."&action=manage_moderators",
			),
			array(
				"name"	=> "Resynchronize Board",
				"url"	=> "./?object=".$_GET["object"]."&action=sync_board",
			),
			array(
				"name"	=> "Forum Posters",
				"url"	=> "./?object=".$_GET["object"]."&action=show_forum_posters",
			),
			array(
				"name"	=> "Future Posts",
				"url"	=> "./?object=".$_GET["object"]."&action=show_future_posts",
			),
			array(
				"name"	=> "Add category",
				"url"	=> "./?object=".$_GET["object"]."&action=add_category",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Forum");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
			"show_forum_posters"=> "Manage forum posters",
			"show_future_posts"	=> "Manage_future_posts",
		);			  		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
