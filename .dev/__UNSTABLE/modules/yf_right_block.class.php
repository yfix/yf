<?php

//-----------------------------------------------------------------------------
// Right block wrapper
class yf_right_block {

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		// Fix user id
		if (empty(main()->USER_ID) && main()->USER_ID) {
			main()->USER_ID		= main()->USER_ID;
			main()->USER_GROUP	= main()->USER_GROUP;
		}
	}

	//-----------------------------------------------------------------------------
	// Show right block contents
	function _show () {
		// Get user info
		if (empty($GLOBALS['user_info'])) {
			return false;
		}
		$this->_user_info = &$GLOBALS['user_info'];
		// Get live quick user stats
		$totals = _class("user_stats")->_get_live_stats(array("user_id" => $this->_user_info["id"]));
		// Interests
		$totals["interests"] = 0;
		if (!empty($totals["try_interests"])) {
			$totals["interests"] = count((array)module_safe("interests")->_get_for_user_id($user_id));
		}
		// Check if this user is in favorites (also check if this is own profile)
		$DISPLAY_CONTACT_ITEMS = 0;
		if (main()->USER_ID && $this->_user_info["id"] != main()->USER_ID) {
//			if ($totals["favorite_users"]) {
				$is_in_favorites	= db()->query_num_rows("SELECT 1 FROM ".db('favorites')." WHERE user_id=".intval(main()->USER_ID)." AND target_user_id=".intval($this->_user_info["id"]));
//			}
//			if ($totals["ignored_users"]) {
				$is_ignored			= db()->query_num_rows("SELECT 1 FROM ".db('ignore_list')." WHERE user_id=".intval(main()->USER_ID)." AND target_user_id=".intval($this->_user_info["id"]));
//			}
			// Check friendship
			$is_a_friend		= module_safe("friends")->_is_a_friend(main()->USER_ID, $this->_user_info["id"]);
			if (!empty($totals["try_friends"])) {
				$is_friend_of		= module_safe("friends")->_is_a_friend($this->_user_info["id"], main()->USER_ID);
			}
			$is_mutual_friends	= $is_a_friend && $is_friend_of;
			// Switch for contact items
			$DISPLAY_CONTACT_ITEMS = 1;
		}
		// Process user reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$reput_info = array(
				"points"	=> $totals["reput_points"],
			);
			$reput_text	= $REPUT_OBJ->_show_for_user($this->_user_info["id"], $reput_info, 1);
		}
		// Check if user has escort referral records (visible only for escorts)
		if ($this->_user_info["group"] == 2 && in_array(main()->USER_GROUP, array(3,4))) {
			list($has_escort_refs) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM ".db('referrals')." WHERE type='e' AND target_id=".intval($this->_user_info["id"]));
		}
		// Check if user has industry referral records (visible only for escorts)
		if ($this->_user_info["group"] == 3/* && in_array(main()->USER_GROUP, array(3,4))*/) {
			list($has_industry_refs) = db()->query_fetch("SELECT COUNT(*) AS `0` FROM ".db('referrals')." WHERE type='i' AND target_id=".intval($this->_user_info["id"]));
		}
		// Array of $_GET vars to skip
		$skip_get = array("page","escort_id","q","show");
		// Process template
		$replace = array(
			"user_avatar"			=> _show_avatar($this->_user_info["id"], $this->_user_info, 1),
			"user_name"				=> _display_name($this->_user_info),
			"other_items"			=> implode("\r\n", (array)$GLOBALS['right_block_items']),
			"user_profile_link"		=> _profile_link($this->_user_info["id"], $skip_get),
			"ads_link"				=> $totals["ads"]			? process_url("./?object=search&action=show&user_id=".$this->_user_info["id"]."&q=results&page=1"._add_get($skip_get)) : "",
			"reviews_link"			=> $totals["reviews"]		? process_url("./?object=reviews_search&action=show&escort_id=".$this->_user_info["id"]."&q=results&page=1"._add_get($skip_get)) : "",
			"gallery_link"			=> $totals["gallery_photos"]? process_url("./?object=gallery&action=show_gallery&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"blogs_link"			=> $totals["blog_posts"]	? process_url("./?object=blog&action=show_posts&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"que_link"				=> $totals["que_answers"]	? process_url("./?object=que&action=view&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"articles_link"			=> $totals["articles"]		? process_url("./?object=articles&action=view_by_user&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"interests_link"		=> $totals["interests"]		? process_url("./?object=interests&action=view&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"escort_refs_link"		=> $has_escort_refs			? process_url("./?object=hobby_references&action=view_user_refs&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"industry_refs_link"	=> $has_industry_refs		? process_url("./?object=industry_references&action=view_user_refs&id=".$this->_user_info["id"]._add_get($skip_get)) : "",
			"account_type"			=> t($this->_account_types[$this->_user_info["group"]]),
			"contact_link"			=> $this->_user_info["contact_by_email"] ? process_url(_prepare_members_link("./?object=email&action=send_form&id=".$this->_user_info["id"])) : "",
			"favorites_link"		=> !empty($is_in_favorites) ? process_url("./?object=account&action=favorite_delete&id=".$this->_user_info["id"]) : process_url("./?object=account&action=favorite_add&id=".$this->_user_info["id"]),
			"is_in_favorites"		=> isset($is_in_favorites) ? intval((bool) $is_in_favorites) : "",
			"ignore_link"			=> !empty($is_ignored) ? process_url("./?object=account&action=unignore_user&id=".$this->_user_info["id"]) : process_url("./?object=account&action=ignore_user&id=".$this->_user_info["id"]),
			"is_ignored"			=> isset($is_ignored) ? intval((bool) $is_ignored) : "",
			"make_friend_link"		=> empty($is_a_friend) ? process_url("./?object=friends&action=add&id=".$this->_user_info["id"]) : "",
			"is_a_friend"			=> isset($is_a_friend) ? intval($is_a_friend) : "",
			"is_friend_of"			=> isset($is_friend_of) ? intval($is_friend_of) : "",
			"is_mutual_friends"		=> isset($is_mutual_friends) ? intval($is_mutual_friends) : "",
			"reput_text"			=> $reput_text,
			"display_contact_items"	=> intval($DISPLAY_CONTACT_ITEMS),
			"add_review_link"		=> $this->_user_info["group"] == 3 ? process_url(_prepare_members_link("./?object=reviews&action=add_for_user&id=".$this->_user_info["id"])) : "",
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}

	//-----------------------------------------------------------------------------
	//
	function _show_for_output_cache () {
		// Get user info for different modules
		if ($_GET["object"] == "escort") {
			// Check if we have an id
			$_GET["id"] = intval($_GET["id"]);
			if (empty($_GET["id"])) {
				return false;
			}
			$GLOBALS['escort_ad_info'] = db()->query_fetch("SELECT ad_id,user_id FROM ".db('ads')." WHERE ad_id=".intval($_GET["id"]));
			if (empty($GLOBALS['escort_ad_info']["ad_id"])) {
				return false;
			}
			// Prepare user info
			$GLOBALS['user_info'] = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id=".$GLOBALS['escort_ad_info']["user_id"]." AND active='1'");
			// Cleanup input
			if (isset($_GET["cat_id"])) {
				unset($_GET["cat_id"]);
			}
		}
		// Display contents
		return $this->_show();
	}
}
