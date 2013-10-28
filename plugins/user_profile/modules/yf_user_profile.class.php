<?php

//-----------------------------------------------------------------------------
// User profile handling module
class yf_user_profile extends yf_module {

	/** @var int */
	public $MAX_SHOW_ADS			= 10;
	/** @var int */
	public $MAX_SHOW_REVIEWS		= 10;
	/** @var int */
	public $MAX_SHOW_USER_REVIEWS	= 10;
	/** @var int */
	public $MAX_SHOW_FORUM_POSTS	= 10;
	/** @var int */
	public $MAX_SHOW_ARTICLES		= 10;
	/** @var int */
	public $MAX_SHOW_BLOG_POSTS	= 10;
	/** @var int */
	public $MAX_SHOW_GALLERY_PHOTO	= 10;
	/** @var int */
	public $MAX_SHOW_COMMENTS		= 10;
	/** @var int */
	public $MAX_SHOW_FRIEND_OF		= 10;
	/** @var int */
	public $MAX_SHOW_FRIENDS		= 10;
	/** @var int */
	public $ADS_TEXT_PREVIEW_LENGTH=250;

	//-----------------------------------------------------------------------------
	// Module constructor
	function _init () {
		if (!empty($_SESSION["edit_escort_id"])) {
			// Do cache
			if (!isset($GLOBALS['_agency_info'])) {
				$agencies_infos = main()->get_data("agencies");
				$GLOBALS['_agency_info'] = $agencies_infos[$_SESSION["user_id"]];
				unset($agencies_infos);
			}
			if (!empty($GLOBALS['_agency_info'])) {
				$this->_agency_info = &$GLOBALS['_agency_info'];
			}
			main()->USER_ID		= $_SESSION["edit_escort_id"];
			main()->USER_GROUP	= 3;
		} else {
			main()->USER_ID		= main()->USER_ID;
			main()->USER_GROUP	= main()->USER_GROUP;
		}
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		if ($_GET["action"] == "show") {
			// Try to get user info
			$this->_get_user_info();
		}
		// Init friends module
		$this->FRIENDS_OBJ = main()->init_class("friends");
		// Params for the comments
		$this->_comments_params = array(
			"return_action" => "show",
			"object_id"		=> intval($this->_user_info["id"]),
		);
		// Array of dynamic info
		if (main()->USER_INFO_DYNAMIC) {
			$sql = "SELECT * FROM ".db('user_data_info_fields')." WHERE active=1 ORDER BY `order`, name";
			$Q = db()->query($sql);
			while ($A = db()->fetch_assoc($Q)) {
				$this->_dynamic_fields[$A["name"]] = $A;
			}
		}
	}

	//-----------------------------------------------------------------------------
	// Try to get user info
	function _get_user_info () {
		if (!empty($this->_user_info)) {
			return "";
		}
		$_GET["id"] = intval($_GET["id"]);
		if (!isset($_GET["profile_url"]) && !isset($_GET["id"]) && !empty(main()->USER_ID)) {
			$user_id = main()->USER_ID;
		} elseif (isset($_GET["profile_url"])) {
			$this->_user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE profile_url='"._es($_GET["profile_url"])."' AND active='1'");
			$user_id = $_GET["id"] = intval($user_info["id"]);
			unset($_GET["profile_url"]);
		} elseif (isset($_GET["id"])) {
			$_GET["id"] = intval($_GET["id"]);
			$user_id = !empty($_GET["id"]) ? $_GET["id"] : main()->USER_ID;
		}
		// Try to get user info
		if (!empty($user_id) && empty($this->_user_info)) {
			$this->_user_info = user($user_id, "full", array("WHERE" => array("active" => 1)));
		}
		// Set global user info (for other modules)
		$GLOBALS['user_info'] = $this->_user_info;
	}

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Check if user exists
		if (empty($this->_user_info)) {
			return _e("No such user!");
		}
		
		if ($this->_user_info["group"] == "99"){
			$community_info = db()->query_fetch("SELECT id FROM ".db('community')." WHERE user_id=".intval($this->_user_info["id"]));
			return js_redirect("./?object=community&action=view&id=".$community_info["id"]);
		}
		
		// Skip other user accounts
		if (!array_key_exists($this->_user_info["group"], $this->_account_types)) {
			return _e("Wrong account type!");
		}
		// Fix dates
		if (empty($this->_user_info["last_login"])) {
			$this->_user_info["last_login"] = $this->_user_info["add_date"];
		}
		if (empty($this->_user_info["last_update"])) {
			$this->_user_info["last_update"] = $this->_user_info["add_date"];
		}
		// Get live quick user stats
		$totals = _class_safe("user_stats")->_get_live_stats(array("user_id" => $this->_user_info["id"]));
		
		// Process template
		$forum_posts = $totals["forum_posts"]?$this->_show_forum_posts():"";
		$blog_posts = $totals["blog_posts"]?$this->_show_blog_posts():"";
		$article_posts = $totals["articles"]?$this->_show_articles():"";
		
		$replace = array(
			"user_name"			=> _display_name($this->_user_info),
			"user_group"		=> $this->_user_info["group"],
//			"birth_date"		=> $this->_user_info["birth_date"],
			"emails_received"	=> intval($this->_user_info["emails"]),
			"emails_sent"		=> intval($this->_user_info["emailssent"]),
			"reg_date"			=> _format_date($this->_user_info["add_date"]),
			"last_update"		=> _format_date($this->_user_info["last_update"], "long"),
			"last_login"		=> _format_date($this->_user_info["last_login"], "long"),
			"num_logins"		=> intval($this->_user_info["num_logins"]),
			"site_visits"		=> intval($this->_user_info["sitevisits"]),
			"visits"			=> in_array($this->_user_info["group"], array(3,4)) ? intval($this->_user_info["visits"]) : "",
			"info_items"		=> $this->_show_info_items(),
			"forum_posts"		=> $forum_posts[0],
			"forum_pages"		=> $forum_posts[1],
			"blog_posts"		=> $blog_posts[0],
			"blog_pages"		=> $blog_posts[1],
			"gallery_photos"	=> $totals["gallery_photos"]? $this->_show_gallery_photos() : "", 
			"friend_of_users"	=> $this->_show_friend_of(),
			"friends_users"		=> /*$totals["try_friends"]	? */$this->_show_friends()/* : ""*/,
			"interests"			=> $totals["try_interests"]	? $this->_show_interests() : "",
			"articles"			=> $article_posts[0],
			"articles_pages"	=> $article_posts[1],
			"num_forum_posts"	=> $this->_num_forum_posts > $this->MAX_SHOW_FORUM_POSTS ? intval($this->_num_forum_posts) : 0,
			"num_blog_posts"	=> $this->_num_blog_posts > $this->MAX_SHOW_BLOG_POSTS ? intval($this->_num_blog_posts) : 0,
			"num_friend_of"		=> $GLOBALS['profile_total_friend_of'] > $this->MAX_SHOW_FRIEND_OF ? intval($GLOBALS['profile_total_friend_of']) : 0,
			"num_friends"		=> $GLOBALS['profile_total_friends'] > $this->MAX_SHOW_FRIENDS ? intval($GLOBALS['profile_total_friends']) : 0,
			"forum_posts_link"	=> "./?object=forum&action=search&user_name=".urlencode(_display_name($this->_user_info))."&sort_by=last_post_date&sort_order=desc&prune_days=1000&prune_type=newer&search_in=posts&result_type=posts&keywords=&q=results",
			"blog_posts_link"	=> "./?object=blog&action=show_posts&id=".$this->_user_info["id"],
			"que_url"			=> "./?object=que&action=view&id=".$this->_user_info["id"],
			"all_friend_of_link"=> "./?object=friends&action=view_all_friend_of&id=".$this->_user_info["id"],
			"all_friends_link"	=> "./?object=friends&action=view_all_friends&id=".$this->_user_info["id"],
			"user_avatar"		=> _show_avatar ($this->_user_info["id"], $this->_user_info, 0),
			"reput_info"		=> $this->_show_reput_info(),
			"page_link"			=> !empty($this->_user_info["profile_url"]) ? $this->_user_info["profile_url"] : "",
			"comments"			=> $this->_show_comments(),
			"custom_css"		=> ""/*$this->_show_custom_design_css ($this->_user_info)*/,
			"add_review_link"	=> $this->_user_info["group"] == 3/* && ($this->_user_info["id"] != main()->USER_ID)*/ ? "./?object=reviews&action=add_for_user&id=".$this->_user_info["id"] : "./?object=login_form&go_url=reviews;add_for_user;id=".$this->_user_info["id"],
			"stats_visit_url"	=> "./?object=".$_GET["object"]."&action=show_visits_stats",
			"stats_friend_url"	=> "./?object=".$_GET["object"]."&action=show_friend_stats",
		);
		// Dynamic info
		
		
		if (main()->USER_INFO_DYNAMIC) {
			$OBJ_DYNAMIC_INFO = &main()->init_class("dynamic_info", "classes/");
			$replace["dynamic_items"] = $OBJ_DYNAMIC_INFO->_view(intval($_GET["id"]));
		}
		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show user info items
	function _show_info_items () {
		// Array of text fields
		$text_fields = array(
			"name"		=> "Name",
			"group"		=> "Group",
			"status"	=> "Agency",
			"sex"		=> "Gender",
			"age"		=> "Age",
			"birth_date"=> "Birth date",
			"country"	=> "Country",
			"state"		=> "State",
			"city"		=> "City",
			"zip_code"	=> "Zip Code",
			"height"	=> "Height",
			"weight"	=> "Weight",
		);
		// Array of fields which value need to be retrieved from array
		$array_fields = array(
			"state"		=> "State",
			"height"	=> "Height",
			"weight"	=> "Weight",
		);
		// Array of fields with link
		$link_fields = array(
			"email"	=> "E-mail",
			"url"	=> "Web Site",
			"phone"	=> "Phone",
			"fax"	=> "Fax",
			"icq"	=> "ICQ",
			"yim"	=> "YIM",
			"aim"	=> "AIM",
			"msn"	=> "MSN",
			"jabber"=> "Jabber",
			"skype"	=> "Skype",
		);
		$other_fields = array(
			"city",
			"race",
			"measurements",
			"height",
			"weight",
			"hair_color",
			"eye_color", 
			"orientation", 
			"star_sign", 
			"smoking", 
			"english", 
			"number_escorts", 
			"working_hours", 
			"cc_payments"
		);
		// Process not empty fields
		foreach ((array)$text_fields as $name => $desc) {
			$value = $this->_user_info[$name];
			if (empty($value)) {
				continue;
			}
			// Skip "status" field for visitors
			if ($this->_user_info["group"] == 2 && $name == "status") {
				continue;
			}
			// Special fields
			if ($name == "group") {
				$value = t($this->_account_types[$value]);
			} elseif ($name == "sex") {
				$value = t($value);
			} elseif ($name == "status" && strtolower($value) == "agency") {
				$parent_agency_info = user($this->_user_info["agency_id"], array("id","login","nick","email"));
				$value = !empty($parent_agency_info) ? "<a href=\""._profile_link($parent_agency_info["id"])."\">"._prepare_html(_display_name($parent_agency_info))."</a>" : "";
			} elseif (array_key_exists($name, $array_fields)) {
				$value = eval("return \$this->_".$name."s[\$value];");
			}
			$body .= $this->_show_item($desc, $value);
		}
		$_login_link = tpl()->parse($_GET["object"]."/login_link", array("link" => "./?object=login_form&go_url=".$_GET["object"].";show;id=".$_GET["id"]));
		$website	= $this->_show_contact_item("Web Site", ($this->_user_info['approved_recip'] && $this->_ad_info["url"]) ? (main()->USER_ID ? "./?object=".$_GET["object"]."&action=go&id=".$this->_ad_info["ad_id"] : "./?object=login_form&go_url=".$_GET["object"].";go;id=".$this->_ad_info["ad_id"]) : "");
		$email		= $this->_show_contact_item("Email",	main()->USER_ID ? "./?object=email&action=send_form&id=".$this->_user_info["id"] : "./?object=login_form&go_url=email;send_form;id=".$this->_user_info["id"]);
		$phone		= $this->_show_item("Phone",	$this->_user_info["phone"]	? (main()->USER_ID ? $this->_user_info["phone"]	: $_login_link) : "");
		$fax		= $this->_show_item("Fax",		$this->_user_info["fax"]	? (main()->USER_ID ? $this->_user_info["fax"]	: $_login_link) : "");
		$icq		= $this->_show_item("ICQ",		$this->_user_info["icq"]	? (main()->USER_ID ? $this->_user_info["icq"]	: $_login_link) : "");
		$yahoo		= $this->_show_item("YIM",		$this->_user_info["yahoo"]	? (main()->USER_ID ? $this->_user_info["yahoo"]	: $_login_link) : "");
		$aim		= $this->_show_item("AIM",		$this->_user_info["aim"]	? (main()->USER_ID ? $this->_user_info["aim"]	: $_login_link) : "");
		$msn		= $this->_show_item("MSN",		$this->_user_info["msn"]	? (main()->USER_ID ? $this->_user_info["msn"]	: $_login_link) : "");
		$jabber		= $this->_show_item("Jabber",	$this->_user_info["jabber"]	? (main()->USER_ID ? $this->_user_info["jabber"]	: $_login_link) : "");
		$skype		= $this->_show_item("Skype",	$this->_user_info["skype"]	? (main()->USER_ID ? $this->_user_info["skype"]	: $_login_link) : "");
		// Process fields with links
		foreach ((array)$link_fields as $name => $desc) {
			if (!empty($this->_user_info[$name])) {
				$body .= $$name;
			}
		}
		// Process other fields
		foreach ((array)$other_fields as $name) {
			$value = $this->_user_info[$name];
			if (empty($value)) {
				continue;
			}
			if (array_key_exists($name, $text_fields)) {
				continue;
			}
			if (array_key_exists($name, $link_fields)) {
				continue;
			}
			// Try to translate item name
			$name = str_replace("_", " ", $name);
			$name = ucwords($name);
			$name = t($name);
			// Process item template
			$body .= $this->_show_item($name, $value);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Show item
	function _show_item($name = "", $value = "") {
		return (!empty($name) && !empty($value)) ? tpl()->parse($_GET["object"]."/item", array("name" => $name,"value" => $value)) : "";
	}

	//-----------------------------------------------------------------------------
	// Show contact item
	function _show_contact_item($name = "", $value = "") {
		return (!empty($name) && !empty($value)) ? tpl()->parse($_GET["object"]."/item_contact", array("name" => $name,"link" => $value)) : "";
	}

	//-----------------------------------------------------------------------------
	// Show user forum posts
	function _show_forum_posts () {
		$OBJ_FORUM = main()->init_class("forum");
		return $OBJ_FORUM->_for_user_profile($this->_user_info["id"], $this->MAX_SHOW_FORUM_POSTS);
	}
	
	//-----------------------------------------------------------------------------
	// Show user forum posts
	function _show_articles () {
		$OBJ_ARTICLE = main()->init_class("articles");
		return $OBJ_ARTICLE->_for_user_profile($this->_user_info["id"], $this->MAX_SHOW_ARTICLES);
	}

	//-----------------------------------------------------------------------------
	// Show user blog posts
	function _show_blog_posts () {
		$OBJ_BLOG = main()->init_class("blog");
		return $OBJ_BLOG->_for_user_profile($this->_user_info["id"], $this->MAX_SHOW_BLOG_POSTS);
	}
	
	//-----------------------------------------------------------------------------
	// Show user blog posts
	function _show_gallery_photos () {
		$OBJ_GALLERY = main()->init_class("gallery");
		return $OBJ_GALLERY->_for_user_profile(user($this->_user_info["id"], "short"), $this->MAX_SHOW_GALLERY_PHOTO);
	}

	//-----------------------------------------------------------------------------
	// Show user comments
	function _show_comments () {
		$COMMENTS_OBJ = main()->init_class("comments");
		return $COMMENTS_OBJ->_for_user_profile($this->_user_info["id"], $this->MAX_SHOW_COMMENTS);
	}
	
	//-----------------------------------------------------------------------------
	// Show users where current one is in friends list
	function _show_friend_of () {
		return is_object($this->FRIENDS_OBJ) ? $this->FRIENDS_OBJ->_show_friend_of_for_profile($this->_user_info, $this->MAX_SHOW_FRIEND_OF) : "";
	}

	//-----------------------------------------------------------------------------
	// Show friends users list
	function _show_friends () {
		return is_object($this->FRIENDS_OBJ) ? $this->FRIENDS_OBJ->_show_friends_for_profile($this->_user_info, $this->MAX_SHOW_FRIENDS) : "";
	}

	//-----------------------------------------------------------------------------
	// Show user reputation info
	function _show_reput_info () {
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$REPUT_INFO			= $REPUT_OBJ->_get_user_reput_info($this->_user_info["id"]);
			$reput_stars		= $REPUT_OBJ->_show_reput_stars($REPUT_INFO["points"]);
			$activity_points	= $REPUT_OBJ->_get_user_activity_points($this->_user_info["id"]);
		}
		if (empty($REPUT_INFO)) {
			return false;
		}
		$replace = array(
			"stars"				=> $reput_stars,
			"activity_points"	=> intval($activity_points),
			"reput_points"		=> intval($REPUT_INFO["points"]),
			"alt_power"			=> intval($REPUT_INFO["alt_power"]),
		);
		return tpl()->parse($_GET["object"]."/reput_info", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show user interests
	function _show_interests () {
		$INTERESTS_OBJ = main()->init_class("interests");
		if (!is_object($INTERESTS_OBJ)) {
			return "";
		}
		$body = "";
		foreach ((array)$INTERESTS_OBJ->_get_for_user_id($this->_user_info["id"]) as $cur_info) {
			$replace = array(
				"search_link"	=> $cur_info["search_link"],
				"keyword"		=> $cur_info["keyword"],
			);
			$body .= tpl()->parse($_GET["object"]."/interests_item", $replace);
			$this->_interests_array[] = $cur_info["keyword"];
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Show stars for the given value between 1 and 5
	function _show_stars ($value = 0) {
		if (empty($value) || $value > 5 || $value < 1) return "Not rated";
		$star_gold = tpl()->parse("reviews/star_gold");
		$star_gray = tpl()->parse("reviews/star_gray");
		for ($i = 1; $i <= $value; $i++)	$body .= $star_gold;
		for ($i = 5; $i > $value; $i--)		$body .= $star_gray;
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Show profile comments
	function _show_custom_design_css ($user_info = array()) {
/*
		$OBJ = main()->init_class("custom_design", "classes/");
		return is_object($OBJ) ? $OBJ->_show_css(array(
			"item_id"			=> $user_info["id"],
			"css_table_main"	=> "tbl,.wrapper,textarea,input",
			"css_table_header"	=> "stripe,.menu_top,.left_menu_header,.right_menu_header",
		)) : "";
*/
	}

	//-----------------------------------------------------------------------------
	// Check if comment delete allowed
	function _comment_delete_allowed ($params = array()) {
		$delete_allowed	= main()->USER_ID && (($params["user_id"] && $params["user_id"] == main()->USER_ID) || ($params["object_id"] && main()->USER_ID == $params["object_id"]));
		return (bool)$delete_allowed;
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params["nav_bar_obj"];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		// Save old items
		$old_items = $params["items"];
		// Create new items
		$items = array();
		$items[]	= $NAV_BAR_OBJ->_nav_item("Profiles");
		$items[]	= $NAV_BAR_OBJ->_nav_item(_display_name($this->_user_info));
		return $items;
	}

	//-----------------------------------------------------------------------------
	// Show compact user info (usually for JavaScript calls)
	function compact_info () {
		main()->NO_GRAPHICS		= true;
		conf('no_ajax_here',  true);
		// Check user id
		$USER_ID = $_REQUEST["id"];
		if (empty($USER_ID)) {
			$error_message = "No id";
		}
		if (empty($error_message)) {
			// Try to get user info
			$this->_user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id=".intval($USER_ID)." AND active='1'");
			if (empty($this->_user_info)) {
				$error_message = "No info";
			}
		}
		if (empty($error_message)) {
			// Get live quick user stats
			$totals = _class_safe("user_stats")->_get_live_stats(array("user_id" => $this->_user_info["id"]));
			// Check if this user is in favorites (also check if this is own profile)
			$DISPLAY_CONTACT_ITEMS = 0;
			if (main()->USER_ID && $this->_user_info["id"] != main()->USER_ID) {
				if ($totals["favorite_users"]) {
					$is_in_favorites	= db()->query_num_rows("SELECT 1 FROM ".db('favorites')." WHERE user_id=".intval(main()->USER_ID)." AND target_user_id=".intval($this->_user_info["id"]));
				}
				if ($totals["ignored_users"]) {
					$is_ignored			= db()->query_num_rows("SELECT 1 FROM ".db('ignore_list')." WHERE user_id=".intval(main()->USER_ID)." AND target_user_id=".intval($this->_user_info["id"]));
				}
				// Check friendship
				$FRIENDS_OBJ		= main()->init_class("friends");
				$is_a_friend		= is_object($FRIENDS_OBJ) ? $FRIENDS_OBJ->_is_a_friend(main()->USER_ID, $this->_user_info["id"]) : -1;
				if (!empty($totals["try_friends"])) {
					$is_friend_of		= $FRIENDS_OBJ->_is_a_friend($this->_user_info["id"], main()->USER_ID);
				}
				$is_mutual_friends	= $is_a_friend && $is_friend_of;
				// Switch for contact items
				$DISPLAY_CONTACT_ITEMS = 1;
			}
			// Interests
			$totals["interests"] = 0;
			if (!empty($totals["try_interests"])) {
				$INTERESTS_OBJ = main()->init_class("interests");
				if (is_object($INTERESTS_OBJ)) {
					$user_interests = $INTERESTS_OBJ->_get_for_user_id($user_id);
					if (!empty($user_interests) && is_array($user_interests)) {
						$totals["interests"] = count($user_interests);
					}
				}
			}
			// Process user reputation
			$reput_text = "";
			$REPUT_OBJ = main()->init_class("reputation");
			if (is_object($REPUT_OBJ)) {
				$reput_info = array(
					"points"	=> $totals["reput_points"],
				);
				$reput_text	= $REPUT_OBJ->_show_for_user($this->_user_info["id"], $reput_info);
			}
			// Array of $_GET vars to skip
			$skip_get = array("page","escort_id","q","show");

			if (empty($this->_user_info["last_login"])) {
				$this->_user_info["last_login"] = $this->_user_info["add_date"];
			}
			// Process template
			$replace = array(
				"user_id"				=> intval($USER_ID),
				"user_avatar"			=> _show_avatar($USER_ID),
				"user_name"				=> _prepare_html(_display_name($this->_user_info)),
				"user_group"			=> t($this->_account_types[$this->_user_info["group"]]),
				"user_profile_link"		=> process_url(_profile_link($USER_ID)),
				"user_level"			=> intval($this->_user_info["level"]),
				"user_level_name"		=> _prepare_html($this->_user_levels[$this->_user_info["level"]]),
				"emails_received"		=> intval($this->_user_info["emails"]),
				"emails_sent"			=> intval($this->_user_info["emailssent"]),
				"reg_date"				=> _format_date($this->_user_info["add_date"]),
				"last_update"			=> _format_date($this->_user_info["last_update"], "long"),
				"last_login"			=> _format_date($this->_user_info["last_login"], "long"),
				"num_logins"			=> intval($this->_user_info["num_logins"]),
				"site_visits"			=> intval($this->_user_info["sitevisits"]),
				"gallery_link"			=> $totals["gallery_photos"]? process_url("./?object=gallery&action=show_gallery&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
				"blog_link"				=> $totals["blog_posts"]	? process_url("./?object=blog&action=show_posts&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
				"articles_link"			=> $totals["articles"]		? process_url("./?object=articles&action=view_by_user&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
				"interests_link"		=> $totals["interests"]		? process_url("./?object=interests&action=view&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
				"contact_link"			=> main()->USER_ID && main()->USER_ID != $this->_user_info["id"] ? process_url(main()->USER_ID ? "./?object=email&action=send_form&id=".$this->_user_info["id"] : "./?object=login_form&go_url=email;send_form;id=".$this->_user_info["id"]) : "",
				"favorites_link"		=> !empty($is_in_favorites) ? process_url("./?object=account&action=favorite_delete&id=".$this->_user_info["id"]) : process_url("./?object=account&action=favorite_add&id=".$this->_user_info["id"]),
				"is_in_favorites"		=> isset($is_in_favorites) ? intval((bool) $is_in_favorites) : "",
				"ignore_link"			=> !empty($is_ignored) ? process_url("./?object=account&action=unignore_user&id=".$this->_user_info["id"]) : process_url("./?object=account&action=ignore_user&id=".$this->_user_info["id"]),
				"is_ignored"			=> isset($is_ignored) ? intval((bool) $is_ignored) : "",
				"make_friend_link"		=> empty($is_a_friend) ? process_url("./?object=friends&action=add&id=".$this->_user_info["id"]) : "",
				"is_a_friend"			=> isset($is_a_friend) ? intval($is_a_friend) : "",
				"is_friend_of"			=> isset($is_friend_of) ? intval($is_friend_of) : "",
				"is_mutual_friends"		=> isset($is_mutual_friends) ? intval($is_mutual_friends) : "",
				"display_contact_items"	=> intval($DISPLAY_CONTACT_ITEMS),
				"sex"					=> _prepare_html($this->_user_info["sex"]),
				"country"				=> _prepare_html($this->_user_info["country"]),
				"state"					=> _prepare_html($this->_user_info["state"]),
				"city"					=> _prepare_html($this->_user_info["city"]),
				"country_code_lower"	=> strtolower($this->_user_info["country"]),
				"reput_text"			=> $reput_text,
				"reput_points"			=> MAIN_TYPE_ADMIN ? intval($totals["reput_points"]) : "",
				"alt_power"				=> MAIN_TYPE_ADMIN ? intval($REPUT_INFO["alt_power"]) : "",
				"activity_points"		=> intval($totals["activity_points"]),
				"is_admin"				=> MAIN_TYPE_ADMIN ? 1 : 0,
			);
			// Admin-only methods
			if (MAIN_TYPE_ADMIN) {
				$replace = array_merge($replace, array(
					"login_stats"			=> process_url("./?object=log_auth_view&action=save_filter&user_id=".$this->_user_info["id"]),
					"multi_accounts_link"	=> process_url("./?object=check_multi_accounts&action=show_by_user&id=".$this->_user_info["id"]),
					"user_errors"			=> process_url("./?object=log_user_errors_viewer&action=save_filter&user_id=".$this->_user_info["id"]),
					"ban_popup_link"		=> _class("manage_auto_ban", "admin_modules/")->_popup_link(array("user_id" => intval($this->_user_info["id"]))),
					"verify_link"			=> !$this->_user_info["photo_verified"] ? "./?object=manage_photo_verify&action=add&id=".intval($this->_user_info["id"]) : "",
				));
			}
			$body = tpl()->parse($_GET["object"]."/compact_info", $replace);
		}
		if (!empty($error_message)) {
			$body = $error_message;
		}
		if (DEBUG_MODE) {
			$body .= "<hr class='clearfloat'>DEBUG INFO:\r\n";
			$body .= common()->_show_execution_time();
//			$body .= common()->show_debug_info();
		}
		echo $body;
	}

	/**
	* Show visits statistics
	*/
	function show_visits_stats () {

		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}

		$_GET["id"] ? $_id = intval($_GET["id"]) : $_id = main()->USER_ID;
		$sql = "SELECT * FROM ".db('log_user_action')." WHERE action_name='visit' AND owner_id=".intval($_id)." ORDER BY add_date DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);		
		$stats_array = db()->query_fetch_all($sql.$add_sql);
		foreach ((array)$stats_array as $A) {
			$members_ids[] = $A["member_id"];
		}
		$members_ids = array_unique((array)$members_ids);
		$user_infos = user($members_ids, "short");
	
		foreach ((array)$stats_array as $A) {
			$replace2 = array(
				"avatar"		=> _show_avatar($A["member_id"]),
				"visit_date"	=> _format_date($A["add_date"], "long"),
				"user_nick"		=> $user_infos[$A["member_id"]]["nick"],
				"profile_url"	=> _profile_link($user_infos[$A["member_id"]]),
			);
			$items .= tpl()->parse($_GET["object"]."/visit_stats_item", $replace2);
		}
		$replace = array(
			"total"			=> $total,
			"pages"			=> $pages,
			"items"			=> $items,
			"back_url"		=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/visit_stats_main", $replace);
	}

	/**
	* Show friendship statistics
	*/
	function show_friend_stats () {

		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}

		$_id = intval(main()->USER_ID);
		$sql = "SELECT * FROM ".db('log_user_action')." WHERE action_name IN('add_friend', 'del_friend') AND owner_id=".$_id." ORDER BY add_date DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);		
		$stats_array = db()->query_fetch_all($sql.$add_sql);
		foreach ((array)$stats_array as $A) {
			$members_ids[] = $A["member_id"];
		}
		$members_ids = array_unique((array)$members_ids);
		$user_infos = user($members_ids, "short");
	
		foreach ((array)$stats_array as $A) {
			$replace2 = array(
				"avatar"		=> _show_avatar($A["member_id"]),
				"event_date"	=> _format_date($A["add_date"], "long"),
				"user_nick"		=> $user_infos[$A["member_id"]]["nick"],
				"event"			=> $A["action_name"],
				"profile_url"	=> _profile_link($user_infos[$A["member_id"]]),
			);
			$items .= tpl()->parse($_GET["object"]."/friend_stats_item", $replace2);
		}
		$replace = array(
			"total"			=> $total,
			"pages"			=> $pages,
			"items"			=> $items,
			"back_url"		=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/friend_stats_main", $replace);
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Show visits statistics",
				"url"	=> "./?object=".$_GET["object"]."&action=show_visits_stats",
			),
			array(
				"name"	=> "Show friendship statistics",
				"url"	=> "./?object=".$_GET["object"]."&action=show_friend_stats",
			),
			array(
				"name"	=> "View All Friends",
				"url"	=> "./?object=friends&action=view_all_friends",
			),
			array(
				"name"	=> "View All Friends Of",
				"url"	=> "./?object=friends&action=view_all_friends_of",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

}
