<?php

//-----------------------------------------------------------------------------
// Comments management module
class yf_manage_articles extends yf_module {

	/** @var int Text preview cutoff */
	var $TEXT_PREVIEW_LENGTH	= 200;
	/** @var bool Filter on/off */
	var $USE_FILTER				= true;
	/** @var bool */
	var $USE_BB_CODES			= true;
	/** @var array Params for the comments */
	var $_comments_params			= array(
		"return_action" => "view",
		"object_name"	=> "articles",
	);

	//-----------------------------------------------------------------------------
	// Constructor
	function yf_manage_articles() {
		$this->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Array of boxes
		$this->_boxes = array(
			"cat_id"	=> 'select_box("cat_id", $this->_articles_cats, $selected, false, 2, "style=\"width:100%;\"", false)',
		);
		// Array of available article statuses
		$this->_articles_statuses = array(
			"new"		=> t("new"),
			"edited"	=> t("edited"),
			"suspended"	=> t("suspended"),
			"active"	=> t("active"),
		);
		// Prepare categories
		$this->CATS_OBJ = main()->init_class("cats", "classes/");
		$this->CATS_OBJ->_default_cats_block = "articles_cats";
		$this->_articles_cats	= $this->CATS_OBJ->_prepare_for_box();
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Calling function to divide records per pages
		$sql = "SELECT SQL_CALC_FOUND_ROWS `id` FROM `".db('articles_texts')."` ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `add_date` DESC ";
		// Add current page limit
		$per_page = conf('admin_per_page');
		$first_record = intval(($_GET["page"] ? $_GET["page"] - 1 : 0) * $per_page);
		if ($first_record < 0) {
			$first_record = 0;
		}
		$sql .= " LIMIT ".intval($first_record).",".intval($per_page);
		// Get ids
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$articles_ids[$A["id"]] = $A["id"];
		}
		unset($users_ids[""]);
		// Prepare pages
		if (!empty($articles_ids)) {
			list($num_records) = db()->query_fetch("SELECT FOUND_ROWS() AS `0`", false);
			list(, $pages, ) = common()->divide_pages("", "./?object=".$_GET["object"], "", $per_page, $num_records);
			// Do get details
			$Q = db()->query("SELECT * FROM `".db('articles_texts')."` WHERE `id` IN(".implode(",", $articles_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$articles_array[$A["id"]] = $A;
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		// Get users infos
		if (!empty($users_ids)) {
			$sql2 = "SELECT `id`,`name`,`nick`,`login`,`email`,`ip`,`add_date`,`photo_verified` FROM `".db('user')."` WHERE `id` IN(".implode(",", $users_ids).")";
			$Q = db()->query($sql2);
			while ($A = db()->fetch_assoc($Q)) $users_array[$A["id"]] = $A;
		}
		// Process articles
		foreach ((array) $articles_ids as $article_id) {
			$article_info = $articles_array[$article_id];
			$user_info = $users_array[$article_info["user_id"]];
			$author_name = !empty($article_info["author_name"]) ? $article_info["author_name"] : _display_name($user_info);
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($article_info["id"]),
				"title"			=> _prepare_html(substr($article_info["title"], 0, $this->TEXT_PREVIEW_LENGTH)),
				"summary"		=> _prepare_html(substr($article_info["summary"], 0, $this->TEXT_PREVIEW_LENGTH)),
				"full_text"		=> _prepare_html(substr($article_info["full_text"], 0, $this->TEXT_PREVIEW_LENGTH)),
				"add_date"		=> _format_date($article_info["add_date"], "long"),
				"status"		=> $this->_articles_statuses[$article_info["status"]],
				"ip"			=> _prepare_html($article_info["ip"]),
				"user_id"		=> intval($user_info["id"]),
				"user_name"		=> _prepare_html(_display_name($user_info)),
				"user_login"	=> _prepare_html($user_info["login"]),
				"user_nick"		=> _prepare_html($user_info["nick"]),
				"user_email"	=> _prepare_html($user_info["email"]),
				"user_avatar"	=> _show_avatar($user_info["id"], $user_info, 1),
				"author_name"	=> _prepare_html($author_name),
				"profile_url"	=> $user_info["profile_url"],
				"member_url"	=> $user_info["id"] ? "./?object=account&action=show&user_id=".$user_info["id"] : "",
				"group_name"	=> $this->_account_types[$user_info["group"]],
				"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$article_info["id"],
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$article_info["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$article_info["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate&id=".$article_info["id"],
				"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($article_info["user_id"])),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"total"		=> intval($num_records),
			"items"		=> $items,
			"pages"		=> $pages,
			"filter"	=> $this->USE_FILTER ? $this->_show_filter() : "",
			"add_link"	=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id"));
		}
		// Try to get record info
		$article_info = db()->query_fetch("SELECT * FROM `".db('articles_texts')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($article_info)) {
			return _e(t("No such record"));
		}
		// Try to get given user info
		if (!empty($article_info["user_id"])) {
			$user_info = db()->query_fetch("SELECT `id`,`name`,`nick` FROM `".db('user')."` WHERE `id`=".intval($article_info["user_id"]));
		}
		// Check posted data and save
		if (count($_POST) > 0) {
			// Check for errors
			if (!common()->_error_exists()) {
				// Add activity points to user when approved
				if (!empty($article_info["user_id"]) && $_POST["status"] == "active") {
					$RECORD_ID	= $_GET["id"];
					$act_name	= $_POST["is_own_article"] ? "article_posted" : "article_reposted";
					common()->_add_activity_points($article_info["user_id"], $act_name, strlen($_POST["full_text"]), $RECORD_ID);
				}
			}
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("articles_texts", array(
					"cat_id"		=> intval($_POST["cat_id"]),
					"author_name"	=> _es($_POST["author_name"]),
					"user_id"		=> _es($_POST["author_id"]),
					"is_own_article"=> intval($_POST["is_own_article"]),
					"title"			=> _es($_POST["title"]),
					"summary"		=> _es($_POST["summary"]),
					"full_text"		=> _es($_POST["full_text"]),
					"credentials"	=> _es($_POST["credentials"]),
					"edit_date"		=> time(),
					"status"		=> _es($_POST["status"]),
				), "`id`=".intval($_GET["id"]));
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		foreach ((array)$article_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Cleanup data arrays
		unset($this->_articles_cats[" "]);
		unset($this->_articles_statuses2[" "]);
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"for_edit"		=> 1,
				"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"	=> _e(),
				"statuses_box"	=> $this->_box("status", $DATA["status"]),
				"cats_box"		=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"		=> _prepare_html($this->_articles_cats[$DATA["cat_id"]]),
				"author_name"	=> _prepare_html($DATA["author_name"], 0),
				"author_id"		=> intval($DATA["user_id"]),
				"is_own_article"=> intval((bool)$DATA["is_own_article"]),
				"title"			=> _prepare_html($DATA["title"], 0),
				"summary"		=> _prepare_html($DATA["summary"], 0),
				"full_text"		=> _prepare_html($DATA["full_text"], 0),
				"credentials"	=> _prepare_html($DATA["credentials"], 0),
				"views"			=> intval($DATA["views"]),
				"status"		=> $this->_articles_statuses[$DATA["status"]],
				"add_date"		=> !empty($DATA["add_date"]) ? _format_date($DATA["add_date"], "long") : "",
				"edit_date"		=> !empty($DATA["edit_date"]) ? _format_date($DATA["edit_date"], "long") : "",
				"members_link"	=> "./?object=members",
				"edit_cats_link"=> "./?object=category_editor&action=show_items&id=".$this->CATS_OBJ->_get_cat_id_by_name(),
				"back_link"		=> "./?object=".$_GET["object"],
				"ban_popup_link"=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($article_info["user_id"])),
			);
			return tpl()->parse($_GET["object"]."/edit", $replace);
		}
	}

	//-----------------------------------------------------------------------------
	// Add record
	function add () {
		// Check posted data and save
		if (!empty($_POST)) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("articles_texts", array(
					"cat_id"		=> intval($_POST["cat_id"]),
					"author_name"	=> _es($_POST["author_name"]),
					"user_id"		=> _es($_POST["author_id"]),
					"is_own_article"=> intval($_POST["is_own_article"]),
					"title"			=> _es($_POST["title"]),
					"summary"		=> _es($_POST["summary"]),
					"full_text"		=> _es($_POST["full_text"]),
					"credentials"	=> _es($_POST["credentials"]),
					"add_date"		=> time(),
					"status"		=> _es($_POST["status"]),
				));
				$RECORD_ID	= db()->INSERT_ID();
				if (!empty($RECORD_ID) && $_POST["status"] == "active") {
					$act_name	= $_POST["is_own_article"] ? "article_posted" : "article_reposted";
					common()->_add_activity_points($article_info["user_id"], $act_name, strlen($_POST["full_text"]), $RECORD_ID);
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		$DATA = &$_POST;
		// Cleanup data arrays
		unset($this->_articles_cats[" "]);
		unset($this->_articles_statuses2[" "]);
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"for_edit"		=> 0,
				"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"	=> _e(),
				"statuses_box"	=> $this->_box("status", $DATA["status"]),
				"cats_box"		=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"		=> _prepare_html($this->_articles_cats[$DATA["cat_id"]]),
				"author_name"	=> _prepare_html($DATA["author_name"], 0),
				"author_id"		=> intval($DATA["user_id"]),
				"is_own_article"=> intval((bool)$DATA["is_own_article"]),
				"title"			=> _prepare_html($DATA["title"], 0),
				"summary"		=> _prepare_html($DATA["summary"], 0),
				"full_text"		=> _prepare_html($DATA["full_text"], 0),
				"credentials"	=> _prepare_html($DATA["credentials"], 0),
				"views"			=> intval($DATA["views"]),
				"status"		=> $this->_articles_statuses[$DATA["status"]],
				"add_date"		=> "",
				"edit_date"		=> "",
				"members_link"	=> "./?object=members",
				"edit_cats_link"=> "./?object=category_editor&action=show_items&id=".$this->CATS_OBJ->_get_cat_id_by_name(),
				"back_link"		=> "./?object=".$_GET["object"],
			);
			return tpl()->parse($_GET["object"]."/edit", $replace);
		}
	}

	/**
	* View single artcile 
	*/
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get article info
		$article_info = db()->query_fetch("SELECT * FROM `".db('articles_texts')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($article_info)) {
			return _e(t("No such article!"));
		}
		$IS_OWN_ARTICLE = true;
		// Do get author info
		$author_info = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `id`=".intval($article_info["user_id"]));
		$author_name = !empty($article_info["author_name"]) ? $article_info["author_name"] : _display_name($author_info);
		// Process template
		$replace = array(
			"id"				=> intval($article_info["id"]),
			"user_id"			=> intval($article_info["user_id"]),
			"user_name"			=> _prepare_html(_display_name($author_info)),
			"author_name"		=> _prepare_html($author_name),
			"user_profile_link"	=> $article_info["user_id"] ? _profile_link($article_info["user_id"]) : "",
			"user_avatar"		=> _show_avatar($article_info["user_id"], $author_info),
			"cat_name"			=> _prepare_html($this->CATS_OBJ->_get_item_name($article_info["cat_id"])),
			"cat_link"			=> "./?object=".$_GET["object"]."&action=search&q=results&cat_id=".$article_info["cat_id"],
			"title"				=> _prepare_html($article_info["title"]),
			"summary"			=> _prepare_html($article_info["summary"]),
			"full_text"			=> $this->_format_text($article_info["full_text"]),
			"credentials"		=> _prepare_html($article_info["credentials"]),
			"add_date"			=> _format_date($article_info["add_date"], "long"),
			"edit_date"			=> _format_date($article_info["edit_date"], "long"),
			"views"				=> intval($article_info["views"]),
			"status"			=> $this->_articles_statuses[$article_info["status"]],
			"edit_link"			=> $IS_OWN_ARTICLE ? "./?object=".$_GET["object"]."&action=edit&id=".$article_info["id"] : "",
			"delete_link"		=> $IS_OWN_ARTICLE ? "./?object=".$_GET["object"]."&action=delete&id=".$article_info["id"] : "",
			"comments"			=> $this->_view_comments(),
			"ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", "user_id=".intval($article_info["user_id"])),
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	//-----------------------------------------------------------------------------
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Get article info
		if (!empty($_GET["id"])) {
			$article_info = db()->query_fetch("SELECT * FROM `".db('articles_texts')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete record
		if (!empty($article_info)) {
			db()->query("DELETE FROM `".db('articles_texts')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"status"		=> 'select_box("status",		$this->_articles_statuses2,	$selected, 0, 2, "", false)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,		$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
		));
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Get user account type
		$this->_articles_statuses2[" "]	= t("-- All --");
		foreach ((array)$this->_articles_statuses as $k => $v) {
			$this->_articles_statuses2[$k]	= $v;
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"id",
			"cat_id",
			"user_id",
			"add_date",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"id_min",
			"id_max",
			"date_min",
			"date_max",
			"nick",
			"user_id",
			"title",
			"summary",
			"text",
			"account_type",
			"status",
			"cat_id",
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
		if ($SF["id_min"]) 				$sql .= " AND `id` >= ".intval($SF["id_min"])." \r\n";
		if ($SF["id_max"])			 	$sql .= " AND `id` <= ".intval($SF["id_max"])." \r\n";
		if ($SF["date_min"]) 			$sql .= " AND `add_date` >= ".strtotime($SF["date_min"])." \r\n";
		if ($SF["date_max"])			$sql .= " AND `add_date` <= ".strtotime($SF["date_max"])." \r\n";
		if ($SF["user_id"])			 	$sql .= " AND `user_id` = ".intval($SF["user_id"])." \r\n";
		if ($SF["cat_id"])			 	$sql .= " AND `cat_id` = ".intval($SF["cat_id"])." \r\n";
		if (strlen($SF["title"]))		$sql .= " AND `title` LIKE '"._es($SF["title"])."%' \r\n";
		if (strlen($SF["summary"]))		$sql .= " AND `summary` LIKE '"._es($SF["summary"])."%' \r\n";
		if (strlen($SF["text"]))		$sql .= " AND `full_text` LIKE '"._es($SF["text"])."%' \r\n";
		if (!empty($SF["status"]) && isset($this->_articles_statuses[$SF["status"]])) {
		 	$sql .= " AND `status` = '"._es($SF["status"])."' \r\n";
		}
		if (strlen($SF["nick"]) || strlen($SF["account_type"])) {
			if (strlen($SF["nick"])) 	$users_sql .= " AND `nick` LIKE '"._es($SF["nick"])."%' \r\n";
			if ($SF["account_type"])	$users_sql .= " AND `group` = ".intval($SF["account_type"])." \r\n";
		}
		// Add subquery to users table
		if (!empty($users_sql)) {
			$sql .= " AND `user_id` IN( SELECT `id` FROM `".db('user')."` WHERE 1=1 ".$users_sql.") \r\n";
		}
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
	* Format given text (convert BB Codes, new lines etc)
	*/
	function _format_text ($body) {
		// Stop here if text is empty
		if (empty($body)) return "";
		// If special code is "on" - process it
		if ($this->USE_BB_CODES) {
			$BB_CODES_OBJ = &main()->init_class("bb_codes", "classes/");
			// We cannot die, need to be safe
			if (is_object($BB_CODES_OBJ)) {
				$body = $BB_CODES_OBJ->_process_text($body);
			} else {
				$body = nl2br(_prepare_html($body, 0));
			}
		} else {
			$body = nl2br(_prepare_html($body, 0));
		}
		return $body;
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
				"name"	=> "Add article",
				"url"	=> "./?object=".$_GET["object"]."&action=add",
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
		$pheader = t("Articles");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"add"					=> "Add new article",
			"edit"					=> "Edit article",
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
