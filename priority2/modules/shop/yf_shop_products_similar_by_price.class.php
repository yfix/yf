<?php
class yf_shop_products_similar_by_price{

	function products_similar_by_price ($price, $id) {
		$price_min = floor($price - ($price  * 10 / 100));
		$price_max = ceil($price + ($price * 10 / 100));
		$sql1 = "SELECT category_id FROM ".db('shop_product_to_category')." WHERE product_id =  ".$id. "";
		$cat_id = db()->query($sql1);
		while ($A = db()->fetch_assoc($cat_id)) {
			$cats_id .= $A["category_id"].",";
		}	
		$cats_id = rtrim($cats_id, ",");
		$sql2 = "SELECT product_id FROM ".db('shop_product_to_category')." WHERE category_id IN ( ".$cats_id. ")";
		$prod = db()->query($sql2);
		while ($A = db()->fetch_assoc($prod)) {
			$prods .= $A["product_id"].",";
		}	
		$prods = rtrim($prods, ",");
		$sql = "SELECT * FROM ".db('shop_products')." WHERE price > ".$price_min." AND price < ".$price_max ." AND id != ". $id. " AND id IN(".$prods.")";
		$product = db()->query_fetch_all($sql);
		foreach ((array)$product as $k => $product_info){
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
		$replace = array(
			"items"	=> $items,
			"title"	=> "Similar price",
		);
		return tpl()->parse("shop/products_similar_by_price", $replace);
	}
	
}