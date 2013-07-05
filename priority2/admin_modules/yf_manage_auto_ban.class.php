<?php

//-----------------------------------------------------------------------------
// Auto-ban management module
class yf_manage_auto_ban {

	// Filter on/off
	public $USE_FILTER				= true;

	//-----------------------------------------------------------------------------
	// Framework constructor
	function _init() {
		main()->USER_ID = $_GET['user_id'];
		// Array of select boxes to process
		$this->_boxes = array(
			"ban_ads"			=> 'radio_box("ban_ads",		$this->_trigger,	$selected, false, 2, "", false)',
			"ban_email"			=> 'radio_box("ban_email",		$this->_trigger,	$selected, false, 2, "", false)',
			"ban_reviews"		=> 'radio_box("ban_reviews",	$this->_trigger,	$selected, false, 2, "", false)',
			"ban_images"		=> 'radio_box("ban_images",		$this->_trigger,	$selected, false, 2, "", false)',
			"ban_forum"			=> 'radio_box("ban_forum",		$this->_trigger,	$selected, false, 2, "", false)',
			"ban_comments"		=> 'radio_box("ban_comments",	$this->_trigger,	$selected, false, 2, "", false)',
			"ban_blog"			=> 'radio_box("ban_blog",		$this->_trigger,	$selected, false, 2, "", false)',
			"ban_bad_contact"	=> 'radio_box("ban_bad_contact",$this->_trigger,	$selected, false, 2, "", false)',
			"ban_reput"			=> 'radio_box("ban_reput",		$this->_trigger,	$selected, false, 2, "", false)',
			"active"			=> 'radio_box("active",			$this->_active,		$selected, false, 2, "", false)',
		);
		$this->_trigger = array(t("<b style='color:green;'>Allowed</b>"), t("<b style='color:red;'>Banned</b>"));
		$this->_active	= array(1 => t("<b style='color:green;'>YES</b>"), 0 => t("<b style='color:red;'>NO</b>"));
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Connect pager
		$sql = "SELECT * FROM ".db('user_ban')."";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Do get data from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"user_name"		=> _prepare_html($A["user_name"]),
				"email"			=> _prepare_html($A["email"]),
				"password"		=> _prepare_html($A["password"]),
				"text"			=> _prepare_html($A["text"]),
				"phone"			=> _prepare_html($A["phone"]),
				"fax"			=> _prepare_html($A["fax"]),
				"url"			=> _prepare_html($A["url"]),
				"recip_url"		=> _prepare_html($A["recip_url"]),
				"ad_text"		=> _prepare_html($A["ad_text"]),
				"forum_text"	=> _prepare_html($A["forum_text"]),
				"comment_text"	=> _prepare_html($A["comment_text"]),
				"email_text"	=> _prepare_html($A["email_text"]),
				"edit_link"		=> "./?object=".__CLASS__."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".__CLASS__."&action=delete&id=".$A["id"],
			);
			$items .= tpl()->parse(__CLASS__."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"add_link"	=> "./?object=".__CLASS__."&action=add",
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Add record
	function add () {
		// Save form
		if (!empty($_POST)) {
			$DATA = $_POST;
			// Do insert data into db
			db()->INSERT("user_ban", array(
				"user_name"			=> _es($DATA["user_name"]),
				"email"				=> _es($DATA["email"]),
				"password"			=> _es($DATA["password"]),
				"text"				=> _es($DATA["text"]),
				"phone"				=> _es($DATA["phone"]),
				"fax"				=> _es($DATA["fax"]),
				"url"				=> _es($DATA["url"]),
				"recip_url"			=> _es($DATA["recip_url"]),
				"ad_text"			=> _es($DATA["ad_text"]),
				"forum_text"		=> _es($DATA["forum_text"]),
				"comment_text"		=> _es($DATA["comment_text"]),
				"email_text"		=> _es($DATA["email_text"]),
				"ban_ads"			=> intval($DATA["ban_ads"]),
				"ban_email"			=> intval($DATA["ban_email"]),
				"ban_reviews"		=> intval($DATA["ban_reviews"]),
				"ban_images"		=> intval($DATA["ban_images"]),
				"ban_forum"			=> intval($DATA["ban_forum"]),
				"ban_comments"		=> intval($DATA["ban_comments"]),
				"ban_blog"			=> intval($DATA["ban_blog"]),
				"ban_bad_contact"	=> intval($DATA["ban_bad_contact"]),
				"ban_reput"			=> intval($DATA["ban_reput"]),
			));
			// Return user back
			return js_redirect("./?object=".__CLASS__);
		}
		// Display form
		$replace = array(
			"for_edit"		=> 0,
			"form_action"	=> "./?object=".__CLASS__."&action=".__FUNCTION__,
			"user_name"		=> _prepare_html($DATA["user_name"]),
			"email"			=> _prepare_html($DATA["email"]),
			"password"		=> _prepare_html($DATA["password"]),
			"text"			=> _prepare_html($DATA["text"]),
			"phone"			=> _prepare_html($DATA["phone"]),
			"fax"			=> _prepare_html($DATA["fax"]),
			"url"			=> _prepare_html($DATA["url"]),
			"recip_url"		=> _prepare_html($DATA["recip_url"]),
			"ad_text"		=> _prepare_html($DATA["ad_text"]),
			"forum_text"	=> _prepare_html($DATA["forum_text"]),
			"comment_text"	=> _prepare_html($DATA["comment_text"]),
			"email_text"	=> _prepare_html($DATA["email_text"]),
			"back_link"		=> "./?object=".__CLASS__,
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, !empty($DATA[$item_name]) ? $DATA[$item_name] : "");
		}
		return tpl()->parse(__CLASS__."/edit_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		// Try ot get ban info
		$ban_info = db()->query_fetch("SELECT * FROM ".db('user_ban')." WHERE id=".intval($_GET["id"]));
		if (empty($ban_info)) {
			return _e("No such record!");
		}
		$DATA = $ban_info;
		foreach ((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $v;
			}
		}
		// Save form
		if (!empty($_POST)) {
			// Do update data
			db()->UPDATE("user_ban", array(
				"user_name"			=> _es($DATA["user_name"]),
				"email"				=> _es($DATA["email"]),
				"password"			=> _es($DATA["password"]),
				"text"				=> _es($DATA["text"]),
				"phone"				=> _es($DATA["phone"]),
				"fax"				=> _es($DATA["fax"]),
				"url"				=> _es($DATA["url"]),
				"recip_url"			=> _es($DATA["recip_url"]),
				"ad_text"			=> _es($DATA["ad_text"]),
				"forum_text"		=> _es($DATA["forum_text"]),
				"comment_text"		=> _es($DATA["comment_text"]),
				"email_text"		=> _es($DATA["email_text"]),
				"ban_ads"			=> intval($DATA["ban_ads"]),
				"ban_email"			=> intval($DATA["ban_email"]),
				"ban_reviews"		=> intval($DATA["ban_reviews"]),
				"ban_images"		=> intval($DATA["ban_images"]),
				"ban_forum"			=> intval($DATA["ban_forum"]),
				"ban_comments"		=> intval($DATA["ban_comments"]),
				"ban_blog"			=> intval($DATA["ban_blog"]),
				"ban_bad_contact"	=> intval($DATA["ban_bad_contact"]),
				"ban_reput"			=> intval($DATA["ban_reput"]),
			), "id=".intval($ban_info["id"]));
			// Return user back
			return js_redirect("./?object=".__CLASS__);
		}
		// Display form
		$replace = array(
			"for_edit"		=> 1,
			"form_action"	=> "./?object=".__CLASS__."&action=".__FUNCTION__."&id=".intval($_GET["id"]),
			"id"			=> intval($DATA["id"]),
			"user_name"		=> _prepare_html($DATA["user_name"]),
			"email"			=> _prepare_html($DATA["email"]),
			"password"		=> _prepare_html($DATA["password"]),
			"text"			=> _prepare_html($DATA["text"]),
			"phone"			=> _prepare_html($DATA["phone"]),
			"fax"			=> _prepare_html($DATA["fax"]),
			"url"			=> _prepare_html($DATA["url"]),
			"recip_url"		=> _prepare_html($DATA["recip_url"]),
			"ad_text"		=> _prepare_html($DATA["ad_text"]),
			"forum_text"	=> _prepare_html($DATA["forum_text"]),
			"comment_text"	=> _prepare_html($DATA["comment_text"]),
			"email_text"	=> _prepare_html($DATA["email_text"]),
			"back_link"		=> "./?object=".__CLASS__,
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, !empty($DATA[$item_name]) ? $DATA[$item_name] : "");
		}
		return tpl()->parse(__CLASS__."/edit_form", $replace);
	}

	//-----------------------------------------------------------------------------
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		// Try ot get ban info
		$ban_info = db()->query_fetch("SELECT * FROM ".db('user_ban')." WHERE id=".intval($_GET["id"]));
		if (empty($ban_info)) {
			return _e("No such record!");
		}
		// Do delete record
		db()->query("DELETE FROM ".db('user_ban')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	//-----------------------------------------------------------------------------
	// Display link for the popup window
	function _popup_link ($user_id = 0) {
		if (is_array($user_id)) {
			$force_text	= $user_id["force_text"];
			$user_id	= $user_id["user_id"];
		}
		if (empty($user_id)) {
			return false;
		}
		if (empty($force_text)) {
			$force_text = "";
		}
		$replace = array(
			"popup_link"	=> "./?object=".__CLASS__."&action=ban_user_popup&id=".$user_id,
			"force_text"	=> $force_text,
		);
		return tpl()->parse(__CLASS__."/popup_link", $replace);
	}

	//-----------------------------------------------------------------------------
	// Popup window
	function ban_user_popup () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "No id";
		}
		$user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id=".intval($_GET["id"]));
		if (empty($user_info)) {
			return "No such user";
		}
		$DATA = $user_info;
		foreach ((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $v;
			}
		}
		// Save
		if (!empty($_POST)) {
			if (!common()->_error_exists()) {
				$admin_info = db()->query_fetch("SELECT * FROM ".db('admin')." WHERE id=".intval($_SESSION["admin_id"]));
				$admin_name = $admin_info["first_name"]." ".$admin_info["last_name"];
				$cur_date	= _format_date(time(), "long");
				// Auto add admin comments
				$NEW_ADMIN_COMMENTS = "";
				$ban_items = array(
					"ban_ads"			=> "Ads",
					"ban_email"			=> "Emails",
					"ban_reviews"		=> "Reviews",
					"ban_images"		=> "Gallery",
					"ban_forum"			=> "Forum posts",
					"ban_comments"		=> "Comments",
					"ban_blog"			=> "Blog posts",
					"ban_bad_contact"	=> "Bad contact reports",
					"ban_reput"			=> "Reputation Vote",
				);
				if (empty($_POST["admin_comments"])) {
					foreach ((array)$ban_items as $_field => $_name) {
						if ($user_info[$_field] != $_POST[$_field]) {
							$_POST["admin_comments"] .= $_name." ".($_POST[$_field] ? "banned" : "allowed")." by ".$admin_name." on ".$cur_date."\r\n";
						}
					}
				}
				// Do update data
				db()->UPDATE("user", array(
					"ban_ads"			=> intval($DATA["ban_ads"]),
					"ban_email"			=> intval($DATA["ban_email"]),
					"ban_reviews"		=> intval($DATA["ban_reviews"]),
					"ban_images"		=> intval($DATA["ban_images"]),
					"ban_forum"			=> intval($DATA["ban_forum"]),
					"ban_comments"		=> intval($DATA["ban_comments"]),
					"ban_blog"			=> intval($DATA["ban_blog"]),
					"ban_bad_contact"	=> intval($DATA["ban_bad_contact"]),
					"ban_reput"			=> intval($DATA["ban_reput"]),
					"active"			=> intval((bool)$DATA["active"]),
					"admin_comments"	=> _es($user_info["admin_comments"].$NEW_ADMIN_COMMENTS),
				), "id=".intval($user_info["id"]));
				// Add admin message
				if (!empty($_POST["admin_message"])) {
					db()->INSERT("admin_messages", array(
						"user_id"	=> intval($user_info["id"]),
						"author_id"	=> intval($_SESSION["admin_id"]),
						"title"		=> _es($_POST["admin_message"]),
						"text"		=> _es($_POST["admin_message"]),
						"time"		=> time(),
					));
				}
				// Display success message
				return common()->show_empty_page(tpl()->parse(__CLASS__."/ban_user_success", $replace), array("close_button" => 1));
			}
		}
		// Display form
		$replace = array_merge((array)$replace, array(
			"form_action"		=> "./?object=".__CLASS__."&action=".__FUNCTION__."&id=".$user_info["id"],
			"error_message"		=> _e(),
			"user_name"			=> _prepare_html(_display_name($user_info)),
			"user_account_link"	=> "./?object=account&user_id=".$user_info["id"],
			"admin_comments"	=> ""/*nl2br(_prepare_html($DATA["admin_comments"]))*/,
			"admin_message"		=> _prepare_html($DATA["admin_message"]),
		));
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $DATA[$item_name]);
		}
		return common()->show_empty_page(tpl()->parse(__CLASS__."/ban_user_popup", $replace), array("close_button" => 1));
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
// TODO
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
// TODO
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
				"name"	=> "Add",
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
		$pheader = t("User auto-ban rules");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"add"					=> "Add user ban",
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
