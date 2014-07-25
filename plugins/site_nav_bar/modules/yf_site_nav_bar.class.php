<?php

// Navigation bar handler
class yf_site_nav_bar {

	/** @var string */
	public $HOOK_NAME		= '_nav_bar_items';
	/** @var string */
	public $HOME_LOCATION	= './';
	/** @var bool */
	public $AUTO_TRANSLATE = true;
	/** @var bool */
	public $SHOW_NAV_BAR	= true;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	// Display navigation bar
	function _show ($return_array = false) {
		if ($return_array) {
			$this->_nav_item_as_array = true;
		}
		$items = array();
		// Switch between specific actions
		if (in_array($_GET['object'], array('', 'home_page'))) {
			// Empty
		} else {
			if (!in_array($_GET['action'], array('', 'show'))) {
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['object']), './?object='.$_GET['object']);
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['action']));
			} else {
				$items[]	= $this->_nav_item($this->_decode_from_url($_GET['object']));
			}
		}
		// Add first item to all valid items
		array_unshift($items, $this->_nav_item('Home', $this->HOME_LOCATION, 'icon-home fa-home'));
		// Try to get items from hook '_nav_bar_items'
		if (!empty($this->HOOK_NAME)) {
			$CUR_OBJ = module($_GET['object']);
			if (is_object($CUR_OBJ) && method_exists($CUR_OBJ, $this->HOOK_NAME)) {
				$hook_params = array(
					'nav_bar_obj'	=> $this,
					'items'			=> $items,
				);
				$func = $this->HOOK_NAME;
				$hooked_items = $CUR_OBJ->$func($hook_params);
			}
		}
		// Do not show nav bar if hooked code set that
		if (!$this->SHOW_NAV_BAR) {
			return false;
		}
		// Stop here if gathered nothing
		if (count($items) == 1) {
			return false;
		}
		// Hook have max priority
		if (!empty($hooked_items)) {
			$items = $hooked_items;
		}
		if ($return_array) {
			$this->_nav_item_as_array = false;
			return $items;
		}
		$replace = array(
			'items'			=> implode(tpl()->parse(__CLASS__.'/div'), $items),
			'is_logged_in'	=> intval((bool) (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
			'bookmark_page'	=> isset($bookmark_page_code) ? $bookmark_page_code : '',
		);
		return tpl()->parse(__CLASS__.'/main', $replace);
	}

	// Display navigation bar item
	function _nav_item ($name = '', $nav_link = '', $nav_icon = '') {
		if ($this->AUTO_TRANSLATE) {
			$name = t($name);
		}
		$replace = array(
			'name'			=> _prepare_html($name),
			'link'			=> $nav_link,
			'icon'			=> $nav_icon,
			'as_link'		=> !empty($nav_link) ? 1 : 0,
			'is_logged_in'	=> intval((bool) (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0)),
		);
		if ($this->_nav_item_as_array) {
			return $replace;
		}
		return tpl()->parse(__CLASS__.'/item', $replace);
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
			'name'				=> 'user_main_menu',
			'force_stpl_name'	=> 'site_nav_bar/dropdown_menu',
			'return_array'		=> 1,
		));
		if (!$items) {
			return false;
		}
		foreach ((array)$items as $id => $item) {
			$item['need_clear'] = 0;
			if ($item['type_id'] != 1/* $item['type_id'] == 1 && !module('admin_home')->_url_allowed($item['link'])*/) {
				unset($items[$id]);
				continue;
			}
			$items[$id] = tpl()->parse('site_nav_bar/dropdown_menu_item', $item);
		}
		return tpl()->parse('site_nav_bar/dropdown_menu', array(
			'items' => implode('', (array)$items)
		));
	}

	/**
	*/
	function _breadcrumbs () {
		$items = $this->_show($return_array = true);
		if (count($items) <= 1) {
			return false;
		}
		foreach ($items as $v) {
			$a[] = array(
				'link'	=> $v['as_link'] ? $v['link'] : false,
				'name'	=> $v['name'],
			);
		}
		css('.navbar { margin-bottom: 0; }');
		return _class('html')->breadcrumbs($a);
	}
}
