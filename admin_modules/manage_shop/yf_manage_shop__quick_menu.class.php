<?php
class yf_manage_shop__quick_menu{

	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage products",
				"url"	=> "./?object=manage_shop&action=show",
			),
			array(
				"name"	=> "Manage orders",
				"url"	=> "./?object=manage_shop&action=orders_manage",
			),
			array(
				"name"	=> "Manage attributes",
				"url"	=> "./?object=manage_shop&action=attributes",
			),
		);
		return $menu;	
	}
	
}