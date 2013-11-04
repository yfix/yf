<?php

// Navigation bar handler
class yf_site_nav_bar {

	/** @var string */
	public $HOOK_NAME = '_nav_bar_items';

	// Display navigation bar
	function _show () {
		$items = array();

		if (in_array($_GET['object'], array())) {

		} else {
			if (!in_array($_GET['action'], array('', 'show'))) {
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['object']), './?object='.$_GET['object']);
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['action']));
			} else {
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['object']));
			}
		}
		if (empty($items)) {
			return false;
		}
		array_unshift($items, $this->_nav_item('Home', './?object=admin_home', 'icon-home fa-home'));
		if (!empty($this->HOOK_NAME)) {
			$CUR_OBJ = module($_GET['object']);
		}
		if (is_object($CUR_OBJ) && method_exists($CUR_OBJ, $this->HOOK_NAME)) {
			$hook_params = array(
				'nav_bar_obj'	=> &$this,
				'items'			=> $items,
			);
			$func = $this->HOOK_NAME;
			$hooked_items = $CUR_OBJ->$func($hook_params);
		}
		// Hook have max priority
		if (!empty($hooked_items)) {
			$items = $hooked_items;
		}
		$replace = array(
			'items'			=> is_array($items) ? implode(tpl()->parse('site_nav_bar/div'), $items) : '',
			'is_logged_in'	=> intval((bool) $_SESSION['user_id']),
			'bookmark_page'	=> $bookmark_page_code,
		);
		return tpl()->parse('site_nav_bar/main', $replace);
	}

	// Display navigation bar item
	function _nav_item ($name = '', $nav_link = '', $nav_icon = '') {
		$replace = array(
			'name'			=> _prepare_html($name),
			'link'			=> $nav_link,
			'icon'			=> $nav_icon,
			'as_link'		=> !empty($nav_link) ? 1 : 0,
			'is_logged_in'	=> intval((bool) $_SESSION['user_id']),
		);
		return tpl()->parse('site_nav_bar/item', $replace);
	}

	// Get root categories array
	function _get_root_cat_ids () {
		foreach ((array)$this->_cats as $A) {
			if ($A['parent_id'] == 0) $root_ids[$A['id']] = $A['id'];
		}
		return $root_ids;
	}

	// 
	function _get_cat_id_by_name ($cat_name = '') {
		$cat_id = 0;
		if (empty($cat_name)) {
			$cat_name = $_GET['cat_name'];
		}
		$cat_name = strtolower(str_replace(' ', '_', $cat_name));
		foreach ((array)$this->_cats as $A) {
			if (strtolower(str_replace(' ', '_', $A['name'])) == $cat_name) {
				$cat_id = $A['id'];
				break;
			}
		}
		return $cat_id;
	}

	// 
	function _get_parent_cat_name ($cat_id = '') {
		$parent_id = $this->_cats[$cat_id]['parent_id'];
		return $this->_cats[$parent_id]['name'];
	}

	// 
	function _get_city_id_by_name ($city_name = '') {
		$city_id = 0;
		if (empty($city_name)) {
			$city_name = $_GET['city'];
		}
		$city_name = strtolower(str_replace('_', ' ', $city_name));
		foreach ((array)$GLOBALS['cities'] as $A) {
			if (strtolower($A['phrase']) == $city_name) {
				$city_id = $A['id'];
				break;
			}
		}
		return $city_id;
	}

	// 
	function _get_city_name_by_id ($city_id = '') {
		return $this->_cities[$city_id]['phrase'];
	}

	// Decode name
	function _decode_from_url ($text = '') {
		return ucwords(str_replace('_', ' ', $text));
	}

	// Encode name
	function _encode_for_url ($text = '') {
		return strtolower(str_replace(' ', '_', $text));
	}

	/**
	*/
	function _show_dropdown_menu () {
		$items = _class('graphics')->_show_menu(array(
			'name'				=> 'admin_home_menu',
			'force_stpl_name'	=> 'site_nav_bar/dropdown_menu',
			'return_array'		=> 1,
		));
		foreach ((array)$items as $id => $item) {
			$item['need_clear'] = 0;
			if ($item['type_id'] == 1 && !module('admin_home')->_url_allowed($item['link'])) {
				unset($items[$id]);
				continue;
			}
			$items[$id] = tpl()->parse('site_nav_bar/dropdown_menu_item', $item);
		}
		return tpl()->parse('site_nav_bar/dropdown_menu', array(
			'items' => implode('', (array)$items)
		));
	}
}
