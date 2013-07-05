<?php
class yf_shop_get_product_related{

	function get_product_related ($id = "") {
		$product_related_data = array();
		$sql = "SELECT * FROM `".db('shop_product_related') . "` WHERE `product_id` = ". $id;
		$product = db()->query($sql);
		while ($A = db()->fetch_assoc($product)){
			$product_related_id .= $A['related_id'].",";
		}
		$product_related_id = rtrim($product_related_id, ",");
		if ($product_related_id != "") {
			$product_related = module("shop")->show_products($product_related_id); 
		}
		return $product_related;
	}	
	
}