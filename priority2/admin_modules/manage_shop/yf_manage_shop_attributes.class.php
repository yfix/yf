<?php
class yf_manage_shop_attributes{

	function attributes () {
		$sql = "SELECT * FROM ".db('shop_product_attributes_info')." WHERE category_id = ".intval(module('manage_shop')->ATTRIBUTES_CAT_ID)." ORDER BY `order`";
		foreach ((array)db()->query_fetch_all($sql) as $A) {
			$values =  (array)unserialize($A["value_list"]);

			$items[$A["id"]] = array(
				"id"			=> intval($A["id"]),
				"name"			=> _prepare_html($A["name"]),
				"type"			=> _prepare_html($A["type"]),
				"value_list"	=> nl2br(_prepare_html(implode("\n", $values))),
				"default_value"	=> _prepare_html($A["default_value"]),
				"order"			=> $A["order"],
				"edit_url"		=> "./?object=manage_shop&action=attribute_edit&id=".$A["id"],
				"delete_url"	=> "./?object=manage_shop&action=attribute_delete&id=".$A["id"],
				"active_link"   => "./?object=manage_shop&action=activate_attribute&id=".$A["id"],
				"active"		=> $A["active"],
			);
		}
		$replace = array(
			"add_url"	=> "./?object=manage_shop&action=attribute_add",
			"items"		=> $items,
		);
		return tpl()->parse("manage_shop/attributes_main", $replace); 
	}	
	
}