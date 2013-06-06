<?php

//-----------------------------------------------------------------------------
// Account favorites module
class yf_account_favorites {

	//-----------------------------------------------------------------------------
	// Constructor
	function yf_account_favorites () {
		$this->ACCOUNT_OBJ	= module(ACCOUNT_CLASS_NAME);
	}

	//-----------------------------------------------------------------------------
	// Edit favorite users list
	function _edit () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if (!$_GET['page'] && $_GET['id']) {
			$_GET['page'] = $_GET['id'];
		}
		$sql = "SELECT `u`.* FROM `".db('favorites')."` AS `f`, `".db('user')."` AS `u` WHERE `f`.`user_id`='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND `f`.`target_user_id`=`u`.`id`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, null, null, $this->ACCOUNT_OBJ->num_per_page);
		// Connect to category display module
		if (!empty($total)) {
			$C = main()->init_class("category");
		}
		// Process records
		$Q = db()->query($sql. $add_sql);
		while ($user_info = db()->fetch_assoc($Q)) {
			$items[] = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"user_name"		=> _prepare_html(_display_name($user_info)),
				"avatar"		=> _show_avatar($user_info["id"], $user_info, 1),
				"location"		=> _country_name($user_info["country"]).(!empty($user_info["state"]) ? ", ".$user_info["state"] : "").(!empty($user_info["city"]) ? ", ".$user_info["city"] : ""),
				"gender"		=> _prepare_html($user_info['sex']),
				"age"			=> $user_info['age'] ? intval($user_info['age']) : "-",
				"delete_link"	=> "./?object=account&action=favorite_delete&id=".intval($user_info['id']),
				"profile_link"	=> "./?object=user_profile&action=show&id=".intval($user_info['id']),
			);
		}
		// Process template
		$replace = array(
			"items"	=> $items,
			"pages"	=> $pages,
			"total"	=> intval($total),
		);
		return tpl()->parse(ACCOUNT_CLASS_NAME."/favorites_edit", $replace);
	}

	//-----------------------------------------------------------------------------
	// Add user to favorites
	function _add () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		// Check if user is already in favorites
		$result = db()->query_fetch("SELECT `id` FROM `".db('favorites')."` WHERE `user_id`='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND `target_user_id`='".intval($_GET['id'])."'");
		if (empty($result['id'])) {
			$sql = "REPLACE INTO `".db('favorites')."` ( 
					`user_id` , 
					`target_user_id` , 
					`add_date` 
				) VALUES (
					'".intval($this->ACCOUNT_OBJ->USER_ID)."', 
					'".intval($_GET['id'])."', 
					'".time()."' 
				);";
			db()->query($sql);
		}
		// Output cache trigger
		if (main()->OUTPUT_CACHING) {
			_class("output_cache")->_exec_trigger(array(
				"user_id"	=> $this->ACCOUNT_OBJ->USER_ID,
				"user_id2"	=> $_GET['id'],
			));
		}
		// Update user stats
		_class("user_stats")->_update(array("user_id" => $this->ACCOUNT_OBJ->USER_ID));
		// Redirect user
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	//-----------------------------------------------------------------------------
	// Do delete user from favorites
	function _delete () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		// Do delete user from favorites table
		db()->query("DELETE FROM `".db('favorites')."` WHERE `user_id`='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND `target_user_id`='".intval($_GET["id"])."' LIMIT 1");
		// Output cache trigger
		if (main()->OUTPUT_CACHING) {
			_class("output_cache")->_exec_trigger(array(
				"user_id"	=> $this->ACCOUNT_OBJ->USER_ID,
				"user_id2"	=> $_GET['id'],
			));
		}
		// Update user stats
		_class("user_stats")->_update(array("user_id" => $this->ACCOUNT_OBJ->USER_ID));
		// Redirect user
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}
}
