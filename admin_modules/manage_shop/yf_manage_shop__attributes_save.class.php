<?php
class yf_manage_shop__attributes_save{

	function _attributes_save ($object_id = 0) {
		if (empty($object_id)) {
			return;
		}
		// 2-nd case of dynamic attributes assignment
		foreach ((array)$_POST["single_attr"] as $_attr_id => $_sel_id) {
			$_item_id = $_attr_id."_".$_sel_id;
			$_POST["attributes_use"][$_item_id] = 1;
		}
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);
		if (empty($category_id)) {
			return;
		}
		$attributes = module('manage_shop')->_get_attributes($category_id);
		if (empty($attributes) || !is_array($attributes)) {
			return;
		}
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		$fields_values = module('manage_shop')->_get_attributes_values ($category_id, $object_id, $fields_ids);
		foreach ((array)$attributes as $_attr_id => $_info) {
			$option_values	= array();
			$value_prices	= array();

			foreach ((array)$_info["value_list"] as $_val_id => $_value) {
				$_item_id = $_attr_id."_".$_val_id;
				if ($_POST["attributes_use"][$_item_id]) {
					$option_values[$_val_id]	= $_POST["attributes_use"][$_item_id];
				}
				$value_prices[$_val_id]		= $_POST["attributes_price"][$_item_id];
			}
			$option_values	= serialize($option_values);
			$value_prices	= serialize($value_prices);
			if (!isset($fields_values[$_attr_id])) {
				db()->INSERT("shop_product_attributes_values", array(
					"category_id"	=> $category_id,
					"object_id"		=> $object_id,
					"field_id"		=> intval($_attr_id),
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				));
			} else {
				db()->UPDATE("shop_product_attributes_values", array(
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				), "category_id = ".$category_id." AND object_id = ".$object_id." AND field_id = ".intval($_attr_id));
			}
		}
		return true;
	}
	
}