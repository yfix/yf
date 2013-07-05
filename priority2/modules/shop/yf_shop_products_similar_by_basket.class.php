<?php
class yf_shop_products_similar_by_basket{

	function products_similar_by_basket ($id) {
		$sql_order_id = "SELECT order_id FROM ".db('shop_order_items')." WHERE product_id =  ".$id;
		$orders = db()->query($sql_order_id);
		while ($A = db()->fetch_assoc($orders))	{
			$order_id .= $A["order_id"].",";
		}	
		$order_id = rtrim($order_id, ",");
		if (!empty($order_id)) {
			$sql_product_id = "SELECT product_id FROM ".db('shop_order_items')." WHERE  order_id IN (  ".$order_id.") AND product_id != ". $id;
			$products = db()->query($sql_product_id);
			while ($A = db()->fetch_assoc($products)) {
				$product_id .= $A["product_id"].",";
			}	
			$product_id = rtrim($product_id, ","); 
		}
		if (!empty($product_id)) {
			$sql = "SELECT * FROM ".db('shop_products')." WHERE  id in ( ".$product_id.")";
			$product = db()->query_fetch_all($sql);
			foreach ((array)$product as $k => $product_info){
				$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".module("shop")->THUMB_SUFFIX.".jpg";
				$URL_PRODUCT_ID = module("shop")->_product_id_url($product_info);
				$items[$product_info["id"]] = array(
					"name"		=> _prepare_html($product_info["name"]),
					"price"		=> module("shop")->_format_price(module("shop")->_product_get_price($product_info)),
					"currency"	=> _prepare_html(module("shop")->CURRENCY),
					"image"		=> file_exists(module("shop")->products_img_dir. $thumb_path)? module("shop")->products_img_webdir. $thumb_path : "",
					"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
					"special" 	=>  "",			
				);
			}
		}
		$replace = array(
			"items"	=> $items,
			"title"	=> "Those who purchased this product also buy",
		);
		return tpl()->parse("shop/products_similar_by_price", $replace);
	}
	
}