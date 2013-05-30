<?php

/**
* Show main board template contents
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_forum_main_tpl {
	
	/**
	* Process main template
	*/
	function _show_main_tpl($items = "") {
		// Prevent multiple recursive calls to the "_show_main_tpl" method one inside another
		if (module('forum')->_MAIN_TPL_VISITED) {
			return $items;
		}
		// Stop here if user is banned
		if (!module('forum')->USER_RIGHTS["view_board"]) {
			$error_message = "Your account is banned by the site admin";
			$error_message .= "For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>";
			if (!empty(module('forum')->BAN_REASONS)) {
				$error_message .= "\r\n<br />Reasons: \r\n<br /><li>".implode("<li>", module('forum')->BAN_REASONS);
			}
			$items = module('forum')->_show_error($error_message, 0);
		}
		module('forum')->_MAIN_TPL_VISITED = true;
		// Seo keywords
		if ($_GET["action"] == "show" && module('forum')->SETTINGS["SEO_KEYWORDS"]) {
			$SEO_OBJ = main()->init_class("se_keywords", "classes/");
			$seo_keywords = is_object($SEO_OBJ) ? $SEO_OBJ->_show_search_keywords() : "";
		}
		// Forum statistics
		if ($_GET["action"] == "show" && module('forum')->SETTINGS["SHOW_TOTALS"]) {
			$STATS_OBJ = main()->init_class("forum_stats", FORUM_MODULES_DIR);
			$stats = is_object($STATS_OBJ) ? $STATS_OBJ->_show_total_board_stats() : "";
		}
		// Main template
		$replace = array(
			"items"			=> $items,
			"user_id"		=> FORUM_USER_ID,
			"user_name"		=> FORUM_USER_ID ? FORUM_USER_NAME : t("Guest"),
			"menu_top"		=> module('forum')->USER_RIGHTS["view_board"] ? $this->_show_menu_top() : "",
			"menu_main"		=> module('forum')->USER_RIGHTS["view_board"] ? $this->_show_menu_main() : "",
			"navigation"	=> module('forum')->USER_RIGHTS["view_board"] ? $this->_show_navigation() : "",
			"footer"		=> module('forum')->USER_RIGHTS["view_board"] ? $this->_show_main_footer() : "",
			"board_version"	=> module('forum')->VERSION,
			"totals"		=> module('forum')->USER_RIGHTS["view_board"] ? $stats : "",
			"keywords"		=> $seo_keywords,
			"main_js_src"	=> WEB_PATH. "js/board_main.js",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/main", $replace);
	}
	
	/**
	* Process navigation
	*/
	function _show_navigation($return_as_array = false) {
		$items_links = $items_texts = array();
		// Array of simple texts
		$simple_texts = array(
			"register"		=> "Registration Form",
			"view_members"	=> "Member List",
			"login"			=> "Log In",
			"send_password" => "Lost Password Form",
			"view_stats"	=> "Online Users",
			"view_profile"	=> "Viewing Profile",
			"view_new_posts"=> "View New Posts",
		);
		// Process items
		if ($_GET["action"] == "show") {
			if (isset(module('forum')->_forum_cats_array[$_GET["id"]])) {
				$cat_id = $_GET["id"];
			}
		} elseif (in_array($_GET["action"], array("view_forum", "new_topic"))) {
			if (isset(module('forum')->_forums_array[$_GET["id"]])) {
				$forum_id	= $_GET["id"];
				$cat_id		= module('forum')->_forums_array[$forum_id]["category"];
			}
		} elseif (in_array($_GET["action"], array("reply","reply_no_quote","new_post","edit_post"))) {
			if (!empty(module('forum')->_topic_info["forum"])) {
				$forum_id	= module('forum')->_topic_info["forum"];
				$cat_id		= module('forum')->_forums_array[$forum_id]["category"];
			}
		} elseif (in_array($_GET["action"], array("new_poll"))) {
			$forum_id	= $_GET["id"];
			$cat_id		= module('forum')->_forums_array[$forum_id]["category"];
		} elseif (in_array($_GET["action"], array("view_topic", "view_post"))) {
			if (isset(module('forum')->_topic_info)) {
				$topic_id	= $_GET["id"];
				$forum_id	= module('forum')->_topic_info["forum"];
				$cat_id		= module('forum')->_forums_array[$forum_id]["category"];
			}
		} elseif ($_GET["action"] == "search") {
			if (empty($_POST["q"]) && empty($_GET["page"])) {
				$items_texts[] = array("name" => t("Search Form"));
			} else {
				$items_links[] = array(
					"link"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]. _add_get(array("page")),
					"name"	=> t("Search Form"),
				);
				$items_texts[] = array("name" => t("Search Engine"));
			}
		} elseif ($_GET["action"] == "help" && !empty($_GET["id"])) {
			$items_links[] = array(
				"link"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]. _add_get(array("page")),
				"name"	=> t("Help Topics"),
			);
			$items_texts[] = array("name" => t("Help Topic"));
		} elseif (in_array($_GET["action"], array("user_cp","tracker_manage_topics","tracker_manage_forums","edit_profile","edit_settings","edit_announces"))) {
			$items_links[] = array(
				"link"	=> "./?object=".FORUM_CLASS_NAME."&action=user_cp". _add_get(array("page")),
				"name"	=> t("Your control panel"),
			);
			if ($_GET["action"] == "edit_announces") {
				$items_texts[] = array("name" => t("Manage Announcements"));
			}
		// Simple texts
		} elseif (isset($simple_texts[$_GET["action"]])) {
			$items_texts[] = array("name" => t($simple_texts[$_GET["action"]]));
		} 
		// Add category item
		if ($cat_id) {
			$items_links[] = array(
				"link"	=> "./?object=".FORUM_CLASS_NAME."&id=".$cat_id. _add_get(array("page")),
				"name"	=> _prepare_html(module('forum')->_forum_cats_array[$cat_id]["name"]),
			);
		}
		// Add forum item
		if ($forum_id) {
			foreach ((array)module('forum')->_get_parent_forums_ids($forum_id) as $_parent_id) {
				$items_links[] = array(
					"link"	=> module('forum')->_link_to_forum($_parent_id),
					"name"	=> _prepare_html(module('forum')->_forums_array[$_parent_id]["name"]),
				);
			}
			$items_links[] = array(
				"link"	=> module('forum')->_link_to_forum($forum_id),
				"name"	=> _prepare_html(module('forum')->_forums_array[$forum_id]["name"]),
			);
		}
		// Add topic item
		if ($topic_id) {
			$items_links[] = array(
				"link"	=> "./?object=".FORUM_CLASS_NAME."&action=view_topic&id=".$topic_id. _add_get(array("page")),
				"name"	=> _prepare_html(module('forum')->_topic_info["name"]),
			);
		}
		// Show template contents
		$replace = array(
			"board_link"	=> "./?object=".FORUM_CLASS_NAME. _add_get(array("page")),
			"board_name"	=> _prepare_html(conf('website_name')),
			"items_links"	=> $items_links,
			"items_texts"	=> $items_texts,
		);
		if ($return_as_array) {
			return $replace;
		}
		return tpl()->parse(FORUM_CLASS_NAME."/navigation", $replace);
	}
	
	/**
	* Process top menu
	*/
	function _show_menu_top() {
		$replace = array(
			"home_link"			=> "./?object=".FORUM_CLASS_NAME. _add_get(array("page")),
			"help_link"			=> module('forum')->SETTINGS["SHOW_HELP"] ? "./?object=".FORUM_CLASS_NAME."&action=help". _add_get(array("page")) : "",
			"search_link"		=> module('forum')->SETTINGS["ALLOW_SEARCH"] ? "./?object=".FORUM_CLASS_NAME."&action=search". _add_get(array("page")) : "",
			"members_link"		=> module('forum')->SETTINGS["SHOW_MEMBERS_LIST"] ? "./?object=".FORUM_CLASS_NAME."&action=view_members". _add_get(array("page")) : "",
			"user_cp_link"		=> FORUM_USER_ID ? "./?object=".FORUM_CLASS_NAME."&action=user_cp". _add_get(array("page")) : "",
			"view_reports_link"	=> (FORUM_IS_ADMIN || FORUM_IS_MODERATOR) ? "./?object=".FORUM_CLASS_NAME."&action=view_reports". _add_get(array("page")) : "",
		);
		return tpl()->parse(FORUM_CLASS_NAME."/menu_top",	$replace);
	}
	
	/**
	* Process main menu
	*/
	function _show_menu_main() {
		$replace = array(
			"logged_in"			=> intval(FORUM_USER_ID),
			"admin_mode"		=> intval(FORUM_IS_ADMIN),
			"admin_cp_link"		=> WEB_PATH."admin/",
			"user_name"			=> FORUM_USER_ID ? FORUM_USER_NAME : t("Guest"),
			"user_info_link"	=> FORUM_USER_ID ? module('forum')->_user_profile_link(FORUM_USER_ID) : "",
			"user_cp_link"		=> "./?object=".FORUM_CLASS_NAME."&action=user_cp". _add_get(array("page")),
			"edit_profile_link"	=> "./?object=".FORUM_CLASS_NAME."&action=edit_profile". _add_get(array("page")),
			"new_posts_link"	=> "./?object=".FORUM_CLASS_NAME."&action=view_new_posts". _add_get(array("page")),
			"inbox_link"		=> "./?object=".FORUM_CLASS_NAME."&action=inbox". _add_get(array("page")),
			"register_link"		=> "./?object=".FORUM_CLASS_NAME."&action=register". _add_get(array("page")),
			"login_link"		=> "./?object=".FORUM_CLASS_NAME."&action=login". _add_get(array("page")),
			"logout_link"		=> "./?object=".FORUM_CLASS_NAME."&action=logout". _add_get(array("page")),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/menu_main", $replace);
	}
	
	/**
	* Process main footer
	*/
	function _show_main_footer() {
		// Get available skins
		if (module('forum')->SETTINGS["ALLOW_SKIN_CHANGE"] && is_array(module('forum')->_skins_array)) {
			$skin_id_box = common()->select_box("skin_id", array("Skin Selector" => module('forum')->_skins_array), conf('theme'), false, 1, " onchange=\"this.form.submit();\"", false);
		}
		// Get available languages
		if (module('forum')->SETTINGS["ALLOW_LANG_CHANGE"]) {
			foreach ((array)conf('languages') as $lang_info) {
				if ($lang_info["active"]) {
					$lang_names[$lang_info["locale"]] = $lang_info["name"];
				}
			}
			if ($lang_names) {
				$lang_id_box = common()->select_box("lang_id", array("Language" => $lang_names), conf('language'), false, 2, " onchange=\"this.form.submit();\"", false);
			}
		}
		// Process footer
		$replace = array(
			"logged_in"			=> intval(FORUM_USER_ID),
			"cur_time"			=> module('forum')->_show_date(time(), "footer"),
			"light_version_link"=> "./?object=".FORUM_CLASS_NAME."&action=light_version". _add_get(),
			"skin_form_link"	=> "./?object=".FORUM_CLASS_NAME."&action=change_skin",
			"lang_form_link"	=> "./?object=".FORUM_CLASS_NAME."&action=change_lang",
			"skin_id_box"		=> $skin_id_box,
			"lang_id_box"		=> $lang_id_box,
			"allow_skin_change"	=> intval(module('forum')->SETTINGS["ALLOW_SKIN_CHANGE"]),
			"allow_lang_change"	=> intval(module('forum')->SETTINGS["ALLOW_LANG_CHANGE"]),
			"back_url"			=> WEB_PATH."?object=".FORUM_CLASS_NAME.($_GET["action"] != "show" ? "&action=".$_GET["action"] : ""). (!empty($_GET["id"]) ? "&id=".$_GET["id"] : ""). (!empty($_GET["page"]) ? "&page=".$_GET["page"] : ""),
			"rss_board_button"	=> module('forum')->_show_rss_link("./?object=".FORUM_CLASS_NAME."&action=rss_board", "RSS feed for board"),
		);
		return tpl()->parse(FORUM_CLASS_NAME."/main_footer", $replace);
	}

	/**
	* Display error message
	*/
	function _show_error($text = "", $use_main_tpl = 1) {
		if (!strlen($text)) {
			$text = t("Unknown error");
		} elseif (common()->_error_exists()) {
			$text = _e();
		}
		$admin_email = explode("@", module('forum')->SETTINGS["ADMIN_EMAIL_FROM"]);
		$replace = array(
			"text"				=> $text,
			"is_logged_in"		=> intval(FORUM_USER_ID),
			"back_url"			=> $this->USER_RIGHTS["view_board"] && !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "",
			"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=login",
			"forgot_pswd_link"	=> "./?object=".FORUM_CLASS_NAME."&action=send_password",
			"register_link"		=> "./?object=".FORUM_CLASS_NAME."&action=register",
			"help_link"			=> "./?object=".FORUM_CLASS_NAME."&action=help",
			"contact_admin_link"=> "./?object=".FORUM_CLASS_NAME."&action=contact_admin",
			"admin_email_1"		=> $admin_email[0],
			"admin_email_2"		=> $admin_email[1],
			"show_login_form"	=> !FORUM_USER_ID && $this->USER_RIGHTS["view_board"],
			"show_useful_links"	=> $this->USER_RIGHTS["view_board"],
		);
		$body = tpl()->parse(FORUM_CLASS_NAME."/errors_main", $replace);
		return $use_main_tpl ? $this->_show_main_tpl($body) : $body;
	}
}
