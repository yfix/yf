<?php

/**
* Category display handler
*
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_cats {

	/** @var mixed @conf_skip */
	var $_cats_blocks			= null;
	/** @var mixed @conf_skip */
	var $_items_cache			= null;
	/** @var mixed @conf_skip */
	var $_default_callback		= null;
	/** @var mixed @conf_skip */
	var $_default_cats_block	= null;
	/** @var bool */
	var $USE_DYNAMIC_ATTS		= 1;

	/**
	* Constructor
	*/
	function _init () {
		// Get available cats blocks
		$this->_cats_blocks = main()->get_data("cats_blocks");
		// Try to get default callback for the current $_GET["object"]
		if (!empty($_GET["object"])) {
			$try_callback = array(module($_GET["object"]), "_callback_cat_link");
			if (is_callable($try_callback)) {
				$this->_default_callback = $try_callback;
			}
		}
		// Try to get default categories block name
		$this->_default_cats_block = $_GET["object"]."_cats";
	}

	/**
	* Get all category items for the given block
	*/
	function _get_items_array($cat_name = "", $recursive_sort = true) {
		// Default items array for the current $_GET["object"]
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		// Check if such block exists
		$cat_id = $this->_get_cat_id_by_name($cat_name);
		if (empty($cat_id)) {
			return false;
		}
		// Do get current cat items
		if (!isset($this->_items_cache[$cat_id])) {

			foreach ((array)main()->get_data("cat_items") as $A) {
				if ($A["cat_id"] != $cat_id) {
					continue;
				}

				$raw_items_array[$A["id"]] = $A;
				// Try to parse "dynamic" attributes for the item
				if ($this->USE_DYNAMIC_ATTS && !empty($A["other_info"])) {
					foreach ((array)$this->_convert_atts_string_into_array($A["other_info"]) as $tmp_k => $tmp_v) {
						$raw_items_array[$A["id"]][$tmp_k] = $tmp_v;
					}
					$raw_items_array[$A["id"]]["other_info"] = "";
				}
			}
			// Put data to memory cache
			$this->_items_cache[$cat_id] = $raw_items_array;
		} else {
			$raw_items_array = $this->_items_cache[$cat_id];
		}
		
		if($recursive_sort && !empty($raw_items_array)){
			$raw_items_array = $this->_recursive_sort_items($raw_items_array);
		}
		
		// Get sorted items
		return $raw_items_array ?  $raw_items_array: false;
	}

	/**
	* Convert string attributes (from field "other_info") into array
	*/
	function _convert_atts_string_into_array($string = "") {
		$output_array = array();
		foreach (explode(";", trim($string)) as $tmp_string) {
			list($try_key, $try_value) = explode("=", trim($tmp_string));
			$try_key	= trim($try_key, "\"");
			$try_value	= trim($try_value, "\"");
			if (strlen($try_key) && strlen($try_value)) {
				$output_array[$try_key] = $try_value;
			}
		}
		return $output_array;
	}

	/**
	* Get all category items names for the given block
	*/
	function _get_items_names($cat_name = "") {
		$items_names = array();
		foreach ((array)$this->_get_items_array($cat_name) as $item_id => $item_info) {
			$items_names[$item_info["id"]] = $item_info["name"];
		}
		return $items_names;
	}

	/**
	* Prepare category items for use in box
	*/
	function _prepare_for_box($cat_items = array(), $with_all = 1) {
		// Default items array for the current $_GET["object"]
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		$items_for_box = array();
		if ($with_all) {
			$items_for_box[" "]	= t("-- All --");
		}
		// Process items
		foreach ((array)$cat_items as $cur_item_info) {
			$cur_item_id = $cur_item_info["id"];
			if (empty($cur_item_id)) {
				continue;
			}
			$items_for_box[$cur_item_id] = str_repeat("&nbsp;", $cur_item_info["level"] * 6).($cur_item_info["level"] > 0 ? /*" &#9492; "*/"&#0124;-- " : "").t($cur_item_info["name"]);
		}
		return $items_for_box;
	}

	/**
	* Prepare category items for use in box
	*/
	function _get_items_for_box($cat_name = "", $with_all = 1) {
		return $this->_prepare_for_box($this->_get_items_array($cat_name), $with_all);
	}

	/**
	* Display category block items box
	*/
	function _cats_box($cat_name = "", $selected = "", $name_in_form = "cat_id", $with_all = 1) {
		$items = $this->_get_items_for_box($cat_name, $with_all);
		return common()->select_box($name_in_form, $items, $selected, false, 2, "", false);
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($cat_items = array(), $skip_item_id = 0, $parent_id = 0, $level = 0) {
		$items_ids		= array();
		$items_array	= array();
		// Default items array for the current $_GET["object"]
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		// Get items from the current level
		foreach ((array)$cat_items as $item_info) {
			// Skip items from other parents
			if ($item_info["parent_id"] != $parent_id) {
				continue;
			}
			// Skip item if needed (and all its children)
			if ($skip_item_id == $item_info["id"]) {
				continue;
			}
			// Process user groups
			$user_groups = array();
			if (!empty($item_info["user_groups"])) {
				foreach (explode(",",$item_info["user_groups"]) as $v) {
					if (empty($v)) {
						continue;
					}
					$user_groups[$v] = $v;
				}
				if (!empty($user_groups) && !isset($user_groups[MAIN_TYPE_USER ? $_SESSION["user_group"] : $_SESSION["admin_group"]])) {
					continue;
				}
			}
			// Add item to the result array
			$items_array[$item_info["id"]] = $item_info;
			$items_array[$item_info["id"]]["level"] = $level;
			// Try to find sub items
			$tmp_array = $this->_recursive_sort_items($cat_items, $skip_item_id, $item_info["id"], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info["id"] == $item_info["id"]) {
					continue;
				}
				$items_array[$sub_item_info["id"]] = $sub_item_info;
			}
		}
		return $items_array;
	}

	/**
	* Helper method
	*/
	function _get_cat_id_by_name($cat_name = "") {
		// Default items array for the current $_GET["object"]
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		if (empty($cat_name)) {
			return false;
		}
		$cat_id = 0;
		foreach ((array)$this->_cats_blocks as $cur_cat_id => $cur_cat_info) {
			if ($cur_cat_info["name"] == $cat_name) {
				$cat_id = $cur_cat_id;
				break;
			}
		}
		return $cat_id;
	}

	/**
	* Helper method
	*/
	function _get_cat_name_by_id($cat_id = "") {
		if (empty($cat_id)) {
			return false;
		}
		return $this->_cats_blocks[$cat_id]["name"];
	}

	/**
	* Helper method
	*/
	function _recursive_get_parents_ids($cat_id = 0, $cat_items = array()) {
		$parents_ids = array();
		if (empty($cat_id)) {
			return $parents_ids;
		}
		// Default items array for the current $_GET["object"]
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items , false);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block, false);
		}
		$cur_func_name = __FUNCTION__;
		// Process items
		foreach ((array)$cat_items as $cur_item_info) {
			// Skip non-matched items
			if ($cur_item_info["id"] != $cat_id) {
				continue;
			}
			// Add parent id
			if (!empty($cur_item_info["parent_id"])) {
				$parents_ids[$cur_item_info["parent_id"]] = $cur_item_info["parent_id"];
				// Try to find sub parents
				foreach ((array)$this->$cur_func_name($cur_item_info["parent_id"], $cat_items) as $cur_parent_id) {
					$parents_ids[$cur_parent_id] = $cur_parent_id;
				}
			}
		}
		return $parents_ids;
	}

	/**
	* Helper method
	*/
	function _get_nav_by_item_id($item_id = 0, $cat_items = array(), $STPL_NAME = "", $prepare_link_callback = null) {
		if (empty($STPL_NAME)) {
			$STPL_NAME = __CLASS__."/nav_item";
		}
		// Default items array for the current $_GET["object"]
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		// Try to assign default callback method
		if (empty($prepare_link_callback) && !empty($this->_default_callback)) {
			$prepare_link_callback = $this->_default_callback;
		}
		// Check if callback correct
		$USE_CALLBACK = !empty($prepare_link_callback) && is_callable($prepare_link_callback);
		// Get parents ids (in reverse order)
		$parents_ids = $this->_recursive_get_parents_ids($item_id, $cat_items);
		if (!empty($parents_ids)) {
			$nav_items_ids = array_reverse($parents_ids, 1);
		}
		// Add current item
		$nav_items_ids[$item_id] = $item_id;
		// Process items
		foreach ((array)$nav_items_ids as $cur_item_id) {
			if ($USE_CALLBACK) {
				$item_link = call_user_func($prepare_link_callback, $cur_item_id);
			} else {
				$item_link = "./?object=".$_GET["object"]."&action=view_cat&id=".$cur_item_id;
			}
			// Process template
			$replace = array(
				"item_link"	=> $item_link,
				"item_name"	=> _prepare_html($cat_items[$cur_item_id]["name"]),
				"is_last"	=> (int)(++$i >= count($nav_items_ids)),
			);
			$body .= tpl()->parse($STPL_NAME, $replace);
		}
		return $body;
	}

	/**
	* Helper method
	*/
	function _recursive_get_children_ids($cat_id = 0, $cat_items = array(), $get_sub_children = 1, $return_array = false) {
		$children_ids = array();
		if (empty($cat_id)) {
			return $children_ids;
		}
		// Default items array for the current $_GET["object"]
		if (!empty($cat_items) && is_string($cat_items)) {
			$cat_items = $this->_get_items_array($cat_items);
		}
		if (empty($cat_items)) {
			$cat_items = $this->_get_items_array($this->_default_cats_block);
		}
		$cur_func_name = __FUNCTION__;
		// Process items
		foreach ((array)$cat_items as $cur_item_info) {
			// Skip non-matched items
			if ($cur_item_info["parent_id"] != $cat_id) {
				continue;
			}
			$sub_children = array();
			// Try to get sub children
			if ($get_sub_children) {
				$sub_children = $this->$cur_func_name($cur_item_info["id"], $cat_items, $get_sub_children);
			}
			
			// Add child item id
			if($return_array){
				$children_ids[$cur_item_info["id"]] = $cur_item_info["id"];
				$children_ids = my_array_merge($children_ids, $sub_children);
			}else{
				$children_ids[$cur_item_info["id"]] = $sub_children;
			}
		}
		return $children_ids;
	}

	/**
	* Helper method
	*/
	function _get_item_name($item_id = "", $cat_name = "") {
		// Default items array for the current $_GET["object"]
		if (empty($cat_name)) {
			$cat_name = $this->_default_cats_block;
		}
		// Check if such block exists
		$cat_id = $this->_get_cat_id_by_name($cat_name);
		if (empty($cat_id) || empty($item_id)) {
			return false;
		}
		return $this->_items_cache[$cat_id][$item_id]["name"];
	}
}
