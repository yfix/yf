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
		$this->ADMIN_URL_HOST	= parse_url(WEB_PATH, PHP_URL_HOST);
		$this->ADMIN_URL_PATH	= parse_url(WEB_PATH, PHP_URL_PATH);
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
		$link_parts = parse_url($link);
		// Outer links simply allowed
		if (isset($link_parts['scheme']) && $link_parts['host'] && $link_parts['path']) {
			if ($link_parts['host']. $link_parts['path'] != $this->ADMIN_URL_HOST. $this->ADMIN_URL_PATH) {
				return true;
			}
		}
		// Maybe this is also outer link and no need to block it (or maybe rewrited?)
		if (!isset($link_parts['query'])) {
			return true;
		}
		parse_str($link_parts['query'], $u);
		$u = (array)$u;
		if (isset($u['task']) && in_array($u['task'], array('login','logout'))) {
			return true;
		}
		return (int)_class('core_blocks')->_check_block_rights($this->CENTER_BLOCK_ID, $u['object'], $u['action']);
	}
}
