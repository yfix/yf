<?php

/**
* Common methods for admin section stored here
*/
class yf_common_admin {

	/**
	*/
	function _init() {
		$this->ADMIN_ID		= main()->ADMIN_ID;
		$this->ADMIN_GROUP	= main()->ADMIN_GROUP;
		$this->CUR_SITE		= (int)conf('SITE_ID');
		$this->CUR_SERVER	= (int)conf('SERVER_ID');
		$this->CUR_LANG		= conf('language');
	}

	/**
	*/
	function _admin_link_is_allowed($link = '') {
		// Guests can see nothing
		if (!strlen($link) || !main()->ADMIN_ID) {
			return false;
		}
		// Super-admin can see any links
		if (main()->ADMIN_GROUP === 1) {
			return true;
		}
/*
		if (empty($menu_id) || empty($this->_menu_items[$menu_id])) {
			return false;
		}
		if (MAIN_TYPE_ADMIN) {
			$CUR_USER_GROUP = (int)main()->ADMIN_GROUP;
		} elseif (MAIN_TYPE_USER) {
			$CUR_USER_GROUP = (int)main()->USER_GROUP;
			if (empty($CUR_USER_GROUP)) {
				$CUR_USER_GROUP = 1;
			}
		}
		$CUR_SITE		= (int)conf('SITE_ID');
		$CUR_SERVER		= (int)conf('SERVER_ID');

		$items_ids		= array();
		$items_array	= array();
		foreach ((array)$this->_menu_items[$menu_id] as $item_info) {
			if (!is_array($item_info)) {
				continue;
			}
			if ($item_info['parent_id'] != $parent_id) {
				continue;
			}
			if ($skip_item_id == $item_info['id']) {
				continue;
			}
			$user_groups = array();
			if (!empty($item_info['user_groups'])) {
				foreach (explode(',',$item_info['user_groups']) as $v) {
					if (empty($v)) {
						continue;
					}
					$user_groups[$v] = $v;
				}
			}
			if (!empty($user_groups) && !isset($user_groups[$CUR_USER_GROUP])) {
				continue;
			}
			// Process site ids
			$site_ids = array();
			if (!empty($item_info['site_ids'])) {
				foreach (explode(',',$item_info['site_ids']) as $v) {
					if (empty($v)) {
						continue;
					}
					$site_ids[$v] = $v;
				}
			}
			if (!empty($site_ids) && !isset($site_ids[$CUR_SITE])) {
				continue;
			}
			$server_ids = array();
			if (!empty($item_info['server_ids'])) {
				foreach (explode(',',$item_info['server_ids']) as $v) {
					if (empty($v)) {
						continue;
					}
					$server_ids[$v] = $v;
				}
			}
			if (!empty($server_ids) && !isset($server_ids[$CUR_SERVER])) {
				continue;
			}
			$items_array[$item_info['id']] = $item_info;
			$items_array[$item_info['id']]['level'] = $level;

			$tmp_array = $this->_recursive_get_menu_items($menu_id, $skip_item_id, $item_info['id'], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info['id'] == $item_info['id']) {
					continue;
				}
				$items_array[$sub_item_info['id']] = $sub_item_info;
			}
		}
		return $items_array;
*/
	}
}
