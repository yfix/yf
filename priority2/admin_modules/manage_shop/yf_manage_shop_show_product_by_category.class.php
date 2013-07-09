<?php
class yf_manage_shop_show_product_by_category{

	function show_product_by_category ($cat = "") {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET["cat_id"];
		$sql1 = "SELECT product_id FROM ".db('shop_product_to_category')." WHERE category_id =". $cat_id ;
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A["product_id"].",";
			}	
			$product_info = rtrim($product_info, ",");
			
		$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' AND id IN (".$product_info .")  ORDER BY name";
		$product = db()->query_fetch_all($sql);
		$products = array();
		foreach ((array)$product as $v) {
			$products []  = array (
				"product_id"	=> $v["id"],
				"name"			=> $v["name"],
			);
		}
		echo json_encode($products);
	}	
	
}