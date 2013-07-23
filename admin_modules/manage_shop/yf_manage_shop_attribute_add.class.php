<?php
class yf_manage_shop_attribute_add{

	function attribute_add () {
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val){
					$value_list[$val] = $val;
				}
				db()->INSERT("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"error"			=> _e(),
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		));
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back()
			->render();
	}
	
}