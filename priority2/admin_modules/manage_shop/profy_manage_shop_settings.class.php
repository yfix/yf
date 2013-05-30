<?php

/**
* Manage shop sub module
*/
class profy_manage_shop_settings {

	/**
	* Constructor
	*/
	function _init () {
		$this->PARENT_OBJ	= module("manage_shop");
	}

	/**
	* Show orders
	*/
	function show_settings() {
		$replace = array(
			"items"		=> "settings",
		);
		return tpl()->parse($_GET["object"]."/settings_show", $replace);
	}
}
