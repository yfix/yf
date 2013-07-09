<?php
class yf_manage_shop_attribute_add{

	function attribute_add () {
		if (isset($_POST["go"])) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (!common()->_error_exists()) {
				$value_list	= explode("\n", $_POST["value_list"]);
				$i=0;
				foreach ((array)$value_list as $val){
					$i++;
					$value_list_temp[$i] = $val;
				}
				$value_list = serialize($value_list_temp);
			
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> _es($_POST["default_value"]),
					"order"			=> $_POST["order"],
					"category_id"	=> intval(module('manage_shop')->ATTRIBUTES_CAT_ID),
				);
				db()->INSERT("shop_product_attributes_info", $sql_array); 

				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"error"			=> _e(),
		));
		return tpl()->parse("manage_shop/attributes_edit", $replace);
	}
	
}