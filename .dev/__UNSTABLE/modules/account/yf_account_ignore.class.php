<?php

//-----------------------------------------------------------------------------
// Account ignore users module
class yf_account_ignore {

	//-----------------------------------------------------------------------------
	// Constructor
	function yf_account_ignore () {
		$this->ACCOUNT_OBJ	= module(ACCOUNT_CLASS_NAME);
	}

	//-----------------------------------------------------------------------------
	// Edit ignored users list
	function _edit () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if (!$_GET['page'] && $_GET['id']) {
			$_GET['page'] = $_GET['id'];
		}
		$sql = "SELECT u.* FROM ".db('ignore_list')." AS i, ".db('user')." AS u WHERE i.user_id='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND i.target_user_id=u.id";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, null, null, $this->ACCOUNT_OBJ->num_per_page);
		// Connect to category display module
		// Process records
		$Q = db()->query($sql. $add_sql);
		while ($user_info = db()->fetch_assoc($Q)) {
			
			$items[] = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"user_name"		=> _prepare_html(_display_name($user_info)),
				"avatar"		=> _show_avatar($user_info["id"], $user_info, 1),
				"location"		=> _country_name($user_info["country"]),
				"gender"		=> _prepare_html($user_info['sex']),
				"age"			=> $user_info['age'] ? intval($user_info['age']) : "-",
				"delete_link"	=> "./?object=account&action=unignore_user&id=".intval($user_info['id']),
				"profile_link"	=> "./?object=user_profile&action=show&id=".intval($user_info['id']),
			);
		}
		// Process template
		$replace = array(
			"items"	=> $items,
			"pages"	=> $pages,
			"total"	=> intval($total),
		);
		return tpl()->parse(ACCOUNT_CLASS_NAME."/ignored_edit", $replace);
	}

	//-----------------------------------------------------------------------------
	//
	function _ignore () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		// Chec if user is already ignored
		$result = db()->query_fetch("SELECT 1 FROM ".db('ignore_list')." WHERE user_id='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND target_user_id='".intval($_GET['id'])."'");
		if (empty($result['id'])) {
			$sql = "REPLACE INTO ".db('ignore_list')." ( 
					user_id , 
					target_user_id , 
					add_date  
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
		_class("user_stats")->_update(array("user_id" => $this->ACCOUNT_OBJ->USER_ID));
		// Redirect user
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	//-----------------------------------------------------------------------------
	//
	function _unignore () {
		// Check for member
		if (empty($this->ACCOUNT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		// Do delete
		db()->query("DELETE FROM ".db('ignore_list')." WHERE user_id='".intval($this->ACCOUNT_OBJ->USER_ID)."' AND target_user_id='".intval($_GET['id'])."' LIMIT 1");
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
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
}
