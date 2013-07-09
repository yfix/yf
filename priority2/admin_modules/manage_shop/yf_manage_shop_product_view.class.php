<?php
class yf_manage_shop_product_view{

	function product_view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$product_info = db()->query_fetch("SELECT * FROM ".db('shop_products')." WHERE id=".$_GET["id"]);
		if ($product_info["image"] == 0) {
			$thumb_path = "";
		} else {
			$dirs = sprintf("%06s",$product_info["id"]);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1."/".$dir2."/";
			$image_files = _class('dir')->scan_dir(module("manage_shop")->products_img_dir.$mpath, true, "/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/");
			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
			foreach((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult["content"];
				$thumb_path_temp = module("manage_shop")->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.module("manage_shop")->THUMB_SUFFIX.".jpg";
				$img_path = module("manage_shop")->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
				$replace2 = array(
					"thumb_path"=> $thumb_path_temp,
					"img_path" 	=> $img_path,
					"name"		=> $product_info["url"],
				);
				$items .= tpl()->parse("manage_shop/image_items", $replace2);
			}
		}	
		$dyn_fields = module("manage_shop")->_attributes_view($_GET["id"]);
		$sql1 = "SELECT category_id FROM ".db('shop_product_to_category')." WHERE product_id = ". $_GET["id"];
		$products = db()->query($sql1);
		while ($A = db()->fetch_assoc($products)) {
			$cat_id[$A["category_id"]] .= $A["category_id"];
		}	
		$replace = array(
			"name"				=> _prepare_html($product_info["name"]),
			"model"				=> _prepare_html($product_info["model"]),
			"desc"				=> _prepare_html($product_info["description"]),
			"meta_keywords"		=> _prepare_html($product_info["meta_keywords"]),
			"meta_desc"			=> _prepare_html($product_info["meta_desc"]),
			"ext_url"			=> _prepare_html($product_info["external_url"]),
			"price"				=> $product_info["price"],
			"dynamic_fields"	=> $dyn_fields,
			"manufacturer"		=> module("manage_shop")->_man_for_select[$product_info["manufacturer_id"]],
			"category"			=> common()->multi_select("category", module("manage_shop")->_cats_for_select, $cat_id, false, 2, " size=15 class=small_for_select ", false, "", true),
			"back_url"			=> "./?object=manage_shop&action=products_manage",
			"image"				=> $items,
			"thumb_path"		=> $thumb_path,
			"product_related"	=>  module("manage_shop")->get_product_related($product_info["id"]),
		);
		return tpl()->parse("manage_shop/product_view", $replace);
	}
	
}