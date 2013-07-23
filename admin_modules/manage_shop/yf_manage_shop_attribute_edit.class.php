<?php
class yf_manage_shop_attribute_edit{

	function attribute_edit () {
		if (empty($_GET["id"])) {
			return _e(t("no id"));
		}
		$_GET["id"] = intval($_GET["id"]);
		$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (empty($_POST["value_list"])) {
				_re(t("Values list is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val) {
					$value_list[$val] = $val;
				}
				db()->UPDATE("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> implode("\n", $value_list),
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)), "id=".$_GET["id"]); 
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$replace = array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
			"name"			=> $A["name"],
			"value_list"	=> $A["value_list"],
			"default_value"	=> $A["default_value"],
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		);
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back()
			->render();
	}
	
}