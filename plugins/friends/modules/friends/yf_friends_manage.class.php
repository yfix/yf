<?php

/**
* Friends manage
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_friends_manage {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->PARENT_OBJ	= module(FRIENDS_CLASS_NAME);
	}

	
	// Add user to friends list
	function add () {
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		// Check target user id
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e("No user id!");
		}
		// Check if such user exists
		$target_user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));
		if (empty($target_user_info)) {
			return _e("No such user!");
		}
		// Check if user is already a friend
		$IS_A_FRIEND = $this->PARENT_OBJ->_is_a_friend($this->PARENT_OBJ->USER_ID, $_GET["id"]);
		if ($IS_A_FRIEND) {
			return _e("This user is already in your friends list");
		}
		// Do add user
		$this->PARENT_OBJ->_add_user_friends_ids($this->PARENT_OBJ->USER_ID, $_GET["id"]);
		// Output cache trigger
		if (main()->OUTPUT_CACHING) {
			_class_safe("output_cache")->_exec_trigger(array(
				"user_id"	=> $this->PARENT_OBJ->USER_ID,
				"user_id2"	=> $target_user_info['id'],
			));
		}
		// Save log
		common()->_log_user_action("add_friend", $_GET["id"], "friends");

		// Send notify email
		if ($this->PARENT_OBJ->SEND_EMAIL_NOTIFY) {
			$replace = array(
				"target_user_name"	=> _display_name($target_user_info),
				"user_name"			=> _display_name($this->PARENT_OBJ->_user_info),
				"profile_link"		=> _profile_link($this->PARENT_OBJ->_user_info["id"]),
			);
			$mail_text = tpl()->parse(FRIENDS_CLASS_NAME."/email_when_added", $replace);
			common()->quick_send_mail($target_user_info["email"], "You have been added to user's friends list", $mail_text);
		}
		// Update user stats
		_class_safe("user_stats")->_update(array("user_id" => $this->PARENT_OBJ->USER_ID));
		// Return user to the "manage" page
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=view_all_friends");
	}

	
	// Delete selected friend
	function delete () {
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$target_user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($target_user_info["id"])) {
			_re("No such user!");
			return _e();
		}
		// Do delete
		$this->PARENT_OBJ->_del_user_friends_ids($this->PARENT_OBJ->USER_ID, $target_user_info);
		// Output cache trigger
		if (main()->OUTPUT_CACHING) {
			_class_safe("output_cache")->_exec_trigger(array(
				"user_id"	=> $this->PARENT_OBJ->USER_ID,
				"user_id2"	=> $target_user_info['id'],
			));
		}
		// Send notify email
		if ($this->PARENT_OBJ->SEND_EMAIL_NOTIFY) {
			$replace = array(
				"target_user_name"	=> _display_name($target_user_info),
				"user_name"			=> _display_name($this->PARENT_OBJ->_user_info),
				"profile_link"		=> _profile_link($this->PARENT_OBJ->_user_info["id"]),
			);
			$mail_text = tpl()->parse(FRIENDS_CLASS_NAME."/email_when_deleted", $replace);
			common()->quick_send_mail($target_user_info["email"], "You have been deleted from user's friends list", $mail_text);
		}

		// Save log
		common()->_log_user_action("del_friend", $_GET["id"], "friends");

		// Update user stats
		_class_safe("user_stats")->_update(array("user_id" => $this->PARENT_OBJ->USER_ID));
		// Return user back
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=view_all_friends");
	}

	
	// Get current user friends ids array
	function _get_user_friends_ids ($target_user_id) {
		$cur_friends_ids = array();
		// Get friends from db
		list($CUR_FRIENDS_LIST) = db()->query_fetch("SELECT friends_list AS `0` FROM ".db('friends')." WHERE user_id=".intval($target_user_id));
		// Convert string into array
		if (!empty($CUR_FRIENDS_LIST)) {
			$tmp_array = explode(",", $CUR_FRIENDS_LIST);
		}
		foreach ((array)$tmp_array as $tmp_friend_id) {
			if (empty($tmp_friend_id) || $tmp_friend_id == $target_user_id) {
				continue;
			}
			$cur_friends_ids[$tmp_friend_id] = $tmp_friend_id;
		}
		return $cur_friends_ids;
	}

	
	// Add friends to user's friends list
	function _add_user_friends_ids ($target_user_id, $add_friends_ids = array()) {
		$cur_friends_ids = $this->_get_user_friends_ids($target_user_id);
		// Merge current friends list with new ones
		if (is_numeric($add_friends_ids)) {
			$add_friends_ids = array($add_friends_ids);
		}
		foreach ((array)$add_friends_ids as $add_friend_id) {
			$cur_friends_ids[$add_friend_id] = $add_friend_id;
		}
		// Save friends ids
		$this->_save_user_friends_ids ($target_user_id, $cur_friends_ids);
	}

	
	// Delete friends to user's friends list
	function _del_user_friends_ids ($target_user_id, $del_friends_ids = array()) {
		$cur_friends_ids = $this->_get_user_friends_ids($target_user_id);
		// Merge current friends list with new ones
		if (is_numeric($del_friends_ids)) {
			$del_friends_ids = array($del_friends_ids);
		}
		foreach ((array)$del_friends_ids as $del_friend_id) {
			if (isset($cur_friends_ids[$del_friend_id])) {
				unset($cur_friends_ids[$del_friend_id]);
			}
		}
		// Save friends ids
		$this->_save_user_friends_ids ($target_user_id, $cur_friends_ids);
	}

	
	// Save friends
	function _save_user_friends_ids ($target_user_id, $friends_array = array()) {
		// Save friends ids
		db()->query("REPLACE INTO ".db('friends')." (user_id,friends_list) VALUES (".intval($target_user_id).",',".implode(",",$friends_array).",')");
	}
}
