<?php
class yf_manage_shop__get_attributes{

	function _get_attributes ($category_id = 0) {
		if (empty($category_id)) {
			return array();
		}
		$fields_info = main()->get_data("shop_product_attributes_info");
		foreach ((array)$fields_info[$category_id] as $A){
			$attributes[$A["id"]] = array(
				"title"			=> $A["name"],
				"type"			=> $A["type"],
				"value_list"	=> explode("\n", $A["value_list"]),
				"default_value"	=> $A["default_value"],
			);
		}
		return $attributes;
	}
	
}