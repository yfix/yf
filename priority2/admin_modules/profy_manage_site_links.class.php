<?php

//-----------------------------------------------------------------------------
// Links management administration
class profy_manage_site_links {

	var $cur_status		= null;
	var $cur_item_num	= null;
	var $cur_item_id	= null;
	var $cur_link_info	= null;
	var $num_links		= null;
	// Filter session array name
	var $_filter_name = "links_filter";
	// Filter on/off
	var $USE_FILTER = true;

	//-----------------------------------------------------------------------------
	// Constructor
	function _init() {
		$this->USER_ID = intval($_GET["user_id"]);
		// Get user info
		if (!empty($this->USER_ID)) {
			$this->_user_info = db()->query_fetch("SELECT * FROM `".db('links_users')."` WHERE `id`=".$this->USER_ID);
		}
		$this->_boxes = array(
			"link_type"		=> 'radio_box("link_type",	$this->_link_types,		$selected, false, 2, "", false)',
			"site_cat"		=> 'select_box("cat_id",	$this->_site_cats,		$selected, false, 2, "", false)',
			"priority"		=> 'select_box("priority",	$this->_link_priorities,$selected, false, 2, "", false)',
			"status"		=> 'radio_box("status",		$this->_link_statuses,	$selected, false, 2, "", false)',
			"linker"		=> 'select_box("linker",	$this->_linkers,		$selected, false, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",	$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",$this->_sort_orders,	$selected, 0, 2, "", false)',
			"per_page"		=> 'select_box("per_page",	$this->_per_page,		$selected, 0, 2, "", 0)',
			"status2"		=> 'select_box("status2",	$this->_statuses2,		$selected, false, 2, "", false)',
			"cat_id"		=> 'select_box("cat_id",	$this->_site_cats,		$selected, false, 2, "", false)',
			"linkers"		=> 'select_box("linker_id",	$this->_linkers2,		$selected, false, 2, "", false)',
		);
		// Number of records per page
		$this->_per_page = array(
			10	=> 10,
			20	=> 20,
			50	=> 50,
			100	=> 100,
		);
		// Sort fields
		$this->_sort_by = array(
			"",
			"status",
			"linker_id",
			"cat_id",
			"priority"
		);
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Fields in the filter
		$this->_fields_in_filter = array(
			"status2",
			"linker",
			"cat_id",
			"url",		// Site URL
			"link_url",	// Reciprocal URL
			"sort_by",
			"sort_order",
			"per_page",
		);
		// Link priorities array
		$this->_link_priorities	= range(0, 5);
		// Link statuses array
		$this->_link_statuses	= array(
			0 => "New",
			1 => "Waiting",
			2 => "Active",
			3 => "Updated",
			4 => "Suspended",
			5 => "Outdated"
		);
		$this->_statuses2 = array_merge(array(" " => "All"), $this->_link_statuses);
		// Link types array
		$this->_link_types	= array(
			"Text",
			"Banner"
		);
		// Sites categories	
		$this->_site_cats[" "] = "All";
		// Get faqs categories
		$this->CATS_OBJ			= &main()->init_class("cats", "classes/");
		$this->CATS_OBJ->_default_cats_block = "links_cats";
		$this->_site_cats_items	= $this->CATS_OBJ->_get_items_array();
		$this->_site_cats		= $this->CATS_OBJ->_prepare_for_box("", 0);
		// Get linkers
		$this->_linkers[" "]	= "All";
		$this->_linkers2[" "]	= "  ";
		$Q = db()->query("SELECT * FROM `".db('admin')."` WHERE `group`=2");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_linkers[$A["id"]]	= $A["first_name"]." ".$A["last_name"];
			$this->_linkers2[$A["id"]]	= $A["first_name"]." ".$A["last_name"];
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Calling function to divide records per pages
		$sql = "SELECT * FROM `".db('links_links')."` ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `add_date` DESC ";

		$per_page = $_SESSION[$this->_filter_name]["per_page"] ? intval($_SESSION[$this->_filter_name]["per_page"]) : intval(conf('admin_per_page'));

		list($add_sql, $pages, $num_items) = common()->divide_pages($sql, "./?object=".$_GET["object"],"blocks",$per_page);
		// Process records
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$links[$A["id"]] = $A;
		}
		// Get users infos
		foreach ((array)$links as $A) {
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		if (is_array($users_ids)) {
			$Q = db()->query("SELECT * FROM `".db('links_users')."` WHERE `id` IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_info[$A["id"]] = $A;
		}
		// Process records
		foreach ((array)$links as $A) {
			$link_urls = array();
			if (!empty($A["link_url"])) {
				$_tmp_links = explode(" ", trim(str_replace(array("\r","\n"), " ", $A["link_url"])));
			}
			foreach ((array)$_tmp_links as $k => $v) {
				if (empty($v)) {
					continue;
				}
				$link_urls[]["url"] = trim($v);
			}
			$replace2 = array(
				"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
				"site_title"		=> $A["title"],
				"user_name"			=> _display_name($users_info[$A["user_id"]]),
				"email"				=> $users_info[$A["user_id"]]["email"],
				"add_date"			=> _format_date($A["email1_time"]),
				"status"			=> $this->_link_statuses[$A["status"]],
				"link_type"			=> $this->_link_types[$A["type"]],
				"category"			=> $this->_site_cats[$A["cat_id"]],
				"priority"			=> $A["priority"],
				"site_url"			=> $A["url"],
				"link_urls"			=> $link_urls,
				"user_account_url"	=> "./?object=".$_GET["object"]."&action=account&user_id=".$A["user_id"],
				"edit_url"			=> "./?object=".$_GET["object"]."&action=admin_edit_link&id=".$A["id"]._add_get(),
				"delete_url"		=> "./?object=".$_GET["object"]."&action=delete_link&id=".$A["id"]._add_get(),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"users_list_url"=> "./?object=".$_GET["object"]."&action=users_list",
			"num_items"		=> intval($num_items),
			"items"			=> $items,
			"pages"			=> $pages,
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show link editing form
	function admin_edit_link () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			common()->_raise_error("No ID!");
			return _e();
		}
		// Try to get link detailed info
		$A = db()->query_fetch("SELECT * FROM `".db('links_links')."` WHERE `id`=".$_GET["id"]);
		if (empty($A["id"])) $body = "Wrong Link ID!";
		else {
			$this->cur_item_id = $A["id"];
			$this->cur_link_info = $A;
			// Show link editing form
			$body = $this->_edit_link($A);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	//
	function _edit_link ($link_info = array()) {
		if (!empty($link_info["id"])) {
			$user_info = db()->query_fetch("SELECT * FROM `".db('links_users')."` WHERE `id`=".$link_info["user_id"]);
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=update_link&id=".$link_info["id"],
				"users_list_url"	=> "./?object=".$_GET["object"]."&action=users_list",
				"user_account_url"	=> "./?object=".$_GET["object"]."&action=account&user_id=".$user_info["id"],
				"user_name"			=> _display_name($user_info),
				"email"				=> $user_info["email"],
				"password"			=> $user_info["password"],
				"add_date"			=> _format_date($link_info["email1_time"]),
				"site_title"		=> $link_info["title"],
				"site_url"			=> $link_info["url"],
				"link_url"			=> $link_info["link_url"],
				"banner_url"		=> $link_info["banner_url"],
				"desc"				=> $link_info["description"],
				"link_type_box"		=> $this->_box("link_type", $link_info["type"]),
				"site_cat_box"		=> $this->_box("site_cat", $link_info["cat_id"]),
				"site_added_to_box"	=> $this->_show_links_sites($link_info),
				"priority_box"		=> $this->_box("priority", $link_info["priority"]),
				"status_box"		=> $this->_box("status", $link_info["status"]),
				"delete_url"		=> "./?object=".$_GET["object"]."&action=delete_link&id=".$link_info["id"],
			);
			$body = tpl()->parse($_GET["object"]."/edit_link_main", $replace);
		} else $body = "No links";
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Main user account
	function account () {
		// Get user links
		$Q = db()->query("SELECT * FROM `".db('links_links')."` WHERE `user_id`=".$this->USER_ID);
		while ($A = db()->fetch_assoc($Q)) $links[$A["id"]] = $A;
		// Process links
		foreach ((array)$links as $link_id => $A) {
			$replace2 = array(
				"num"			=> ++$i,
				"site_title"	=> $A["title"],
				"cat_name"		=> $this->_site_cats[$A["cat_id"]],
				"type"			=> $this->_link_types[$A["type"]],
				"site_url"		=> $A["url"],
				"link_url"		=> $A["link_url"],
				"status"		=> $this->_link_statuses[$A["status"]],
				"add_date"		=> _format_date($A["email1_time"]),
				"edit_url"		=> "./?object=".$_GET["object"]."&action=edit_link&id=".$A["id"]._add_get(),
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete_link&id=".$A["id"]._add_get(),
			);
			$items .= tpl()->parse($_GET["object"]."/account_item", $replace2);
		}
		$replace = array(
			"add_site_url"	=> "./?object=".$_GET["object"]."&action=add_link"._add_get(),
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/account_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show edit form
	function edit_link () {
		return $this->admin_edit_link();
	}

	//-----------------------------------------------------------------------------
	// Show add form
	function add_link () {
		$user_info = &$this->_user_info;
		// Process main template
		$replace = array(
			"banners_url"		=> "./?object=banners"._add_get(),
			"site_list_url"		=> "./?object=".$_GET["object"]."&action=account"._add_get(),
			"form_action"		=> "./?object=".$_GET["object"]."&action=insert_link"._add_get(),
			"add_date"			=> _format_date($link_info["email1_time"]),
			"site_title"		=> $link_info["title"],
			"site_url"			=> $link_info["url"],
			"link_url"			=> $link_info["link_url"],
			"banner_url"		=> $link_info["banner_url"],
			"desc"				=> $link_info["description"],
			"link_type_box"		=> $this->_box("link_type", $link_info["type"]),
			"site_cat_box"		=> $this->_box("site_cat", $link_info["cat_id"]),
			"sites_box"			=> $this->_show_links_sites($link_info),
		);
		return tpl()->parse($_GET["object"]."/add_link", $replace);
	}

	//-----------------------------------------------------------------------------
	// Delete link
	function delete_link () {
		$_GET["id"]		= intval($_GET["id"]);
		if ($_GET["id"]) {
			db()->query("DELETE FROM `".db('links_links')."` WHERE `id`=".$_GET["id"]." LIMIT 1");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	//-----------------------------------------------------------------------------
	// Update links
	function update_link () {
		$_GET["id"]			= intval($_GET["id"]);
		$_POST["cat_id"]	= intval($_POST["cat_id"]);
		// Get link info
		if ($_GET["id"]) $link_info = db()->query_fetch("SELECT * FROM `".db('links_links')."` WHERE `id`=".$_GET["id"]);
		if ($link_info["id"]) {
			// Get category info
			$cat_info = $this->_site_cats_items[$_POST["cat_id"]];
			// Ge user info
			$user_info = db()->query_fetch("SELECT * FROM `".db('links_users')."` WHERE `id`=".$link_info["user_id"]);
			// Process banner
			if (!empty($_POST["banner_url"]) && $_POST["get_banner"]) {
				preg_match('#\.(jpg|jpeg|gif|png)$#i', $url, $ext);
				$new_file = uniqid('img') . "." . $ext['1'];
				$new_file_path = SITE_UPLOADS_DIR."banners/".$new_file;
				$fp1 = fopen($_POST["banner_url"], "r") or trigger_error("Unable to open remote file ".$_POST["banner_url"]." for reading", E_USER_WARNING);
				$fp2 = fopen(INCLUDE_PATH.$new_file_path, "w") or trigger_error("Unable to open local file for writing");
				while (!feof($fp1)) {
					$line = fgets($fp1, 1024);
					fputs($fp2, $line, strlen($line));
				} 
				fclose($fp1);
				fclose($fp2);
				chmod(INCLUDE_PATH.$new_file_path, 0777);
				// If upload is ok - chenge banner url to new one
				if (file_exists(INCLUDE_PATH.$new_file_path) && filesize(INCLUDE_PATH.$new_file_path) > 10) {
					$_POST["banner_url"] = SITE_ADVERT_URL.$new_file_path;
				}
			}
			// Process sites
			$Q = db()->query("SELECT * FROM `".db('links_sites')."`");
			while ($A = db()->fetch_array($Q)) {
				$_POST["site"][$A["id"]] = intval($_POST["site"][$A["id"]]);
		        if (!empty($_POST["site"][$A["id"]])) {
					$site_names .= $A["title"]." - ".$A["url"]."\r\n";
				}
			}
			// Generate sites SQL
			for ($i = 1; $i <= 30; $i++) {
				$sites_sql_array[$i] = "\r\n `site".$i."` = ".intval($_POST["site"][$i])." ";
			}
			// Generate SQL
			$sql = "UPDATE `".db('links_links')."` SET 
					`cat_id`		= ".intval($_POST["cat_id"]).",
					`status`		= ".intval($_POST["status"]).",
					`title`			= '"._es($_POST["title"])."',
					`url`			= '"._es($_POST["url"])."',
					`link_url`		= '"._es($_POST["link_url"])."',
					`banner_url`	= '"._es($_POST["banner_url"])."',
					`description`	= '"._es($_POST["description"])."',
					`type`			= ".intval($_POST["link_type"]).",
					`priority`		= ".intval($_POST["priority"]).",
					".implode(",", $sites_sql_array)."
				 WHERE `id`=".$_GET["id"];
			db()->query($sql);
			// If link is approved - then send email to the user
			if ($_POST["status"] == 2 && $link_info["status"] != 2) {
				if ($cat_info["id"] && $user_info["id"]) {
					$text = tpl()->parse($_GET["object"]."/email_approve", array("site_names" => $site_names));
					$email_to	= $user_info["email"];
					$name_to	= _display_name($user_info);
					common()->send_mail(SITE_ADMIN_EMAIL_LINKS, SITE_ADVERT_NAME, $email_to, $name_to, "Your Link Has Been Approved!", $text, nl2br($text));
				}
			}
			js_redirect("./?object=".$_GET["object"]);
		} else {
			common()->_raise_error("Wrong link ID");
			return _e();
		}
	}

	//-----------------------------------------------------------------------------
	// Show users
	function users_list () {
		$i = 0;
		$sql = "SELECT * FROM `".db('links_users')."` ORDER BY `time` DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"name"		=> _display_name($A),
				"email"		=> $A["email"],
				"password"	=> $A["password"],
				"date"		=> _format_date($A["time"]),
				"login_url"	=> "./?object=".$_GET["object"]."&action=account&user_id=".$A["id"],
				"edit_url"	=> "./?object=".$_GET["object"]."&action=edit_user&user_id=".$A["id"],
				"del_url"	=> "./?object=".$_GET["object"]."&action=delete_user&user_id=".$A["id"],
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
			);
			$items .= tpl()->parse($_GET["object"]."/user_list_item", $replace2);
		}
		$replace = array(
			"items" => $items,
			"pages"	=> $pages,
			"total"	=> $total,
		);
		return tpl()->parse($_GET["object"]."/user_list_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show user account info
	function user_account () {
	}

	//-----------------------------------------------------------------------------
	// Edit user info form
	function edit_user () {
		if ($this->USER_ID) {
			$replace = array(
				"form_action"	=> "./?object=".$_GET["object"]."&action=update_user"._add_get(),
				"name"			=> _display_name($this->_user_info),
				"email"			=> $this->_user_info["email"],
				"password"		=> $this->_user_info["password"],
				"linkers_box"	=> $this->_box("linkers", $this->_user_info["linker_id"]),
				"back_url"		=> "./?object=".$_GET["object"]."&action=users_list",
			);
			$body = tpl()->parse($_GET["object"]."/edit_user", $replace);
		} else $body = "No user ID";
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Update user account
	function update_user () {
		if (!empty($this->_user_info["id"])) {
			foreach ((array)$_POST as $k => $v) $_POST[$k] = _es($v);
			$sql = "UPDATE `".db('links_users')."` SET
					`name`		= '".$_POST["name"]."',
					`password`	= '".$_POST["password"]."',
					`email`		= '".$_POST["email"]."',
					`linker_id`	= ".intval($_POST["linker_id"])."
				WHERE `id`=".intval($this->_user_info["id"]);
			db()->query($sql);
		}
		js_redirect($_SERVER["HTTP_REFERER"]);
	}

	//-----------------------------------------------------------------------------
	// Delete user account
	function delete_user () {
		if (!empty($this->_user_info["id"])) {
			db()->query("DELETE FROM `".db('links_links')."` WHERE `user_id`=".intval($this->_user_info["id"]));
			db()->query("DELETE FROM `".db('links_users')."` WHERE `id`=".intval($this->_user_info["id"])." LIMIT 1");
		}
		js_redirect($_SERVER["HTTP_REFERER"]);
	}

	//-----------------------------------------------------------------------------
	// Process banners file from remote image
	function _get_remote_image() {
		if (!empty($_POST["banner_url"]) && $_POST["get_banner"]) {
			preg_match('#\.(jpg|jpeg|gif|png)$#i', $url, $ext);
			$new_file = uniqid('img') . "." . $ext['1'];
			$new_file_path = SITE_UPLOADS_DIR."banners/".$new_file;
			$fp1 = fopen($_POST["banner_url"], "r") or trigger_error("Unable to open remote file ".$_POST["banner_url"]." for reading", E_USER_WARNING);
			$fp2 = fopen($new_file_path, "w") or trigger_error("Unable to open local file for writing");
			while (!feof($fp1)) {
				$line = fgets($fp1, 1024);
				fputs($fp2, $line, strlen($line));
			} 
			fclose($fp1);
			fclose($fp2);
			chmod($new_file_path, 0777);
			// If upload is ok - chenge banner url to new one
			if (file_exists($new_file_path) && filesize($new_file_path) > 10) {
				return $_POST["banner_url"] = SITE_ADVERT_URL.$new_file_path;
			}
		}
		return false;
	}

	//-----------------------------------------------------------------------------
	// Verify posted data
	function _verify_link_post() {
		// Check required data
		if (strlen($_POST["title"]) < 3) {
			common()->_raise_error("Site title is too short!");
		}
		if (strlen($_POST["title"]) > 50) {
			common()->_raise_error("Site title is too long! Maximum 50 characters allowed!");
		}
		if (strlen($_POST["url"]) < 10) {
			common()->_raise_error("Site URL is too short!");
		}
		if (strlen($_POST["url"]) > 250) {
			common()->_raise_error("Site URL is too long!");
		}
		if (!common()->url_verify($_POST["url"])) {
			common()->_raise_error("Invalid site URL!");
		}
		if (strlen($_POST["link_url"]) < 10) {
			common()->_raise_error("Link URL is too short!");
		}
		if (strlen($_POST["link_url"]) > 250) {
			common()->_raise_error("Link URL is too long!");
		}
		if (!common()->url_verify($_POST["link_url"])) {
			common()->_raise_error("Invalid link URL syntax!");
		}
		if (!$_POST["banner_url"] && $_POST["type"] == 1) {
			common()->_raise_error("Banner URL is too short!");
		}
		if (strlen($_POST["banner_url"]) > 250 && $_POST["type"] == 1) {
			common()->_raise_error("Banner URL is too long!");
		}
		if ($_POST["banner_url"] == "http://") {
			$_POST["banner_url"] = "";
		} elseif (!common()->url_verify($_POST["banner_url"]) && $_POST["type"] == 1) {
			common()->_raise_error("Invalid banner URL!");
		}
		if (!$_POST["description"] && $_POST["type"] == 0) {
			common()->_raise_error("Site description is too short!");
		} elseif (strlen($_POST["description"]) > 512 && $_POST["type"] == 0) {
			common()->_raise_error("Site description is too long!");
		}
		// Clean up description
		$_POST["description"] = str_replace("'", "&#39;", htmlspecialchars(strip_tags($_POST["description"])));
	}

	//-----------------------------------------------------------------------------
	// Show sites
	function _show_links_sites ($link_info = array()) {
		$items = "";
		$Q = db()->query("SELECT * FROM `".db('links_sites')."`");
		while ($A = db()->fetch_array($Q)) {
			$replace = array(
				"site_id"		=> $A["id"],
				"check"			=> !empty($link_info["site".$A["id"]]) ? "checked" : "",
				"site_url"		=> $A["url"],
				"site_name"		=> $A["title"],
				"links_url"		=> $A["links_url"],
				"banners_url"	=> $A["banner_url"],
			);
			$items .= tpl()->parse($_GET["object"]."/sites_item", $replace);
		}
		return $items;
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$LF = &$_SESSION[$this->_filter_name];
		foreach ((array)$LF as $k => $v) $LF[$k] = trim($v);
		// Generate filter for the common fileds
		if (strlen($LF["status2"]))		$sql .= " AND `status` = ".intval($LF["status2"])." \r\n";
		if ($LF["linker"])		$sql .= " AND `linker_id` = ".intval($LF["linker"])." \r\n";
		if ($LF["cat_id"])		$sql .= " AND `cat_id` = ".intval($LF["cat_id"])." \r\n";
		if ($LF["url"])			$sql .= " AND `url` LIKE '%"._es($LF["url"])."%' \r\n";
		if ($LF["link_url"])	$sql .= " AND `link_url` LIKE '%"._es($LF["link_url"])."%' \r\n";
		// Sorting here
		if ($LF["sort_by"])		$sql .= " ORDER BY `".$this->_sort_by[$LF["sort_by"]]."` \r\n";
		if ($LF["sort_by"] && strlen($LF["sort_order"])) 	$sql .= " ".$LF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter form stored in $_SESSION[$this->_filter_name][...]
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
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Manage site links");
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
//-----------------------------------------------------------------------------
