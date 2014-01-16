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
#		$this->CUR_SITE		= (int)conf('SITE_ID');
#		$this->CUR_SERVER	= (int)conf('SERVER_ID');
#		$this->CUR_LANG		= conf('language');
		$this->CENTER_BLOCK_ID = _class('core_blocks')->_get_center_block_id();
	}

	/**
	*/
	function _get_center_block_rules() {
		$rules = &$this->CENTER_BLOCK_RULES;
		if (isset($rules)) {
			return $rules;
		}
		$rules = array();
		foreach ((array)_class('core_blocks')->_blocks_rules as $rid => $rinfo) {
			if ($rinfo != $this->CENTER_BLOCK_ID) {
				continue;
			}
			$rules[$rid] = $rinfo;
		}
		$this->CENTER_BLOCK_RULES = $rules;
		return $rules;
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
#print_r($u);
// Temporary allow until checking code is done
#		return true;

#		$is_allowed = _class('core_blocks')->_check_block_rights($this->CENTER_BLOCK_ID, $object, $action);

/*
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
