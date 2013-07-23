<?php
class yf_manage_shop_attribute_delete{

	function attribute_delete () {
		$_GET["id"] = intval($_GET["id"]);
		$field_info = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id = ".intval($_GET["id"]));
		if (empty($field_info)) {
			return _e(t("no field"));
		}
		if ($_GET["id"]) {
			db()->query("DELETE FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
			db()->query("DELETE FROM ".db('shop_product_attributes_values')." WHERE category_id = ".module('manage_shop')->ATTRIBUTES_CAT_ID." AND field_id = ".$_GET["id"]);
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("shop_product_attributes_info");
			}
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}
	
}