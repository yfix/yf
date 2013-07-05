<?php
class yf_shop_products_show{

	function products_show($search = "", $str_search = "") {
		foreach ((array)module("shop")->_shop_cats as $_cat_id => $_cat_name) {
			if ($_GET['id'] == module("shop")->_shop_cats_all[$_cat_id]['url'] && $_GET['id'] != "" ) {
				$_GET['id'] = $_cat_id;
				$_show_by_cat = 1;
				$cat_name = $_cat_name;
			}
		}
		foreach ((array)module("shop")->_manufacturer as $_man_id => $_man_name) {
			if ($_GET['id'] == "none") {
				$_GET['id'] = "";
				$_SESSION['man_id'] =   "none";
			}else if ($_GET['id'] == $_man_name['url']) {
				$_GET['id'] = $_man_id;
				$_show_by_man = 1;
				$cat_name = $_man_name['name'];
				$_SESSION['man_id'] =  $_man_name['url'];
			}
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) { 
			$cat_child = $_GET["id"].",";
			$cat_child .= module("shop")->_get_children_cat ( $_GET["id"]);
			$cat_child = rtrim($cat_child, ",");
			$sql1 = "SELECT product_id FROM ".db('shop_product_to_category')." WHERE category_id IN ( ". $cat_child. ")";
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A["product_id"].",";
			}	
			$product_info = rtrim($product_info, ",");
		}
		if ($product_info == "") {
			$product_info = 0;
		}
		if ($search == "" && $str_search =="") {
			if ($_show_by_cat == 1){
				$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' ".($_GET["id"] ? " AND id IN (".$product_info .")" : " AND featured='1'")." ORDER BY add_date";
			}else if ($_show_by_man == 1) {
				$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' ".($_GET["id"] ? " AND manufacturer_id = " . $_GET["id"]  : " AND featured='1'")." ORDER BY add_date";
			}
		} elseif ($search == "" && $str_search !="") {
			
		} else {
			$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' AND id IN (".$search .")  ORDER BY add_date";
		}
		if ($sql != ""){
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$product_info = db()->query_fetch_all($sql.$add_sql);
		}
		if (!empty($product_info)) {
			$group_prices = module("shop")->_get_group_prices(array_keys($product_info));
		}
		$items = array();
		$counter = 1;
		foreach ((array)$product_info as $v) {
			$dirs = sprintf("%06s",$v["id"]);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1."/".$dir2."/";
			$thumb_path = $v["url"]."_".$v["id"]."_1".module("shop")->THUMB_SUFFIX.".jpg";
			$img_path = $v["url"]."_".$v["id"]."_1".module("shop")->FULL_IMG_SUFFIX.".jpg";
			$v["_group_price"] = $group_prices[$v["id"]][module("shop")->USER_GROUP];
			$URL_PRODUCT_ID = module("shop")->_product_id_url($v);
			$items[$v["id"]] = array(
				"name"				=> _prepare_html($v["name"]),
				"desc"				=> _prepare_html($v["description"]),
				"date"				=> _format_date($v["add_date"], "long"),
				"price"				=> module("shop")->_format_price(module("shop")->_get_product_price($v)),
				"currency"			=> _prepare_html(module("shop")->CURRENCY),
				"thumb_path"		=> file_exists(module("shop")->products_img_dir.$mpath. $thumb_path)? module("shop")->products_img_webdir. $mpath.$thumb_path : "",
				"img_path"			=> file_exists(module("shop")->products_img_dir. $mpath.$img_path)	? module("shop")->products_img_webdir.$mpath. $img_path : "",
				"basket_add_url"	=> ($v["external_url"]) ? $v["external_url"] : process_url("./?object=shop&action=basket_add&id=".$URL_PRODUCT_ID),
				"external_url"		=> intval((bool)$v["external_url"]),
				"details_url"		=> ($v["external_url"]) ? $v["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"counter"			=> $counter,
			);
			if ($counter == 4) {
				$counter = 1;
			} else {
				++ $counter;
			}
		}
		if (empty($items)) {
			$items = "";
		}
		$replace = array(
			"search_string"	=> $str_search,
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> $total,
			"currency"		=> _prepare_html(module("shop")->CURRENCY),
			"show_basket_url"	=> process_url("./?object=shop&action=basket"),
			"cur_cat_id"	=> intval($_GET["id"]),
			"cur_cat_name"	=> _prepare_html($cat_name),
			"cats_block"	=> module("shop")->_show_shop_cats(),
		);
		return tpl()->parse("shop/main", $replace);
	}
	
}