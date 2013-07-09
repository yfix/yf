<?php
class yf_manage_shop_product_delete{

	function product_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_image_delete($_GET["id"]);
		db()->query("DELETE FROM ".db('dynamic_fields_values')." WHERE object_id=".$_GET["id"]);
		db()->query("DELETE FROM ".db('shop_group_options')." WHERE product_id=".$_GET["id"]);		
		db()->query("DELETE FROM ".db('shop_products')." WHERE id=".$_GET["id"]);
		return js_redirect("./?object=manage_shopaction=products_manage");
	}
	
}