<?php
class yf_manage_shop_image_delete{

	function image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_image_delete($_GET["id"], $_GET["name"], $_GET["key"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
	
}