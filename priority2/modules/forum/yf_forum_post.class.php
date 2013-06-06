<?php

/**
* Make posts module
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_post {

	/** @var bool */
	var $DEF_USE_SIG = 1;
	/** @var bool */
	var $DEF_USE_EMO = 1;

	/**
	* Constructor
	*/
	function _init () {
		// Init bb codes module
		$this->BB_OBJ = main()->init_class("bb_codes", "classes/");
		// Get smilies
		$this->_smiles_array = main()->get_data("smilies");
	}

	/**
	* New topic creation form
	*/
	function _new_topic () {
		$_GET["id"] = intval($_GET["id"]);
		// Get forum id
		if (!empty(module('forum')->_forums_array[$_GET["id"]])) {
			$forum_info = &module('forum')->_forums_array[$_GET["id"]];
		}
		return $forum_info["id"] 
			? module('forum')->_show_main_tpl($this->_show_post_form(null, null, true)) 
			: module('forum')->_show_error("Wrong forum ID!");
	}

	/**
	* New topic creation form
	*/
	function _new_poll () {
		$_GET["id"] = intval($_GET["id"]);
		// Get forum id
		if (!empty(module('forum')->_forums_array[$_GET["id"]])) {
			$forum_info = &module('forum')->_forums_array[$_GET["id"]];
		}
		return $forum_info["id"] 
			? module('forum')->_show_main_tpl($this->_show_post_form(null, null, true)) 
			: module('forum')->_show_error("Wrong forum ID!");
	}

	/**
	* Reply to the existing topic (post message)
	*/
	function _reply () {
		$_GET["id"] = intval($_GET["id"]);
		// Get replying post info
		$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `status`='a' AND `id`=".intval($_GET["id"]));
		if (!empty($post_info["id"])) {
			$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".$post_info["topic"]." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
		}
		return $post_info["id"] 
			? module('forum')->_show_main_tpl($this->_show_post_form($post_info, $topic_info)) 
			: module('forum')->_show_error("Wrong post ID!");
	}

	/**
	* Add new post item
	*/
	function _new_post () {
		$_GET["id"] = intval($_GET["id"]);
		// Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".$_GET["id"]." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
		return $topic_info["id"] 
			? module('forum')->_show_main_tpl($this->_show_post_form($post_info, $topic_info)) 
			: module('forum')->_show_error("Wrong topic ID!");
	}

	/**
	* Edit existing post
	*/
	function _edit_post () {
		$_GET["id"] = intval($_GET["id"]);
		// Get post info to edit
		if (FORUM_USER_ID) {
			$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($_GET["id"]).(!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR ? " AND `status`='a' " : ""));
			// Check author
			if (!FORUM_IS_ADMIN && $post_info["user_id"] != FORUM_USER_ID) {
				if (FORUM_IS_MODERATOR) {
					if (!module('forum')->_moderate_forum_allowed($post_info["forum"])) {
						unset($post_info);
					}
				} else {
					unset($post_info);
				}
			}
			// Check if allowed
			if (!empty($post_info["id"])) {
				$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".$post_info["topic"]."  ".(!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR ? " AND `approved`=1 " : "")." LIMIT 1");
			}
		}
		return $post_info["id"]
			 ? module('forum')->_show_main_tpl($this->_show_post_form($post_info, $topic_info, false, true))
			 : module('forum')->_show_error("Wrong post ID!");
	}

	/**
	* Delete post
	*/
	function _delete_post ($SILENT_MODE = false, $_FORCE_ID = 0) {
		$POST_ID = intval($_FORCE_ID ? $_FORCE_ID : $_GET["id"]);
		// Get post info to edit
		if (FORUM_USER_ID) {
			$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE ".(!FORUM_IS_ADMIN ? " `status`='a' AND " : "")." `id`=".intval($POST_ID));
			// Check author
			if (!FORUM_IS_ADMIN && $post_info["user_id"] != FORUM_USER_ID) {
				if (FORUM_IS_MODERATOR) {
					if (!module('forum')->_moderate_forum_allowed($post_info["forum"])) {
						unset($post_info);
					}
				} else {
					unset($post_info);
				}
			}
			// Check if allowed
			if (!empty($post_info["id"]) && empty($post_info["new_topic"])) {
				$forum_info = &module('forum')->_forums_array[$post_info["forum"]];
				$topic_info	= db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($post_info["topic"])." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
				if (empty($topic_info["id"])) {
					common()->_raise_error(t("No such topic!"));
				}
				// Check if forum or topic closed
				if (!FORUM_IS_ADMIN) {
					$forum_is_closed	= $forum_info["options"] == "2" ? 1 : 0;
					$topic_is_closed	= intval($topic_info["status"] != "a");
					if ($forum_is_closed) {
						return module('forum')->_show_error("Forum is closed!");
					}
					if ($topic_is_closed) {
						return module('forum')->_show_error("Topic is closed!");
					}
				}
			} else {
				common()->_raise_error(t("No such post!"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Delete post
				db()->query("DELETE FROM `".db('forum_posts')."` WHERE `id`=".intval($post_info["id"])." LIMIT 1");
				// Synchronize forum and topic
				$SYNC_OBJ = main()->init_class("forum_sync", FORUM_MODULES_DIR);
				if (is_object($SYNC_OBJ)) {
					$SYNC_OBJ->_update_topic_record($post_info["topic"]);
					$SYNC_OBJ->_update_forum_record($post_info["forum"]);
					$SYNC_OBJ->_fix_subforums();
				}
				// Remove activity points
				common()->_remove_activity_points($post_info["user_id"], "forum_post", $post_info["id"]);
			} else {
				return module('forum')->_show_error(_e());
			}
		}
		return !$SILENT_MODE ? js_redirect($_SERVER["HTTP_REFERER"], false) : "";
	}

	/**
	* Create reply form contents (with quote or not)
	*/
	function _show_post_form ($post_info = array(), $topic_info = array(), $new_topic = false, $edit_post = false) {
		// Disabling posting ability for guests
		if (!FORUM_USER_ID && !module('forum')->SETTINGS["ALLOW_GUESTS_POSTS"]) {
			common()->_raise_error(t("Guests are not allowed to make posts!"));
			return module('forum')->_show_error(_e(), 0);
		}
		$forum_id = $new_topic ? $_GET["id"] : $topic_info["forum"];
		if (isset($topic_info)) {
			module('forum')->_topic_info = $topic_info;
		}
		// Check if it is first post
		$is_first_post = $topic_info["first_post_id"] && $topic_info["first_post_id"] == $post_info["id"];
		// Reference to the forums array
		$forum_info = &module('forum')->_forums_array[$forum_id];
		// Reference to the cats array
		$cat_info = &module('forum')->_forum_cats_array[$forum_info["category"]];
		// Get act name
		if ($edit_post)						$act_name = "edit_post";
		elseif ($new_topic)					$act_name = "new_topic";
		elseif (!empty($post_info["id"]))	$act_name = "reply";
		else								$act_name = "new_post";
		// Check if forum or topic closed
		if (!FORUM_IS_ADMIN) {
			$forum_is_closed	= $forum_info["options"] == "2" ? 1 : 0;
			if ($forum_is_closed) {
				return module('forum')->_show_error("Forum is closed!");
			}
			if (isset($topic_info)) {
				$topic_is_closed	= intval($topic_info["status"] != "a");
				if ($topic_is_closed) {
					return module('forum')->_show_error("Topic is closed!");
				}
			}
		}
		// Skip non-active forums and categories
		if ($forum_info["status"] != "a" || $cat_info["status"] != "a") {
			return module('forum')->_show_error("Forum is inactive!");
		}
		// Check user group access rights to the current forum
		$only_for_groups = $forum_info["user_groups"] ? explode(",", $forum_info["user_groups"]) : "";
		if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
			return module('forum')->_show_error("Private Forum!");
		}
		// Get last posts for the given topic
		if (!$new_topic && $topic_info["id"]) {
			$Q = db()->query("SELECT * FROM `".db('forum_posts')."` WHERE `topic`=".intval($topic_info["id"])." AND `status`='a' ORDER BY `created` DESC LIMIT 10");
			while ($A = db()->fetch_assoc($Q)) {
				$last_posts[$A["id"]] = array(
					"user_name"		=> !empty($A["user_name"]) ? _prepare_html($A["user_name"]) : t("Anonymous"),
					"posted_date"	=> module('forum')->_show_date($A["created"], "post_date"),
					"text"			=> $this->BB_OBJ->_process_text(_strlen($A["text"]) > module('forum')->SETTINGS["LAST_POSTS_MAX_LENGTH"] ? _substr($A["text"], 0, module('forum')->SETTINGS["LAST_POSTS_MAX_LENGTH"]). "..." : $A["text"]),
				);
			}
		}
		// Process post icons
		for ($i = 1; $i <= 14; $i++) {
			$replace3 = array(
				"icon_id"	=> $i, 
				"need_div"	=> $i == 7,
				"selected"	=> $is_first_post && $topic_info["icon_id"] == $i ? "checked" : "",
				"img_src"	=> WEB_PATH. module('forum')->SETTINGS["POST_ICONS_DIR"]. "icon".$i.".gif",
			);
			$post_icons .= tpl()->parse(FORUM_CLASS_NAME."/new_post_icon", $replace3);
		}
		$as_image = 0;
		if (module('forum')->SETTINGS["SMILIES_IMAGES"]) {
			$as_image = FORUM_USER_ID && (!module('forum')->USER_SETTINGS["VIEW_IMAGES"] && !module('forum')->SETTINGS["USE_GLOBAL_USERS"]) ? 0 : 1;
		}
		// Process smilies
		foreach ((array)$this->_smiles_array as $smile_info) {
			$replace4 = array(
				"img_src"	=> WEB_PATH. module('forum')->SETTINGS["SMILIES_DIR"]. $smile_info["url"],
				"img_alt"	=> _prepare_html($smile_info["emoticon"]),
				"css_class"	=> module('forum')->_CSS["smile"],
				"text"		=> $smile_info["code"],
				"as_image"	=> intval($as_image),
			);
			$replace2 = array(
				"code"		=> $smile_info["code"],
				"smile_item"=> tpl()->parse(FORUM_CLASS_NAME."/smile_item", $replace4),
				"need_div"	=> !(++$i % 3),
			);
			$smile_icons .= tpl()->parse(FORUM_CLASS_NAME."/new_post_smile_icon", $replace2);
		}
		// Process text
		$text = "";
		if (!isset($GLOBALS['_forum_reply_no_quote'])) {
			$text = $post_info["text"];
		}
		// Reply text (need to add quotes)
		if ($post_info["id"] && !$edit_post) {
			$text = !empty($text) ? "[quote".($post_info["user_id"] ? "=\""._prepare_html($post_info["user_name"], 0)."\"" : "")."]". $text. "[/quote]" : "";
		}
		if ($post_info["id"] && module('forum')->SETTINGS["ALLOW_ATTACHES"]) {
			$attach_path	= module('forum')->_get_attach_path($post_info["id"]);
		}
		// Allow create polls only for members
		$allow_polls = FORUM_USER_ID && module('forum')->SETTINGS["ALLOW_POLLS"] && module('forum')->USER_RIGHTS["make_polls"];
		$bb_codes_params = array(
			"unique_id" 	=> "text",
			"stpl_name"		=> FORUM_CLASS_NAME."/new_post_buttons",
		);
		// Process template
		$replace = array(
			"is_admin"			=> intval(FORUM_IS_ADMIN),
			"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=save_post&id=".$_GET["id"]._add_get(array("id")),
			"bbcode_js_src"		=> WEB_PATH. "js/bbcode.js",
			"cat_name"			=> _prepare_html($cat_info["name"]),
			"forum_name"		=> _prepare_html($forum_info["name"]),
			"topic_name"		=> !$new_topic ? _prepare_html($topic_info["name"]) : "",
			"cat_link"			=> "./?object=".FORUM_CLASS_NAME._add_get(array("id")),
			"forum_link"		=> module('forum')->_link_to_forum($forum_info["id"]),
			"topic_link"		=> !$new_topic ? "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$_GET["id"]._add_get(array("id")) : "",
			"subject"			=> !$new_topic ? "Re:"._prepare_html($topic_info["name"]) : "",
			"text"				=> !$new_topic ? $text : "",
			"last_posts"		=> !$new_topic ? $last_posts : "",
			"topic_id"			=> !$new_topic ? intval($topic_info["id"]) : "",
			"forum_id"			=> intval($forum_info["id"]),
			"post_id"			=> $edit_post ? intval($post_info["id"]) : "",
			"new_topic"			=> intval($new_topic),
			"edit_post"			=> intval($edit_post),
			"user_id"			=> intval(FORUM_USER_ID),
			"user_name"			=> _prepare_html($post_info["user_name"]),
			"post_icons"		=> $post_icons,
			"smile_icons"		=> $smile_icons,
			"act_name"			=> $act_name,
			"parent_id"			=> intval(module('forum')->SETTINGS["TOPIC_VIEW_TYPE"] == 1 && in_array($act_name, array("reply")) ? $_GET["id"] : 0),
			"use_emo"			=> $post_info["use_emo"] ? "checked" : "",
			"use_sig"			=> $post_info["use_sig"] ? "checked" : "",
			"show_edit_by"		=> $post_info["show_edit_by"] ? "checked" : "",
			"add_reply"			=> in_array($act_name, array("reply", "new_post")),
			"show_post_icons"	=> intval(module('forum')->SETTINGS["ENABLE_POST_ICONS"] && !empty($post_icons) && ($act_name == "new_topic" || ($act_name == "edit_post" && $is_first_post))),
			"no_post_icon"		=> $is_first_post && !empty($topic_info["icon_id"]) ? "" : "checked",
			"allow_bb_code"		=> intval((bool) module('forum')->SETTINGS["BB_CODE"]),
			"allow_help"		=> intval((bool) module('forum')->SETTINGS["BB_CODE"] && module('forum')->SETTINGS["SHOW_HELP"]),
			"allow_smilies"		=> intval(module('forum')->SETTINGS["BB_CODE"] && module('forum')->SETTINGS["ENABLE_SMILIES"]),
			"allow_attaches"	=> intval(module('forum')->SETTINGS["ALLOW_ATTACHES"]),
			"allow_polls"		=> intval($allow_polls && in_array($_GET["action"], array("new_poll", "new_topic"))),
			"hide_poll_data"	=> $allow_polls && $_GET["action"] == "new_poll" ? 0 : 1,
			"attach_max_size"	=> intval(module('forum')->SETTINGS["ATTACH_MAX_SIZE"]),
			"attach_max_width"	=> intval(module('forum')->SETTINGS["ATTACH_LIMIT_X"]),
			"attach_max_height"	=> intval(module('forum')->SETTINGS["ATTACH_LIMIT_Y"]),
			"attach_image_src"	=> !empty($attach_path) && file_exists(INCLUDE_PATH. $attach_path) ? WEB_PATH. $attach_path : "",
			"del_attach_link"	=> !empty($attach_path) && FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=delete_attach&id=".$post_info["id"]._add_get(array("id")) : "",
			"bb_codes_block"	=> module('forum')->SETTINGS["BB_CODE"] ? $this->BB_OBJ->_display_buttons($bb_codes_params) : "",
			"bb_pop_link"		=> process_url("./?object=".FORUM_CLASS_NAME."&action=bb_code_help"._add_get(array("page"))),
			"wysiwyg_editor"	=> module('forum')->_show_wysiwyg_editor($text),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/new_post", $replace);
	}

	/**
	* Add new post
	*/
	function _save_post () {
		// Disabling posting ability for guests
		if (!FORUM_USER_ID && !module('forum')->SETTINGS["ALLOW_GUESTS_POSTS"]) {
			common()->_raise_error(t("Guests are not allowed to make posts!"));
		}
		// Check if user in ban list
		if (module('forum')->SETTINGS["USE_BAN_IP_FILTER"]) {
			if (db()->query_num_rows("SELECT `ip` FROM `".db('bannedip')."` WHERE `ip`='"._es(common()->get_ip())."'")) {
				return module('forum')->_show_error(
					"Your IP address was banned!"
					."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
				);
			}
		}
		// Fix for the FCK editor post
		if (isset($_POST["text2"])) {
			$_POST["text"] = $_POST["text2"];
			unset($_POST["text2"]);
		}
		// Check if user allowed to post in forum
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			// Check for errors
			if (!common()->_error_exists()) {
				$info_for_check = array(
					"text"			=> $_POST["title"],
					"forum_text"	=> $_POST["text"],
					"user_id"		=> FORUM_USER_ID,
				);
				$USER_BANNED = _check_user_ban($info_for_check);
				if ($USER_BANNED) {
					$user_info = user(FORUM_USER_ID);
				}
				// Stop here if user is banned
				if ($user_info["ban_forum"]) {
					return _e(
						"Sorry, you are not allowed to make forum posts!\r\nPerhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!"
						."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
					);
				}
			}
		}
		$_GET["id"] = intval($_GET["id"]);
		// Get forum id
		$forum_id = intval($_POST["forum_id"]);
		// Reference to the forums array
		$forum_info = &module('forum')->_forums_array[$forum_id];
		// Reference to the cats array
		$cat_info = &module('forum')->_forum_cats_array[$forum_info["category"]];
		// Check forum existance
		if (empty($forum_info["id"])) {
			common()->_raise_error(t("No such forum!"));
		}
		// Get act name
		$ACT = $_POST["act"];
		if (empty($ACT) || !in_array($ACT, array("reply","new_post","new_topic","edit_post"))) {
			common()->_raise_error(t("Dont know what to do!"));
		}
		// Reference to the cats array
		if (!common()->_error_exists()) {
			$cat_info = &module('forum')->_forum_cats_array[$forum_info["category"]];
			// Get topic info
			if ($ACT != "new_topic") {
				$topic_id	= intval($_POST["topic_id"]);
				$topic_info	= db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($topic_id)." ".(!FORUM_IS_ADMIN ? " AND `approved`=1 " : "")." LIMIT 1");
				if (empty($topic_info["id"])) common()->_raise_error(t("No such topic!"));
			}
		}
		// Check if forum or topic closed
		if (!FORUM_IS_ADMIN) {
			$forum_is_closed	= $forum_info["options"] == "2" ? 1 : 0;
			if ($forum_is_closed) {
				return module('forum')->_show_error("Forum is closed!");
			}
			if (isset($topic_info)) {
				$topic_is_closed	= intval($topic_info["status"] != "a");
				if ($topic_is_closed) {
					return module('forum')->_show_error("Topic is closed!");
				}
			}
		}
		// Skip non-active forums and categories
		if ($forum_info["status"] != "a" || $cat_info["status"] != "a") {
			return module('forum')->_show_error("Forum is inactive!");
		}
		// Check user group access rights to the current forum
		$only_for_groups = $forum_info["user_groups"] ? explode(",", $forum_info["user_groups"]) : "";
		if (!empty($only_for_groups) && !in_array(FORUM_USER_GROUP_ID, $only_for_groups) && !FORUM_IS_ADMIN) {
			return module('forum')->_show_error("Private Forum!");
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			// Get edited post info
			if ($ACT == "edit_post") {
				$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($_POST["post_id"])." LIMIT 1");
				// Check if moderator is allowed here
				if (FORUM_IS_MODERATOR) {
					if (!module('forum')->_moderate_forum_allowed($post_info["forum"])) {
						unset($post_info);
					}
				} elseif (!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR) {
					if ($post_info["user_id"] != FORUM_USER_ID) {
						unset($post_info);
					}
				}
				if (empty($post_info["id"])) {
					common()->_raise_error(t("No such post!"));
				}
			}
		}
		// Anti-flood filter
		if (!common()->_error_exists()) {
			$POSSIBLE_FLOOD	= db()->query_num_rows("SELECT `id` FROM `".db('forum_posts')."` WHERE `created` > ".(time() - module('forum')->SETTINGS["ANTISPAM_TIME"])." AND `poster_ip`='".common()->get_ip()."' LIMIT 1");
			if ($POSSIBLE_FLOOD) {
				common()->_raise_error(t("Possible flood detected! Try again later!"));
			}
		}
		// Check required fields
		if (!common()->_error_exists()) {
			if ($ACT == "new_topic" && empty($_POST["title"])) {
				common()->_raise_error(t("Topic title required!"));
			}
			// Prepare text
			$_POST["text"] = _substr(trim($_POST["text"]), 0, module('forum')->SETTINGS["MSG_TEXT_TRIM"]);
			// Cut loooooong lines 
//			$_POST["text"] = wordwrap($_POST["text"], 75, "\r\n", 1);
			// Try to cut "bad" words
			if (module('forum')->SETTINGS["USE_GLOBAL_USERS"] && module('forum')->SETTINGS["POST_CUT_BAD_WORDS"]) {
				$_POST["text"] = $this->_text_filter($_POST["text"]);
			}
			if (empty($_POST["text"])) {
				common()->_raise_error(t("Text required!"));
			}
		}
		// Check if post is just quote without additions
		if (!common()->_error_exists()) {
			$QUOTE_SPAM = _substr($_POST["text"], 0, 6) == "[quote" && _substr($_POST["text"], -8) == "[/quote]";
			if ($QUOTE_SPAM) {
				common()->_raise_error(t("Possible quote spam! Please enter your text not just quote!"));
			}
		}
		// Do close BB Codes (if needed)
		if (module('forum')->SETTINGS["BB_CODE"]) {
			$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
			if (is_object($BB_CODES_OBJ)) {
				$_POST["title"]	= $BB_CODES_OBJ->_force_close_bb_codes($_POST["title"]);
				$_POST["desc"]	= $BB_CODES_OBJ->_force_close_bb_codes($_POST["desc"]);
				$_POST["text"]	= $BB_CODES_OBJ->_force_close_bb_codes($_POST["text"]);
			}
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			// Create new topic record
			if ($ACT == "new_topic") {
				db()->INSERT("forum_topics", array(
					"forum"		=> intval($forum_info["id"]),
					"name"		=> _es($_POST["title"]),
					"desc"		=> _es($_POST["desc"]),
					"user_id"	=> intval(FORUM_USER_ID),
					"user_name"	=> _es(strlen(FORUM_USER_NAME) ? FORUM_USER_NAME : $_POST["user_name"]),
					"icon_id"	=> intval($_POST["iconid"]),
					"created"	=> time(),
					"approved"	=> module('forum')->SETTINGS["POSTS_NEED_APPROVE"] ? 0 : 1,
				));
				$new_topic_id = db()->insert_id();
			}
			// Get new topic info
			if (!empty($new_topic_id)) {
				$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($new_topic_id)." LIMIT 1");
				// Verify new topic
				if (empty($topic_info)) {
					common()->_raise_error(t("Error while creating new topic! Please contact site admin!"));
					trigger_error("Error while creating new topic!", E_USER_WARNING);
				}
			}
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			// Create new post record
			if (in_array($ACT, array("reply", "new_post", "new_topic"))) {
				db()->INSERT("forum_posts", array(
					"parent"	=> intval($_POST["parent_id"]),
					"forum"		=> intval($topic_info["forum"]),
					"topic"		=> intval($topic_info["id"]),
					"subject"	=> _es(($ACT != "new_topic" ? "Re:" : ""). $topic_info["name"]),
					"text"		=> _es($_POST["text"]),
					"user_id"	=> intval(FORUM_USER_ID),
					"user_name"	=> _es(strlen(FORUM_USER_NAME) ? FORUM_USER_NAME : $_POST["user_name"]),
					"created"	=> time(),
					"poster_ip"	=> common()->get_ip(),
					"new_topic"	=> $ACT == "new_topic" ? 1 : 0,
					"use_sig"	=> intval((bool)(isset($_POST["enable_sig"]) ? $_POST["enable_sig"] : $this->DEF_USE_SIG)),
					"use_emo"	=> intval((bool)(isset($_POST["enable_emo"]) ? $_POST["enable_emo"] : $this->DEF_USE_EMO)),
					"icon_id"	=> intval($_POST["iconid"]),
					"status"	=> module('forum')->SETTINGS["POSTS_NEED_APPROVE"] ? "c" : "a",
				));
				$new_post_id = db()->INSERT_ID();
			}
			// Get new post info
			if (!empty($new_post_id)) {
				$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($new_post_id)." LIMIT 1");
				// Verify new post
				if (empty($post_info)) {
					common()->_raise_error(t("Error while creating new post! Please contact site admin!"));
					trigger_error("Error while creating new post!", E_USER_WARNING);
				}
			}
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			// Update post
			if ($ACT == "edit_post") {
				db()->UPDATE("forum_posts", array(
					"text"			=> _es($_POST["text"]),
					"edit_name"		=> _es(FORUM_USER_NAME),
					"edit_time"		=> time(),
					"show_edit_by"	=> intval((bool) $_POST["enable_edit_by"]),
					"use_sig"		=> intval((bool)(isset($_POST["enable_sig"]) ? $_POST["enable_sig"] : $this->DEF_USE_SIG)),
					"use_emo"		=> intval((bool)(isset($_POST["enable_emo"]) ? $_POST["enable_emo"] : $this->DEF_USE_EMO)),
				), "`id`=".intval($post_info["id"]));
			}
			// Set first post for the topic
			if ($ACT == "new_topic") {
				db()->UPDATE("forum_topics", array(
					"first_post_id"		=> intval($post_info["id"]),
					"last_post_id"		=> intval($post_info["id"]),
					"last_poster_id"	=> intval($post_info["user_id"]),
					"last_poster_name"	=> _es($post_info["user_name"]),
					"last_post_date"	=> time(),
				), "`id`=".intval($topic_info["id"]));
			}
			// Update topic icon
			if ($ACT == "edit_post" && $topic_info["first_post_id"] == $post_info["id"]) {
				db()->UPDATE("forum_topics", array(
					"icon_id"	=> intval($_POST["iconid"]),
				), "`id`=".intval($topic_info["id"]));
			}
			// Refresh caches
			if (main()->USE_SYSTEM_CACHE) {
				cache()->refresh("forum_home_page_posts");
			}
		}
		// Get post id
		$POST_ID = $post_info["id"] ? $post_info["id"] : $new_post_id;
		// Load attached_image
		if (!common()->_error_exists() && module('forum')->SETTINGS["ALLOW_ATTACHES"] && !empty($POST_ID)) {
			$attach_image = !empty($_FILES["attach"]["size"]) ? $this->_load_attach($POST_ID) : "";
		}
		// Create new poll
		if (!common()->_error_exists() && module('forum')->SETTINGS["ALLOW_POLLS"] && module('forum')->USER_RIGHTS["make_polls"] && !empty($new_topic_id)) {
			$this->_create_poll($new_topic_id);
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			if (in_array($ACT, array("new_topic", "new_post", "reply"))) {
				$text_length = strlen(preg_replace("/\[quote[^\]]*\].*?\[\/quote\]/ims", "", $_POST["text"]));
				// Save activity log
				common()->_add_activity_points(FORUM_USER_ID, "forum_post", $text_length, $new_post_id);
			}
		}
		// Check for errors and continue
		if (!common()->_error_exists()) {
			// Update forum, topic, topic_watch, user tables etc
			if (in_array($ACT, array("reply","new_post","new_topic","new_poll")) && !module('forum')->SETTINGS["POSTS_NEED_APPROVE"] && !empty($new_post_id)) {
				// Increment number of user posts
				if (FORUM_USER_ID) {
					db()->query("UPDATE `".db('forum_users')."` SET `user_posts`=`user_posts`+1 WHERE `id`=".intval(FORUM_USER_ID));
				}
				// Synchronize forum and topic
				$SYNC_OBJ = main()->init_class("forum_sync", FORUM_MODULES_DIR);
				if (is_object($SYNC_OBJ)) {
					// $SYNC_OBJ->_sync_forum($forum_info["id"], 1);
					$SYNC_OBJ->_update_topic_record($topic_info["id"]);
					$SYNC_OBJ->_update_forum_record($forum_info["id"]);
					$SYNC_OBJ->_fix_subforums();
				}
				// Set topic "read"
				module('forum')->_set_topic_read($topic_info);
				// Send notification emails
				if (module('forum')->SETTINGS["SEND_NOTIFY_EMAILS"]) {
// TODO
				}
			}
		} else {
			return module('forum')->_show_error(_e());
		}
		// redirect user on the last page after posting, not just on the first page
		if (module('forum')->SETTINGS["TOPIC_VIEW_TYPE"] == 1) {
			$topic_last_page = intval($_POST["parent_id"]);
		} else {
			$topic_last_page = $this->_get_topic_last_page($topic_info["id"]);
			// Hide first page from redirect
			if ($topic_last_page == 1) {
				$topic_last_page = 0;
			}
		}
		// Redirect user back
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$topic_info["id"]. (!empty($topic_last_page) ? "&page=".intval($topic_last_page) : ""). _add_get(array("page")). "#last_post");
	}

	/**
	* Filter text for specified symbols
	*/
	function _text_filter ($str) {
		$str = _prepare_html($str);
		if ((defined('SITE_BAD_WORD_FILTER') && SITE_BAD_WORD_FILTER == 1) || module('forum')->SETTINGS["POST_CUT_BAD_WORDS"]) {
			if (!isset($GLOBALS["BAD_WORDS_ARRAY"])) {
				$GLOBALS["BAD_WORDS_ARRAY"] = array();
				$Q = db()->query("SELECT `word` FROM `".db('badwords')."`");
				while ($A = db()->fetch_assoc($Q)) $GLOBALS["BAD_WORDS_ARRAY"] = $A["word"];
			}
			$str = str_replace($GLOBALS["BAD_WORDS_ARRAY"], "", $str);
	    } 
		return $str;
	} 

	/**
	* Load attach file
	*/
	function _load_attach ($POST_ID = 0, $new_file_name = "") {
		if (!module('forum')->SETTINGS["ALLOW_ATTACHES"]) {
			return false;
		}
		// Create new file name
		if (empty($new_file_name)) {
			$new_file_name = $POST_ID.".jpg";
		}
		// Params
		$LIMIT_X		= module('forum')->SETTINGS["ATTACH_LIMIT_X"];
		$LIMIT_Y		= module('forum')->SETTINGS["ATTACH_LIMIT_Y"];
		$MAX_IMAGE_SIZE = module('forum')->SETTINGS["ATTACH_MAX_SIZE"];
		// Get attached files dir
		$photo_dir = dirname(INCLUDE_PATH. module('forum')->_get_attach_path($POST_ID))."/";
		_mkdir_m($photo_dir, $this->BLOG_OBJ->DEF_DIR_MODE, 1);
		$photo_path = $photo_dir. $new_file_name;
		// Do upload image
		$upload_result = common()->upload_image($photo_path, "attach", $MAX_IMAGE_SIZE);
		if (!$upload_result) {
			return false;
		}
		// Make thumbnail
		$resize_result = common()->make_thumb($photo_path, $photo_path, $LIMIT_X, $LIMIT_Y);
		// Check if file uploaded successfully
		if (!$resize_result || !file_exists($photo_path) || !filesize($photo_path)) {
			if (file_exists($photo_path)) {
				unlink($photo_path);
			}
			return trigger_error("Unable to resize image", E_USER_WARNING);
		}
		return $new_file_name;
	}

	/**
	* Delete attached file
	*/
	function _delete_attach () {
		// Disabling posting ability for guests
		if (!FORUM_USER_ID) {
			return module('forum')->_show_error("You are not allowed to perform this action");
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get given user info
		$post_info = db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($post_info["id"])) {
			return _e(t("No such post!"));
		}
		// Check rights
		if ($post_info["user_id"] != FORUM_USER_ID) {
			return module('forum')->_show_error("Not your post");
		}
		// Delete image
		$attach_path = module('forum')->_get_attach_path($_GET["id"]);
		if (file_exists(INCLUDE_PATH. $attach_image_path)) {
			unlink(INCLUDE_PATH. $attach_path);
		}
/*
		// Update post record
		db()->query("UPDATE `".db('blog_posts')."` SET `attach_image`='' WHERE `id`=".intval($_GET["id"])." LIMIT 1");
*/
		// Last update
		update_user(FORUM_USER_ID, array("last_update"=>time()));
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 1);
	}

	/**
	* Create new poll
	*/
	function _create_poll ($new_topic_id = 0) {
		if (!module('forum')->SETTINGS["ALLOW_POLLS"] || !module('forum')->USER_RIGHTS["make_polls"] || empty($new_topic_id)) {
			return false;
		}
		$POLL_OBJ = main()->init_class("poll");
		if (is_object($POLL_OBJ)) {
			$POLL_OBJ->_create(array(
				"silent"		=> 1,
				"object_name"	=> "forum",
				"object_id"		=> $new_topic_id,
			));
		}
	}

	/**
	* Get number of pages for given topic
	*/
	function _get_topic_last_page ($topic_id = 0) {
		$posts_per_page = !empty(module('forum')->USER_SETTINGS["POSTS_PER_PAGE"]) ? module('forum')->USER_SETTINGS["POSTS_PER_PAGE"] : module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"];
		list($num_posts) = db()->query_fetch(
			"SELECT COUNT(*) AS `0` FROM `".db('forum_posts')."` WHERE `topic`=".intval($topic_id)
		);
		$last_page = $posts_per_page ? ceil($num_posts / $posts_per_page) : 1;
		return $last_page ? $last_page : 1;
	}
}
