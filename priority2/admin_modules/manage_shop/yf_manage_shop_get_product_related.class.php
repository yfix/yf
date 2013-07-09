<?php
class yf_manage_shop_get_product_related{

	function get_product_related ($id = "") {
		$product_related_data = array();
		$sql = "SELECT * FROM ".db('shop_product_related') . " WHERE product_id = ". $id;
		$product = db()->query($sql);
		while ($A = db()->fetch_assoc($product)){
			$product_related_id .= $A['related_id'].",";
		}
		$product_related_id = rtrim($product_related_id, ",");
		if ($product_related_id != "") {
			$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1'  AND id IN (".$product_related_id .")  ORDER BY name";
			$product = db()->query_fetch_all($sql);
			$products = array();
			foreach ((array)$product as $v) {
				$product_related_data[] = array(
					"related_id"	=> $v["id"],
					"name"			=> $v["name"],
				);
			}
		}
		return $product_related_data;
	}	
	
}