<?php
class yf_shop__show_shop_last_viewed{

	function _show_shop_last_viewed () {
		$sql_prod_id = "SELECT * FROM  ". db('shop_products') ."  ORDER BY last_viewed_date  DESC LIMIT 5";	
		$item_prod_id = db()->query_fetch_all($sql_prod_id);
		$items = array();
		foreach ((array)$item_prod_id as $k => $product_info){
			$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".module("shop")->THUMB_SUFFIX.".jpg";
			$URL_PRODUCT_ID = module("shop")->_product_id_url($product_info);
			$items[$product_info["id"]] = array(
				"name"		=> _prepare_html($product_info["name"]),
				"price"		=> module("shop")->_format_price(module("shop")->_get_product_price($product_info)),
				"currency"	=> _prepare_html(module("shop")->CURRENCY),
				"image"		=> file_exists(module("shop")->products_img_dir. $thumb_path)? module("shop")->products_img_webdir. $thumb_path : "",
				"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"special" 	=>  "",			
			);
		}
		return tpl()->parse("shop/last_viewed", array(
			"items"	=> $items,
		));
	}
	
}