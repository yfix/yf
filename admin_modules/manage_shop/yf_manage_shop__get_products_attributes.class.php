<?php
class yf_manage_shop__get_products_attributes{

	function _get_products_attributes($products_ids = array()) {
		if (is_numeric($products_ids)) {
			$return_single_id = $products_ids;
			$products_ids = array($products_ids);
		}
		if (empty($products_ids)) {
			return array();
		}
		$fields_info = main()->get_data("shop_product_attributes_info");

		$Q = db()->query("SELECT * FROM ".db('shop_product_attributes_values')." WHERE object_id IN (".implode(",", $products_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$_product_id = $A["object_id"];

			$A["value"]		= explode("\n", $A["value"]);
			$A["add_value"] = explode("\n", $A["add_value"]);

			foreach ((array)$A["value"] as $_attr_id => $_dummy) {
				$_price = $A["add_value"][$_attr_id];
				$_item_id = $A["field_id"]."_".$_attr_id;
				$_field_info = $fields_info[$A["field_id"]];
				$_field_info["value_list"] = explode("\n", $_field_info["value_list"]);

				$data[$_product_id][$_item_id] = array(
					"id" 			=> $_item_id,
					"price"			=> $_price,
					"name"			=> $_field_info["name"],
					"value"			=> $_field_info["value_list"][$_attr_id],
					"product_id"	=> $_product_id,
				);
			}
		}
		if ($return_single_id) {
			return $data[$return_single_id];
		}
		return $data;
	}
	
}