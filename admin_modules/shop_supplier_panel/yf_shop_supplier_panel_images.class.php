<?php
class yf_shop_supplier_panel_images{

	/**
	*/
	function product_image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		if (empty($_GET["key"])) {
			return "Empty image key!";
		}
		$A = db()->get_all("SELECT * FROM `".db('shop_product_images')."` WHERE `product_id`=".intval($_GET['id'])." && `id`=".intval($_GET['key']));
		if (count($A) == 0){
			 return "Image not found";
		}
		module("manage_shop")->_product_image_delete($_GET["id"], $_GET["key"]);
		module("manage_shop")->_product_images_add_revision($_GET['id']);		
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	*/
	function product_image_upload () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_product_image_upload($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

}