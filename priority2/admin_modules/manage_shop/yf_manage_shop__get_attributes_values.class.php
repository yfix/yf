<?php
class yf_manage_shop__get_attributes_values{

	function _get_attributes_values ($category_id = 0, $object_id = 0, $fields_ids = 0) {
		$category_id	= $params["category_id"];
		$object_id		= $params["object_id"];
		$fields_ids		= $params["fields_ids"];

		if (empty($category_id) || empty($object_id) || empty($fields_ids)) {
			return array();
		}
		$Q = db()->query(
			"SELECT field_id,value,add_value 
			FROM ".db('dynamic_fields_values')."
			WHERE category_id = ".$category_id." 
				AND object_id = ".intval($object_id)." 
				AND field_id IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A["field_id"]] = array(
				"is_selected"	=> (array)unserialize($A["value"]),
				"value_price"	=> (array)unserialize($A["add_value"]),
			);
		}
		return $fields_values;
	}
	
}