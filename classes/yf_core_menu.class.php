<?php

/**
* Menu API methods
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_core_menu {

	/** @conf_skip */
	public $USE_DYNAMIC_ATTS = true;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Show menu (alias for the '_show_menu')
	*/
	function show ($params) {
		return $this->_show_menu($params);
	}

	/**
	* Show menu (alias for the '_show_menu')
	*/
	function show_menu ($params) {
		return $this->_show_menu($params);
	}

	/**
	* Show menu
	* 	$_item_types = array(
	*		1 => 'Internal link',
	*		2 => 'External link',
	*		3 => 'Spacer',
	*		4 => 'Divider',
	*	);
	*/
	function _show_menu ($params = array()) {
		$return_array	= isset($params['return_array']) ? $params['return_array'] : false;
		$force_stpl_name= isset($params['force_stpl_name']) ? $params['force_stpl_name'] : false;
		$menu_name		= $params['name'];
		if (empty($menu_name)) {
			trigger_error(__CLASS__.': Given empty menu name to display', E_USER_WARNING);
			return false;
		}
		$cur_menu_info	= $this->_get_cur_menu_info($menu_name);
		if (!$cur_menu_info['active']) {
			return false;
		}
		_class('core_events')->fire('core.before_menu', array(
			'name'		=> $menu_name,
			'info'		=> $cur_menu_info,
			'params'	=> $params,
		));
		if ($force_stpl_name) {
			$cur_menu_info['stpl_name'] = $force_stpl_name;
		}
		$stpl_item		= !empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'].'_item' : 'system/menu_item';
		$stpl_main 		= !empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'] : 'system/menu_main';
		$stpl_pad		= !empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'].'_pad' : 'system/menu_pad';
		$level_pad_text	= tpl()->parse($stpl_pad);

		$menu_items = $this->_get_menu_items($cur_menu_info);
		if (empty($menu_items)) {
			return false;
		}
		$num_menu_items = count($menu_items);
		$_prev_level = 0;
		$_next_level = 0;
		$item_counter = 0;
		$in_output_cache = main()->_IN_OUTPUT_CACHE;
		$ICONS_DIR = _class('graphics')->ICONS_PATH;
		$MEDIA_PATH = _class('graphics')->MEDIA_PATH;

		foreach ((array)$menu_items as $i => $item) {
			$item_counter++;
			$_next_info	= isset($menu_items[$i + 1]) ? $menu_items[$i + 1] : array();
			$_next_level = isset($_next_info['level']) ? (int)$_next_info['level'] : 0;
			$is_cur_page = false;
			$item_link = '';
			if (substr($item['location'], 0, 3) == './?') {
				$item['location'] = substr($item['location'], 3);
			}
			// Internal link
			if ($item['type_id'] == 1 && strlen($item['location']) > 0) {
				$is_cur_page = $this->_is_current_page($item);
				$item_link = './?'.$item['location'];
			} elseif ($item['type_id'] == 2) {
				$item_link = $item['location'];
			}
			$icon = trim($item['icon']);
			$icon_path = '';
			$icon_class = '';
			if ($icon) {
				// Icon class from bootstrap icon class names 
				if (preg_match('/^icon\-[a-z0-9_-]+$/i', $icon) || (strpos($icon, '.') === false)) {
					$icon_class = $icon;
				} else {
					$_icon_fs_path = PROJECT_PATH. $ICONS_DIR. $icon;
					if (file_exists($_icon_fs_path)) {
						$icon_path = $MEDIA_PATH. $ICONS_DIR. $icon;
					}
				}
			}
			$items[$item['id']] = array(
				'item_id'		=> intval($item['id']),
				'parent_id'		=> intval($item['parent_id']),
				'bg_class'		=> !(++$i % 2) ? 'bg1' : 'bg2',
				'link'			=> !empty($in_output_cache) ? process_url($item_link) : $item_link,
				'name'			=> _prepare_html(t($item['name'])),
				'level_pad'		=> str_repeat($level_pad_text, $item['level']),
				'level_num'		=> intval($item['level']),
				'prev_level'	=> intval($_prev_level),
				'next_level'	=> intval($_next_level),
				'type_id'		=> $item['type_id'],
				'icon_path'		=> $icon_path,
				'icon_class'	=> $icon_class,
				'is_first_item'	=> (int)($item_counter == 1),
				'is_last_item'	=> (int)($item_counter == $num_menu_items),
				'is_cur_page'	=> (int)$is_cur_page,
				'have_children'	=> intval((bool)$item['have_children']),
				'next_level_diff'=> intval(abs($item['level'] - $_next_level)),
			);
			// Save current level for the next iteration
			$_prev_level = $item['level'];
		}
		_class('core_events')->fire('core.after_menu', array(
			'name'		=> $menu_name,
			'items'		=> $items,
			'params'	=> $params,
		));
		if ($return_array) {
			return $items;
		}
		foreach ((array)$items as $id => $item) {
			$items[$id] = tpl()->parse($stpl_item, $item);
		}
		return tpl()->parse($stpl_main, array(
			'items' => implode(PHP_EOL, (array)$items),
		));
	}

	/**
	*/
	function _get_menu_items($cur_menu_info) {
		$menu_id = $cur_menu_info['id'];
		$menu_name = $cur_menu_info['name'];
		if (!isset($this->_menu_items)) {
			$this->_menu_items = main()->get_data('menu_items');
		}
		// Do not show menu if there is no items in it
		if (empty($this->_menu_items[$menu_id])) {
			return false;
		}
		// Check if we need to call special menu handler
		$special_class_name = '';
		$special_method_name = '';
		if (false !== strpos($cur_menu_info['method_name'], '.')) {
			list($special_class_name, $special_method_name) = explode('.', $cur_menu_info['method_name']);
		}
		$special_params = array(
			'menu_name'	=> $menu_name,
			'menu_id'	=> $menu_id,
		);
		$menu_items = array();
		if (!empty($special_class_name) && !empty($special_method_name)) {
			$menu_items = _class($special_class_name, $special_path)->$special_method_name($special_params);
		} else {
			$menu_items = $this->_recursive_get_menu_items($menu_id);
		}
		// Support for custom fields, that will be available as menu_items array keys
		$menu_items = $this->_apply_custom_fields($cur_menu_info, $menu_items);
		$menu_items = $this->_cleanup_menu_items($menu_items);
		return $menu_items;
	}

	/**
	*/
	function _get_cur_menu_info($menu_name) {
		if (!isset($this->_menus_infos)) {
			$this->_menus_infos = main()->get_data('menus');
		}
		if (empty($this->_menus_infos)) {
			if (!$this->_error_no_menus_raised) {
				trigger_error(__CLASS__.': Menus info not loaded', E_USER_WARNING);
				$this->_error_no_menus_raised = true;
			}
			return false;
		}
		$menu_id = 0;
		foreach ((array)$this->_menus_infos as $menu_info) {
			if ($menu_info['type'] != MAIN_TYPE) {
				continue;
			}
			if ($menu_info['name'] == $menu_name) {
				$menu_id = $menu_info['id'];
				break;
			}
		}
		if (!$menu_id) {
			trigger_error(__CLASS__.': Menu name "'._prepare_html($menu_name).'" not found in menus list', E_USER_WARNING);
			return false;
		}
		return $this->_menus_infos[$menu_id];
	}

	/**
	*/
	function _apply_custom_fields($cur_menu_info, $menu_items) {
		$custom_fields = array();
		if ($cur_menu_info['custom_fields']) {
			foreach (explode(',', str_replace(';', ',', trim($cur_menu_info['custom_fields']))) as $f) {
				$f = trim($f);
				if ($f) {
					$custom_fields[$f] = $f;
				}
			}
		}
		if ($this->USE_DYNAMIC_ATTS && $custom_fields) {
			foreach ((array)$menu_items as $item_id => $item_info) {
				$custom_attrs = array();
				if (!strlen($item_info['other_info'])) {
					continue;
				}
				$custom_attrs = (array)_attrs_string2array($item_info['other_info']);
				foreach ((array)$custom_fields as $f) {
					$menu_items[$item_id][$f] = strval($custom_attrs[$f]);
				}
			}
		}
		return $menu_items;
	}

	/**
	*/
	function _cleanup_menu_items($menu_items = array()) {
		$center_block_id = _class('graphics')->_get_center_block_id();

		$out = array();
		foreach ((array)$menu_items as $item_id => $item) {
			if (empty($item)) {
				continue;
			}
			// Check PHP conditional code for display
			if (!empty($item['cond_code'])) {
				$cond_result = (bool)eval('return ('.$item['cond_code'].');');
				if (!$cond_result) {
					continue;
				}
			}
			if (substr($item['location'], 0, 3) == './?') {
				$item['location'] = substr($item['location'], 3);
			}
			// Internal link
			if ($item['type_id'] == 1 && strlen($item['location']) > 0) {
				parse_str($item['location'], $_item_parts);
				if (_class('graphics')->MENU_HIDE_INACTIVE_MODULES) {
					if (!isset($this->_active_modules)) {
						$cl_name = MAIN_TYPE_USER ? 'user_modules' : 'admin_modules';
						$this->_active_modules = _class($cl_name, 'admin_modules/')->_get_modules();
					}
					if ($_item_parts['object'] && !isset($this->_active_modules[$_item_parts['object']])) {
						continue;
					}
					if ($center_block_id && !_class('graphics')->_check_block_rights($center_block_id, $_item_parts['object'], $_item_parts['action']) && $_item_parts['task'] != 'logout') {
						continue;
					}
				}
			}
			$out[] = $item;
		}
		// Check for empty blocks starts with spacers
		if (_class('graphics')->MENU_HIDE_INACTIVE_MODULES) {
			foreach ((array)$out as $i => $item) {
				if ($item['level_num'] == 0 && $item['type_id'] == 3) {
					$next_item = $out[$i + 1];
					if (!$next_item || ($next_item['level_num'] == 0 && $next_item['type_id'] == 3)) {
						unset($out[$i]);
					}
				}
			}
		}
		return $out;
	}

	/**
	*/
	function _is_current_page(&$item) {
		$is_cur_page = false;
		parse_str($item['location'], $_item_parts);
		// Check if we are on the current page
		if (isset($_item_parts['object']) && $_item_parts['object'] && $_item_parts['object'] == $_GET['object']) {
			if (isset($_item_parts['action']) && $_item_parts['action']) {
				if ($_item_parts['action'] == $_GET['action']) {
					// Needed for static pages
					if ($_item_parts['id']) {
						if ($_item_parts['id'] == $_GET['id']) {
							$is_cur_page = true;
						}
					} else {
						$is_cur_page = true;
					}
				}
			} else {
				$is_cur_page = true;
			}
		}
		return $is_cur_page;
	}

	/**
	* Template for the custom class method for menu block (useful to inherit)
	*/
	function _custom_menu_items($params = array()) {
		// Example what passes by params
		$params = array(
			'menu_name'	=> $menu_name,
			'menu_id'	=> $menu_id,
		);
		return false;
	}

	/**
	* Get menu items ordered array (recursively)
	*/
	function _recursive_get_menu_items($menu_id = 0, $skip_item_id = 0) {
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
		foreach ((array)$this->_menu_items[$menu_id] as $item) {
			if ($skip_item_id == $item['id']) {
				continue;
			}
			if (!empty($item['user_groups'])) {
				$user_groups = array();
				foreach (explode(',',$item['user_groups']) as $v) {
					if (!empty($v)) {
						$user_groups[$v] = $v;
					}
				}
				if (!empty($user_groups) && !isset($user_groups[$CUR_USER_GROUP])) {
					continue;
				}
			}
			if (!empty($item['site_ids'])) {
				$site_ids = array();
				foreach (explode(',',$item['site_ids']) as $v) {
					if (!empty($v)) {
						$site_ids[$v] = $v;
					}
				}
				if (!empty($site_ids) && !isset($site_ids[$CUR_SITE])) {
					continue;
				}
			}
			if (!empty($item['server_ids'])) {
				$server_ids = array();
				foreach (explode(',',$item['server_ids']) as $v) {
					if (!empty($v)) {
						$server_ids[$v] = $v;
					}
				}
				if (!empty($server_ids) && !isset($server_ids[$CUR_SERVER])) {
					continue;
				}
			}
			$items_array[$item['id']] = $item;
		}
		return $this->_recursive_sort_items($items_array, $skip_item_id);
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($items = array(), $skip_item_id = 0) {
		$children = array();
		foreach ((array)$items as $id => $info) {
			$parent_id = $info['parent_id'];
			if ($skip_item_id == $id) {
				continue;
			}
			$children[$parent_id][$id] = $id;
		}
		$ids = $this->_count_levels(0, $children);
		$new_items = array();
		foreach ((array)$ids as $id => $level) {
			$new_items[$id] = $items[$id] + array('level' => $level);
		}		
		return $new_items;
	}

	/**
	*/
	function _count_levels($start_id = 0, &$children, $level = 0) {
		$ids = array();
		foreach ((array)$children[$start_id] as $id => $_tmp) {
			$ids[$id] = $level;
			if (isset($children[$id])) {
				foreach ((array)$this->_count_levels($id, $children, $level + 1) as $_id => $_level) {
					$ids[$_id] = $_level;
				}
			}
		}
		return $ids;
	}
}
