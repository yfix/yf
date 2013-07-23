<?php
class yf_manage_shop_image_upload{

	function image_upload () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_image_upload($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
	
}