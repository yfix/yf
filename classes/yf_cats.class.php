<?php

/**
* Category display handler
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_cats {

	/** @var mixed @conf_skip */
	public $_category_sets		= null;
	/** @var mixed @conf_skip */
	public $_items_cache		= null;
	/** @var mixed @conf_skip */
	public $_default_callback	= null;
	/** @var mixed @conf_skip */
	public $_default_cats_block	= null;
	/** @var bool */
	public $USE_DYNAMIC_ATTS	= 1;
	/** @var string */
	public $BOX_LEVEL_SPACER	= '&nbsp;&nbsp;';
	/** @var string */
	public $BOX_LEVEL_MARKER	= '&#0124;-- ';

	/**
	*/
	function _init () {
		$this->_category_sets = main()->get_data('category_sets');
		$this->_default_cats_block = $_GET['object'].'_cats';
	}

	/**
	* Get all category items for the given block
	*/
	function _get_items_array($cat_name = '', $recursive_sort = true, $all = false) {
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		$cat_id = $this->_get_cat_id_by_name($cat_name);
		if (empty($cat_id)) {
			return false;
		}
		if (!isset($this->_items_cache[$cat_id])) {
			$cat_id = $this->_get_cat_id_by_name($cat_name);
			$custom_fields = array();
			if ($cat_id) {
				foreach (explode(',', $this->_category_sets[$cat_id]['custom_fields']) as $f) {
					$f = trim($f);
					if ($f) {
						$custom_fields[$f] = $f;
					}
				}
			}
			$cat_items = $all ? 'category_items_all': 'category_items';
			foreach ((array)main()->get_data($cat_items) as $a) {
				if ($a['cat_id'] != $cat_id) {
					continue;
				}
				// Try to parse 'dynamic' attributes for the item
				if ($this->USE_DYNAMIC_ATTS && $custom_fields) {
					if ($a['other_info']) {
						$custom_attrs = (array)$this->_convert_atts_string_into_array($a['other_info']);
					}
					foreach ((array)$custom_fields as $f) {
						$a[$f] = isset($custom_attrs[$f]) ? (string)$custom_attrs[$f]: '';
					}
				}
				$raw_items[$a['id']] = $a;
			}
			$this->_items_cache[$cat_id] = $raw_items;
		} else {
			$raw_items = $this->_items_cache[$cat_id];
		}
		if ($recursive_sort && !empty($raw_items)) {
			$raw_items = $this->_recursive_sort_items($raw_items);
		}
		return $raw_items ? $raw_items : false;
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($cat_items = array(), $skip_item_id = 0, $parent_id = 0, $level = 0) {
		$children = array();
		$cur_group = MAIN_TYPE_USER ? $_SESSION['user_group'] : $_SESSION['admin_group'];
		foreach ((array)$cat_items as $id => $info) {
			$parent_id = $info['parent_id'];
			if ($skip_item_id == $id) {
				continue;
			}
			$user_groups = array();
			if (!empty($info['user_groups'])) {
				foreach (explode(',',$info['user_groups']) as $v) {
					if (!empty($v)) {
						$user_groups[$v] = $v;
					}
				}
				if (!empty($user_groups) && !isset($user_groups[$cur_group])) {
					continue;
				}
			}
			$children[$parent_id][$id] = $id;
		}
		$ids = $this->_count_levels(0, $children);
		$new_items = array();
		foreach ((array)$ids as $id => $level) {
			$new_items[$id] = $cat_items[$id] + array('level' => $level);
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

	/**
	* Convert string attributes (from field 'other_info') into array
	*/
	function _convert_atts_string_into_array($string = '') {
		$output_array = array();
		foreach (explode(';', trim($string)) as $tmp_string) {
			list($try_key, $try_value) = explode('=', trim($tmp_string));
			$try_key	= trim(trim(trim($try_key), '"'));
			$try_value	= trim(trim(trim($try_value), '"'));
			if (strlen($try_key) && strlen($try_value)) {
				$output_array[$try_key] = $try_value;
			}
		}
		return $output_array;
	}

	/**
	* Get all category items names for the given block
	*/
	function _get_items_names($cat_name = '') {
		$items = array();
		foreach ((array)$this->_get_items_array($cat_name) as $item_id => $item_info) {
			$items[$item_info['id']] = $item_info['name'];
		}
		return $items;
	}

	/**
	*/
	function _get_items_names_cached($cat_name = '') {
		$cache_name = 'cats__get_items_names__'.$cat_name;
		$items = cache_get($cache_name);
		if ($items) {
			return $items;
		}
		$items = $this->_get_items_names($cat_name);
		cache_set($cache_name, $items);
		return $items;
	}

	/**
	*/
	function _prepare_for_box_cached($cat_name = '', $with_all = 1, $parent_item_id = 0) {
		$cache_name = 'cats__prepare_for_box__'.$cat_name.'_'.$with_all.'_'.$parent_item_id;
		$items = cache_get($cache_name);
		if ($items) {
			return $items;
		}
		$items = $this->_prepare_for_box($cat_name, $with_all, $parent_item_id);
		cache_set($cache_name, $items);
		return $items;
	}

	/**
	* Prepare category items for use in box
	*/
	function _prepare_for_box($cat_items = array(), $with_all = true, $parent_item_id = 0) {
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		$items_for_box = array();
		if ($with_all) {
			$items_for_box[' ']	= t('-- All --');
		}
		$only_children_ids = array();
		if ($parent_item_id) {
			// build list of children allowed and show only them
			$only_children_ids[$parent_item_id] = $parent_item_id;
			foreach ((array)$this->_recursive_get_children_ids($parent_item_id, $cat_items, $get_sub_children = true, $return_array = true) as $cid => $cinfo) {
				$only_children_ids[$cid] = $cid;
			}
		}
		foreach ((array)$cat_items as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) {
				continue;
			}
			if ($only_children_ids && !isset($only_children_ids[$cur_item_id])) {
				continue;
			}
			$items_for_box[$cur_item_id] = str_repeat($this->BOX_LEVEL_SPACER, $cur_item_info['level'])
				.($cur_item_info['level'] > 0 ? $this->BOX_LEVEL_MARKER : '')
				.$cur_item_info['name'];
		}
		return $items_for_box;
	}

	/**
	* Prepare category items for use in box
	*/
	function _get_items_for_box($cat_name = '', $with_all = true) {
		return $this->_prepare_for_box($this->_get_items_array($cat_name), $with_all);
	}

	/**
	* Display category block items box
	*/
	function _cats_box($cat_name = '', $selected = '', $name_in_form = 'cat_id', $with_all = 1) {
		$items = $this->_get_items_for_box($cat_name, $with_all);
		return common()->select_box($name_in_form, $items, $selected, false, 2, '', false);
	}

	/**
	*/
	function _get_cat_id_by_name($cat_name = '') {
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		if (empty($cat_name)) {
			return false;
		}
		$cat_id = 0;
		foreach ((array)$this->_category_sets as $cur_cat_id => $cur_cat_info) {
			if ($cur_cat_info['name'] == $cat_name) {
				$cat_id = $cur_cat_id;
				break;
			}
		}
		return $cat_id;
	}

	/**
	*/
	function _get_cat_name_by_id($cat_id = '') {
		if (empty($cat_id)) {
			return false;
		}
		return $this->_category_sets[$cat_id]['name'];
	}

	/**
	*/
	function _recursive_get_parents_ids($cat_id = 0, $cat_items = array()) {
		$parents_ids = array();
		if (empty($cat_id)) {
			return $parents_ids;
		}
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items , false);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block, false);
		}
		$cur_func_name = __FUNCTION__;
		foreach ((array)$cat_items as $cur_item_info) {
			if ($cur_item_info['id'] != $cat_id) {
				continue;
			}
			if (!empty($cur_item_info['parent_id'])) {
				$parents_ids[$cur_item_info['parent_id']] = $cur_item_info['parent_id'];
				foreach ((array)$this->$cur_func_name($cur_item_info['parent_id'], $cat_items) as $cur_parent_id) {
					$parents_ids[$cur_parent_id] = $cur_parent_id;
				}
			}
		}
		return $parents_ids;
	}

	/**
	*/
	function _get_nav_by_item_id($item_id = 0, $cat_items = array(), $STPL_NAME = '', $prepare_link_callback = null) {
		if (empty($STPL_NAME)) {
			$STPL_NAME = __CLASS__.'/nav_item';
		}
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		if (!isset($this->_default_callback)) {
			$this->_default_callback = false;
			if (!empty($_GET['object'])) {
				$try_callback = array(module($_GET['object']), '_callback_cat_link');
			}
			if (is_callable($try_callback)) {
				$this->_default_callback = $try_callback;
			}
		}
		if (empty($prepare_link_callback) && !empty($this->_default_callback)) {
			$prepare_link_callback = $this->_default_callback;
		}
		$USE_CALLBACK = !empty($prepare_link_callback) && is_callable($prepare_link_callback);
		$parents_ids = $this->_recursive_get_parents_ids($item_id, $cat_items);
		if (!empty($parents_ids)) {
			$nav_items_ids = array_reverse($parents_ids, 1);
		}
		$nav_items_ids[$item_id] = $item_id;
		foreach ((array)$nav_items_ids as $cur_item_id) {
			if ($USE_CALLBACK) {
				$item_link = call_user_func($prepare_link_callback, $cur_item_id);
			} else {
				$item_link = './?object='.$_GET['object'].'&action=view_cat&id='.$cur_item_id;
			}
			$replace = array(
				'item_link'	=> $item_link,
				'item_name'	=> _prepare_html($cat_items[$cur_item_id]['name']),
				'is_last'	=> (int)(++$i >= count($nav_items_ids)),
			);
			$body .= tpl()->parse($STPL_NAME, $replace);
		}
		return $body;
	}

	/**
	*/
	function _recursive_get_children_ids($cat_id = 0, $cat_items = array(), $get_sub_children = true, $return_array = false) {
		$children_ids = array();
		if (empty($cat_id)) {
			return $children_ids;
		}
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		$cur_func_name = __FUNCTION__;
		foreach ((array)$cat_items as $cur_item_info) {
			if ($cur_item_info['parent_id'] != $cat_id) {
				continue;
			}
			$sub_children = array();
			if ($get_sub_children) {
				$sub_children = $this->$cur_func_name($cur_item_info['id'], $cat_items, $get_sub_children);
			}
			if ($return_array) {
				$children_ids[$cur_item_info['id']] = $cur_item_info['id'];
				$children_ids = $children_ids + (array)$sub_children;
			} else {
				$children_ids[$cur_item_info['id']] = $sub_children;
			}
		}
		return $children_ids;
	}

	/**
	*/
	function _get_item_name($item_id = '', $cat_name = '') {
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		$cat_id = $this->_get_cat_id_by_name($cat_name);
		if (empty($cat_id) || empty($item_id)) {
			return false;
		}
		return $this->_items_cache[$cat_id][$item_id]['name'];
	}

	function _get_recursive_cat_ids ($cat_id = 0, $all_cats = false) {
		$cat_id = intval($cat_id);
		if (empty($all_cats)) {
			$all_cats = conf('all_cats');
			if (empty($all_cats)) {
				$all_cats = main()->get_data('category_items_all');
				if (empty($all_cats)) {
					return false;
				}
				conf('all_cats', $all_cats);
			}
		}

		$current_func = __FUNCTION__;
		$ids[$cat_id] = $cat_id;
		foreach ($all_cats as $key => $item) {
			if ($item['parent_id'] == $cat_id) {
				$ids += $this->$current_func($item['id'], $all_cats);
			}
		}

		return $ids;
	}
}
