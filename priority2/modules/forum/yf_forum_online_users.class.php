<?php

/**
* Online users view
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_online_users {

	/** @var string Session array name where filter vars are stored */
	public $_filter_name = "online_users_filter";
	/** @var bool Filter on/off */
	public $USE_FILTER = 0;

	/**
	* Constructor
	*/
	function _init () {
		// Detailed stats types
		$this->_stat_types = array(
			1	=> "By Last Click",
			2	=> "By Member Name",
/*
			3	=> "Today's active topics",
			4	=> "The moderating team",
			5	=> "Today's top 10 posters",
			6	=> "Overall top 10 posters",
*/
		);
		if ($this->USE_FILTER) {
			$this->_filter_sort_by = array(
				"click" => "Last Click",
				"name"	=> "Member Name",
			);
			$this->_filter_sort_orders = array(
				"desc"	=> "Descending",
				"asc"	=> "Ascending",
			);
			$this->_filter_show_members = array(
				"all"	=> "Show All Users",
				"reg"	=> "Show Registered Only",
				"guest"	=> "Show Guests Only",
			);
			$this->_boxes = array(
				"sort_by"		=> 'select_box("sort_by",		$this->_filter_sort_by,		$selected, 0, 2, "", false)',
				"sort_order"	=> 'select_box("sort_order",	$this->_filter_sort_orders,	$selected, 0, 2, "", false)',
				"show_members"	=> 'select_box("show_members",	$this->_filter_show_members,$selected, 0, 2, "", false)',
			);
			$this->_fields_in_filter = array_keys($this->_boxes);
		}
	}

	/**
	*  View Stats
	*/
	function _view_stats() {
		if (!module('forum')->SETTINGS["ONLINE_USERS_STATS"]) {
			return module('forum')->_show_error("Online users stats disabled!");
		}
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"]) || $_GET["id"] == 1)	$_SESSION[$this->_filter_name]["sort_by"] = "last_click";
		elseif ($_GET["id"] == 2)					$_SESSION[$this->_filter_name]["sort_by"] = "member_name";
		// Check if filter need to be saved
		if (!empty($_POST["act"])) {
			if ($_POST["act"] == "save_filter")			return $this->_save_filter();
			elseif ($_POST["act"] == "clear_filter")	return $this->_clear_filter();
		}
		$F = &$_SESSION[$this->_filter_name];
		foreach ((array)$F as $k => $v) $F[$k] = trim($v);
		// Filter users matching given params
		foreach ((array)module("forum")->online_array as $k => $online_info) {
			$online_users[$k] = $online_info;
			if (!empty($online_info["user_id"])) {
				$users_ids[$online_info["user_id"]] = $online_info["user_id"];
			}
		}
		// Process users
		if (!empty($users_ids)) {
			$online_users_infos = module('forum')->_get_users_infos($users_ids);
		}
		foreach ((array)$online_users as $online_info) {
			$user_info = $online_info["user_id"] ? $online_users_infos[$online_info["user_id"]] : null;
			$replace2 = array(
				"is_admin"			=> intval(FORUM_IS_ADMIN),
				"user_id"			=> $online_info["user_id"],
				"user_name"			=> $online_info["user_id"] ? _prepare_html($online_info["user_name"]) : t("Guest"),
				"user_profile_link"	=> $user_info["id"] ? module('forum')->_user_profile_link($user_info["id"]) : "",
				"user_ip"			=> module('forum')->USER_RIGHTS["view_ip"] ? $online_info["ip_address"] : "",
				"user_lastup_time"	=> module('forum')->_show_date($online_info["last_update"], "user_last_update"),
				"user_action"		=> $this->_show_user_location($online_info["location"]),
				"user_pm_link"		=> "",
			);
			$items .= tpl()->parse(FORUM_CLASS_NAME."/online_users/item", $replace2);
		}
		// Show template
		$replace = array(
			"form_action"	=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
			"use_filter"	=> intval($this->USE_FILTER),
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
			"items"			=> $items,
		);
		$body = tpl()->parse(FORUM_CLASS_NAME."/online_users/main", $replace);
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Show readable reprsentation of user location
	*/
	function _show_user_location ($code = "") {
		list($action, $id, $page) = explode(";", $code);
		$code_texts = array(
			"show"					=> "Viewing Board Index",
			"view_stats"			=> "Viewing Online List",
			"view_forum"			=> "Viewing Forum",
			"view_topic"			=> "Viewing Topic",
			"view_post"				=> "Viewing Topic",
			"view_members"			=> "Viewing Members list",
			"register"				=> "Viewing Register Form",
			"login"					=> "Viewing Log In form",
			"search"				=> "Searching board",
			"help"					=> "Viewing Help",
			"reply"					=> "Replying to Post",
			"new_post"				=> "Posting new Message",
			"new_topic"				=> "Creating New Topic",
			"send_password"			=> "Retrieving new password",
			"user_cp"				=> "In control panel",
			"edit_profile"			=> "In control panel",
			"edit_settings"			=> "In control panel",
			"edit_announces"		=> "In control panel",
			"tracker_manage_topics"	=> "In control panel",
			"tracker_manage_forums"	=> "In control panel",
		);
		$code_action = isset($code_texts[$action]) ? $action : "show";
		if (in_array($code_action, array("view_forum","view_topic","view_post")) && !empty($id)) {
			if ($code_action == "view_forum") $text = module('forum')->_forums_array[$id]["name"];
			else $text = $this->_topic_names[$id];
			$replace = array(
				"link"	=> "./?object=".FORUM_CLASS_NAME."&action=".$code_action."&id=".$id._add_get(array("page")),
				"text"	=> _prepare_html($text),
			);
			$add_text = !empty($text) ? tpl()->parse(FORUM_CLASS_NAME."/online_users/add_text", $replace) : "";
		}
		return $code_texts[$code_action]/*t($code_texts[$action])*/.$add_text;
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
		return tpl()->parse(FORUM_CLASS_NAME."/online_users/filter", $replace);
	}

	/**
	* Filter save method
	*/
	function _save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
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
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
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
