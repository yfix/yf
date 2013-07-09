<?php
class yf_shop__show_shop_best_sales{

	function _show_shop_best_sales () {
		$sql_prod_id = "SELECT product_id, COUNT(quantity) FROM ". db('shop_order_items') ." GROUP BY product_id ORDER BY COUNT(quantity) DESC LIMIT 0,5";	
		$item_prod_id = db()->query_fetch_all($sql_prod_id);
		$items = array();
		foreach ((array)$item_prod_id as $k => $v){
			$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' AND id = ".$v["product_id"];
			$product_info = db()->query_fetch($sql);
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
		return tpl()->parse("shop/best_sales", array(
			"items"	=> $items,
		));
	}
	
}