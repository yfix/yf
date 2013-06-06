<?php

/**
* Other useful methods here
*
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_utils {

	/**
	* Change current user language
	*/
	function _change_lang () {
		if (!module('forum')->SETTINGS["ALLOW_LANG_CHANGE"]) {
			return module('forum')->_show_error("Changing language not allowed!");
		}
		$new_lang = _prepare_html($_REQUEST["lang_id"]);
		// If new language found - check it
		if (!empty($new_lang) && conf('languages::'.$new_lang.'::active')) {
			$_SESSION["user_lang"] = $new_lang;
			// Try to get user back
			$old_location = !empty($_POST["back_url"]) ? str_replace(WEB_PATH, "./", $_POST["back_url"]) : "./?object=".FORUM_CLASS_NAME;
			return js_redirect($old_location. "&language=".(!isset($_GET["language"]) ? $_SESSION["user_lang"] : $_GET["language"]));
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Change current user skin
	*/
	function _change_skin () {
		if (!module('forum')->SETTINGS["ALLOW_SKIN_CHANGE"]) {
			return module('forum')->_show_error("Changing skin not allowed!");
		}
		$new_skin = intval($_REQUEST["skin_id"]);
		// If new skin found - check it
		if (!empty($new_skin) && !empty(module('forum')->_skins_array) && !empty(module('forum')->_skins_array[$new_skin])) {
			$_SESSION["user_skin"] = module('forum')->_skins_array[$new_skin];
			// Try to get user back
			$old_location = !empty($_POST["back_url"]) ? str_replace(WEB_PATH, "./", $_POST["back_url"]) : "./?object=".FORUM_CLASS_NAME;
			return js_redirect($old_location);
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	/**
	* Change between tree and flat topic view model
	*/
	function _change_topic_view () {
		if (!module('forum')->SETTINGS["ALLOW_CHANGE_TOPIC_VIEW"]) {
			return module('forum')->_show_error("Changing topic view not allowed!");
		}
		$new_topic_view = intval($_GET["id"]);
		$topic_views = array(
			1	=> "Tree",
			2	=> "Flat",
		);
		// If new topic view ID is valid - then
 		if (!empty($new_topic_view) && array_key_exists($new_topic_view, $topic_views)) {
			$_SESSION["board_topic_view"] = $new_topic_view;
		}
		js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Email Topic
	*/
	function _email_topic () {
		if (!module('forum')->SETTINGS["ALLOW_EMAIL_TOPIC"]) {
			return module('forum')->_show_error("Email topic is disabled");
		}
// TODO
		return "Email topic will be here...";
	}

	/**
	* Report Post
	*/
	function _report_post () {
		if (!FORUM_USER_ID) {
			return module('forum')->_show_error(t("Guests are not allowed to do this action")."!");
		}
		main()->NO_GRAPHICS = true;

		if(isset($_POST["post_id"])) {
			db()->INSERT("forum_reports", array(
				"post_id"		=> intval($_POST["post_id"]),
				"user_id"		=> FORUM_USER_ID,
				"time"			=> time(this),
				"text"			=> _es(""),
			));
			echo common()->show_empty_page("<div align='center'>{t(Thank you)}!<br /><a href='javascript:window.close()'>{t(Close Window)}</a></div>");
			return false;
		}
		$replace = array(
			"post_id"			=> intval($_GET["id"]),
			"form_action"		=> process_url("./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"])
		);

		$body = common()->show_empty_page(tpl()->parse(FORUM_CLASS_NAME."/report_post", $replace));
		echo $body;
	}

	/**
	* View users reports
	*/
	function _view_reports() {
		// Check permissions
		if (!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR) {
			return module('forum')->_show_error("You are not allowed to do this action!");
		}

		$_reports_per_page = 10;
		$sql = "SELECT * FROM `".db('forum_reports')."`";
		$order_by = " WHERE `active`=1 ORDER BY `id` ASC ";
		$url = "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=all";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $url, null, $_reports_per_page);

		$BB_OBJ = main()->init_class("bb_codes", "classes/");

		// Get records from db	  выбираем из репортов все id
		$Q = db()->query($sql. $order_by. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$records[] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
			$post_ids[$A["post_id"]] = $A["post_id"];
		}

		// Get post infos		 выбираем из постов id написавшего пост
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT `id` ,`user_id`, `user_name`, `text`, `forum`, `topic` FROM `".db('forum_posts')."` WHERE `id` IN(".implode(",", $post_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$post_infos[$A["id"]] = $A;
				$users_ids[$A["user_id"]] = $A["user_id"];
				$topic_ids[$A["topic"]] = $A["topic"];
			}
		}
		// Get topic infos
		if (!empty($post_infos)){
			$Q = db()->query("SELECT `id` ,`name` FROM `".db('forum_topics')."` WHERE `id` IN(".implode(",", $topic_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$topic_infos[$A["id"]] = $A;
			}
		}

		// Get users infos	выбираем значение ников для всех id
		if (!empty($users_ids)) {
			$users_infos = user($users_ids, array("id" ,"nick"));
		}

		foreach ((array)$records as $A) {
			if (!empty($A["user_id"])) {
				$cur_user_info = $users_infos[$A["user_id"]];
			}

			$reported_post_info = $post_infos[$A["post_id"]];
			$reported_topic_info = $topic_infos[$reported_post_info["topic"]];
			$reported_post_author = $users_infos[$reported_post_info["user_id"]];

			//  если  $reported_post_author пустой, тогда нужно взять ник из таблицы постов
			if (empty($reported_post_author["nick"])) {
				$reported_post_author["nick"] = $reported_post_info["user_name"];
			}

			$reports[$A["id"]] = array(
				"report_id"			=> intval($A["id"]),
				"post_id"			=> intval($A["post_id"]),
				"post_author"		=> _prepare_html($reported_post_author["nick"]),
				"post_user_link"	=> module('forum')->_user_profile_link($reported_post_info["user_id"]),
				"report_user_name"	=> _prepare_html($cur_user_info["nick"]),
				"report_user_link"	=> module('forum')->_user_profile_link($A["user_id"]),
				"post_text"			=> $BB_OBJ->_process_text($reported_post_info["text"]),
				"time"				=> _format_date($A["time"], "long"),
				"text"				=> $BB_OBJ->_process_text($A["text"]),
				"button_action"		=> process_url("./?object=".FORUM_CLASS_NAME."&action=close_reports&id=".$A["post_id"]),
				"forum_link"		=> module('forum')->_link_to_forum($reported_post_info["forum"]),
				"forum_name"		=> _prepare_html(module('forum')->_forums_array[$reported_post_info["forum"]]["name"]),
				"topic_id"			=> intval($reported_post_info["topic"]),
				"topic_name"		=> _prepare_html($reported_topic_info["name"]),
				"topic_link"		=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$reported_post_info["topic"],
			);
		}
		$replace = array(
			"reports"	=> $reports,
			"total"		=> intval($total),
			"pages"		=> $reports ? $pages : "",
		);
		return module('forum')->_show_main_tpl(tpl()->parse(FORUM_CLASS_NAME."/view_reports", $replace));
	}

	/**
	* Do close reports
	*/
	function _close_reports() {
		// Check permissions
		if (!FORUM_IS_ADMIN && !FORUM_IS_MODERATOR) {
			return module('forum')->_show_error("You are not allowed to do this action!");
		}
		$post_info	= db()->query_fetch("SELECT * FROM `".db('forum_posts')."` WHERE `id`=".intval($_GET["id"]));
		if ($post_info) {
			$need_del_topic = isset($_POST["delete_topic"][$post_info["topic"]]);
			$need_del_post	= $need_del_topic || isset($_POST["delete_post"][$post_info["id"]]);
			// Delete topic if needed
			if ($need_del_topic) {
				$FORUM_ADMIN_OBJ = module('forum')->_load_sub_module("forum_admin");
				$FORUM_ADMIN_OBJ->_topic_delete(true, $post_info["topic"]);
			// Delete post if needed
			} elseif ($need_del_post) {
				module('forum')->delete_post(true, $post_info["id"]);
			}
		}
		// Close report record
		db()->UPDATE("forum_reports", array(
			"active" => 0
		), "`post_id`=".intval($_GET["id"]));
		// Return user back
		return js_redirect("./?object=".FORUM_CLASS_NAME."&action=view_reports");
	}

	/**
	* Update number of users posts for all users
	*/
	function _update_users_number_posts () {
// TODO: need to check
/*
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
			return false;
		}
*/
		$Q = db()->query("SELECT * FROM `".db('forum_users')."` WHERE `status`='a'");
		while ($A = db()->fetch_assoc($Q)) {
			$num_posts = db()->query_num_rows("SELECT `id` FROM `".db('forum_posts')."` WHERE `user_id`=".intval($A["id"]));
			if ($num_posts > 0) {
				$num_posts = intval($num_posts - 1);
			}
			db()->query("UPDATE `".db('forum_users')."` SET `user_posts`=".intval($num_posts)." WHERE `id`=".intval($A["id"]));
		}
	}

	/**
	* Update column `last_post_date` for all topics and forums
	*/
	function _update_last_post_date () {
		db()->query("UPDATE `".db('forum_topics')."` AS t, `".db('forum_posts')."` AS p SET t.`last_post_date`=p.`created` WHERE p.`id` = t.`last_post_id`");
		db()->query("UPDATE `".db('forum_forums')."` AS f, `".db('forum_posts')."` AS p SET f.`last_post_date`=p.`created` WHERE p.`id` = f.`last_post_id`");
	}
}
