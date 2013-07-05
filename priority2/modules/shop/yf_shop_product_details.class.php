<?php
class yf_shop_product_details{

	function product_details() {
		if (!$_GET["id"]) {
			return is_redirect("./?object=shop");
		}
		// Get products from database
		if (is_numeric($_GET["id"] )) {
			$add_sql = "`id`= '".intval($_GET["id"]);
		} else {
			$add_sql = "`url`='"._es($_GET['id']);
		}
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		
		// Required for comments
		module("shop")->_comments_params["object_id"] = $product_info["id"];
		module("shop")->_comments_params["objects_ids"] = $product_info["id"];
		$N = module("shop")-> _get_num_comments();
		$N = $N[$product_info["id"]];
		if ($N =="") {
			$N = 0;
		}
		$dirs = sprintf("%06s",$product_info["id"]);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		$group_prices = module("shop")->_get_group_prices($product_info["id"]);
		$product_info["_group_price"] = $group_prices[module("shop")->USER_GROUP];
		module("shop")->_product_info = $product_info;
		$atts = module("shop")->_get_products_attributes($product_info["id"]);
		$thumb_path = $product_info["url"]."_".$product_info["id"]."_".$product_info["image"].module("shop")->THUMB_SUFFIX.".jpg";
		$img_path = $product_info["url"]."_".$product_info["id"]."_".$product_info["image"].module("shop")->FULL_IMG_SUFFIX.".jpg";
		if ($product_info["image"] == 0) {
			$image = "";
		} else {
			$image_files = _class('dir')->scan_dir(module("shop")->products_img_dir.$mpath, true, "/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/");
			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
			foreach ((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult["content"];
				if ($i != $product_info["image"]) {
					$thumb_temp = module("shop")->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.module("shop")->THUMB_SUFFIX.".jpg";
					$img_temp = module("shop")->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.module("shop")->FULL_IMG_SUFFIX.".jpg";
					$replace2 = array(
						"thumb_path"=> $thumb_temp,
						"img_path" 	=> $img_temp,
						"name"		=> $product_info["url"],
					);
					$image .= tpl()->parse("shop/image_items", $replace2);
				}
			}
		}	
		$URL_PRODUCT_ID = module("shop")->_product_id_url($product_info);
		$sql_man = "SELECT * FROM `".db('shop_manufacturer')."` WHERE `id` = ".$product_info["manufacturer_id"];
		$manufacturer = db()->query_fetch($sql_man);
		if (module("shop")->SHOW_SIMILAR_PRICE == true){
			$similar_price = module("shop")->similar_price ( $product_info["price"],  $product_info["id"] );
		}
		if (module("shop")->THIS_ITEM_OFTEN_BUY == true){
			$this_item_often_buy = module("shop")->this_item_often_buy ( $product_info["id"] );
		}
		$replace = array(
			"name"					=> _prepare_html($product_info["name"]),
			"model"					=> _prepare_html($product_info["model"]),
			"desc"					=> $product_info["description"],
			"manufacturer"			=>	_prepare_html(module("shop")->_manufacturer[$product_info["manufacturer_id"]]["name"]),
			"url_manufacturer"		=> process_url("./?object=shop&action=products_show&id=".module("shop")->_manufacturer[$product_info["manufacturer_id"]]["url"]),
			"date"					=> _format_date($product_info["add_date"], "long"),
			"price"					=> module("shop")->_format_price(module("shop")->_get_product_price($product_info)),
			"currency"				=> _prepare_html(module("shop")->CURRENCY),
			"thumb_path"			=> file_exists(module("shop")->products_img_dir.$mpath. $img_path)	? module("shop")->products_img_webdir. $mpath.$img_path : "",
			"img_path"				=> file_exists(module("shop")->products_img_dir. $mpath.$img_path)	? module("shop")->products_img_webdir. $mpath.$img_path : "",
			"image"					=> $image,
			"add_to_cart_url"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=add_to_cart&id=".$URL_PRODUCT_ID),
			"external_url"			=> intval((bool)$product_info["external_url"]),
			"back_url"				=> process_url("./?object=shop"),
			"show_cart_url"			=> process_url("./?object=shop&action=cart"),
			"dynamic_atts"			=> module("shop")->_get_select_attributes($atts),
			"cats_block"			=> module("shop")->_show_shop_cats(),
			"cat_name"				=> _prepare_html(module("shop")->_shop_cats[$product_info["cat_id"]]),
			"cat_url"				=> process_url("./?object=shop&action=".__FUNCTION__."&id=".(module("shop")->_shop_cats_all[$product_info["cat_id"]]['url'])),
			'comments'				=> module("shop")->_view_comments(),
			"N"						=> $N,
			"similar_price"			=> $similar_price,
			"this_item_often_buy"	=> $this_item_often_buy,
			"product_related"		=> module("shop")->products_related($product_info["id"]),
		);
		db()->query("UPDATE `".db('shop_products')."` SET `viewed` = `viewed`+1 , `last_viewed_date` = ".time()."  WHERE ".$add_sql."'");
		return tpl()->parse("shop/details", $replace);
	}

}