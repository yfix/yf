<?php
class yf_manage_shop__show_filter{

	function _show_filter () {
/*
		$replace = array(
			"save_action"	=> "./?object=manage_shop&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=manage_shop&action=clear_filter"._add_get(),
		);
		foreach ((array)module("manage_shop")->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[module("manage_shop")->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)module("manage_shop")->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = module("manage_shop")->_box($item_name, $_SESSION[module("manage_shop")->_filter_name][$item_name]);
		}
		return tpl()->parse("manage_shop/filter", $replace);
*/
	}
	
}