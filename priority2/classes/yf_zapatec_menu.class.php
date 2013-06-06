<?php

/**
* Zapatec Menu handler
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_zapatec_menu {

	/** @var array */
	var $_menu_params = array(
		"vertical"			=> 0,
		"scrollWithWindow"	=> 1,
		"top"				=> '0px',
		"left"				=> '0px',
		"dropShadow"		=> 25,
		"zIndex"			=> 99,
		"drag"				=> 1,
	);
	/** @var array @conf_skip */
	var $_menu_items = array();
	/** @var string */
	var $_theme_name = "winxp1";

	/**
	* Display menu code
	*/
	function _display_code () {
		$replace = array(
			"cur_menu_id"	=> substr(md5(rand().microtime(true)), 0, 8),
			"menu_params"	=> $this->_show_menu_params(),
			"menu_items"	=> $this->_recursive_get_menu_items($this->_menu_items),
			"zp_theme_name"	=> $this->_theme_name,
		);
		return tpl()->parse("system/zpmenu", $replace);
	}

	/**
	* Display menu params for JS
	*/
	function _show_menu_params () {
		$data = array();
		foreach ((array)$this->_menu_params as $k => $v) {
			$data[] = $k.":".(is_numeric($v) ? $v : "'".$v."'")."";
		}
		$body = implode(",", $data);
		return $body;
	}

	/**
	* Get menu items ordered array (recursively)
	*/
	function _recursive_get_menu_items($menu_items = array(), $skip_item_id = 0, $parent_id = 0, $level = 0) {
		$items_ids		= array();
		$items_array	= array();
		// Get items from the current level
		foreach ((array)$menu_items as $item_info) {
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
			// Prepare item link
			$item_link = "";
			if ($item_info["type_id"] == 1 && strlen($item_info['location']) > 0) {
				$item_link = "./?".$item_info['location'];
			} elseif ($item_info["type_id"] == 2) {
				$item_link = $item_info['location'];
			}
			// Process template
			$replace = array(
				"link"			=> $item_link,
				"name"			=> _prepare_html(translate($item_info['name'])),
				"level"			=> range(0, $item_info["level"]),
				"type_id"		=> $item_info["type_id"],
				"sub_items"		=> $this->_recursive_get_menu_items($menu_items, $skip_item_id, $item_info["id"], $level + 1),
			);
			$body .= tpl()->parse("system/zpmenu_item", $replace);
		}
		return $body;
	}
}
