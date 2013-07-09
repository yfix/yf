<?php
class yf_shop__hook_meta_tags{

	function _hook_meta_tags ($meta) {
		if (in_array($_GET["action"], array("show","products_show")) && $_GET["id"]) {
			$subtitle .= module("shop")->_shop_cats[$_GET["id"]];
		} elseif (in_array($_GET["action"], array("product_details")) /* && $_GET["id"] */) {
			$meta["keywords"] = module("shop")->_product_info["meta_keywords"];
			$meta["description"] = module("shop")->_product_info["meta_desc"];
		}
		return $meta;	
	}
	
}