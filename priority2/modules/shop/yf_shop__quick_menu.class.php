<?php
class yf_shop__quick_menu{

	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Shopping cart",
				"url" 	=> "./?object=shop&action=cart",
			),
		);
		return $menu;
	}
	
}