<?php
class yf_manage_shop__attributes_html{

	function _attributes_html ($object_id = 0, $only_selected = false) {
		$object_id		= $params["object_id"];
		$only_selected	= $params["only_selected"];

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
			$i++;
			foreach ((array)$_info["value_list"] as $_val_id => $_value) {
				$_item_id = $_attr_id."_".$_val_id;
				$selected_info = $fields_values[$_attr_id];
				if ($only_selected && !$selected_info["is_selected"][$_val_id]) {
					continue;
				}
				$data[$_item_id] = array(
					"bg_class"		=> !($i % 2) ? "bg1" : "bg2",
					"id"			=> $_item_id,
					"attr_checked"	=> intval((bool)$selected_info["is_selected"][$_val_id]),
					"attr_price"	=> _prepare_html($selected_info["value_price"][$_val_id]),
					"attr_name"		=> _prepare_html($_info["title"]),
					"attr_value"	=> _prepare_html($_value),
				);
			}
		}
		return $data;
	}
	
}