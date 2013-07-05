<?php
class yf_shop__quick_menu{

	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Shopping basket",
				"url" 	=> "./?object=shop&action=basket",
			),
		);
		return $menu;
	}
	
}