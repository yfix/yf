<?php

//-----------------------------------------------------------------------------
// Reputation management module
class profy_manage_reput {

	/** @var bool Filter on/off */
	var $USE_FILTER				= true;
	/** @var array */
	var $_known_ban_items = array(
		"ban_ads",
		"ban_email",
		"ban_reviews",
		"ban_images",
		"ban_forum",
		"ban_comments",
		"ban_blog",
		"ban_bad_contact",
		"ban_reput",
	);
	/** @var array */
	var $_map_vote_for = array(
		"forum_posts"	=> "./?object=forum&action=edit_post&id=",
		"articles_texts"=> "./?object=manage_articles&action=edit&id=",
		"blog_posts"	=> "./?object=manage_blogs&action=edit&id=",
		"comments"		=> "./?object=manage_comments&action=edit&id=",
		"reviews"		=> "./?object=reviews&action=edit&id=",
	);

	//-----------------------------------------------------------------------------
	// Constructor
	function profy_manage_reput() {
		$this->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Array of boxes
		$this->_boxes = array(
		);
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	//-----------------------------------------------------------------------------
	// Default method (display blog posts)
	function show () {
		// Prepare filter
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql = "SELECT * FROM `".db('reput_user_votes')."` WHERE 1=1 ";
		$sql .= $filter_sql ? $filter_sql : " ORDER BY `add_date` DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get contents from db
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A["id"]] = $A;
			$users_ids[$A["user_id"]]		= $A["user_id"];
			$users_ids[$A["target_user_id"]]= $A["target_user_id"];
		}
		// Get authors infos
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `id` IN(".implode(",", array_keys($users_ids)).")");
			while ($A = db()->fetch_assoc($Q)) {
				$users_infos[$A["id"]] = $A;
			}
		}
		// Process posts
		foreach ((array) $data as $A) {
			// Process template
			$replace2 = array(
				"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
				"id"				=> intval($A["id"]),
				"voter_id"			=> intval($A["user_id"]),
				"voter_nick"		=> _prepare_html(_display_name($users_infos[$A["user_id"]])),
				"voter_link"		=> _profile_link($A["user_id"]),
				"voter_avatar"		=> _show_avatar($A["user_id"], $users_infos[$A["user_id"]], 1, 1),
				"target_id"			=> intval($A["target_user_id"]),
				"target_nick"		=> _prepare_html(_display_name($users_infos[$A["target_user_id"]])),
				"target_link"		=> _profile_link($A["target_user_id"]),
				"target_avatar"		=> _show_avatar($A["target_user_id"], $users_infos[$A["target_user_id"]], 1, 1),
				"voter_ip"			=> _prepare_html($A["ip"]),
				"voted"				=> intval($A["voted"]),
				"counted"			=> intval($A["counted"]),
				"penalty"			=> intval($A["penalty"]),
				"country_match"		=> intval((bool)$A["country_match"]),
				"same_voter"		=> intval((bool)$A["same_voter"]),
				"comment"			=> _prepare_html($A["comment"]),
				"add_date"			=> _format_date($A["add_date"], "long"),
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"all_by_voter_link"	=> "./?object=".$_GET["object"]."&action=all_by_voter&id=".$A["user_id"],
				"all_by_target_link"=> "./?object=".$_GET["object"]."&action=all_by_target&id=".$A["target_user_id"],
				"voter_ban_info"	=> $this->_prepare_ban_info($users_infos[$A["user_id"]]),
				"target_ban_info"	=> $this->_prepare_ban_info($users_infos[$A["target_user_id"]]),
				"voter_ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($A["user_id"])),
				"target_ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($A["target_user_id"])),
				"vote_for_object"	=> _prepare_html($A["object_name"]),
				"vote_for_id"		=> intval($A["object_id"]),
				"vote_for_link"		=> $A["object_name"] && $this->_map_vote_for[$A["object_name"]] ? $this->_map_vote_for[$A["object_name"]].$A["object_id"] : "",
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_delete",
			"total"			=> intval($total),
			"items"			=> $items,
			"pages"			=> $pages,
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Do delete record (mass method)
	function mass_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items_to_delete"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM `".db('reput_user_votes')."` WHERE `id` IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	//-----------------------------------------------------------------------------
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM `".db('reput_user_votes')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Display all votes done by selected voter
	*/
	function all_by_voter () {
		$_POST["user_id"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show(1);
	}

	/**
	* Display all votes done for selected target user
	*/
	function all_by_target () {
		$_POST["target_user_id"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show(1);
	}

	/**
	* Prepare ban info
	*/
	function _prepare_ban_info ($user_info = array()) {
		$body = array();
		foreach ((array)$this->_known_ban_items as $_field_name) {
			$body[] = $user_info[$_field_name] ? "<span style='color:red;'>X</span>" : "<span style='color:green;'>0</span>";
		}
		return "<b>".implode(" ", $body)."</b>";
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,	$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
		));
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"user_id",
			"target_user_id",
			"add_date",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"user_id",
			"target_user_id",
			"sort_by",
			"sort_order",
		);
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["user_id"])			 	$sql .= " AND `user_id` = ".intval($SF["user_id"])." \r\n";
		if ($SF["target_user_id"])	 	$sql .= " AND `target_user_id` = ".intval($SF["target_user_id"])." \r\n";
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$SF["sort_by"]]."` \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	//-----------------------------------------------------------------------------
	// Filter save method
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
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
		$pheader = t("Manage reputation votes");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
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
