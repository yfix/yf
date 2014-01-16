<?php

/**
* Common methods for admin section stored here
*/
class yf_common_admin {

	/**
	*/
	function _init() {
		$this->USER_ID		= main()->USER_ID;
		$this->USER_GROUP	= main()->USER_GROUP;
		$this->ADMIN_ID		= main()->ADMIN_ID;
		$this->ADMIN_GROUP	= main()->ADMIN_GROUP;
		$this->CENTER_BLOCK_ID = _class('core_blocks')->_get_center_block_id();
	}

	/**
	*/
	function _admin_link_is_allowed($link = '') {
		// Currently this works only for admin section
		if (MAIN_TYPE == 'user') {
			return false;
		}
		// Guests can see nothing
		if (!strlen($link) || !main()->ADMIN_ID || MAIN_TYPE == 'user') {
			return false;
		}
		// Super-admin can see any links
		if (main()->ADMIN_GROUP === 1) {
			return true;
		}
		$u = array();
		parse_str(parse_url($link, PHP_URL_QUERY), $u);
		$u = (array)$u;
		if (isset($u['task']) && in_array($u['task'], array('login','logout'))) {
			return true;
		}
		return (int)_class('core_blocks')->_check_block_rights($this->CENTER_BLOCK_ID, $u['object'], $u['action']);
	}
}
