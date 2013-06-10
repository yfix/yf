<?php

/**
* Members list and filter
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_members {

	/** @var string Session array name where filter vars are stored */
	public $_filter_name = "members_filter";
	/** @var bool Filter on/off */
	public $USE_FILTER = false;

	/**
	* Constructor
	*/
	function _init () {
		// Get all ranks into array
		if (module('forum')->SETTINGS["SHOW_USER_RANKS"]) {
			$this->_ranks_array = main()->get_data("forum_user_ranks");
		}
		// Process user ranks
		$rank_num = 0;
		$this->_ranks_array2 = array();
		foreach ((array)$this->_ranks_array as $rank_info) {
			if ($rank_info["special"] == 1) continue;
			$this->_ranks_array2[++$rank_num] = $rank_info;
		}
	}
	
	/**
	* Show Main
	*/
	function _show_main() {
		if (!module('forum')->SETTINGS["SHOW_MEMBERS_LIST"]) {
			return module('forum')->_show_error("Members list is disabled!");
		}
		// Stop here for now if global users mode is "ON"
		if (module('forum')->SETTINGS["USE_GLOBAL_USERS"]) {
// TODO: need to turn on in globals mode
			return module('forum')->_show_error("Disabled in global board mode!");
		}
		// Check if filter need to be saved
		if (!empty($_POST["act"])) {
			if ($_POST["act"] == "save_filter")	{
				return $this->_save_filter();
			} elseif ($_POST["act"] == "clear_filter") {
				return $this->_clear_filter();
			}
		}
		// Page number
		if (isset($_GET["id"]) && empty($_GET["page"])) $_GET["page"] = $_GET["id"];
		// Prepare SQL query
		$sql = "SELECT * FROM `".db('forum_users')."` WHERE 1=1 ";
		// For user hide unapproved topics
		$sql .= !FORUM_IS_ADMIN ? " AND `status`='a' " : "";
		// Add filter SQL
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? $filter_sql : " ORDER BY `name` ASC ";
		// Prepare path for the pages
		$path = "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"];
		// Call pager
		list($add_sql, $pages, $num_posts) = common()->divide_pages(str_replace("SELECT *","SELECT `id`",$sql), $path, null, module('forum')->SETTINGS["NUM_MEMBERS_ON_PAGE"], null, FORUM_CLASS_NAME."/pages_1/");
		// Get data from db
		$Q = db()->query($sql. $order_by. $add_sql);
		while ($user_info = db()->fetch_assoc($Q)) {
			$user_level = 1;
			// Get number of user's posts
			$user_num_posts = intval($user_info["user_posts"]);
			// Get user avatar
			if (FORUM_USER_ID && !module('forum')->USER_SETTINGS["VIEW_AVATARS"]) {
				$user_avatar_src = "";
			} elseif (!empty($user_info["user_avatar"])) {
				$img_src = module('forum')->SETTINGS["AVATARS_DIR"]. $user_info["user_avatar"];
				$user_avatar_src = file_exists(REAL_PATH. $img_src) ? WEB_PATH. $img_src : "";
			} else {
				$user_avatar_src = "";
			}
			// Get user rank
			foreach ((array)$this->_ranks_array2 as $rank_num => $rank_info) {
				if ($user_num_posts > $rank_info["min"]) $user_level = $rank_num;
			}
			// Process template
			$replace2 = array(
				"user_id"			=> $user_info["id"],
				"user_name"			=> _prepare_html($user_info["name"]),
				"user_profile_link"	=> module('forum')->_user_profile_link($user_info["id"]),
				"user_group"		=> t(module('forum')->FORUM_USER_GROUPS[$user_info["group"]]),
				"add_date"			=> module('forum')->_show_date($user_info["user_regdate"], "user_reg_date"),
				"num_posts"			=> intval($user_info["user_posts"]),
				"user_avatar_src"	=> $user_info["id"] ? $user_avatar_src : "",
				"user_level"		=> $user_info["id"] && $user_level && module('forum')->SETTINGS["SHOW_USER_LEVEL"] ? ($user_level > 1 ? range(1, $user_level) : array(1)) : "",
				"show_user_level"	=> intval($user_info["id"] && $user_level && module('forum')->SETTINGS["SHOW_USER_LEVEL"]),
			);
			$items .= tpl()->parse(FORUM_CLASS_NAME."/members/item", $replace2);
		}
		// Process template
		$replace = array(
			"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(),
			"name_begins_box"	=> $this->_box("name_begins"),
			"use_filter"		=> intval($this->USE_FILTER),
			"items"				=> $items,
			"pages"				=> $pages,
		);
		return module('forum')->_show_main_tpl(tpl()->parse(FORUM_CLASS_NAME."/members/form_main", $replace));
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$F = &$_SESSION[$this->_filter_name];
		foreach ((array)$F as $k => $v) $F[$k] = trim($v);
/*
		// Default values
		if (!isset($F["prune_day"])) $F["prune_day"] = array_pop(array_keys($this->_filter_prune_days));
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
			// Only for logged in users
			} elseif ($F["topics_flag"] == "ireplied" && FORUM_USER_ID)	{
// TODO
//				$sql .= "  \r\n";
			}
		}
		// Sorting here
		if (isset($this->_filter_sort_by[$F["sort_by"]])) {
			$sql .= " ORDER BY `".$F["sort_by"]."` ".($F["sort_order"] == "Z-A" ? "DESC" : "ASC")." \r\n";
		}
*/
		return substr($sql, 0, -3);
	}

	/**
	* Session - based members filter form stored in $_SESSION[$this->_filter_name][...]
	*/
	function _show_filter () {
		if (!module('forum')->SETTINGS["SHOW_MEMBERS_LIST"]) {
			return module('forum')->_show_error("Members list is disabled!");
		}
		$replace = array(
			"save_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
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
		if (!module('forum')->SETTINGS["SHOW_MEMBERS_LIST"]) {
			return module('forum')->_show_error("Members list is disabled!");
		}
		foreach ((array)$this->_fields_in_filter as $name) {
			$_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect("./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]."&id=".$_GET["id"]._add_get());
		}
	}

	/**
	* Clear filter
	*/
	function _clear_filter ($silent = false) {
		if (!module('forum')->SETTINGS["SHOW_MEMBERS_LIST"]) {
			return module('forum')->_show_error("Members list is disabled!");
		}
		foreach ((array)$_SESSION[$this->_filter_name] as $name) {
			unset($_SESSION[$this->_filter_name]);
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
}
