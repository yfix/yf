<?php

/**
* Chat internal arrays
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_chat_arrays {

	var $CHAT_OBJ = null;

	/**
	* Chat Register
	*/
	function yf_chat_arrays () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}

	/**
	* Define some arrays as class properties according to $_GET["action"]
	*/
	function _define_arrays () {
		if (!CHAT_USER_ID) {
			return false;
		}
		// Array of educations
		$this->CHAT_OBJ->_edu_array = array (
			1	=> t("edu_1"),
			2	=> t("edu_2"),
			3	=> t("edu_3"),
			4	=> t("edu_4"),
			5	=> t("edu_5"),
		);
		// Array of hair colors
		$this->CHAT_OBJ->_hair_color = array (
			1	=> t("hair_1"),
			2	=> t("hair_2"),
			3	=> t("hair_3"),
		);
		// Array of eyes colors
		$this->CHAT_OBJ->_eyes_color = array (
			1	=> t("eyes_1"),
			2	=> t("eyes_2"),
			3	=> t("eyes_3"),
			4	=> t("eyes_4"),
		);
		// Array of custom refresh times allowed for user to select (in seconds)
		$this->CHAT_OBJ->_refresh_select_array = array(
			1	=> 1,
			2	=> 2,
			3	=> 3,
			4	=> 4,
			5	=> 5,
			10	=> 10,
			20	=> 20,
			30	=> 30,
			60	=> 60,
		);
		// Get ignore list for the current user
		$Q = db()->query("SELECT * FROM `".db('chat_ignore')."` WHERE `user_id`=".intval(CHAT_USER_ID));
		while ($A = db()->fetch_assoc($Q)) {
			if ($A["user_ignore"] != CHAT_USER_ID) {
				$this->CHAT_OBJ->ignore_list[$A["user_ignore"]] = 1;
			}
		}
	}
}
