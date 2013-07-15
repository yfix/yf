<?php
class yf_manage_shop_product_clone{

	function product_clone () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
/*
		module("manage_shop")->_image_delete($_GET["id"]);
		db()->query("DELETE FROM ".db('shop_product_attributes_values')." WHERE object_id=".$_GET["id"]);
		db()->query("DELETE FROM ".db('shop_group_options')." WHERE product_id=".$_GET["id"]);		
		db()->query("DELETE FROM ".db('shop_products')." WHERE id=".$_GET["id"]);
*/
		$info = db()->query_fetch("SELECT * FROM ".db('shop_products')." WHERE id=".intval($_GET["id"]));
		if (empty($info["id"])) {
			return _e(t("No such product!"));
		}
		$sql = $info;
		unset($sql["id"]);
		$sql["name"] = "Clone ".$sql["name"];
		$sql["active"] = 0;
		db()->insert('shop_products', $sql);
// TODO: clone product attributes
// TODO: clone product images
		return js_redirect("./?object=manage_shopaction=products");
	}
	
}