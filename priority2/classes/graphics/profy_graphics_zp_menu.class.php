<?php

/**
* Show Zapatec menu items
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_graphics_zp_menu {

	/**
	* Show Zapatec menu items
	*
	* @access	private
	* @return	string	Meta tags
	*/
	function _show ($input = array()) {
		$menu_name = $input["name"];
		if (empty($menu_name)) {
			trigger_error("GRAPHICS: Given empty menu name to display", E_USER_WARNING);
			return false;
		}
		// Try to get available menus infos
		if (!isset(_class('graphics')->_menus_infos)) {
			_class('graphics')->_menus_infos = main()->get_data("menus");
		}
		// Check if such menu exists
		if (empty(_class('graphics')->_menus_infos)) {
			trigger_error("GRAPHICS: Menus info not loaded", E_USER_WARNING);
			return false;
		}
		$MENU_EXISTS = false;
		foreach ((array)_class('graphics')->_menus_infos as $menu_info) {
			// Skip menus from other init type ("admin" or "user")
			if ($menu_info["type"] != MAIN_TYPE) {
				continue;
			}
			// Found!
			if ($menu_info["name"] == $menu_name) {
				$MENU_EXISTS = true;
				$menu_id = $menu_info["id"];
				break;
			}
		}
		if (!$MENU_EXISTS) {
			trigger_error("GRAPHICS: Menu name \""._prepare_html($menu_name)."\" not found in menus list", E_USER_WARNING);
			return false;
		}
		$cur_menu_info	= &_class('graphics')->_menus_infos[$menu_id];
		// Try to get available menus items
		if (!isset(_class('graphics')->_menu_items)) {
			_class('graphics')->_menu_items = main()->get_data("menu_items");
		}
		// Do not show menu if there is no items in it
		if (empty(_class('graphics')->_menu_items[$menu_id])) {
			return false;
		}
		// Prepare params
		$ZP_MENU_OBJ = main()->init_class("zapatec_menu", "classes/");
		$ZP_MENU_OBJ->_menu_items = _class('graphics')->_menu_items[$menu_id];
		return $ZP_MENU_OBJ->_display_code($menu_params);
	}
}
