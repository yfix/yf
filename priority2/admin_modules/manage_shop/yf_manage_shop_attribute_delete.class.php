<?php
class yf_manage_shop_attribute_delete{

	function attribute_delete () {
		$_GET["id"] = intval($_GET["id"]);
		$field_info = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_info')." WHERE id = ".intval($_GET["id"]));
		if (empty($field_info)) {
			return _e(t("no field"));
		}
		if ($_GET["id"]) {
			db()->query("DELETE FROM ".db('dynamic_fields_info')." WHERE id=".$_GET["id"]);
			db()->query("DELETE FROM ".db('dynamic_fields_values')." WHERE category_id = ".module('manage_shop')->ATTRIBUTES_CAT_ID." AND field_id = ".$_GET["id"]);
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("dynamic_fields_info");
			}
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}
	
}