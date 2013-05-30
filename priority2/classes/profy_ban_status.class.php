<?php

/**
* User ban status handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_ban_status {

	/**
	* Check if specified action is banned for the given user_id
	* 
	* @access	public
	* @return	bool
	*/
	function _auto_check () {
// TODO
//common()->_raise_error(t("Testing auto-ban checking"));
//return true;
return false;
	}

	/**
	* Check if specified action is banned for the given user_id
	* 
	* @access	public
	* @param	$action_name	string
	* @param	$user_id		int
	* @return	bool
	*/
	function _check_if_banned ($action_name, $user_id = 0) {
		if (empty($user_id) && main()->USER_ID) {
			$user_id = main()->USER_ID;
		}
		$result = false;
		if (empty($user_id)) {
			return false;
		}
		list($result) = db()->query_fetch("SELECT `value` AS `0` FROM `".db('ban_status')."` WHERE `user_id`=".intval($user_id)." AND `action`='"._es($action_name)."'");
		return $result;
	}

	/**
	* Get ban info array for given users ids (could be called from other modules)
	* 
	* @access	public
	* @param	$users_ids	array
	* @return	array
	*/
	function _get_info_for_user_ids ($users_ids = array()) {
		if (isset($users_ids[""])) {
			unset($users_ids[""]);
		}
		if (!is_array($users_ids) || empty($users_ids)) {
			return false;
		}
		$ban_infos = array();
		// Do get records from db
		$Q = db()->query("SELECT * FROM `".db('ban_status')."` WHERE `user_id` IN(".implode(",", $users_ids).")");
		while ($A = db()->fetch_assoc($Q)) $ban_infos[$A["user_id"]] = $A;
		return $ban_infos;
	}
}
